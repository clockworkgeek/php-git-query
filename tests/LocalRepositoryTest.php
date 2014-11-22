<?php
namespace GitQuery;

use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;

class LocalRepositoryTest extends \PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        vfsStream::setup('localrepo');
    }

    public function testEmptyDirectory()
    {
        $repo = new LocalRepository(vfsStream::url('localrepo'));
        $this->assertEmpty($repo->head());
    }

    public function testInit()
    {
        $repo = new LocalRepository(vfsStream::url('localrepo/.git'));
        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('.git'));
        $repo->init();
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('.git'));
        $this->assertStringMatchesFormat('ref: refs/heads/%s', file_get_contents(vfsStream::url('localrepo/.git/HEAD')));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('.git/objects'));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('.git/refs/heads'));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('.git/refs/tags'));
    }

    /**
     * Fake a read only dir and fail at writing to it
     *
     * @expectedException RuntimeException
     */
    public function testNoWriteInit()
    {
        // new directory has no write permission
        $dir = vfsStream::newDirectory('readonly', 0500);
        $this->assertFalse($dir->isWritable($dir->getUser(), $dir->getGroup()));
        
        $repo = new LocalRepository($dir->url());
        $repo->init();
    }

    public function testGetContentUrls()
    {
        $repo = new LocalRepository(__DIR__ . '/sample.git');
        
        // individual file
        $this->assertEquals('git-object://commit/' . __DIR__ . '/sample.git/objects/43/6e298f70ec95470282ef104738edd503bfb65a', $repo->getContentURL('commit', '436e298f70ec95470282ef104738edd503bfb65a'));
        // stored in packfile
        $this->assertEquals('git-packfile://' . __DIR__ . '/sample.git/objects/pack/pack-c5968142451b92172aa57b185874143d125fbdee.pack#12', $repo->getContentURL('blob', '5e4999f3bfe35be914c4bba7b0a362112cd4474c'));
    }

    public function testObjectsIntegration()
    {
        $repo = new LocalRepository(__DIR__ . '/sample.git');
        
        $commit = $repo->head();
        $this->assertInstanceOf('\GitQuery\Commit', $commit);
        $this->assertEquals('436e298f70ec95470282ef104738edd503bfb65a', $commit->sha1);
        
        $tree = $commit->tree;
        $this->assertInstanceOf('\GitQuery\Tree', $tree);
        $this->assertEquals('80865964295ae2f11d27383e5f9c0b58a8ef21da', $tree->sha1);
        
        $blob = $tree[0];
        $this->assertInstanceOf('\GitQuery\Blob', $blob);
        $this->assertEquals('d670460b4b4aece5915caf5c68d12f560a9fe3e4', $blob->sha1);

        $blob = new Blob('5e4999f3bfe35be914c4bba7b0a362112cd4474c');
        $this->assertEquals("packed content\n", $blob->content);
    }

    public function testFetchFromRemoteRepository()
    {
        $local = new LocalRepository(vfsStream::url('localrepo/fetch.git'));
        $local->init();
        $remote = new RemoteRepository(new LocalConnector('file:'.__DIR__.'/sample.git'));
        $local->fetch($remote);
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('fetch.git/objects/pack/pack-533cb428d39e698641ec41438f683ce279a45812.pack'));
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('fetch.git/objects/pack/pack-533cb428d39e698641ec41438f683ce279a45812.idx'));
        
        $this->assertEquals('git-packfile://'.vfsStream::url('localrepo/fetch.git/objects/pack/pack-533cb428d39e698641ec41438f683ce279a45812.pack#12'), $local->getContentURL('commit', '436e298f70ec95470282ef104738edd503bfb65a'));
    }
}
