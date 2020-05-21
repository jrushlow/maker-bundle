<?php

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Docker\ComposeFileManipulator;
use Symfony\Bundle\MakerBundle\Docker\DataDirGuesser;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractDockerMaker implements MakerInterface
{
    /** @var ComposeFileManipulator */
    protected $composeFileManipulator;
    protected $fileManager;
    protected $guesser;
    protected $dockerComposeFile;
    protected $dockerDataDir = '';

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;

        //$TODO refactor the guesser, naming conventions, etc..
        $this->guesser = new DataDirGuesser($fileManager);
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command
            ->addArgument('existing-docker-compose')
        ;

        $io->section('- Docker Compose Setup-');

        $this->dockerComposeFile = sprintf('%s/docker-compose.yaml', $this->fileManager->getRootDirectory());

//        $this->dockerDataDir =$io->ask(
//            'What directory should we store docker data in?',
//            sprintf('%s/docker', $this->fileManager->getRootDirectory())
//        );

        $composeFileContents = '';

        if ($this->fileManager->fileExists($this->dockerComposeFile)) {
            $input->setArgument('existing-docker-compose', true);

            $composeFileContents = $this->fileManager->getFileContents($this->dockerComposeFile);

            $io->text('Existing Docker Compose file found.');
        }

        $this->composeFileManipulator = new ComposeFileManipulator($composeFileContents);

        // @todo duh! change this up to created or save the created for later
        $io->text('The docker-compose file is located in your project root directory.');
//        $io->text(sprintf('All other docker related files will be stored in %s', $this->dockerDataDir));
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Yaml::class,
            'yaml'
        );
    }

    protected function dataDirQuestion(ConsoleStyle $io): void
    {
        $guessedDir = $this->guesser->guessDataDir();
        $confirm = false;

        if (null !== $guessedDir) {
            $confirm = $io->confirm(sprintf('Should we use %s to store docker related files and container data?', $guessedDir));
        }

        if ($confirm) {
            $this->dockerDataDir = $guessedDir;
            return;
        }

        $this->dockerDataDir = $io->ask(
            'What directory should we store docker related files and container data in?',
            sprintf('%s/docker', $this->fileManager->getRootDirectory())
        );
    }

    // @todo This method and the one above should be consolidated
    protected function createDataDir(string $path): void
    {
        if (!empty($this->dockerDataDir) && !$this->fileManager->fileExists($this->dockerDataDir)) {
            $this->fileManager->mkdir($this->dockerDataDir);
        }
    }

    protected function serviceAlreadyDefinedQuestion(ConsoleStyle $io, string $serviceName): void
    {
        $io->warning(sprintf('A service is already defined with the name "%s".', strtolower($serviceName)));

        if (!$io->confirm(sprintf('Do you want to create a new %s Service?', $serviceName))) {
            $io->success('Quit Early - No files were changed.');
            $io->newLine();

            exit();
        }
    }

    protected function writeSuccessMessage(ConsoleStyle $io): void
    {
        $io->newLine();
        $io->writeln(' <bg=green;fg=white>          </>');
        $io->writeln(' <bg=green;fg=white> Success! </>');
        $io->writeln(' <bg=green;fg=white>          </>');
        $io->newLine();
    }
}
