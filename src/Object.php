<?php
namespace GitQuery;

/**
 * Read Git objects from a stream
 *
 * @see http://git-scm.com/book/en/v2/Git-Internals-Git-Objects#Object-Storage
 * @property string sha1 Read-only hash
 * @property Repository repository
 */
abstract class Object
{

    /**
     * Check $verb is valid and parse $content appropriately
     * 
     * Implementers are expected to store whatever data they find.
     * 
     * @param string $verb Should be a single word like "blob", "commit" or "tree"
     * @param string $content Caution, may contain binary data
     */
    public abstract function parse($verb, $content);

    /**
     * @var Repository
     */
    protected $repository;
    protected $sha1;

    private $hasBeenRead = false;

    public function __construct(Repository $repository, $sha1, $stream = null)
    {
        $this->repository = $repository;
        $this->sha1 = $sha1;
        if (is_stream($stream)) {
            $this->readContent($content);
        }
    }

    public function __get($name)
    {
        // read-only properties
        if (('sha1' === $name) || ('repository' === $name)) {
            return $this->$name;
        }

        if ($this->read()) {
            // property might exist now
            return @$this->$name;
        }

        // if has been read then no more properties to find
        return null;
    }

    /**
     * Reads $stream once.
     * 
     * If $stream is null then contact repository to find a suitable source.
     *
     * @param resource $stream
     * @return boolean TRUE if a read occurred, otherwise FALSE
     */
    public function read($stream = null)
    {
        if ($this->hasBeenRead) {
            return false;
        }

        if (is_stream($stream)) {
            $this->readContent($stream);
        }
        else {
            // FIXME could cause an infinite loop if repository passes a bad stream
            $this->repository->streamInto($this->sha1, array($this, 'read'));
        }
        return true;
    }

    protected function readContent($stream)
    {
        $params = array('window' => 15);
        $inflate = stream_filter_append($stream, 'zlib.inflate', null, $params);

        // read header 1 byte at a time
        // cannot fseek() with a deflated stream so be careful not to overshoot content
        // it would be nice to use fscanf here but in PHP it always reads to the next
        //  newline which could be past the end of this object
        $header = '';
        do {
            $byte = fgetc($stream);
            if ($byte === false) {
                // EOF
                break;
            }
            $header .= $byte;
            // break on null char or sanity check header length
        } while ((ord($byte) !== 0) && (strlen($header) < 20));

        if (!preg_match('/^(\w+) (\d+)\\0/', $header, $fields)) {
            // clean up stream which might be shared
            stream_filter_remove($inflate);
            throw new \RuntimeException("Compressed object {$this->sha1} was encoded badly");
        }
        // first match from PCRE is a copy of $header, ignore it
        list(, $verb, $length) = $fields;

        $content = fread($stream, $length);
        stream_filter_remove($inflate);

        $this->parse($verb, $content);
        $this->hasBeenRead = true;
    }
}
