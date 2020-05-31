<?php

namespace Symfony\Bundle\MakerBundle\Tests;

use Symfony\Bundle\MakerBundle\ArgumentCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\MakerArgument;

class ArgumentCollectionTest extends TestCase
{
    public function testCountable(): void
    {
        $arg1 = new MakerArgument('arg1');
        $arg2 = new MakerArgument('arg2');

        $collection = new ArgumentCollection();
        $collection->addArgument($arg1);
        $collection->addArgument($arg2);

        $this->assertCount(2, $collection);
    }

    public function testIteratorable(): void
    {
        $arg1 = new MakerArgument('arg1');
        $arg2 = new MakerArgument('arg2');

        $collection = new ArgumentCollection();
        $collection->addArgument($arg1);
        $collection->addArgument($arg2);

        foreach ($collection as $item) {
            self::assertInstanceOf(MakerArgument::class, $item);
        }
    }
}
