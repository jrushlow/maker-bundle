<?php

namespace Symfony\Bundle\MakerBundle\Docker;

class DatabaseServices
{
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
