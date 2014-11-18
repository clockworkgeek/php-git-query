<?php
namespace GitQuery;

class PackfileStreamTest extends \PHPUnit_Framework_TestCase
{

    function testRegistered()
    {
        $this->assertTrue(in_array('git-packfile', stream_get_wrappers()));
    }

    function testRead()
    {
        $content = file_get_contents('git-packfile://' . __DIR__ . '/sample.git/objects/pack/pack-c5968142451b92172aa57b185874143d125fbdee.pack#12');
        $this->assertEquals("packed content\n", $content);
    }
}
