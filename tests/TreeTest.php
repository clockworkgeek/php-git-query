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
        $tree = new Tree($this->repo, '80865964295ae2f11d27383e5f9c0b58a8ef21da');

        $stream = fopen(__DIR__.'/sample.git/objects/80/865964295ae2f11d27383e5f9c0b58a8ef21da', 'rb');
        $tree->read($stream);
        fclose($stream);
        $this->assertCount(1, $tree);
        $this->assertInstanceOf('\GitQuery\Blob', $tree[0]);
        $this->assertEquals('test.txt', $tree[0]->name);
        $this->assertEquals('100644', $tree[0]->mode);
    }
}
