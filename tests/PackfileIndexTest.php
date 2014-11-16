<?php
namespace GitQuery;

class PackfileIndexTest extends \PHPUnit_Framework_TestCase
{

    function testRead()
    {
        $stream = fopen(__DIR__.'/sample.git/objects/pack/pack-c5968142451b92172aa57b185874143d125fbdee.idx', 'rb');
        $index = new PackfileIndex($stream);
        fclose($stream);

        $this->assertCount(1, $index);
        $this->assertEquals(12, $index['5e4999f3bfe35be914c4bba7b0a362112cd4474c']);
    }
}
