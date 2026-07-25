<?php

declare(strict_types=1);

namespace App\Database;

use Faker\Factory as FakerFactory;
use Faker\Generator;

abstract class Factory
{
    /**
     * @var Generator
     */
    protected Generator $faker;

    /**
     * @var string
     */
    protected string $model;

    /**
     * @var string
     */
    protected string $repository;

    /**
     * @var int
     */
    protected int $count = 1;

    public function __construct()
    {
        $this->faker = FakerFactory::create('es_VE');
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    abstract public function definition(): array;

    /**
     * Specify how many models should be generated.
     */
    public function count(int $count): static
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Create a new instance of the factory.
     */
    public static function new(): static
    {
        return new static();
    }

    /**
     * Create a collection of models and persist them to the database.
     */
    public function create(array $attributes = []): array|object
    {
        $results = [];
        $repositoryInstance = new $this->repository();

        for ($i = 0; $i < $this->count; $i++) {
            $data = array_merge($this->definition(), $attributes);
            
            // Build the model DTO from array
            $modelClass = $this->model;
            $modelInstance = $modelClass::fromArray($data);
            
            // Persist the model
            $repositoryInstance->save($modelInstance);
            
            $results[] = $modelInstance;
        }

        return $this->count === 1 ? $results[0] : $results;
    }
}
