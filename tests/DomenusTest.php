<?php

require_once dirname(dirname(__FILE__)).'/src/Domenus.php';

class DomenusTest extends PHPUnit_Framework_TestCase {


    public function test__CheckValidateMethod() {
        
        $result = Domenus::validate();
        $this->assertEquals($result[0], "Fields are empty. Check the request body.");
    }


    public function test__checkValidateMethodWrongArguments() {
        $result = Domenus::validate(['domain','flag'], ['foo' => 'bar', 'baz' => 'bar1']);

        $this->assertEquals($result[0], 'Required field [domain] is empty.');
        $this->assertEquals($result[1], 'Required field [flag] is empty.');
    } 

    public function test__get_ns_servers__ReturnDefault() {
        $result = Domenus::get_ns_servers();
        
        $this->assertNotEmpty($result);
    }
}
?>
