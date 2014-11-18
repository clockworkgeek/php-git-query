<?php
namespace GitQuery;

/**
 *
 * @property string $content
 */
class Blob extends Object
{

    protected function getVerb()
    {
        return 'blob';
    }

    public function parse($content)
    {
        // nothing to check, it's just binary data
        $this->content = $content;
    }
}
