<?php

namespace Symfony\Bundle\MakerBundle\Tests\Docker;

use Symfony\Bundle\MakerBundle\Docker\ComposeFileManipulator;
use PHPUnit\Framework\TestCase;

class ComposeFileManipulatorTest extends TestCase
{
    public function testCurrentVersion(): void
    {
        self::assertSame('3.7', ComposeFileManipulator::COMPOSE_FILE_VERSION);
    }

    public function testBasicComposeFileReturnedWhenContentsIsEmpty(): void
    {
        $expected = [
            'version' => '3.7',
            'services' => []
        ];

        self::assertSame($expected, (new ComposeFileManipulator())->getData());
    }

    public function testContentsParsedWhenContentStringNotEmpty(): void
    {
        $contentString = "version: '3.7'\nservices: {}\nnetworks:\n App: Bridge\n";

        $expected = [
            'version' => '3.7',
            'services' => [],
            'networks' => [
                'App' => 'Bridge'
            ]
        ];

        self::assertSame($expected, (new ComposeFileManipulator($contentString))->getData());
    }

    public function testAddDockerService(): void
    {
        $expectedServiceProperties = [
            'image' => 'mariadb:latest',
            'port' => 1234,
            'networks' => ['front', 'back']
        ];

        $manipulator = new ComposeFileManipulator();
        $manipulator->addDockerService('database', $expectedServiceProperties);

        $results = $manipulator->getData();

        self::assertArrayHasKey('database', $results['services']);
        self::assertSame($results['services']['database'], $expectedServiceProperties);
    }

    public function testRemoveDockerService(): void
    {
        $manipulator = new ComposeFileManipulator(
            "version: '3.7'\nservices: { database: }\n"
        );

        $manipulator->removeDockerService('database');
        $result = $manipulator->getData();

        self::assertArrayNotHasKey('database', $result['services']);
    }

    public function testServiceExists(): void
    {
        $manipulator = new ComposeFileManipulator("version: '3.7'\nservices: { database: }\n");

        self::assertFalse($manipulator->serviceExists('mariadb'));
        self::assertTrue($manipulator->serviceExists('database'));
    }

    public function testAddVolume(): void
    {
        $manipulator = new ComposeFileManipulator("version: '3.7'\nservices: { database: }\n");
        $manipulator->addVolume('database', '/host-path', '/container/path');

        $result = $manipulator->getData();

        self::assertSame(['/host-path:/container/path'], $result['services']['database']['volumes']);
    }
}
