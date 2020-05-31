<?php

namespace Symfony\Bundle\MakerBundle;

class ArgumentCollection implements \IteratorAggregate
{
    private $arguments = [];

    public function addArgument(MakerArgument $argument): void
    {
        $this->arguments[$argument->getName()] = $argument;
    }

    public function getArgument(string $name): MakerArgument
    {
        $this->argumentExists($name);

        return $this->arguments[$name];
    }

    public function replaceArgument(MakerArgument $argument): void
    {
        $this->argumentExists($argument->getName());

        $this->arguments[$argument->getName()] = $argument;
    }

    public function removeArgument(string $name): void
    {
        unset($this->arguments[$name]);
    }

    public function setArgumentValue(string $name, $value): void
    {
        $this->argumentExists($name);

        $this->arguments[$name]->setValue($value);
    }

    public function getArgumentValue(string $name)
    {
        $this->argumentExists($name);

        return $this->arguments[$name]->getValue();
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->arguments);
    }

    private function argumentExists(string $name): void
    {
        if (!isset($this->arguments[$name])) {
            // @TODO Throw arg doesnt exist exception
        }
    }
}
