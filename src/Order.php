<?php

class Order extends Domenus {


    public static function order_Status($data = []) {
        $result = ['content' => '', 'errors' => [], 'status' => false];
        
        $fields = ['id'];

        $validated = parent::validate($fields, $data);
        
        if( !empty($validated) ) {
            $result['errors'] = $validated;
            return $result;
        } 

        $response   = parent::send_request('order/status', $data);
        $result['content'] = parent::parse_response($response);

        if( $result['content']['success'] == true ) {
            $result['status'] = true;
        }

        return $result;
    }


    public static function order_Cancel($data = []) {
        $result = ['content' => '', 'errors' => [], 'status' => false];

        $fields = ['id'];

        $validated = parent::validate($fields, $data);

        if( !empty($validated) ) {
            $result['errors'] = $validated;
            return $result;
        } 

        $response = parent::send_request('order/cancel', $data);
        $result['content']   = parent::parse_response($data);

        if( $result['content']['success'] == true ) {
            $result['status'] = true;
        }

        return $result;
    }
    
    public static function order_Discloseinfo($data = []) {
        $result = ['content' => '', 'errors' => [], 'status' => false];

        $fields = ['registrant','hide_person','hide_email','hide_phone'];

        $validated = parent::validate($fields, $data);

        if( !empty($validated) ) {
            $result['errors'] = $validated;
            return $result;
        } 

        $response   = parent::send_request('order/discloseinfo', $data);
        $result['content'] = parent::parse_response($response);

        if( $result['content']['success'] == true ) {
            $result['status'] = true;
        }

        return $result;
    }

}

?>
