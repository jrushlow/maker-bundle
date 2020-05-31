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
        return $this->arguments[$name];
    }

    public function removeArgument(string $name): void
    {
        unset($this->arguments[$name]);
    }

    public function setArgumentValue(string $name, $value): void
    {
        $this->arguments[$name]->setValue($value);
    }

    public function getArgumentValue(string $name)
    {
        return $this->arguments[$name]->getValue();
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->arguments);
    }
}
