<?php
namespace GitQuery;

abstract class Repository
{

    /**
     * Dereference HEAD to find a Commit instance
     *
     * Commit file need not exist
     *
     * @return Commit
     */
    public abstract function head();

    /**
     * Search this repository for an object matching this hash
     *
     * If none is found return a null.
     *
     * @param string $verb
     * @param string $sha1
     */
    public abstract function getContentURL($verb, $sha1);

    private static $repositories = array();

    public static function walk($method, $params = array())
    {
        foreach (self::$repositories as $repository) {
            $result = call_user_func_array(array(
                $repository,
                $method
            ), $params);
            if ($result) {
                return $result;
            }
        }
        return null;
    }

    public function __construct()
    {
        self::$repositories[spl_object_hash($this)] = $this;
    }

    public function __destruct()
    {
        unset(self::$repositories[spl_object_hash($this)]);
    }
}
