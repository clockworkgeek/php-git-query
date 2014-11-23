<?php
namespace Rackem;

require_once __DIR__.'/../vendor/autoload.php';

class Git extends Cgi
{

    const EXECUTABLE = 'git http-backend';

    public function is_valid($path)
    {
        return true;
    }

    protected function run($env, $path)
    {
        $env['GIT_HTTP_EXPORT_ALL'] = '';
        $env['GIT_PROJECT_ROOT'] = $this->public_folder;
        return parent::run($env, self::EXECUTABLE);
    }
}

return \Rackem::run(new Git(null, __DIR__));
