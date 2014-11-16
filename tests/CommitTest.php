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
        $commit = new Commit($this->repo, '436e298f70ec95470282ef104738edd503bfb65a');

        $stream = fopen(__DIR__.'/sample.git/objects/43/6e298f70ec95470282ef104738edd503bfb65a', 'rb');
        $commit->read($stream);
        fclose($stream);
        $this->assertEquals("test commit\n", $commit->message);
        $this->assertFalse($commit->parent);
        $this->assertNotFalse($commit->tree);
    }
}
