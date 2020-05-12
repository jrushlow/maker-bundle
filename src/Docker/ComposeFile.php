<?php

namespace Symfony\Bundle\MakerBundle\Docker;

class ComposeFile
{
    public static function getBasicStructure(string $version = '3.7'): array
    {
        return [
            'version' => $version,
            'services' => []
        ];
    }
}
