<?php
namespace GitQuery;

/**
 * Pipes input and output to a local "git" process for testing.
 * 
 * Do not use for production.  If you have access to git locally then use
 * that instead of this library.  If you want to legitimately access
 * local files without the git executable then use \GitQuery\LocalRepository.
 */
class LocalConnector extends Connector
{

    /**
     * Path to local repository directory
     * 
     * @var string
     */
    private $path;

    private $resource;

    private $pipes = array();

    /**
     * URL should be formed like these:
     * 
     * file:relative/path/to/.git/
     * file:/absolute/path/to/.git/
     * 
     * @param string $url
     */
    public function __construct($url)
    {
        $parts = parse_url($url);
        if (($parts['scheme'] !== 'file') || isset($parts['host'])) {
            throw new \InvalidArgumentException($url.' cannot be understood as file: protocol');
        }
        $this->path = $parts['path'];
    }

    private function open()
    {
        if (is_null($this->process)) {
            throw new \RuntimeException('Process name has not been set, must be one of "git-upload-pack" or "git-receive-pack"');
        }
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("file", "php://stderr", "a")
        );
        $this->resource = proc_open($this->process.' '.$this->path, $descriptorspec, $this->pipes);
        if (! is_resource($this->resource)) {
            throw new \RuntimeException('Unable to execute process, check STDERR');
        }
    }

    public function __destruct()
    {
        foreach ($this->pipes as $pipe) {
            fclose($pipe);
        }
        if (isset($this->resource)) {
            proc_close($this->resource);
        }
    }

    public function read($length)
    {
        if (! $this->pipes) {
            $this->open();
        }
        return fread($this->pipes[1], $length);
    }

    public function write($data)
    {
        if (! $this->pipes) {
            $this->open();
        }
        return fwrite($this->pipes[0], $data);
    }

    public function flush()
    {
        if (! $this->pipes) {
            $this->open();
        }
        fflush($this->pipes[0]);
    }
}
