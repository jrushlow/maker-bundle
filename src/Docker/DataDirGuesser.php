<?php

namespace Symfony\Bundle\MakerBundle\Docker;

use Symfony\Bundle\MakerBundle\FileManager;

class DataDirGuesser
{
    private $fileManager;

    public function __construct(FileManager $manager)
    {
        $this->fileManager = $manager;
    }

    public function guessDataDir(): ?string
    {
        $projectDir = $this->fileManager->getRootDirectory();

        $possibilities = ['docker', '.docker'];

        foreach ($possibilities as $dir) {
            $path = sprintf('%s/%s', $projectDir, $dir);

            if ($this->fileManager->fileExists($path)) {
                return $path;
            }
        }

        return null;
    }
}
