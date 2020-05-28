<?php

namespace Symfony\Bundle\MakerBundle;

class ArgumentCollection implements \ArrayAccess, \IteratorAggregate
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

    public function offsetExists($offset): bool
    {
        return isset($this->arguments[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->arguments[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->arguments[] = $value;

            return;
        }

        $this->arguments[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->arguments[$offset]);
    }
}
