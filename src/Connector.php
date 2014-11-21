<?php
namespace GitQuery;

/**
 * Base class for several connection types.
 * 
 * Descendents are responsible for opening a connection on read or flush,
 * for managing sockets/streams, for closing connection on destruction.
 */
abstract class Connector
{

    /**
     * Either "git-upload-pack" or "git-receive-pack".
     * 
     * Potentially also accepts "git-upload-archive".
     * 
     * @var string
     */
    public $process = null;

    /**
     * Read $length bytes from this connection
     * 
     * Equivalent to fread()
     * 
     * @param int $length
     * @return string
     */
    public abstract function read($length);

    /**
     * Write $data to this connection
     * 
     * Equivalent to fwrite()
     * 
     * @param int $data
     * @return int Number of bytes written or false
     */
    public abstract function write($data);

    /**
     * Finish sending current packet being written
     * 
     * Equivalent to fflush()
     */
    public abstract function flush();

    /**
     * Reads a whole line from this connection
     * 
     * Equivalent to fges()
     * 
     * @return string
     */
    public function readLine()
    {
        $length = hexdec($this->read(4));
        if (is_int($length) && $length) {
            return $this->read($length - 4);
        }
        if ($length === 0) {
            return ''; // end of packet not EOF
        }
        throw new \RuntimeException('Packet is malformed. Could not read length.');
    }

    /**
     * More than just fwrite() this func maintains packet formatting
     * 
     * Non-binary lines should be LF terminated, writeLine() does not append this
     * automatically in case $line is a binary string.
     * 
     * @param string $line If empty writes a "flush pkt-line" but does not flush yet
     */
    public function writeLine($line = '')
    {
        $length = strlen($line) + 4;
        if ($length > 65524) {
            throw new \InvalidArgumentException('Packet line is too long.');
        }
        if ($length === 4) {
            return $this->write('0000');
        }
        return $this->write(sprintf('%04x%s', $length, $line));
    }

    private $active = false;

    /**
     * Descendents should keep this active flag updated
     * 
     * @param boolean $active
     * @return boolean
     */
    public function isActive($active = null)
    {
        if (isset($active)) {
            $this->active = (bool) $active;
        }
        return $this->active;
    }
}
