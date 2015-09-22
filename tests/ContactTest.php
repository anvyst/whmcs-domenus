<?php

require_once dirname(dirname(__FILE__)).'/src/Domenus.php';
require_once dirname(dirname(__FILE__)).'/src/Contact.php';

class ContactTest extends PHPUnit_Framework_TestCase {

    public function test__ContactInfo__returnData() {
    
        $result = Contact::contact_Info(['registrant' => '']);
        
        if( $result['content']['success'] == true ) {
            $this->assertNotEmpty($result['content']['data']);
            $this->assertNotEmpty($result['content']['data']['login']);
        }
    }

}
