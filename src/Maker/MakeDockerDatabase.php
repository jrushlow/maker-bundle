<?php

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Docker\ComposeFile;
use Symfony\Bundle\MakerBundle\Docker\DatabaseServices;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\Yaml\Yaml;

class MakeDockerDatabase extends AbstractMaker
{
    private $fileManager;
    private $yamlData = [];

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

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

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Yaml::class,
            'yaml'
        );
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        $command
            ->addArgument('service-name')
            ->addArgument('database')
            ->addArgument('version')
            ->addArgument('schema-name')
            ->addArgument('root-password', null, '', 'password')
            ->addArgument('username', null, '', 'user')
            ->addArgument('password', null, '', 'password')
        ;

        $io->section('- Docker Compose -');

        $io->text(sprintf('Using %s/docker-compose.yaml', $this->fileManager->getRootDirectory()));
        $io->newLine();

        $databaseChoice = $io->choice(
            'Which database service will you be creating?',
            ['MySQL', 'MariaDB', 'Postgres']
        );

        $io->text([sprintf('For a list of supported versions, check out %s', $this->getVersionLink($databaseChoice))]);

        $database = strtolower($databaseChoice);

        $input->setArgument('service-name', $database);
        $input->setArgument('version', $io->ask('What version would you like to use?', 'latest'));

        if ($this->fileManager->fileExists($dockerComposeFile = 'docker-compose.yaml')) {
            $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($dockerComposeFile));
            $this->yamlData = $manipulator->getData();

            if (isset($this->yamlData['services'][$database])) {
                $io->warning(sprintf('A service is already defined with the name "%s".', $database));

                if (!$io->confirm(sprintf('Do you want to create a new %s Service?', $databaseChoice))) {
                    $io->success('Quit Early - No files were changed.');
                    $io->newLine();

                    exit();
                }

                $input->setArgument('service-name', $io->ask(sprintf('What name should we call the new %s service? e.g. %s', $databaseChoice, str_replace(' ', '-', Str::getRandomTerm()))));
            }
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

        $useDefaults = $io->confirm('Do you want to change the default credentials?', false);

        if (!$useDefaults) {
            return;
        }

        if ('postgres' !== $database) {
            $input->setArgument('root-password', $io->askHidden('Root password'));
        }

        $input->setArgument('username', $io->ask('Username:', 'user'));
        $input->setArgument('password', $io->askHidden('Password'));
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $env = $this->getDatabaseEnvVars(
            $input->getArgument('database'),
            $input->getArgument('root-password'),
            $input->getArgument('schema-name'),
            $input->getArgument('username'),
            $input->getArgument('password')
        );

        if (empty($this->yamlData)) {
            $this->yamlData = ComposeFile::getBasicStructure();
        }

        $this->yamlData['services'][$input->getArgument('service-name')] = [
            'image' => sprintf('%s:%s', $input->getArgument('database'), $input->getArgument('version')),
            'environment' => $env
        ];
//        dd(Yaml::dump($composeYaml, 20));

        $generator->dumpFile($this->fileManager->getRootDirectory().'/docker-compose.yaml', Yaml::dump($this->yamlData, 20));
        $generator->writeChanges();
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
