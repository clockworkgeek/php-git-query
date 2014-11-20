<?php
namespace GitQuery;

/**
 * @requires OS Linux
 */
class LocalConnectorTest extends \PHPUnit_Framework_TestCase
{

    function testReadCatProcess()
    {
        $connector = new LocalConnector('file:'.__DIR__.'/sample.git/HEAD');
        $connector->process = 'cat';
        $this->assertEquals("ref: refs/heads/master\n", $connector->read(23));
    }

    function testReadSampleReferences()
    {
        $connector = new LocalConnector('file:'.__DIR__.'/sample.git');
        $connector->process = 'git-upload-pack';
        $this->assertTrue((bool) preg_match('/^[0-9a-f]{40} HEAD\\0/', $connector->readLine()));
        $this->assertEquals("436e298f70ec95470282ef104738edd503bfb65a refs/heads/master\n", $connector->readLine());
        $this->assertEquals('', $connector->readLine());
        // close gracefully
        $connector->writeLine();
    }
}
