<?php

require_once dirname(dirname(__FILE__)).'/src/Domenus.php';
require_once dirname(dirname(__FILE__)).'/src/Domain.php';


class DomainTest extends  PHPUnit_Framework_TestCase {

    public function test__DomainInfo__passingDomainForInfo() {
    
        $result = Domain::domain_Info(['domain' => 'cypulse123.ru']);
        print_r($result);
        if( $result['content']['success'] == true ) {
            $this->assertNotEmpty($result['content']['data']);
            $this->assertEquals(strtoupper('cypulse123.ru'), $result['content']['data']['domain']);
        } else {
            $this->assertNotEmpty($result['errors']);
            $this->assertEquals($result['errors'][0], 'No such domain');
        }
    
    }

    public function test__DomainList__checkingList() {
    
        $result = Domain::domain_List();
    
        if( $result['content']['success'] == true ) {
            if( count($result['content']['data']['total_count']) > 0 ) { 
                $this->assertNotEmpty($result['content']['data']['domain_0']);
            } else {
                $this->assertEquals(0, $result['content']['data']['total_count']);
            }
        }
    }   


    public function test__DomainGetExtAttributes__returnData() {
    
        $result = Domain::domain_Getextattributes(['domain' => 'cypulse123.com']);
        if( !$result['content']['success'] ) {
            $this->assertEquals($result['content']['status'], 9550);
        }
    }

    
    public function test__DomainGetexttlds__returnResults() {
        
        $result = Domain::domain_Getexttlds();

        if( $result['content']['success'] == true ) {
            $count = $result['content']['data']['total_count'];
            $expected = count($result['content']['data']) - 1;
            $this->assertEquals($count, $expected);
        }
    }

}

?>
