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
            ->addArgument('schema-name')
            ->addArgument('root-password', null, '', 'password')
            ->addArgument('username', null, '', 'user')
            ->addArgument('password', null, '', 'password')
            ->addArgument('expose-ports-to-host')
        ;

        $io->newLine();

        $databaseChoice = $io->choice(
            'Which database service will you be creating?',
            ['MySQL', 'MariaDB', 'Postgres']
        );

        $io->text([sprintf('For a list of supported versions, check out %s', $this->getVersionLink($databaseChoice))]);

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

        $input->setArgument('schema-name', $io->ask('What should we name the default schema?', str_replace(' ', '_', Str::getRandomTerm())));

        $defaultCredentials = [
            'Username: "user"',
            'Password: "password"',
        ];

        if ('postgres' !== $database) {
            $defaultCredentials[] = 'Root Password: "password"';
        }

        $io->section('Default Credentials');
        $io->text($defaultCredentials);

        if ($io->confirm('Do you want to change the default credentials?', false)) {
            $this->changeDefaultCredentials($input, $io, $database);
        }

        $io->section('- Networking -');

        $ports = DatabaseServices::getDefaultPorts($database);

        $input->setArgument('expose-ports-to-host', $io->ask(sprintf('Do you want to expose port %s to the host?', $ports[0])));
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        parent::generate($input, $io, $generator);

        $env = $this->getDatabaseEnvVars(
            $input->getArgument('database'),
            $input->getArgument('root-password'),
            $input->getArgument('schema-name'),
            $input->getArgument('username'),
            $input->getArgument('password')
        );

        $this->composeFileManipulator->addDockerService($input->getArgument('service-name'), [
            'image' => sprintf('%s:%s', $input->getArgument('database'), $input->getArgument('version')),
            'environment' => $env
        ]);

        if ($input->getArgument('expose-ports-to-host')) {
            $this->composeFileManipulator->exposePorts(
                $input->getArgument('service-name'),
                DatabaseServices::getDefaultPorts($input->getArgument('database'))
            );
        }

        //@TODO dump and write could be abstracted
        $generator->dumpFile($this->dockerComposeFile, Yaml::dump($this->composeFileManipulator->getData(), 20));
        $generator->writeChanges();
    }

    private function changeDefaultCredentials(InputInterface $input, ConsoleStyle $io, string $database): void
    {
        if ('postgres' !== $database) {
            $input->setArgument('root-password', $io->askHidden('Root password'));
        }

        $input->setArgument('username', $io->ask('Username:', 'user'));
        $input->setArgument('password', $io->askHidden('Password'));
    }

    private function getVersionLink(string $databaseName): string
    {
        $docker = 'https://hub.docker.com/_/';

        switch ($databaseName) {
            case 'MariaDB':
                return sprintf('%smariadb', $docker);
                break;
            case 'MySQL':
                return sprintf('%smysql', $docker);
                break;
            case 'Postgres':
                return sprintf('%spostgres', $docker);
                break;
        }
    }

    private function getDatabaseEnvVars(string $database, string $rootPwd, string $schema, string $username, string $password): array
    {
        switch ($database) {
            case 'mariadb':
                return DatabaseServices::envMariaDb($schema, $rootPwd, $username, $password);
                break;
            case 'mysql':
                return DatabaseServices::envMySql($schema, $rootPwd, $username, $password);
                break;
            case 'postgres':
                return DatabaseServices::envPostgres($schema, $username, $password);
                break;
        }
    }
}
