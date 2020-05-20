<?php

namespace Symfony\Bundle\MakerBundle\Docker;

class DatabaseServices
{
    // @TODO think we may be better off provide 1 public method e.g. getPorts(string $serviceName): array
    // @TODO then internally use switch? to get the correct port map for the service. Same for the env's
    public static function portMariaDb(string $port = '3306'): array
    {
        return [$port];
    }

    public static function portMySql(string $port = '3306'): array
    {
        return [$port];
    }
    public static function portPostgres(string $port = '5432'): array
    {
        return [$port];
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
