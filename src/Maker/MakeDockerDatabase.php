<?php

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Docker\DatabaseServices;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

class MakeDockerDatabase extends AbstractDockerMaker
{
    public static function getCommandName(): string
    {
        return 'make:docker:database';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Create a database Docker container.')
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        parent::interact($input, $io, $command);

        $command
            ->addArgument('service-name')
            ->addArgument('database')
            ->addArgument('version')
        ;

        $io->newLine();

        $databaseChoice = $io->choice(
            'Which database service will you be creating?',
            ['MySQL', 'MariaDB', 'Postgres']
        );

        $io->text([sprintf('For a list of supported versions, check out https://hub.docker.com/_/%s', strtolower($databaseChoice))]);

        $database = strtolower($databaseChoice);

        $input->setArgument('service-name', $database);
        $input->setArgument('version', $io->ask('What version would you like to use?', 'latest'));


        if ($this->composeFileManipulator->serviceExists($database)) {
            $this->serviceAlreadyDefinedQuestion($io, $databaseChoice);

            $input->setArgument('service-name', $io->ask(sprintf(
                'What name should we call the new %s service? e.g. %s',
                $databaseChoice,
                str_replace(' ', '-', Str::getRandomTerm())
            )));
        }

        $input->setArgument('database', $database);

        $io->section(sprintf('- %s -', $databaseChoice));

        $defaults = [
            sprintf('Port(s) %s are exposed to the host.', ...(DatabaseServices::getDefaultPorts($database))),
        ];

        $io->text($defaults);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        parent::generate($input, $io, $generator);

        $service = DatabaseServices::getDatabase($input->getArgument('database'), $input->getArgument('version'));

        $this->composeFileManipulator->addDockerService($input->getArgument('service-name'), $service);

        //@TODO dump and write could be abstracted
        $generator->dumpFile($this->dockerComposeFile, Yaml::dump($this->composeFileManipulator->getData(), 20));
        $generator->writeChanges();
    }
}
