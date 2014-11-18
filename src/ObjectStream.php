<?php
namespace GitQuery;

/**
 * Unwrap a git 'object' file and stream the remaining content.
 */
class ObjectStream
{

    const SCHEME = 'git-object';

    const PROTOCOL = 'git-object://';

    /**
     * The real stream in use
     *
     * @var resource
     */
    private $stream = false;

    /**
     * zlib.deflate filter
     *
     * @var resource
     */
    private $deflate;

    /**
     * zlib.inflate filter
     *
     * @var resource
     */
    private $inflate;

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $pattern = sprintf('#%s(?<verb>\w+)/(?<url>.*)#', preg_quote(self::PROTOCOL));
        if (! preg_match($pattern, $path, $parts)) {
            return false;
        }
        // create vars $verb and $url
        extract($parts, EXTR_SKIP);
        
        $this->stream = fopen($url, $mode, (bool) $options & STREAM_USE_PATH);
        if ($this->stream === false) {
            return false;
        }
        
        $params = array(
            'window' => 15
        );
        $this->inflate = stream_filter_append($this->stream, 'zlib.inflate', STREAM_FILTER_READ, $params);
        $this->deflate = stream_filter_append($this->stream, 'zlib.deflate', STREAM_FILTER_WRITE, $params);
        
        if (is_file_mode_read($mode)) {
            $verb .= ' ';
            if (fread($this->stream, strlen($verb)) !== $verb) {
                return false;
            }
            while (($char = fgetc($this->stream)) !== false) {
                if ($char === "\0")
                    break;
                if (strpbrk($char, '0123456789') === false) {
                    return false;
                }
                // TODO record length and limit bytes read, currently length limited by EOF
            }
        }
        // TODO write object verb if in write mode
        
        return true;
    }

    public function stream_close()
    {
        stream_filter_remove($this->deflate);
        stream_filter_remove($this->inflate);
        return fclose($this->stream);
    }

    public function stream_read($count)
    {
        return fread($this->stream, $count);
    }

    public function stream_stat()
    {
        return fstat($this->stream);
    }

    public function stream_eof()
    {
        return feof($this->stream);
    }
}
