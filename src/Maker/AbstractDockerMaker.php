<?php

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Docker\ComposeFileManipulator;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractDockerMaker implements MakerInterface
{
    /** @var ComposeFileManipulator */
    protected $composeFileManipulator;
    protected $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command
            ->addArgument('docker-dir')
            ->addArgument('existing-docker-compose')
        ;

        $input->setArgument('docker-dir', $io->ask('What directory should we store your docker files in?', $this->fileManager->getRootDirectory()));

        $dockerComposeFile = sprintf('%s/docker-compose.yaml', $input->getArgument('docker-dir'));

        $composeFileContents = '';

        if ($this->fileManager->fileExists($dockerComposeFile)) {
            $input->setArgument('existing-docker-compose', true);

            $composeFileContents = $this->fileManager->getFileContents($dockerComposeFile);

            $io->text('Existing Docker Compose file found.');
        }

        $this->composeFileManipulator = new ComposeFileManipulator($composeFileContents);

        $io->text(sprintf('Using %s', $dockerComposeFile));
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Yaml::class,
            'yaml'
        );
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
