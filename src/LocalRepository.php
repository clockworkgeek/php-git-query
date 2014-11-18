<?php
namespace GitQuery;

use RuntimeException;

class LocalRepository extends Repository
{

    /**
     * Path of git dir without trailing slash
     *
     * @var string
     */
    private $path;

    /**
     * The directory expected to contain a HEAD and refs
     *
     * @param string $path
     *            Absolute location
     */
    public function __construct($path)
    {
        $this->path = rtrim($path, '/\\');
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * Create necessary dirs and files to represent a repository
     *
     * Roughly equivalent to "git init --bare [directory]".
     * Safe to call multiple times.
     * Remote repos have no init() because they are read-only.
     *
     * It is an exception if $path is unwritable, sometimes this happens
     * because a parent dir is unwritable.
     *
     * @throws RuntimeException
     */
    public function init()
    {
        $path = $this->getPath();
        
        // enforce a writable location
        if (! is_dir($path)) {
            if (! mkdir($path, 0755, true)) {
                throw new RuntimeException('Could not init repository location');
            }
        }
        $objects = $path . DS . 'objects';
        if (! is_dir($objects)) {
            if (! mkdir($objects, 0755, true)) {
                throw new RuntimeException('Could not init objects directory');
            }
        }
        $refsHeads = $path . DS . 'refs' . DS . 'heads';
        if (! is_dir($refsHeads)) {
            if (! mkdir($refsHeads, 0755, true)) {
                throw new RuntimeException('Could not init heads directory');
            }
        }
        $refsTags = $path . DS . 'refs' . DS . 'tags';
        if (! is_dir($refsTags)) {
            if (! mkdir($refsTags, 0755, true)) {
                throw new RuntimeException('Could not init tags directory');
            }
        }
        
        if (! is_file($path . DS . HEAD)) {
            if (! file_put_contents($path . DS . HEAD, 'ref: refs/heads/master' . LF)) {
                throw new RuntimeException('Could not init HEAD reference');
            }
        }
    }

    protected function dereference($filename)
    {
        $content = file_get_contents($this->getPath() . DS . $filename);
        
        // 20-byte hexidecimal hash
        if (preg_match('/^[0-9a-z]{40}$/i', $content)) {
            return $content;
        }
        
        // symref format
        if (preg_match('/^ref: (.*)\Z/', $content, $refname)) {
            return $this->dereference($refname[1]);
        }
        
        // dunno
        return null;
    }

    public function head()
    {
        $sha1 = $this->dereference(HEAD);
        return new Commit($sha1);
    }

    public function getContentURL($sha1)
    {
        $path = $this->getPath() . DS . 'objects' . DS . substr($sha1, 0, 2) . DS . substr($sha1, 2);
        if (is_file($path)) {
            return ObjectStream::PROTOCOL . 'commit/' . $path;
        }
        return null;
    }
}
