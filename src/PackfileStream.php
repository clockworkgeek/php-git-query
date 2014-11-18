<?php
namespace GitQuery;

/**
 * Extract decompressed data from a packfile at a given offset
 * 
 * Read only.
 * Called for URLs like "git-packfile://{URL}#{OFFSET}".
 */
class PackfileStream
{

    const SCHEME = 'git-packfile';

    const PROTOCOL = 'git-packfile://';

    /**
     * The real stream in use
     *
     * @var resource
     */
    private $stream = false;

    /**
     * zlib.inflate filter
     *
     * @var resource
     */
    private $inflate;

    /**
     * @var int
     */
    private $bytesRemaining;
    
    /**
     * @var int
     */
    private $size;

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        if (is_file_mode_write($mode)) {
            return false;
        }
        $pattern = '~'.preg_quote(self::PROTOCOL).'(?<url>.*)#(?<offset>.*)~';
        if (! preg_match($pattern, $path, $parts)) {
            return false;
        }
        // create vars $offset and $url
        extract($parts, EXTR_SKIP);
        
        $this->stream = $stream = fopen($url, $mode, (bool) $options & STREAM_USE_PATH);
        if ($this->stream === false) {
            return false;
        }

        fseek($stream, $offset);
        list($type, $size) = Packfile::readSize($stream);
        $this->bytesRemaining = $this->size = $size;
        
        // clear file read buffer by seeking to a known position
        fseek($stream, ftell($stream));
        
        $params = array(
            'window' => 15
        );
        $this->inflate = stream_filter_append($stream, 'zlib.inflate', STREAM_FILTER_READ, $params);
        
        return true;
    }

    public function stream_close()
    {
        stream_filter_remove($this->inflate);
        return fclose($this->stream);
    }

    public function stream_read($count)
    {
        $count = min($count, $this->bytesRemaining);
        $data = $count ? fread($this->stream, $count) : '';
        $this->bytesRemaining -= strlen($data);
        return $data;
    }

    public function stream_stat()
    {
        $stat = fstat($this->stream);
        $stat['size'] = $this->size;
        return $stat;
    }

    public function stream_eof()
    {
        return ($this->bytesRemaining <= 0) && feof($this->stream);
    }
}
