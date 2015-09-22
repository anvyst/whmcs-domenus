<?php

class Contact extends Domenus {

    public static function contact_Info($data = []) {
        $result = ['content' => '', 'errors' => [], 'status' => false];

        $fields = ['registrant'];

        $validated = parent::validate($fields, $data);

        if( !empty($validated) ) {
            $result['errors'] = $validated;
            return $result;
        }

        $response   = parent::send_request('contact/info', $data);
        $result['content'] = parent::parse_response($response);

        if( $result['content']['success'] == true ) {
            $result['status'] = true;   
        } else {
            $result['errors'][] = $result['content']['message'];
            $result['status'] = false;
        } 

        return $result;
    }


    public static function contact_Update($data = []) {
       $result = ['content' => '', 'errors' => [], 'status' => false];

       // contact details required for ordinary users
       $fields = [
           'registrant','name','name_ru','passport', 'birth_date','address_ru','paddr',
           'phone','email','fax','firstname','lastname','address1','city','postalcode','country',
           'state_province'
           ];

       // @NOTE: other types of users have different required fields,
       // and should be added later, if needed.
    
        $validated = parent::validate($fields, $data);
        
        if( !empty($validated) ) {
            $result['errors'] = $validated;
            return $result;
        }

        $response = parent::send_request('contact/update', $data);
        $result['content'] = parent::parse_response($response);

        if( $result['content']['success'] == true ) {
            $result['status'] = true;
        }

        return $result;
    }


    public static function contact_Register($data = []) {
       $result = ['content' => '', 'errors' => [], 'status' => false];
    
       $fields = [
           'contact_type', 'name','name_ru','passport','birth_date',
           'address_ru','paddr','phone','email','fax','firstname','lastname',
           'address1','city','postalcode','country','state_province'
       ];

        //by default we register domains for private users, not organizations or others 
       if(!isset($data['contact_type']) || empty($data['contact_type']) ) {
           $data['contact_type'] = 'person';
       }

       $validates = parent::validate($fields, $data);

       if( !empty($validated) ) {
            $result['errors'] = $validated;
            return $result;
       }

       $response = parent::send_request('contact/register', $data);
       $result['content'] = parent::parse_response($response);

       if( $result['content']['success'] == true ) {
            $result['status'] = true; 
       } else {
            $result['status'] = false;
            $result['errors'][] = $result['content']['message'];
       }

       return $result;
    }
}

?>
