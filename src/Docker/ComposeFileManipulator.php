<?php

namespace Symfony\Bundle\MakerBundle\Docker;

use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;

/**
 * @TODO Move to Util namespace
 */
class ComposeFileManipulator
{
    //@TODO hmm, probably need to come up with a current ver and supported ver array
    public const COMPOSE_FILE_VERSION = '3.7';

    private $contents;
    private $composeData;

    public function __construct(string $contents = '')
    {
        $this->contents = $contents;

        if ($this->contents === '') {
            $this->composeData = ComposeFile::getBasicStructure(self::COMPOSE_FILE_VERSION);
        } else {
            $this->composeData = (new YamlSourceManipulator($this->contents))->getData();
        }
    }

    public function getData(): array
    {
        return $this->composeData;
    }

    public function serviceExists(string $name): bool
    {
        if (array_key_exists('services', $this->composeData)) {
            return array_key_exists($name, $this->composeData['services']);
        }

        return false;
    }

    public function addDockerService(string $name, array $details): void
    {
        $this->composeData['services'][$name] = $details;
    }

    public function removeDockerService(string $name): void
    {
        unset($this->composeData['services'][$name]);
    }
}
