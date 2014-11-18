<?php
namespace GitQuery;

class ObjectStreamTest extends \PHPUnit_Framework_TestCase
{

    function testRegistered()
    {
        $this->assertTrue(in_array('git-object', stream_get_wrappers()));
    }

    function testRead()
    {
        $content = file_get_contents('git-object://blob/' . __DIR__ . '/sample.git/objects/d6/70460b4b4aece5915caf5c68d12f560a9fe3e4');
        $this->assertEquals("test content\n", $content);
    }
}
