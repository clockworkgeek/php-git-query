<?php
namespace GitQuery;

/**
 * @property string $content
 */
class Blob extends Object
{

    const TYPE = 'blob';

    public function parse($verb, $content)
    {
        if ($verb !== self::TYPE) {
            throw new \RuntimeException($verb . ' is not ' . self::TYPE);
        }

        // nothing to check, it's just binary data
        $this->content = $content;
    }
}
