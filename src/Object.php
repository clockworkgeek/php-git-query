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
     * @param string $content
     *            Caution, may contain binary data
     */
    public abstract function parse($content);

    /**
     *
     * @var string
     */
    protected $sha1;

    private $hasBeenRead = false;

    public function __construct($sha1)
    {
        $this->sha1 = $sha1;
    }

    public function __get($name)
    {
        // read-only properties
        if (('sha1' === $name)) {
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
     * Interrogate repository objects about an URL for SHA1
     *
     * @return boolean TRUE if a read occurred, otherwise FALSE
     */
    public function read()
    {
        if ($this->hasBeenRead) {
            return false;
        }
        
        $url = Repository::walk('getContentURL', array(
            $this->sha1
        ));
        if ($url) {
            $content = file_get_contents($url);
            if ($content !== false) {
                $this->parse($content);
                return $this->hasBeenRead = true;
            }
        }
        
        return false;
    }
}
