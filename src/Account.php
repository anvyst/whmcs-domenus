<?php


class Account extends Domenus {

    public static function account_Balance($data = []) {
        $result = ['content' => '', 'errors' => [], 'status' => false];

        //nothing to validate
        $response = parent::send_request('account/balance', $data);
        $result['content'] = parent::parse_response($response);

        if( $result['content']['success'] == true ) {
            $result['status'] = true;
        } else {
            $result['errors'][] = parent::get_status_code($result['content']['status']);
            $result['status'] = false;
        } 

        return $result;
    }

}
?>
