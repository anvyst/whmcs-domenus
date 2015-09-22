<?php
/**
 *  Domenus registrar API 
 *
 *
 * */
class Domenus {

    public static $dbh  = null; //db handler
    
    public static $MODE = 'production';

    /* Domain check flag */
    const DOMAIN_FOR_RENEW      = 1;    /* domain available for renewal */
    const DOMAIN_FOR_RETURN     = 2;    /* domain available for return */
    const DOMAIN_IS_FREE        = 4;    /* if domain is available for registration via whois */
    const DOMAIN_IS_VALID       = 8;    /* checking domain validity */
    const DOMAIN_FOR_REGISTER   = 16;   /* if domain is for registration */
    const DOMAIN_FOR_IDPROTECT  = 32;   /* check if id Protect can be requested for domain */

    const RESPONSE_SUCCESS      = 'OK';

    /**
     *  Generic call method to distribute
     *  request logic with autoloading the classes
     *  @param String $method containing <Class/Method> notation
     *  @param Array  $data   containing all required information
     *  @return Nothing
     * */
    public static function call($method, $data = []) {
        
        if( preg_match('/^(\w+)\/(.*)$/i', $method, $matches) ) {
            $class = ucfirst($matches[1]);
            $class_method = str_replace('/','_', ucfirst($matches[2]));
            $class_method = strtolower($class).'_'.$class_method;
        }     
        
        spl_autoload_register(function($class) {
            $file = dirname(__FILE__).'/'.$class.'.php';

            if( file_exists($file) ) {
                require_once $file;
            }
        });

        //calling method with the arguments; 
        $result = $class::$class_method($data);
        
        return $result;
    }




    /**
     *  get_ns_servers method
     *  @return Array $result containing index array of NS servers
     * */
    public static function get_ns_servers() {
        $result  = [];
        $configs = yaml_parse( file_get_contents( dirname(dirname(__FILE__)).'/config.yml' ) );

        $ns_servers = $configs[self::$MODE]['api_default_ns'];

        if( !empty($ns_servers) ) { 
            $result = $ns_servers;
        }

        return $result;
    }


    /**
     *  Generic send_request Method
     *  @param 
     * */
    public static function send_request($method, $arguments = []) {
        $fields_string  = '';
        $result         = array();

        $configs = yaml_parse(file_get_contents(dirname(dirname(__FILE__)).'/config.yml'));
        
        mb_internal_encoding('UTF-8');
        
        if( empty($configs) ) {
            die("No [config.yml] found\n");
        }

        $full_url = $configs[self::$MODE]['api_url'] . $configs[self::$MODE]['api_current'] . $method;


        //passing login credentials in each request
        if( empty($arguments['p_login']) || empty($arguments['p_password']) ) {
            $arguments['p_login']       = $configs[self::$MODE]['api_username'];
            $arguments['p_password']    = $configs[self::$MODE]['api_password']; 
        } 

        // defining reponse, using JSON, by default XML is used.
        if( !isset($arguments['response_type']) || empty($arguments['response_type']) ) {
            $arguments['response_type'] = 'json';
        }

        foreach($arguments as $k => $v) {
            if( !is_array($v) ) {
                $fields_string .= sprintf("%s=%s&", $k, urlencode($v));
            } else {
                foreach($v as $key => $val) { 
                    $fields_string .= sprintf("%s[]=%s&", $k, urlencode($val));
                }  
            }
        }

        $fields_string = rtrim($fields_string, '&');
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_HTTPHEADER, array(
            "Content-Type: application/x-www-form-urlencoded; charset=utf-8"
        ));        
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_POST, count($arguments));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        
        //avoid printing out the result
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);

        curl_close($ch);
    
        return $result;
    }    


    /**
     *  parse_response method
     *  @param Array $data containing whatever's been returned from 
     *  cURL request
     *  @return JSON $result containing the response
     * */
    public static function parse_response($data = []) {
        $result = [];

        if( empty($data) ) {
            return $result;
        }

        if( json_decode($data) != null ) { 
            $result = json_decode($data, true);

            // got rid off annoying <api-result> array key
            // at the top of the structure
            $result = array_shift($result);
        } else {
            print "\n It's not JSON\n";
        }

        return $result;
    }


    public static function validate($fields = [], $data = []) {
        $errors = [];

        if( empty($fields) || empty($data) ) {
            $errors[] = "Fields are empty. Check the request body.";
            return $errors;
        }
    
        $data_keys = array_keys($data);

        foreach($fields as $field) {
            if( !in_array($field, $data_keys) ) {
               $errors[] = "Required field [$field] is empty."; 
            } 
        }

        return $errors;    
    }


    /**
     *  get_status_code method
     *  @param Mixed $status containing message status code 
     *  @return String $status result message
     * */
    public static function get_status_code($status = null) {
 
        if( !isset($status)) {
            return "Unknown STATUS code received. Check status message";
        }
       
        $codes = array(
            '0' => 'OK',
            '1' => 'Unavailable TLD zone',
            '2' => 'Wrong name',
            '3' => 'Domain reserved by ICANN',
            '4' => 'Domain reserved by CCLTD',
            '5' => 'Domain reserved by FID',
            '6' => 'Domain name is rejected. Might contain profanity',
            '7' => 'Domain is taken', 
            '8' => 'Domain access is denied',
            '9' => 'Domain not found',
            '10' => 'Domain name is too short',
            '11' => 'Domain name is too long',
            '12' => 'Cannot perform requested action',
            '51' => 'TLD zone is not supported',
            '53' => 'Requested action is rejected',
            '60' => 'WHOIS service was unreachable',
            '65' => 'Requested action was rejected by superior registrar',
            '68' => 'All fields are required for performing requested action',
            '70' => 'Requested action is already in the task queue', 
            '80' => 'Not enough money on the balance',
            '81' => 'Not enough money on the personal balance',
            '82' => 'Not enough money on bonus balance', 
            '115' => 'Domain name is located in the stop-list',
            '400' => 'Unknown error on the backend system',
            '402' => 'Requested action is not implemented',
            '405' => 'Unsupported action for requested TLD',
            '9510' => 'Authorization error',
            '9515' => 'Error request',
            '9516' => 'Command cannot be performed',
            '9517' => 'Access to API request command is denied',
            '9521' => 'Required parameter is missing',
            '9522' => 'Given registant does not exist in the system',
            '9523' => 'Error parameter',
            '9530' => 'Client error, inappropriate parameters set',
            '9531' => 'Requests from given IP is blocked',
            '9550' => 'Unknown error from the frontend',
            '9560' => 'Method is not implemented',   
        );
        
        return $codes[$status];
    }


    public static function get_translit($s) {
        $t = array(
            "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
            "Д"=>"D","Е"=>"E","Ё"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
            "Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
            "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
            "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"Ts","Ч"=>"Ch",
            "Ш"=>"Sh","Щ"=>"Sch","Ъ"=>"","Ы"=>"Y","Ь"=>"",
            "Э"=>"E","Ю"=>"Yu","Я"=>"Ya","а"=>"a","б"=>"b",
            "в"=>"v","г"=>"g","д"=>"d","е"=>"e", "ё"=>"e","ж"=>"j",
            "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
            "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
            "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
            "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"",
            "ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
        );

        return strtr($s, $t); 
    }


    public static function is_cyrillic($str) {
        $result = (bool) preg_match('/[\p{Cyrillic}]/u', $str);
        return $result;
    }


    public static function get_db_connection() {
        include dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/configuration.php';
        
        self::$dbh = new PDO(
            "mysql:host={$db_host};dbname={$db_name}",
            $db_username,
            $db_password,
            array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
            )
        );

        return self::$dbh;
    }


    public static function close_db_connection() {
        self::$dbh = null;
    }


    public static function get_customfieldvalue($field_name, $field_type, $rel_id) {

        $dbh = !self::$dbh ? self::get_db_connection() : self::$dbh;

        $sql = "SELECT value
                FROM tblcustomfieldsvalues
                WHERE fieldid IN(SELECT id FROM tblcustomfields WHERE fieldname = :fieldname AND type = :type)
                AND relid = :relid";

        $sth = $dbh->prepare($sql);
        $sth->execute(array(':fieldname' => $field_name, ':type' => $field_type, ':relid' => $rel_id));

        // Return.
        return $sth->fetchColumn();
    }

    public static function set_customfieldvalue($field_name, $field_type, $rel_id, $value) {
        $result = false;

        $dbh = !self::$dbh ? self::get_db_connection() : self::$dbh;

        $sql = "SELECT f.*,v.* 
            FROM tblcustomfields AS f LEFT JOIN tblcustomfieldsvalues AS v ON v.fieldid = f.id 
            WHERE f.type=:field_type 
            AND f.fieldname=:field_name 
            AND v.relid=:rel_id";

        $sth = $dbh->prepare($sql);
        $sth->execute(array(':field_type' => $field_type,':field_name' => $field_name, ':relid' => $rel_id));

        $customfield = $sth->fetch(PDO::FETCH_ASSOC);


        if( empty($customfield['value']) || is_null($customfield['value']) ) {
            $sql = "INSERT INTO tblcustomfieldsvalues(fieldid, relid, value) VALUES(:fieldid, :relid, :value)";
            $sth = $dbh->prepare($sql);
            $result = $sth->execute(array(':fieldid' => $customfield['id'], ':relid' => $rel_id, ':value' => $value));
        
        } else {
            $sql = "UPDATE tblcustomfieldsvalues SET value=:value WHERE fieldid=:fieldid AND relid=:relid";
            $sth = $dbh->prepare($sql);
            $result = $sth->execute(array(':value' => $value, ':fieldid' => $customfield['id'], ':relid' => $rel_id));
        }  

        return $result; 
    }


    public static function get_domainadditionalfield($domain_id, $field_name) {
        $result = []; 
        $dbh = !self::$dbh ? self::get_db_connection() : self::$dbh;
    
        $sql = "SELECT * FROM tbldomainsadditionalfields WHERE domainid=:domainid AND name=:name";
        $sth = $dbh->prepare($sql);
        $sth->execute(array(':domainid' => $domain_id, ':name' => $field_name)); 
        
        $data = $sth->fetch(PDO::FETCH_ASSOC);

        if( !empty($data) ) {
            $result = $data;
        }
        
        return $result;
    }


    public static function set_domainadditionalfield($domain_id, $field_name, $value) {
        $result = [];
        $dbh = !self::$dbh ? self::get_db_connection() : self::$dbh;

        $record = self::get_domainadditionalfield($domain_id, $field_name);

        if( !empty($record) && !empty($record['value']) ) {
            $sql = "UPDATE tbldomainsadditionalfields SET value=:value WHERE domainid=:domainid AND name=:name";
            $sth = $dbh->prepare($sql);
            $result = $sth->execute(array(':value' => $value, ':domainid' => $domain_id, ':name' => $field_name)); 
        }

        return $result; 
    }


    public static function translit($s) {
        $t = array(
           "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
           "Д"=>"D","Е"=>"E","Ё"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
           "Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
           "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
           "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"Ts","Ч"=>"Ch",
           "Ш"=>"Sh","Щ"=>"Sch","Ъ"=>"","Ы"=>"Y","Ь"=>"",
           "Э"=>"E","Ю"=>"Yu","Я"=>"Ya","а"=>"a","б"=>"b",
           "в"=>"v","г"=>"g","д"=>"d","е"=>"e", "ё"=>"e","ж"=>"j",
           "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
           "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
           "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
           "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"",
           "ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
        );

        return strtr($s, $t);
    }

}
