<?php
namespace GitQuery;

class ConnectorTest extends \PHPUnit_Framework_TestCase
{

    function testRead()
    {
        $connector = $this->getMockForAbstractClass('\GitQuery\Connector');
        $connector->expects($this->atLeastOnce())
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                '0009',
                "test\n");
        $this->assertEquals("test\n", $connector->readLine());
    }

    function testWrite()
    {
        $connector = $this->getMockForAbstractClass('\GitQuery\Connector');
        $connector->expects($this->once())
            ->method('write')
            ->with("0009test\n");
        $connector->writeLine("test\n");
    }
}
