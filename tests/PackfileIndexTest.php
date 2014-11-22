<?php
namespace GitQuery;

class PackfileIndexTest extends \PHPUnit_Framework_TestCase
{

    static $filename;

    static function setUpBeforeClass()
    {
        self::$filename = __DIR__ . '/sample.git/objects/pack/pack-c5968142451b92172aa57b185874143d125fbdee.idx';
    }

    function testRead()
    {
        $index = new PackfileIndex(self::$filename);
        
        $this->assertCount(1, $index);
        $this->assertEquals(12, $index['5e4999f3bfe35be914c4bba7b0a362112cd4474c']);
    }

    function testWrite()
    {
        $index = new PackfileIndex(self::$filename);
        $index->packfileSha1 = 'c5968142451b92172aa57b185874143d125fbdee';
        $stream = fopen('php://temp', 'w+');
        $index->write($stream);
        rewind($stream);
        $this->assertEquals(file_get_contents(self::$filename), stream_get_contents($stream));
        fclose($stream);
    }
}
