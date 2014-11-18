<?php
namespace GitQuery;

class ObjectTest extends \PHPUnit_Framework_TestCase
{

    var $repo;

    function setUp()
    {
        $this->repo = $this->getMock('\GitQuery\Repository');
    }

    function testRead()
    {
        // test data taken from http://git-scm.com/book/en/v2/Git-Internals-Git-Objects
        $this->repo->expects($this->once())
            ->method('getContentURL')
            ->with(__METHOD__)
            ->willReturn("data://text/plain,test content\n");
        
        $object = $this->getMock('\GitQuery\Object', array(
            'parse'
        ), array(
            __METHOD__
        ));
        $object->expects($this->once())
            ->method('parse')
            ->with("test content\n");
        
        $object->read();
    }
}
