<?php

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class APrototypeMaker extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:prototype';
    }

    public function configureCommand(
        Command $command,
        InputConfiguration $inputConfig
    ) {
        $command
            ->setDescription('Prototype Maker using CodeGenerator.')
        ;
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $codeBlocks = new Generator\PrototypeCodeGenerator();

        $classNameDetails = $generator->createClassNameDetails(
            'PrototypeClass',
            'Prototype\\'
        );

        $generator->generateClass(
            $classNameDetails->getFullName(),
            'aPrototype/PrototypeClass.tpl.php',
            ['someMethod' => $codeBlocks->getCodeBlock()]
        );

        $generator->writeChanges();
    }
}
