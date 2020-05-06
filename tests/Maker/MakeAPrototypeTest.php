<?php

declare(strict_types=1);

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\APrototypeMaker;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Component\Filesystem\Filesystem;

class MakeAPrototypeTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'prototype_test' => [MakerTestDetails::createTest(
            $this->getMakerInstance(APrototypeMaker::class),
            []
        )
        ->assert(
            function (string $output, string $directory) {
                $fs = new Filesystem();

                $generatedFile = 'src/Prototype/PrototypeClass.php';

                $this->assertTrue($fs->exists(sprintf('%s/%s', $directory, $generatedFile)));
            }
        )
        ];
    }
}
