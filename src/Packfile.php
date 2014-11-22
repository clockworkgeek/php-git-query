<?php
namespace GitQuery;

/**
 * Extract objects from a single stream.
 *
 * When using an index file it is necessary this stream is seekable.
 * Stream remains open indefinitely, to read it all and close would use
 * too much memory.
 *
 * @see https://www.kernel.org/pub/software/scm/git/docs/technical/pack-format.html
 */
class Packfile
{

    /**
     *
     * @var PackfileIndex
     */
    private $index;

    /**
     * @var int
     */
    private $size;

    /**
     *
     * @var resource
     */
    private $stream;

    public function __construct($url, $indexUrl = null)
    {
        $this->size = filesize($url);
        $this->stream = fopen($url, 'rb');
        $this->confirmHeader();
        if (isset($indexUrl)) {
            $this->index = new PackfileIndex($indexUrl);
        }
    }

    public function __destruct()
    {
        fclose($this->stream);
    }

    private function confirmHeader()
    {
        $header = fread($this->stream, 8);
        if ("PACK\x00\x00\x00\x02" !== $header) {
            throw new \UnexpectedValueException('Packfile header not recognised');
        }
    }

    /**
     * Read an object header from packfile's curious format
     * 
     * Starting from the current stream position several bytes are read.
     * list($type, $size, $orig) = \GitQuery\Packfile::readSize($stream);
     * $type = 1, 2, 3, 4, 6 or 7
     * $size = number of bytes if the stream were uncompressed from this point on
     * $orig = raw data actually read
     * 
     * @param resource $stream
     * @throws \OverflowException
     * @return array
     */
    public static function readSize($stream)
    {
        $orig = $char = fgetc($stream);
        if ($char === false) {
            return array(
                0,
                0
            );
        }
        
        $byte = ord($char);
        $type = ($byte >> 4) & 0x7;
        
        $size = $byte & 0xf;
        $shift = 4;
        
        while ($byte & 0x80) {
            if ($size > (PHP_INT_MAX >> 7)) {
                // if $size is shifted any more it will overflow
                throw new \OverflowException('Unpacked size is too large');
            }
            
            $char = fgetc($stream);
            $orig .= $char;
            $byte = ord($char);
            $size |= ($byte & 0x7f) << $shift;
            $shift += 7;
        }
        
        return array(
            $type,
            $size,
            $orig
        );
    }

    public static function getType($byte)
    {
        switch ($byte) {
            case 1:
                return 'commit';
            case 2:
                return 'tree';
            case 3:
                return 'blob';
            // 4 = tag
            // 6 = ofs-delta
            // 7 = ref-delta
        }
        return null;
    }

    /**
     * Read entire packfile, indexing object offsets and verifies total
     * 
     * @return \GitQuery\PackfileIndex
     */
    public function buildIndex()
    {
        $index = new PackfileIndex();
        $stream = $this->stream;
        rewind($stream);
        $this->confirmHeader(); // advance 8 bytes

        // unpack returns 1-based array?!
        list(, $count) = unpack('N', fread($stream, 4)); // advance 4 bytes
        // ftell() is wrong for compressed streams so track offset independently
        $offset = 12;
        // params equal to deflate settings
        $params = array('window' => 15);

        while ($count-- && ! feof($stream)) {
            // expect file position to be correct here
            list($type, $size, $orig) = self::readSize($stream);

            // zlib bug, must fseek to clear read buffer
            fseek($stream, $offset + strlen($orig));
            $inflate = stream_filter_append($stream, 'zlib.inflate', null, $params);
            // object data might be big, store in php://temp instead?
            $object = fread($stream, $size);
            stream_filter_remove($inflate);

            // potentially slow hashing and compressing
            $sha1 = sha1(sprintf("%s %d\0%s", self::getType($type), $size, $object));
            $compressed = gzcompress($object);
            $crc = crc32($orig . $compressed);
            $index[$sha1] = $offset;
            $index->crc[$sha1] = $crc;

            // seek to correct position now that inflate is removed
            $offset += strlen($orig) + strlen($compressed);
            fseek($stream, $offset);
        }

        // should be 20 bytes left for a final hash
        rewind($stream);
        $hash = hash_init('sha1');
        // $offset is pos after last object
        hash_update_stream($hash, $stream, $offset);
        // binary value instead of hexidecimal
        $sha1 = hash_final($hash, true);
        if ($sha1 !== fread($stream, 20)) {
            throw new \UnexpectedValueException('Packfile footer does not match rest of file, it might be corrupt');
        }

        return $index;
    }
}
