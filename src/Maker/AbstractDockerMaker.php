<?php

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ArgumentCollection;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Docker\ComposeFileManipulator;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\MakerArgument;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Convenient abstract class for docker makers.
 *
 * @author  Jesse Rushlow <jr@rushlow.dev>
 */
abstract class AbstractDockerMaker implements MakerInterface
{
    /** @var ComposeFileManipulator */
    protected $composeFileManipulator;
    protected $fileManager;
//    protected $guesser;
    protected $arguments;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
//        $this->guesser = new DataDirGuesser($fileManager);
        $this->arguments = new ArgumentCollection();

        $arguments = ['existing-setup', 'compose-file', 'data-dir', 'service-name', 'database-name', 'version'];

        foreach ($arguments as $argument) {
            $this->arguments->addArgument(new MakerArgument($argument));
        }
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $io->section('- Docker Compose Setup-');

        $this->arguments->setArgumentValue('compose-file', sprintf('%s/docker-compose.yaml', $this->fileManager->getRootDirectory()));

        $composeFileContents = '';

        if ($this->fileManager->fileExists($this->arguments->getArgumentValue('compose-file'))) {
            $input->setArgument('existing-docker-compose', true);

            $composeFileContents = $this->fileManager->getFileContents($this->arguments->getArgumentValue('compose-file'));

            $io->text('Existing Docker Compose file found.');
        }

        $this->composeFileManipulator = new ComposeFileManipulator($composeFileContents);
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

//    protected function dataDirQuestion(ConsoleStyle $io): void
//    {
//        $guessedDir = $this->guesser->guessDataDir();
//        $confirm = false;
//
//        if (null !== $guessedDir) {
//            $confirm = $io->confirm(sprintf('Should we use %s to store docker related files and container data?', $guessedDir));
//        }
//
//        if ($confirm) {
//            $this->dockerDataDir = $guessedDir;
//            return;
//        }
//
//        $this->dockerDataDir = $io->ask(
//            'What directory should we store docker related files and container data in?',
//            sprintf('%s/docker', $this->fileManager->getRootDirectory())
//        );
//    }
//
//    protected function createDataDir(string $path): void
//    {
//        if (!empty($this->dockerDataDir) && !$this->fileManager->fileExists($this->dockerDataDir)) {
//            $this->fileManager->mkdir($this->dockerDataDir);
//        }
//    }

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
