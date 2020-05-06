<?php

namespace Symfony\Bundle\MakerBundle\Generator;

class PrototypeCodeGenerator
{
    public function getCodeBlock(): string
    {
        return <<< 'EOT'
public function testing(): int
{
    return 1234;
}
EOT;
    }
}
