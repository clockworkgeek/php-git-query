<?php
namespace GitQuery;

/**
 *
 * @property Commit $parent
 * @property Commit[] $parents
 * @property string $tree
 * @property string $author
 * @property string $committer
 * @property string $message
 */
class Commit extends Object
{

    protected function getVerb()
    {
        return 'commit';
    }

    public function parse($content)
    {
        // reset properties
        $this->author = false;
        $this->committer = false;
        $this->message = false;
        $this->parent = false;
        $this->parents = array();
        $this->tree = false;
        
        list ($head, $this->message) = explode(LF . LF, $content, 2);
        
        preg_match_all('/^(\w+) (.*)$/m', $head, $lines, PREG_SET_ORDER);
        foreach ($lines as $line) {
            list (, $name, $value) = $line;
            switch ($name) {
                case 'parent':
                    $this->parents[] = new Commit($value);
                    break;
                case 'tree':
                    $this->tree = new Tree($value);
                    break;
                case 'author':
                case 'committer':
                    $this->$name = $value;
                    break;
            }
        }
        // if parents is empty parent will be false
        $this->parent = reset($this->parents);
    }
}
