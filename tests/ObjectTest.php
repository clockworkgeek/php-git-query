<?php
namespace GitQuery;

class StoredObjectTest extends \PHPUnit_Framework_TestCase
{

    var $repo;

    function setUp()
    {
        $this->repo = $this->getMock('\GitQuery\Repository');
    }

    function testRead()
    {
        // test data taken from http://git-scm.com/book/en/v2/Git-Internals-Git-Objects

        $object = $this->getMock('\GitQuery\Object', array('parse'), array($this->repo, null));
        $object->expects($this->once())
            ->method('parse')
            ->with('blob', "test content\n");

        $stream = fopen(__DIR__.'/sample.git/objects/d6/70460b4b4aece5915caf5c68d12f560a9fe3e4', 'rb');
        $object->read($stream);
        fclose($stream);
    }

    function testAutoRead()
    {
        $repo = $this->repo;
        $repo->expects($this->once())
            ->method('streamInto')
            ->with('not a real hash')
            ->willReturnCallback(function($sha1, $callback) {
                $stream = fopen(__DIR__.'/sample.git/objects/d6/70460b4b4aece5915caf5c68d12f560a9fe3e4', 'rb');
                call_user_func($callback, $stream);
                fclose($stream);
            });
        $object = $this->getMock('\GitQuery\Object', array('parse'), array($this->repo, 'not a real hash'));
        $object->expects($this->once())
            ->method('parse')
            ->with('blob', "test content\n");

        $object->read();
    }
}
