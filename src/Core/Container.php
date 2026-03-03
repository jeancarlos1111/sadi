<?php

namespace App\Core;

use Exception;
use ReflectionClass;

/**
 * Contenedor de Inyección de Dependencias básico.
 * Resuelve e instancia clases leyendo sus constructores (Autowiring).
 */
class Container
{
    private array $instances = [];
    private array $bindings = [];

    /**
     * Vincula una interfaz a una implementación concreta.
     */
    public function bind(string $abstract, string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Registra una instancia única compartida (Singleton).
     */
    public function singleton(string $class, object $instance): void
    {
        $this->instances[$class] = $instance;
    }

    /**
     * Resuelve y devuelve una instancia de la clase solicitada,
     * autoinyectando sus dependencias en el constructor.
     */
    public function get(string $class)
    {
        // 1. Si ya hay una instancia singleton, devolverla
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        // 2. Si está vinculada a otra concreta
        if (isset($this->bindings[$class])) {
            $class = $this->bindings[$class];
        }

        // 3. Reflexión de la clase para instanciarla dinámicamente
        try {
            $reflector = new ReflectionClass($class);

            if (!$reflector->isInstantiable()) {
                throw new Exception("La clase {$class} no es instanciable.");
            }

            $constructor = $reflector->getConstructor();

            // Si no tiene constructor explícito, crearla directamente
            if (is_null($constructor)) {
                return new $class();
            }

            $parameters = $constructor->getParameters();
            $dependencies = $this->resolveDependencies($parameters);

            return $reflector->newInstanceArgs($dependencies);

        } catch (Exception $e) {
            throw new Exception("Error resolviendo la dependencia {$class}: " . $e->getMessage());
        }
    }

    /**
     * Resuelve la matriz de dependencias recursivamente.
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type && !$type->isBuiltin()) {
                // Es una clase o interfaz. Recursividad.
                $dependencyClass = $type->getName();
                $dependencies[] = $this->get($dependencyClass);
            } else {
                // Es un tipo primitivo (int, string) o sin tipo
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("No se puede resolver la dependencia primitivas sin valor por defecto: \${$parameter->getName()}");
                }
            }
        }

        return $dependencies;
    }
}
