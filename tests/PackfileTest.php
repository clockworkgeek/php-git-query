<?php
namespace GitQuery;

class PackfileTest extends \PHPUnit_Framework_TestCase
{

    static $filename;

    static function setUpBeforeClass()
    {
        self::$filename = __DIR__ . '/sample.git/objects/pack/pack-c5968142451b92172aa57b185874143d125fbdee.pack';
    }

    /**
     * @dataProvider readSizeProvider
     */
    function testReadSize($string, $type, $size)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $string);
        rewind($stream);
        $this->assertEquals(array(
            $type,
            $size
        ), array_slice(Packfile::readSize($stream), 0, 2));
        fclose($stream);
    }

    function readSizeProvider()
    {
        return array(
            array(
                "\x3f",
                3,
                15
            ),
            array(
                "\x90\x09",
                1,
                144
            ),
            array(
                "\x00",
                0,
                0
            ),
            array(
                "\xff\xff\x7f",
                7,
                0x3ffff
            ),
            array(
                "\xff\xff\xff\x7f",
                7,
                0x1ffffff
            )
        );
    }

    /**
     * @expectedException OverflowException
     */
    function testOversizedObject()
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "\xff\xff\xff\xff\xff\xff\xff\xff\xff");
        rewind($stream);
        Packfile::readSize($stream);
    }

    function testBuildIndex()
    {
        $packfile = new Packfile(self::$filename);
        $index = $packfile->buildIndex();
        $this->assertCount(1, $index);
        $this->assertCount(1, $index->crc);
        $this->assertEquals(12, $index['5e4999f3bfe35be914c4bba7b0a362112cd4474c']);
        $this->assertEquals('c5968142451b92172aa57b185874143d125fbdee', $index->packfileSha1);
    }

    function testWriteIndex()
    {
        $packfile = new Packfile(self::$filename);

        $stream = fopen('php://temp', 'w+');
        $packfile->writeIndex($stream);
        rewind($stream);
        $indexFilename = __DIR__ . '/sample.git/objects/pack/pack-c5968142451b92172aa57b185874143d125fbdee.idx';
        $this->assertEquals(file_get_contents($indexFilename), stream_get_contents($stream));
        fclose($stream);
    }
}
