<?php
namespace GitQuery;

class CommitTest extends \PHPUnit_Framework_TestCase
{

    var $repo;

    function setUp()
    {
        $this->repo = $this->getMock('\GitQuery\Repository');
    }

    function testRead()
    {
        // use class::method as a hash
        $commit = new Commit(__METHOD__);
        $commit->parse("tree anotherHash\n\ntest message");
        
        $this->assertEquals("test message", $commit->message);
        $this->assertFalse($commit->parent);
        $this->assertNotFalse($commit->tree);
    }
}
