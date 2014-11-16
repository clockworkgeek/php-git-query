<?php
namespace GitQuery;

class PackfileTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider readSizeProvider
     */
    function testReadSize($string, $type, $size)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $string);
        rewind($stream);
        $this->assertEquals(array($type, $size), Packfile::readSize($stream));
        fclose($stream);
    }

    function readSizeProvider()
    {
        return array(
            array("\x3f", 3, 15),
            array("\x90\x09", 1, 144),
            array("\x00", 0, 0),
            array("\xff\xff\x7f", 7, 0x3ffff),
            array("\xff\xff\xff\x7f", 7, 0x1ffffff)
        );
    }

    /**
     * @expectedException RuntimeException
     */
    function testOversizedObject()
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "\xff\xff\xff\xff\xff\xff\xff\xff\xff");
        rewind($stream);
        Packfile::readSize($stream);
    }

    function testGetBlobWithoutIndex()
    {
        $repo = $this->getMock('\GitQuery\Repository');
        $stream = fopen(__DIR__.'/sample.git/objects/pack/pack-c5968142451b92172aa57b185874143d125fbdee.pack', 'rb');
        $packfile = new Packfile($repo, $stream);
        $blob = $packfile->getObject('5e4999f3bfe35be914c4bba7b0a362112cd4474c');
        fclose($stream);

        $this->assertInstanceOf('\GitQuery\Blob', $blob);
        $this->assertEquals("packed content\n", $blob->content);
    }

    function testGetBlobWithIndex()
    {
        $repo = $this->getMock('\GitQuery\Repository');
        $stream = fopen(__DIR__.'/sample.git/objects/pack/pack-c5968142451b92172aa57b185874143d125fbdee.pack', 'rb');
        $packfile = new Packfile($repo, $stream);

        $indexStream = fopen(__DIR__.'/sample.git/objects/pack/pack-c5968142451b92172aa57b185874143d125fbdee.idx', 'rb');
        $packfile->readIndex($indexStream);
        fclose($indexStream);

        $blob = $packfile->getObject('5e4999f3bfe35be914c4bba7b0a362112cd4474c');
        fclose($stream);

        $this->assertInstanceOf('\GitQuery\Blob', $blob);
        $this->assertEquals("packed content\n", $blob->content);
    }
}
