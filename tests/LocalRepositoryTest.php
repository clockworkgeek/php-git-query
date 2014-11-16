<?php
namespace GitQuery;

use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;
use GitQuery\LocalRepository;
use GitQuery\LF;

class LocalRepositoryTest extends \PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        vfsStream::setup('localrepo');
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

    public function testStreamInto()
    {
        $copy = vfsStream::copyFromFileSystem(__DIR__.'/sample.git');
        $repo = new LocalRepository($copy->url());

        $commit = $repo->head();
        $this->assertInstanceOf('\GitQuery\Commit', $commit);
        $this->assertEquals("test commit\n", $commit->message);

        $tree = $commit->tree;
        $this->assertInstanceOf('\GitQuery\Tree', $tree);
        $this->assertCount(1, $tree);

        $blob = $tree[0];
        $this->assertInstanceOf('\GitQuery\Blob', $blob);
        $this->assertEquals("test content\n", $blob->content);
    }
}
