<?php
namespace GitQuery;

class CommitTest extends \PHPUnit_Framework_TestCase
{

    function testRead()
    {
        // use class::method as a hash
        $commit = new Commit(__METHOD__);
        $commit->parse("tree anotherHash\n\ntest message");
        
        $this->assertEquals("test message", $commit->message);
        $this->assertFalse($commit->parent);
        $this->assertNotFalse($commit->tree);
    }

    function testAutoRead()
    {
        $repo = $this->getMock('\GitQuery\Repository');
        $repo->expects($this->once())
            ->method('getContentURL')
            ->with('commit', __METHOD__)
            ->willReturn("data://text/plain,tree anotherHash\n\ntest message");

        $commit = new Commit(__METHOD__);
        $tree = $commit->tree;
        $this->assertInstanceOf('\GitQuery\Tree', $tree);
        $this->assertEquals('anotherHash', $tree->sha1);
    }
}
