<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

abstract class Seeder
{
    /**
     * @var PDO
     */
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    abstract public function run(): void;

    /**
     * Seed the given connection from the given path.
     *
     * @param string|array $class
     * @return $this
     */
    public function call(string|array $class): self
    {
        $classes = is_array($class) ? $class : [$class];

        foreach ($classes as $class) {
            $seeder = new $class();
            $seeder->run();
        }

        return $this;
    }
}
