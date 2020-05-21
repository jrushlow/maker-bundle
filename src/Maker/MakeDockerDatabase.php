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

        //@TODO am i supposed to even be allowed to set defaults on null modes. hmmmm
        $command
            ->addArgument('service-name')
            ->addArgument('database')
            ->addArgument('version')
            ->addArgument('customize', null, '', false)
            ->addArgument('schema-name')
            ->addArgument('root-password', null, '', 'password')
            ->addArgument('username', null, '', 'user')
            ->addArgument('password', null, '', 'password')
            ->addArgument('expose-ports-to-host', null, '', true)
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
            sprintf('Using default %s credentials.', $databaseChoice),
            'A default schema is not defined.', // @TODO verify this across all db images
            sprintf('Port(s) %s are exposed to the host.', ...(DatabaseServices::getDefaultPorts($database))),
            'Data is not persisted to the host.'
        ];

        $io->text($defaults);

        if ($io->confirm('Do you want to customize this service?', false)) {
            $input->setArgument('customize', true);

            $this->customizeService($input, $io);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        parent::generate($input, $io, $generator);

        $options['image'] = sprintf('%s:%s', $input->getArgument('database'), $input->getArgument('version'));

        if ($input->getArgument('customize')) {
            $options['environment'] = $this->getDatabaseEnvVars(
                $input->getArgument('database'),
                $input->getArgument('root-password'),
                $input->getArgument('schema-name'),
                $input->getArgument('username'),
                $input->getArgument('password')
            );
        }

        $this->composeFileManipulator->addDockerService($input->getArgument('service-name'), $options);

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

    private function customizeService(InputInterface $input, ConsoleStyle $io): void
    {
        $database = $input->getArgument('database');

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

        if ($io->confirm('Do you want to persist container data to the host? e.g. Database files')) {
            $this->dataDirQuestion($io);
            $this->createDataDir($this->dockerDataDir);

            $this->fileManager->mkdir(sprintf('%s/%s/data', $this->dockerDataDir, $input->getArgument('service-name')));

            $this->composeFileManipulator->addVolume($database, '', DatabaseServices::getDataLocation($database));
        }

        $io->section('- Networking -');

        $ports = DatabaseServices::getDefaultPorts($input->getArgument('database'));

        $input->setArgument('expose-ports-to-host', $io->confirm(sprintf('Do you want to expose port(s) %s to the host?', ...$ports)));
    }

    private function changeDefaultCredentials(InputInterface $input, ConsoleStyle $io, string $database): void
    {
        if ('postgres' !== $database) {
            $input->setArgument('root-password', $io->askHidden('Root password'));
        }

        $input->setArgument('username', $io->ask('Username:', 'user'));
        $input->setArgument('password', $io->askHidden('Password'));
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
