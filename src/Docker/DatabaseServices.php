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
                    'ports' => ['3306']
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

    public static function getDataLocation(string $service): string
    {
        switch ($service) {
            case 'mariadb':
            case 'mysql':
                return '/var/lib/mysql';
            case 'postgres':
                return '/var/lib/postgresql/data';
        }
    }

    public static function envMariaDb(string $schema, string $rootPwd, string $user, string $password): array
    {
        return [
            'MYSQL_DATABASE' => $schema,
            'MYSQL_ROOT_PASSWORD' => $rootPwd,
            'MYSQL_USER' => $user,
            'MYSQL_PASSWORD' => $password
        ];
    }

    public static function envMySql(string $schema, string $rootPwd, string $user, string $password): array
    {
        return [
            'MYSQL_ROOT_PASSWORD' => $rootPwd,
            'MYSQL_DATABASE' => $schema,
            'MYSQL_USER' => $user,
            'MYSQL_PASSWORD' => $password
        ];
    }

    public static function envPostgres(string $schema, string $user, string $password): array
    {
        return [
            'POSTGRES_DB' => $schema,
            'POSTGRES_PASSWORD' => $password,
            'POSTGRES_USER' => $user,
        ];
    }
}
