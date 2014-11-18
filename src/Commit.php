<?php
namespace GitQuery;

/**
 *
 * @property Commit $parent
 * @property string $tree
 * @property string $author
 * @property string $committer
 * @property string $message
 */
class Commit extends Object
{

    public function parse($content)
    {
        // reset properties
        $this->author = false;
        $this->committer = false;
        $this->message = false;
        $this->parent = false;
        $this->tree = false;
        
        list ($head, $this->message) = explode(LF . LF, $content, 2);
        
        preg_match_all('/^(\w+) (.*)$/m', $head, $lines, PREG_SET_ORDER);
        foreach ($lines as $line) {
            list (, $name, $value) = $line;
            switch ($name) {
                case 'parent':
                    $this->parent = new Commit($this->repository, $value);
                    break;
                case 'tree':
                    $this->tree = new Tree($this->repository, $value);
                    break;
                case 'author':
                case 'committer':
                    $this->$name = $value;
                    break;
            }
        }
    }
}
