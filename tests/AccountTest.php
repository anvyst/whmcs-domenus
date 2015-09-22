<?php

require_once dirname(dirname(__FILE__)). '/src/Domenus.php';
require_once dirname(dirname(__FILE__)). '/src/Account.php';

class AccountTest extends PHPUnit_Framework_TestCase {

    public function test__AccountBalance__returnData() {
        
        $result = Account::account_Balance();
        
        if( $result['content']['success'] == true ) {
            $this->assertNotEquals(0, $result['content']['data']['personal_balance']);
        } else {
            echo __FUNCTION__ . ": ". parent::get_status_code($result['content']['status']) . "\n";
        }
    }
}
