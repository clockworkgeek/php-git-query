<?php
namespace GitQuery;

class RemoteRepositoryTest extends \PHPUnit_Framework_TestCase
{

    function testReadReference()
    {
        $connector = $this->getMockForAbstractClass('\GitQuery\Connector');
        $connector->expects($this->atLeast(3))
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                '004e',
                "436e298f70ec95470282ef104738edd503bfb65a HEAD\0ignore list of capabilities\n",
                '0000');
        $repo = new RemoteRepository($connector);
        
        $commit = $repo->head();
        $this->assertInstanceOf('\GitQuery\Commit', $commit);
        $this->assertEquals('436e298f70ec95470282ef104738edd503bfb65a', $commit->sha1);
    }

    function testFetch()
    {
        $connector = $this->getMockForAbstractClass('\GitQuery\Connector');
        $connector->expects($this->any())
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                '0032',
                "436e298f70ec95470282ef104738edd503bfb65a HEAD\n",
                '0000');
        $connector->expects($this->exactly(7))
            ->method('write')
            ->withConsecutive(
                array("0032want 436e298f70ec95470282ef104738edd503bfb65a\n"),
                array('0000'),
                array("0009done\n"),
                array("0032want xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\n"),
                array('0000'),
                array("0032have yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy\n"),
                array("0009done\n"));
        
        $repo = new RemoteRepository($connector);
        $filename = $repo->fetch();
        unlink($filename);

        $want = array('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $have = array('yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy');
        $filename = $repo->fetch($want, $have);
        unlink($filename);
    }

    /**
     * @requires OS Linux
     */
    function testConnectToLocalGit()
    {
        $connector = new LocalConnector('file:'.__DIR__.'/sample.git');
        $repo = new RemoteRepository($connector);
        
        $commit = $repo->head();
        $this->assertInstanceOf('\GitQuery\Commit', $commit);
        $this->assertEquals('436e298f70ec95470282ef104738edd503bfb65a', $commit->sha1);

        $filename = $repo->fetch();
        $this->assertNotNull($filename);
        unlink($filename);
    }
}
