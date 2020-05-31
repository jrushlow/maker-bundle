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

        $io->newLine();

        $this->arguments->createArgument(
            'database-choice',
            $io->choice(
            'Which database service will you be creating?',
            ['MySQL', 'MariaDB', 'Postgres']
        ));

        $this->arguments->createArgument('database', strtolower($this->getValue('database-choice')));

        $io->text([sprintf(
            'For a list of supported versions, check out https://hub.docker.com/_/%s',
            $this->getValue('database-choice')
        )]);

        $this->arguments->setArgumentValue('service-name', $this->getValue('database'));
        $this->arguments->setArgumentValue('version', $io->ask('What version would you like to use?', 'latest'));


        if ($this->composeFileManipulator->serviceExists($this->getValue('service-name'))) {
            $this->serviceAlreadyDefinedQuestion($io, $this->getValue('database-choice'));

            $this->arguments->setArgumentValue('service-name', $io->ask(sprintf(
                'What name should we call the new %s service? e.g. %s',
                $this->getValue('database-choice'),
                str_replace(' ', '-', Str::getRandomTerm())
            )));
        }

        $this->arguments->setArgumentValue('database', $this->getValue('database'));

        $io->section(sprintf('- %s -', $this->getValue('database-choice')));

        $defaults = [
            sprintf('Port(s) %s are exposed to the host.', ...(DatabaseServices::getDefaultPorts($this->getValue('database')))),
        ];

        $io->text($defaults);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        parent::generate($input, $io, $generator);

        $service = DatabaseServices::getDatabase($this->getValue('database'), $this->getValue('version'));

        $this->composeFileManipulator->addDockerService($this->getValue('service-name'), $service);

        //@TODO dump and write could be abstracted
        $generator->dumpFile($this->getValue('compose-file'), Yaml::dump($this->composeFileManipulator->getData(), 20));
        $generator->writeChanges();
    }
}
