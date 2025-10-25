<?php

/**
 * This class uses to register migrations.
 *
 * @package StellarPay\Core\Migrations
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Migrations;

use StellarPay\Core\Exceptions\Primitives\InvalidArgumentException;
use StellarPay\Core\Migrations\Contracts\Migration;

/**
 * @since 1.2.0
 *
 * @template T of Migration
 */
class MigrationsRegister
{
    /**
     * FQCN of Migration classes
     *
     * @var class-string<T>[]
     *
     * @since 1.2.0
     */
    private array $migrations = [];

    /**
     * Returns all the registered migrations
     *
     * @since 1.2.0
     */
    public function getMigrations(): array
    {
        return $this->migrations;
    }

    /**
     * Checks to see if a migration is registered with the given ID
     *
     * @since 1.2.0
     */
    public function hasMigration(string $id): bool
    {
        return isset($this->migrations[$id]);
    }

    /**
     * Returns a migration with the given ID
     *
     * @since 1.2.0
     * @return class-string<T>
     */
    public function getMigration(string $id): string
    {
        if (! isset($this->migrations[$id])) {
            throw new InvalidArgumentException("No migration exists with the ID $id"); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        return $this->migrations[$id];
    }

    /**
     * Returns all the registered migration ids
     *
     * @since 1.2.0
     */
    public function getRegisteredIds(): array
    {
        return array_keys($this->migrations);
    }

    /**
     * Add a migration to the list of migrations
     *
     * @since 1.2.0
     *
     * @param class-string<T> $migrationClass FQCN of the Migration Class
     */
    public function addMigration(string $migrationClass): void
    {
        if (! is_subclass_of($migrationClass, Migration::class)) {
            throw new InvalidArgumentException('Class must extend the ' . Migration::class . ' class');
        }

        $migrationId = $migrationClass::id();

        if (isset($this->migrations[$migrationId])) {
            throw new InvalidArgumentException(
                'A migration can only be added once. Make sure there are not id conflicts.'
            );
        }

        $this->migrations[$migrationId] = $migrationClass;
    }

    /**
     * Helper for adding a bunch of migrations at once
     *
     * @since 1.2.0
     *
     * @param class-string<T>[] $migrationClasses Array of FQCN of the Migration Class
     */
    public function addMigrations(array $migrationClasses): void
    {
        foreach ($migrationClasses as $migrationClass) {
            $this->addMigration($migrationClass);
        }
    }
}
