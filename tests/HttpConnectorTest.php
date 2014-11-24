<?php
namespace GitQuery;

/**
 * @requires OS Linux
 */
class HttpConnectorTest extends \PHPUnit_Framework_TestCase
{

    static function setUpBeforeClass()
    {
        // runs git http-backend as CGI
        exec('timeout 3 '.__DIR__.'/../vendor/bin/rackem '.__DIR__.'/rackem.config.php >/dev/null 2>&1 &');
        // give rackem time to start up
        sleep(1);
    }

    function testReadSampleReferences()
    {
        // Rackem listens on port 9393
        $connector = new HttpConnector('http://localhost:9393/sample.git');
        $connector->process = 'git-upload-pack';
        $this->assertEquals("# service=git-upload-pack\n", $connector->readLine());
        $this->assertEquals('', $connector->readLine());
        $this->assertTrue((bool) preg_match('/^[0-9a-f]{40} HEAD\\0/', $connector->readLine()));
        $this->assertEquals("436e298f70ec95470282ef104738edd503bfb65a refs/heads/master\n", $connector->readLine());
        $this->assertEquals('', $connector->readLine());
        // close gracefully
        $connector->writeLine();
    }

    function testRepositoryFetchRequiresUploading()
    {
        $connector = new HttpConnector('http://localhost:9393/sample.git');
        $repo = new RemoteRepository($connector);
        $filename = $repo->fetch();
        $this->assertNotEmpty($filename);
        unlink($filename);
    }
}
