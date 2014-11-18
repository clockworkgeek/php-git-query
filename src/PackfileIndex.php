<?php
namespace GitQuery;

class PackfileIndex extends \ArrayObject
{

    public function __construct($stream = null)
    {
        if (is_stream($stream)) {
            $this->read($stream);
        }
    }

    /**
     * Read all offsets to memory and discard the rest
     *
     * @param resource $stream            
     */
    public function read($stream)
    {
        $header = fread($stream, 8);
        if ("\xFFtOc\x00\x00\x00\x02" !== $header) {
            throw new \RuntimeException('Packfile index header was not recognised');
        }
        
        // N = 4 bytes
        $fanout = unpack('N256', fread($stream, 256 * 4));
        $count = $fanout[255];
        
        // read SHA1s in binary
        $hashes = str_split(fread($stream, $count * 20), 20);
        
        // skip CRC
        fseek($stream, $count * 4, SEEK_CUR);
        
        // read short offsets, might be long ones later
        $offsets = unpack("N$count", fread($stream, $count * 4));
        
        foreach ($hashes as $hash) {
            $hash = bin2hex($hash);
            $offset = array_shift($offsets);
            
            // long format
            if (0x8000 & $offset) {
                $offset = bindec(fread($stream, 8));
            }
            
            $this[$hash] = $offset;
        }
        
        // TODO read packfile & index SHA1 checksums from tail
    }
}
