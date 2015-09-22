<?php
//namespace Anvyst\Domenus;

class Domain extends Domenus {

    const FILTER_EQ   = 'EQ'; /* equals */
    const FILTER_GT   = 'GT'; /* greater than */
    const FILTER_LT   = 'LT'; /* less than */
    const FILTER_BTWN = 'BTWN'; /* between */


    /**
     *  domain_Info method
     *  Geting information on the domain
     *  @param Array $data containing domain name (if IDN - then in punycode)
     *  @return Array $result containing information on the Domain/Info request
     * */
    public static function domain_Info($data = []) {
        $result = ['content' => '', 'errors' => [], 'status' => false]; 

        $fields = ['domain'];

        $validated = parent::validate($fields, $data);

        if( !empty($validated) ) {
            $result['errors'] = $validated;
            return $result;
        }

        if( isset($data['domain']) ) {
            $data['domain'] = idn_to_ascii($data['domain']);
        }

        $response = parent::send_request('domain/info', $data);
        $result['content'] = parent::parse_response($response);

        if( $result['content']['success'] == true ) { 
            $result['status'] = true;
        } else {
            $result['errors'][] = $result['content']['message'];
            $result['errors'][] = self::get_status_code($result['content']['status']);
            $result['status'] = false;
        }

        return $result;
    }


    /**
     *  domain_List method
     *  @param Array $data containing registrant and offset
     *  @return Array $result containing info on domain/List
     * */
    public static function domain_List($data = []) {
        $result = ['content' => '', 'errors' => [], 'status' => false];

        //registrant/offset fields are optional, thus no validation is needed. 
        // 1. registrant
        // 2. offset
        // 3. limit
        // 4. flag
        // 5. sort
        // 6. filter-name
        // 7. filter-value
        // 8. filter-cond
    
        $response = parent::send_request('domain/list', $data);
        $result['content'] = parent::parse_response($response);

        if( $result['content']['success'] == true ) {
            $result['status'] = true;
        }

        return $result;
    }
    


    public static function domain_List_Expired($data = []) {
        /* identical to domain_List */    
    }

    /**
     *  domain_Update method
     *  @param Array $data containing information on domain's update
     *  @return Array $result of the response
     * */
    public static function domain_Update($data = []) {
        $result = ['content' => '', 'errors' => [], 'status' => false];
            
        $fields = ['domain'];

        $validated = parent::validate($fields, $data);

        if( !empty($validated) ) {
            $result['errors'] = $validated;
            return $result;
        } 

        if( !empty($data['domain']) ) {
            $data['domain'] = idn_to_ascii($data['domain']);
        }

        $response = parent::send_request('domain/update', $data);
        $result['content']   = parent::parse_response($response);

        if( $result['success'] == true ) {
            $result['status'] = true;
        } else {
            $result['status'] = false;
            $result['errors'][] = $result['content']['message'];
        }

        return $result;
    }


    /**
     *  domain_Prolong method
     *
     *  Domain can be prolonged only for 1 (one) year.
     *  In order to check the prolonging of the domain
     *  you should send domain/check with flag 1 to identify whether
     *  it's possible to prolong it.
     *
     *  @param Array $data
     *  @return Array $result containing orderId 
     * */
    public static function domain_Prolong($data = []) {
        $result = ['content' => '', 'errors' => [], 'status' => false];

        $fields = ['domain'];

        $validated = parent::validate($fields, $data);

        if( !empty($validated) ) { 
            $result['errors'] = $validated;
            return $result;
        }

        if( !empty($data['domain']) ) {
            $data['domain'] = idn_to_ascii($data['domain']);
        }

        $response = parent::send_request('domain/prolong', $data);
        $result['content'] = parent::parse_response($response);

        if( $result['content']['success'] == true ) {
            $result['status'] = true;
        } else {
            $result['status'] = false;
            $result['errors'][] = $result['content']['message'];
            $result['errors'][] = self::get_status_code($result['content']['status']);
        }

        return $result; 
    }



    /**
     *  domain_GetExtAttributes method
     *
     * */
    public static function domain_Getextattributes($data = []) {
        $result = ['content' => '', 'errors' => [], 'status' => false];

        $fields = ['domain'];

        $validated = parent::validate($fields, $data);

        if( !empty($validated) ) {
            $result['errors'] = $validated;
            return $result;
        } 

        if( !empty($data['domain']) ) {
            $data['domain'] = idn_to_ascii($data['domain']);
        }

        $response = parent::send_request('domain/getextattributes', $data);
        $result['content'] = parent::parse_response($response); 

        if( $result['content']['success'] == true ) {
            $result['status'] = true;
        } else {
            $result['errors'][] = $result['content']['message'];
            $result['status'] = false;
        }

        return $result;
    }



    public static function domain_Getexttlds($data = []) {
       $result = ['content' => '', 'errors' => [], 'status' => false];

       $response = parent::send_request('domain/getexttlds', $data);
       $result['content']   = parent::parse_response($response);

       if( $result['content']['success'] == true ) {
           $result['status'] = true;
       }

       return $result;
    }


    /*
     *  Working cURL request: curl -v -k -X GET 'https://api-test.domenus.ru/CURRENT/domain/check?p_login=CL274630-ORG-DMS&p_password=iFNZfQrsblxvA5Gl&domain=anvyst.com&flag=4'
     *
     */
    public static function domain_Check($data = []) {
        $result = ['content' => '', 'errors' => [], 'status' => false];

        $fields = ['domain','flag'];

        $validated = parent::validate($fields, $data);

        if( !empty($validated) ) {
            $result['errors'] = $validated;
            return $result;
        }

        if( isset($data['domain']) ) { 
            $data['domain'] = idn_to_ascii($data['domain']); 
        }

        $response           = parent::send_request('domain/check', $data);
        $result['content']  = parent::parse_response($response);
        
        if( $result['content']['success'] == true ) {
            $result['status'] = true;
        }

        return $result; 
    }

    
   /**
    *   Register domain
    *   @param Array $data for registering domain
    *   @return Mixed orderId 
    */
    public static function domain_Register($data = []) {
        $result = ['content' => '', 'errors' => [], 'status' => false];

        $fields = ['domain','nserver','someextattrib'];

        if( isset($data['domain']) ) { 
            $data['domain'] = idn_to_ascii($data['domain']);
        }

        $ns_servers = Domenus::get_ns_servers();

        // substituting default NS servers from configs
        if( empty($data['nserver']) ) {
            $data['nserver'] = $ns_servers;
        } 

        $validated =  parent::validate($fields, $data);

        if( !empty($validated) ) {
            $result['errors'] = $validated;
            return $result;
        }
        
        $response = parent::send_request('domain/register', $data);
        $result['content'] = parent::parse_response($response);

        if( $result['content']['success'] == true ) {
            $result['status'] = true;
        } else {
            $result['errors'][] = self::get_status_code($result['content']['status']);
            $result['status'] = false;
        }

        return $result;
    }
}
