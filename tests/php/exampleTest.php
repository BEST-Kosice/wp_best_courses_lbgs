<?php

// This is a first test to serve as a template to make other tests.
// All test php file names need to end with 'Test' in order to be detected and run.
class ExampleTest extends PHPUnit_Framework_TestCase
{
    public function testPushAndPop()
    {
        // Passes test if the file is called 'exampleTest.php'.
        $this->assertEquals( 'exampleTest', pathinfo(__FILE__, PATHINFO_FILENAME) );
    }
}
