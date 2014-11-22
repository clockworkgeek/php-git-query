<?php
namespace GitQuery;

/**
 * Index files provide a fast way to find compressed data in packfiles.
 * 
 * They are encoded as:
 * <ul>
 *   <li>8 byte header with version
 *   <li>1KB fanout table
 *     <p>Fanout has 256 entries such that $fanout[$i] = number of SHA1
 *     hashes with first byte less than or equal to $i</p>
 *     <p>Therefore $fanout[255] = total count of hashes</p>
 *     <p>If $fanout[-1] existed it would always be 0 because the first hash must have the index 0</p>
 *   <li>$count SHA1 hashes in raw binary
 *   <li>$count CRC32 hashes of compressed packfile data including object header
 *   <li>$count long offsets of compressed data in packfile
 *   <li>Optional longlong offsets for packfiles bigger than 2GB
 *   <li>SHA1 hash of packfile without it's tail
 *   <li>SHA1 hash of index file up to and including previous hash
 * </ul>
 *
 * @see https://www.kernel.org/pub/software/scm/git/docs/technical/pack-format.html
 */
class PackfileIndex extends \ArrayObject
{

    const HEADER = "\xFFtOc\x00\x00\x00\x02";

    public $crc = array();

    /**
     * 20-byte binary hash
     * 
     * @var string
     */
    public $packfileSha1;

    public function __construct($url = null)
    {
        if ($url && ($stream = fopen($url, 'rb'))) {
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
        if (self::HEADER !== $header) {
            throw new \UnexpectedValueException('Packfile index header was not recognised');
        }
        
        // N = 4 bytes
        $fanout = unpack('N256', fread($stream, 256 * 4));
        $count = $fanout[255];
        
        // read SHA1s in binary
        $hashes = str_split(fread($stream, $count * 20), 20);
        
        // skip CRC
        $this->crc = unpack('N*', fread($stream, $count * 4));
        
        // read short offsets, might be long ones later
        $offsets = unpack('N*', fread($stream, $count * 4));
        
        foreach ($hashes as $hash) {
            $hash = bin2hex($hash);
            $offset = array_shift($offsets);
            
            // long format
            if (0x80000000 & $offset) {
                list(, $offset) = unpack('J', fread($stream, 8));
            }
            
            $this[$hash] = $offset;
        }
        
        $this->sha1Packfile = fread($stream, 20);
        $this->sha1Index = fread($stream, 20);
    }

    /**
     * Recreate index file from scratch
     * 
     * If a single entry has changed then it is likely the whole file will too
     * 
     * @param resource $stream
     * @param string $packfileSha1 Hash of packfile being indexed
     */
    public function write($stream)
    {
        $hash = hash_init('sha1');
        fwrite($stream, self::HEADER);
        hash_update($hash, self::HEADER);
        $this->ksort();

        $i = $count = 0;
        foreach ($this as $sha1 => $offset) {
            $byte0 = hexdec($sha1[0] . $sha1[1]);
            while ($i++ < $byte0) {
                $raw = pack('N', $count);
                fwrite($stream, $raw);
                hash_update($hash, $raw);
            }
            $count++;
        }
        while ($i++ <= 256) {
            $raw = pack('N', $count);
            fwrite($stream, $raw);
            hash_update($hash, $raw);
        }

        foreach ($this as $sha1 => $offset) {
            $raw = hex2bin($sha1);
            fwrite($stream, $raw);
            hash_update($hash, $raw);
        }

        ksort($this->crc);
        foreach ($this->crc as $crc) {
            $raw = pack('N', $crc);
            fwrite($stream, $raw);
            hash_update($hash, $raw);
        }

        foreach ($this as $sha1 => $offset) {
            $raw = $offset < 0x80000000 ? pack('N', $offset) : pack('J', 0x80000000);
            fwrite($stream, $raw);
            hash_update($hash, $raw);
        }

        $raw = hex2bin($this->packfileSha1);
        fwrite($stream, $raw);
        hash_update($hash, $raw);
        fwrite($stream, hash_final($hash, true));
    }
}
