<?php
namespace GitQuery;

/**
 * Extract objects from a single stream.
 *
 * When using an index file it is necessary this stream is seekable.
 * Stream remains open indefinitely, to read it all and close would use
 * too much memory.
 * 
 * @see https://www.kernel.org/pub/software/scm/git/docs/technical/pack-format.txt
 */
class Packfile
{

    /**
     * @var PackfileIndex
     */
    private $index;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var resource
     */
    private $stream;

    public function __construct(Repository $repository, $stream)
    {
        $this->repository = $repository;
        $this->stream = $stream;
        $this->confirmHeader();
    }

    /**
     * This stream will be read then closed
     * 
     * @param resource $stream
     */
    public function readIndex($stream)
    {
        $this->index = new PackfileIndex($stream);
    }

    private function confirmHeader()
    {
        $header = fread($this->stream, 8);
        if ("PACK\x00\x00\x00\x02" !== $header) {
            throw new \RuntimeException('Packfile header not recognised');
        }
    }

    public function getObject($sha1)
    {
        $stream = $this->stream;
        if (is_null($this->index)) {
            $this->index = new PackfileIndex();
            return $this->buildIndex($stream, $sha1);
        }

        $offset = $this->index[$sha1];
        if (! is_int($offset)) {
            return null;
        }
        fseek($stream, $offset);

        return $this->readObject($stream);
    }

    private function readObject($stream)
    {
        list($type, $size) = self::readSize($stream);

        // clear file read buffer by seeking to a known position
        fseek($stream, ftell($stream));

        // decompress
        $params = array('window' => 15); // necessary for inflate to match deflate
        $inflate = stream_filter_append($stream, 'zlib.inflate', null, $params);
        $content = fread($stream, $size);
        stream_filter_remove($inflate);

        $repository = $this->repository;
        /* @var $object Object */
        switch ($type) {
            case 1: // commit
                $verb = 'commit';
                $object = new Commit($repository, sha1("$verb $size\0".$content));
                break;
            case 2: // tree
                $verb = 'tree';
                $object = new Tree($repository, sha1("$verb $size\0".$content));
                break;
            case 3: // blob
                $verb = 'blob';
                $object = new Blob($repository, sha1("$verb $size\0".$content));
                break;
            case 4: // tag
            case 6: // ofs-delta
            case 7: // ref-delta
            default: // undefined
                throw new \RuntimeException('Packed object is unknown type '.dechex($type));
        }

        $object->parse($verb, $content);
        return $object;
    }

    public static function readSize($stream)
    {
        $char = fgetc($stream);
        if ($char === false) {
            return array(0, 0);
        }

        $byte = ord($char);
        $type = ($byte >> 4) & 0x7;

        $size = $byte & 0xf;
        $shift = 4;

        while ($byte & 0x80) {
            if ($size > (PHP_INT_MAX >> 7)) {
                // if $size is shifted any more it will overflow
                throw new \RuntimeException('Unpacked size is too large');
            }

            $char = fgetc($stream);
            $byte = ord($char);
            $size |= ($byte & 0x7f) << $shift;
            $shift += 7;
        }

        return array($type, $size);
    }

    /**
     * Scan entire file and hash each block and note it's offset

     * @param resource $stream
     * @param string $sha1 Optional object to return if encountered
     * @return Object
     * @throws \RuntimeException
     */
    private function buildIndex($stream, $sha1 = null)
    {
        $index = $this->index;
        $object = null;

        // ensure position is correct
        if ((ftell($stream) !== 8) && (fseek($stream, 8) === -1)) {
            // absolute seeking is sometimes possible, sometimes it is reverse/negative instead
            throw new \RuntimeException('Packfile is not seekable for indexing');
        }

        // long number of objects
        list(, $count) = unpack('N', fread($stream, 4));

        while (($count--) && (! feof($stream))) {
            $offset = ftell($stream);
            $object = $this->readObject($stream);

            // update index object
            $index[$object->sha1] = $offset;
        }

        return $object;
    }
}
