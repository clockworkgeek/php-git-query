<?php
namespace GitQuery;

class Tree extends Object implements \ArrayAccess, \Countable, \IteratorAggregate
{

    private $items = array();

    public function parse($content)
    {
        preg_match_all('/\G(?<mode>\d{6}) (?<name>[^\0]+)\0(?<sha1>.{20})/s', $content, $parsed, PREG_SET_ORDER);
        foreach ($parsed as $item) {
            $sha1 = bin2hex($item['sha1']);
            switch ($item['mode']) {
                case '100644':
                case '100755':
                    $object = new Blob($this->repository, $sha1);
                    break;
                case '040000':
                    $object = new Tree($this->repository, $sha1);
                    break;
                default:
                    $object = new \stdClass();
            }
            $object->name = $item['name'];
            $object->mode = $item['mode'];
            $this->items[] = $object;
        }
    }

    /**
     * From interface Countable
     *
     * @return number
     */
    public function count()
    {
        $this->read();
        return count($this->items);
    }

    /**
     * From interface IteratorAggregate
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $this->read();
        return new \ArrayIterator($this->items);
    }

    /**
     * (non-PHPdoc)
     * 
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
        $this->read();
        return isset($this->items[$offset]);
    }

    /**
     * (non-PHPdoc)
     * 
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {
        $this->read();
        return @$this->items[$offset];
    }

    /**
     * (non-PHPdoc)
     * 
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
        $this->read();
        $this->items[$offset] = $value;
    }

    /**
     * (non-PHPdoc)
     * 
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
        $this->read();
        unset($this->items[$offset]);
    }
}
