<?php
/**
 *  WHMCS Domenus container
 *  used for domain registrar
 * */
require_once ROOTDIR . '/init.php';
require_once dirname(__FILE__). DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Domenus.php';


/**
 *  prepare_contact_fields method
 *  @param Array $params containing all the WHMCS info
 *  @return Array $result prepared for Domenus data submission
 * */
function prepare_contact_fields($params = []) {
    $result = [];
    $errors = [];

    if( empty($params) ) {
        return $result;
    }

    if( Domenus::is_cyrillic($params['fullname']) ) {
        $result['name']     = trim(Domenus::translit($params['fullname']));
        $result['name_ru']  = trim($params['fullname']);
    } else {
        $result['name']     = trim($params['fullname']); 
        $errors[] = "Param [name_ru] should be in Russian. Fill in first/last names in Russian in the profile.";
    } 

    if( Domenus::is_cyrillic($params['address1']) && Domenus::is_cyrillic($params['address2']) ) {
        $result['address_ru'] = sprintf("%s %s", $params['address1'], $params['address2']);
        $result['address1'] = Domenus::translit(sprintf("%s %s", $params['address1'], $params['address2']));
    } else {
        $result['address1'] = Domenus::translit(sprintf("%s %s", $params['address1'], $params['address2']));
        $errors[] = "Param [address_ru] should be in Russian.";
    }

    $result['firstname'] = Domenus::is_cyrillic($params['firstname']) ? Domenus::translit($params['firstname']) : $params['firstname'];
    $result['lastname']  = Domenus::is_cyrillic($params['lastname']) ? Domenus::translit($params['lastname']) : $params['lastname'];
    $result['email']     = $params['email'];

    $result['country']          = $params['countryname'];
    $result['state_province']   = $params['state'];
    $result['paddr']            = $params['address1'];

    if( Domenus::is_cyrillic($params['city']) ) {
        $errors[] = "Param [City] should be in English";
    } else {
        $result['city'] = $params['city'];
    }

    if( Domenus::is_cyrillic($params['postcode']) ) {
        $errors[] = "Param [Postalcode] should be in English";
    } else {
        $result['postalcode'] = $params['postcode'];
    } 

    // custom field for birth date
    $result['birth_date']       = prepare_birth_date($params['userid']);
    
    // getting passport custom field
    $result['passport'] = prepare_passport_fields($params['userid']);
   
    // custom fields for phone identification 
    $phone_parts        = prepare_phone_fields($params['userid']);
    $result['phone']    = sprintf("+%s %s", $phone_parts['code'], $phone_parts['number']);
    
    // we assume that the fax and phone num are the same. Fax, ha?!
    $result['fax']      = $result['phone'];

    // stop executing if any errors added
    if( !empty($errors) ) {
        $result['errors'] = $errors;
    }

    return $result;
}


/**
 *  prepare_phone fields
 *  With exact top bottom priority of localized fields
 *  @param Integer $user_id
 *  @return Array $result with code and phone
 * */
function prepare_phone_fields($user_id) {
    $result = ['code' => '', 'phone' => ''];

    $check_code_cn   = Domenus::get_customfieldvalue('国家代码', 'client', $user_id);
    $check_number_cn = Domenus::get_customfieldvalue('电话号码', 'client', $user_id);

    if( !empty($check_code_cn) && $check_code_cn != '000'  && !empty($check_code_cn) ) {
        $result['code'] = $check_code_cn;
    }

    if( !empty($check_number_cn) && $check_number_cn != '000' && !empty($check_number_cn) ) {
        $result['number'] = $check_number_cn;
    }

     // English.
    $check_code_en = Domenus::get_customfieldvalue('Country Code', 'client', $user_id);
    $check_number_en = Domenus::get_customfieldvalue('Phone Number', 'client', $user_id);

    if( !empty($check_code_en) && $check_code_en != '000' && !empty($check_code_en) ) {
        $result['code'] = $check_code_en;
    }

    if( !empty($check_number_en) && $check_number_en != '000' && !empty($check_number_en) ) {
        $result['number'] = $check_number_en;
    }

    // Russian.
    $check_code_ru = Domenus::get_customfieldvalue('Код страны', 'client', $user_id);
    $check_number_ru = Domenus::get_customfieldvalue('Номер телефона', 'client', $user_id);

    if( !empty($check_code_ru) && $check_code_ru != '000' && !empty($check_code_ru) ){
        $result['code'] = $check_code_ru;
    }

    if( !empty($check_number_ru) && $check_number_ru != '000' && !empty($check_number_ru) ) {
        $result['number'] = $check_number_ru;
    }

    if( $result['code'] == '000' ) {
        $result['code'] = '';
    }

    if( $result['number'] == '000' ) {
        $result['number'] = '';
    }

    $result['code'] = preg_replace('/[^0-9]/','', $result['code']);
    $result['number'] = preg_replace('/[^0-9]/', '', $result['number']);

    return $result;
}



/**
 *  prepare_birth_date method
 *  @param Integer $user_id
 *  @return String $result containing birthdate
 * */
function prepare_birth_date($user_id) {
    $result = '';

    $birthdate_en = Domenus::get_customfieldvalue('Birthdate', 'client', $user_id);

    if( !empty($birthdate_en) ) {
        $result = $birthdate_en;
    }

    $birthdate_ru = Domenus::get_customfieldvalue('Дата рождения', 'client', $user_id);

    if( !empty($birthdate_ru) ) {
        $result = $birthdate_ru;
    }

    return $result;
}


/**
 *  prepare_passport_fields method
 *  @param Integer $user_id
 *  @return String $result containing user's passport ID
 * */
function prepare_passport_fields($user_id) {
    $result = '';
    
    $passport = Domenus::get_customfieldvalue('ID number', 'client',$user_id); 

    if( !empty($passport) ) {
        $result = $passport;
    }
    
    $passport = Domenus::get_customfieldvalue('Серия, номер паспорта', 'client', $user_id);
    $issued_by = Domenus::get_customfieldvalue('Кем выдан паспорт', 'client', $user_id);

    if( !empty($passport) ) {
        $result = $passport;

        if( !empty($issued_by) ) {
            $result .= " ".$issued_by;
        }
    }
    
    return $result;
}


/**
 *  prepare_ns_servers method
 *  @param Array $params containing WHMCS data
 *  @return Array $result containing either defined servers
 *  or default NS servers from config.yml
 * */
function prepare_ns_servers($params = []) {
    $result = [];

    $servers = range(1,5);

    foreach($servers as $i) {
        $key = 'ns'.$i;
        if( !empty($params[$key]) ) {
            array_push($result, $params[$key]);
        }
    }

    if( empty($result) ) {
        $result = Domenus::get_ns_servers();
    }

    return $result;
}


function domenus_getConfigArray() {
    return array(
        'Url'           => array('Type' => 'text', 'Size' => '50', 'Description' => 'Enter your API URL'),  
        'Username'      => array('Type' => 'text', 'Size' => '30', 'Description' => 'Enter your username'),
        'Password'      => array('Type' => 'password', 'Size' => '30', 'Description' => 'Enter your password'),
        'DefaultNs'     => array('Type' => 'text', 'Size' => '200', 'Description' => 'Default NS servers, comma separated'),
        'TestMode'      => array('Type' => 'yesno'), // starts looking for config.yml in the registrars folder for test credentials
    );
}



/**
 *  Register domain in WHMCS
 *  @params Array $params containing Domain and User info
 *  @return String $success
 * */
function domenus_RegisterDomain($params) {
    $return_data = [];
    $registered = null;

    // making sure that we got domain name in hand.
    if( !empty($params['domainname']) ) {
        $domain = $params['domainname'];    
    } else {
        //find via tbldomains
        $res = full_query("SELECT domain FROM tbldomains WHERE id='{$params['domainid']}'");
        $record = mysql_fetch_assoc($res);
        $domain = $record['domain'];
    }

    // do preliminary check for domain registry with inclusive OR.
    $check_flag = Domenus::DOMAIN_IS_FREE | Domenus::DOMAIN_IS_VALID | Domenus::DOMAIN_FOR_REGISTER;
    
    $checked = Domenus::call('domain/check', ['domain' => $domain, 'flag' => $check_flag] );
    logModuleCall('domenus_registrar', __FUNCTION__.':domain/check', print_r(['domain' => $domain, 'flag' => $check_flag],1), print_r($checked, 1) ); 
    
    die("No registration avaiable. Remove die flag if you're sure");

    if( $checked['status'] == true ) {
        $domain_registrant = Domenus::get_customfieldvalue('domenus_registrant', 'client', $params['userid']);

        // we know that registrant exists on WHMCS, we also need to 
        // check if it exists on the side of the registrar. 
        if( !empty($domain_registrant) ) { 
            $existing_user = Domenus::call('contact/info', [ 'registrant' => $domain_registrant ]);
            logModuleCall('domenus_registrar', __FUNCTION__.':contact/info', print_r(['registrant' => $domain_registrant],1), print_r($existing_user,1)); 
        }

        // the info is verified, and we can proceed with registering domain
        if( $existing_user['status'] == true && !empty($domain_registrant) ) {
            $data = array(
                'domain'     => $domain,
                'registrant' => $domain_registrant,
                'nserver'    => prepare_ns_servers($params),
            );
            $registered = Domenus::call('domain/register', $data);
            logModuleCall('domenus_registrar', __FUNCTION__.':domain/register', print_r($data,1), print_r($registered,1));
            unset($data); // to avoid var overwrites 

        } else {
            //we have to register the user at first 
            $prepared_data = prepare_contact_fields($params);
            $prepared_data['contact_type'] = 'person';
            
            if( !isset($prepared_data['errors']) || empty($prepared_data['errors']) ) {  
                $saved_user = Domenus::call('contact/register', $prepared_data); 
                logModuleCall('domenus_registrar', __FUNCTION__.':contat/register', print_r($prepared_data,1), print_r($saved_user,1));
                
                if( $saved_user['status'] == true ) {
                    $domain_registrant = $saved_user['content']['data']['registrant'];
                    $saved = Domenus::set_customfieldvalue('domenus_registrant','client', $params['userid'], $domain_registrant);
                
                    if( $saved ) {
                        $data = array(
                            'domain'     => $domain,
                            'registrant' => $domain_registrant,
                            'nserver'    => prepare_ns_servers($params),
                        );
                        $registered = Domenus::call('domain/register', $data); 
                        logModuleCall('domenus_registrar', __FUNCTION__.':domain/register', print_r($data,1), print_r($registered,1));
                    }
                }
            } else {
                $return_data['error'] = join('. ', $prepared_data['errors']);
            }
        }

        // if everything's okay, we should store orderId of the registered domain,
        // for future use.
        if( !is_null($registered) && $registered['status'] == true ) {
            $order_id = $registered['content']['data']['orderId'];
            $saved_order = Domenus::set_domainadditionalfield($params['domainid'], 'orderId', $order_id);
            if( $saved_order ) {
                $return_data['success'] = true;
            }
        } else {
            $return_data['error'] = "Couldn't receive domain registration orderId. Check logs";
        } 
    } else {
        $return_data['error'] = "Couldn't check the domain before registration: " .join('. ', $checked['errors']);
    }

    return $return_data; 
}


/**
 *  GetNameservers method
 *  @param Array $params with user info
 *  @return Array $data containing NS servers
 * */
function domenus_GetNameservers($params) {
    $data       = [];
    $domain     = $params['domainname'];
    $response   = Domenus::call('domain/info', ['domain' => $domain]);
    
    if( $response['status'] == true ) {
        $content = $response['content']['data'];

        foreach($content as $k => $val) {
            if( preg_match('/^nserver(\d+)$/i', $k, $matches) ) {
                $data['ns'.$matches[1]] = $val; 
            }
        } 
    } else {
        $data['error'] = "Couldn't GetNameServers() data from registrar. Domenus Error: ". join('. ', $response['errors']);
        logModuleCall('domenus_registrar', __FUNCTION__.':domain/info', print_r($data,1), print_r($registered,1));
    }

    return $data;
}



/**
 *  Save changes on the clients name servers
 *  @param Array $params containing all user info
 *  @return Array $data containing result of Saving name servers
 * */
function domenus_SaveNameservers($params) {
    $request['domain']  = $params['domainname'];
    $request['nserver'] = [];
    $data               = []; 
    
    foreach($params as $k => $val) {
        if( preg_match('/^ns(\d+)$/i', $k, $matches) ) {
            array_push($request['nserver'], $val);
        }
    }

    $response = Domenus::call('domain/update', $request);
    
    if( $response['status'] == true ) {
        $data['success'] = true;
    } else {
        $data['error'] = "Couldn't Update NS-servers for the domain. Domenus Error: ". join('. ', $response['errors']);
    }

    return $data; 
}



/**
 *  SaveContactDetails method
 *  @param Array $params of the user contact details
 *  @return Array $return_data with success/error keys
 * */
function domenus_SaveContactDetails($params) {
    $return_data = [];
    $titles_map = array(
        'Email'             => 'email',
        'First Name'        => 'firstname',
        'Last Name'         => 'lastname',
        'Full Name English' => 'name',
        'Full Name Russian' => 'name_ru',
        'Passport'          => 'passport',
        'Birth Date'        => 'birth_date',
        'Address Full'       => 'address1',
        'Address Russian'   => 'address_ru',
        'Postal Address'    => 'paddr',
        'Phone Number'      => 'phone',
        'Fax'               => 'fax',
        'Country'           => 'country',
        'City'              => 'city',
        'Postcode'          => 'postalcode',
        'State'             => 'state_province',
    );

    $data = [];

    foreach($params['contactdetails']['Admin'] as $k => $val) {
        $data[$titles_map[$k]] = $val;
    }

    if( !empty($data) ) {
        $res = full_query("SELECT domain,id,userid FROM tbldomains WHERE id='{$params['domainid']}'");
        $domain_record  = mysql_fetch_assoc($res);
        $registrant     = Domenus::get_customfieldvalue('domenus_registrant', 'client', $domain_record['userid']);
        
        $data['registrant'] = $registrant;
        
        if( !empty($registrant) ) { 
            $response = Domenus::call('contact/update', $data);

            if( $response['status'] == true ) {
                $return_data['success'] = true;
            } else {
                $return_data['error'] = join('. ', $response['errors']);
            }
        } else {
            $return_data['error'] = "Couldn't find registrant with given username [$registrant]";
        } 
    }
}



/**
 *  GetContactDetails
 *  Getter of user contact details via API
 *  @param Array $params of domain details
 *  @return Array $return_data containing user profile data
 * */
function domenus_GetContactDetails($params) {
    $return_data = [];
    $res = full_query("SELECT domain,id,userid FROM tbldomains WHERE id='{$params['domainid']}'");
    $domain_record = mysql_fetch_assoc($res);

    if( empty($domain_record) ) {
        $return_data['error'] = "Couldn't find domain based on its ID.";
        return $return_data;
    }

    $registrant = Domenus::get_customfieldvalue('domenus_registrant', 'client', $domain_record['userid']);
    
    $contact_info = Domenus::call('contact/info', ['registrant' => $registrant]);  

    if( $contact_info['status'] == true ) {
        $return_data['Admin']['Email']          = $contact_info['content']['data']['email'];
        $return_data['Admin']['First Name']     = $contact_info['content']['data']['firstname'];
        $return_data['Admin']['Last Name']      = $contact_info['content']['data']['lastname'];
        $return_data['Admin']['Full Name English'] = $contact_info['content']['data']['name_en'];
        
        $full_russian_name = preg_replace('/\\\u0([0-9a-fA-F]{3})/','&#x\1;',$contact_info['content']['data']['name_ru']);
        $decoded = html_entity_decode($full_russian_name, ENT_NOQUOTES, 'UTF-8');
        $return_data['Admin']['Full Name Russian'] = iconv("utf-8", "windows-1251", $decoded);

        $return_data['Admin']['Passport']       = $contact_info['content']['data']['passport'];
        if( isset($contact_info['content']['data']['birth_date']) ) { 
            $birth = DateTime::createFromFormat("D, d M Y g:i:s O", $contact_info['content']['data']['birth_date']);
            $return_data['Admin']['Birth Date']     = $birth->format('d.m.Y');
        }
        $return_data['Admin']['Address Full']   = $contact_info['content']['data']['address1'];
       
        
        $full_address_ru = preg_replace('/\\\u0([0-9a-fA-F]{3})/','&#x\1;',$contact_info['content']['data']['address_ru']);
        $decoded = html_entity_decode($full_address_ru, ENT_NOQUOTES, 'UTF-8');
        $return_data['Admin']['Address Russian'] = iconv("utf-8", "windows-1251", $decoded);

        $return_data['Admin']['Postal Address']  = $contact_info['content']['data']['paddr'];
        $return_data['Admin']['Phone Number']   = $contact_info['content']['data']['phone'];
        $return_data['Admin']['Fax']            = $contact_info['content']['data']['fax'];
        $return_data['Admin']['Country']        = $contact_info['content']['data']['country'];
        $return_data['Admin']['City']           = $contact_info['content']['data']['city'];
        $return_data['Admin']['Postcode']       = $contact_info['content']['data']['postalcode'];
        $return_data['Admin']['State']          = $contact_info['content']['data']['state_province'];
    }
    
    return $return_data;
}



/**
 *  RenewDomain method 
 *  Prolonging domain registry
 *  @param Array $params 
 * */
function domenus_RenewDomain($params) {
    $return_data = [];

    if( !empty($params['domainname']) ) {
        $response = Domenus::call('domain/prolong', ['domain' => $params['domainname']]);
        
        if( $response['status'] == true ) {
            $order_id = $registered['content']['data']['orderId'];
            $saved_order = Domenus::set_domainadditionalfield($params['domainid'], 'orderId', $order_id);
            if( $saved_order ) {
                $return_data['success'] = true;
            } else {
                $return_data['error'] = "Couldn't receive domain registration orderId. Check logs";
            }
        } else {
            $return_data['error'] = join('. ', $response['errors']);
        }
        
    }
    logModuleCall('domenus_registrar', __FUNCTION__, print_r(['domain' => $params['domainname']], 1), null, print_r($response, 1));
    return $return_data;
}


/**
 *  Syncing the domains
 *  @param Array $params containing domain arguments
 * */
function domenus_Sync($params) {
    $return_data = [];
    $domain = "{$params['sld']}.{$params['tld']}";

    if( empty($domain) ) {
        $return_data['error'] = "There's no domain passed in sync function";
        return $return_data; 
    }

    $checked = Domenus::call('domain/info', ['domain' => $domain]);

    if( $checked['status'] == true ) {
        if( $checked['content']['data']['reg_till'] ) { 
            $reg_till = $checked['content']['data']['reg_till'];
            $reg_till = date('Y-m-d', strtotime($reg_till));
            $return_data['expirydate'] = $reg_till;

            if( date('Ymd') <= str_replace('-', '', $return_data['expirydate']) ) {
                $return_data['active'] = true;
            } else {
                $return_data['expired'] = true;
            }
        } else {
            $return_data['expired'] = true;
        }

    } else {
        $return_data['error'] = join('. ', $checked['errors']);
    }

    logModuleCall('domenus_registrar', __FUNCTION__, print_r(['domain' => $domain], 1), null, print_r($checked, 1));
    return $return_data;
}


?>
