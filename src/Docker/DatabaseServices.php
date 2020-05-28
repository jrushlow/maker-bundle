<?php

namespace Symfony\Bundle\MakerBundle\Docker;

class DatabaseServices
{
    public static function getDatabase(string $name, string $version): array
    {
        switch ($name) {
            case 'mariadb':
                return [
                    'image' => sprintf('mariadb:%s', $version),
                    'environment' => [
                        'MYSQL_ROOT_PASSWORD' => 'password'
                    ],
                    'ports' => ['3306:3306']
                ];
            case 'mysql':
                return [
                    'image' => sprintf('mysql:%s', $version),
                    'environment' => [
                        'MYSQL_ROOT_PASSWORD' => 'password'
                    ],
                    'ports' => ['3306:3306']
                ];
            case 'postgres':
                return [
                    'image' => sprintf('postgres:%s', $version),
                    'ports' => ['5432']
                ];
        }
    }

    public static function getDefaultPorts(string $service): array
    {
        $ports = [];

        switch ($service) {
            case 'mariadb':
            case 'mysql':
                $ports = ['3306'];
                break;
            case 'postgres':
                $ports = ['5432'];
                break;
        }

        return $ports;
    }
}
