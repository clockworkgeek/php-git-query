<?php
namespace GitQuery;

interface Repository
{

    /**
     * If a stream can be found for $sha1 pass it to $callback
     *
     * @param string $sha1
     * @param Object $object
     */
    public function streamInto($sha1, Object $object);

    /**
     * Dereference HEAD to find a Commit instance
     * 
     * Commit file need not exist
     *
     * @return Commit
     */
    public function head();
}
