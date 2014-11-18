<?php
namespace GitQuery;

class TreeTest extends \PHPUnit_Framework_TestCase
{

    function testRead()
    {
        $tree = new Tree(__METHOD__);
        $tree->parse("100644 test.txt\0abcdefghijklmnopqrst");
        
        $this->assertCount(1, $tree);
        $this->assertInstanceOf('\GitQuery\Blob', $tree[0]);
        $this->assertEquals('test.txt', $tree[0]->name);
        $this->assertEquals('100644', $tree[0]->mode);
    }

    function testAutoRead()
    {
        $repo = $this->getMock('\GitQuery\Repository');
        $repo->expects($this->once())
            ->method('getContentURL')
            ->with('tree', __METHOD__)
            ->willReturn('data://text/plain;base64,MTAwNjQ0IHRlc3QudHh0AGFiY2RlZmdoaWprbG1ub3BxcnN0');

        $tree = new Tree(__METHOD__);
        $this->assertCount(1, $tree);
        $this->assertInstanceOf('\GitQuery\Blob', $tree[0]);
        $this->assertEquals('test.txt', $tree[0]->name);
        $this->assertEquals('100644', $tree[0]->mode);
    }
}
