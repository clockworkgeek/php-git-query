<?php
namespace GitQuery;

class TreeTest extends \PHPUnit_Framework_TestCase
{

    var $repo;

    function setUp()
    {
        $this->repo = $this->getMock('\GitQuery\Repository');
    }

    function testRead()
    {
        $tree = new Tree(__METHOD__);
        $tree->parse("100644 test.txt\0abcdefghijklmnopqrst");
        
        $this->assertCount(1, $tree);
        $this->assertInstanceOf('\GitQuery\Blob', $tree[0]);
        $this->assertEquals('test.txt', $tree[0]->name);
        $this->assertEquals('100644', $tree[0]->mode);
    }
}
