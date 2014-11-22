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
     * Cache of PackfileIndex objects, keyed by path
     * 
     * @var array
     */
    private $indexes = array();

    /**
     * The directory expected to contain a HEAD and refs
     *
     * @param string $path
     *            Absolute location
     */
    public function __construct($path)
    {
        parent::__construct();
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
        $content = @file_get_contents($this->getPath() . DS . $filename);
        
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

    protected function getReferences()
    {
        $refs = array();

        // look for index first
        $refsFilename = $this->getPath() . '/info/refs';
        if (file_exists($refsFilename)) {
            $file = fopen($refsFilename, 'r');
            while (fscanf($file, '%s %s', $sha1, $ref)) {
                $refs[$ref] = $sha1;
            }
            return $refs;
        }

        // fallback to scanning the slow way
        $refsDir = new \RecursiveDirectoryIterator($this->getPath().'/refs', \FilesystemIterator::SKIP_DOTS);
        foreach (new \RecursiveIteratorIterator($refsDir) as $ref => $file) {
            $refs[$ref] = file_get_contents($ref);
        }
        return $refs;
    }

    public function head()
    {
        $sha1 = $this->dereference(HEAD);
        return $sha1 ? new Commit($sha1) : null;
    }

    public function getContentURL($verb, $sha1)
    {
        // check individual file
        $path = $this->getPath() . '/objects/' . substr($sha1, 0, 2) . DS . substr($sha1, 2);
        if (is_file($path)) {
            return ObjectStream::PROTOCOL . $verb . '/' . $path;
        }

        // check recent packfile indexes
        foreach ($this->indexes as $path => $index) {
            $offset = @$index[$sha1];
            if ($offset && is_int($offset)) {
                $path = str_replace('.idx', '.pack', $path);
                return PackfileStream::PROTOCOL . $path . '#' . $index[$sha1];
            }
        }
        
        // check all packfile indexes
        foreach (glob($this->getPath() . '/objects/pack/*.idx') as $path) {
            $index = new PackfileIndex($path);
            $this->indexes[$path] = $index;
            $offset = @$index[$sha1];
            if ($offset && is_int($offset)) {
                $path = str_replace('.idx', '.pack', $path);
                return PackfileStream::PROTOCOL . $path . '#' . $index[$sha1];
            }
        }
        
        return null;
    }

    public function fetch($remote)
    {
        if (! $remote instanceof RemoteRepository) {
            throw new \InvalidArgumentException('Remote parameter is not a repository');
        }
        $packfilename = $remote->fetch(array(), $this->getReferences());
        mkdir($this->getPath().'/objects/pack', 0755, true);
        $packfile = new Packfile($packfilename);
        $index = $packfile->buildIndex();
        $packname = $this->getPath().'/objects/pack/pack-' . $index->packfileSha1;
        $destpackfilename = $packname . '.pack';
        @rename($packfilename, $destpackfilename) or copy($packfilename, $destpackfilename) and unlink($packfilename);
        $index->save($packname.'.idx');
        $this->indexes[$packname.'.idx'] = $index;
    }
}
