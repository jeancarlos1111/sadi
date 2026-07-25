<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Database\Factory;
use App\Repositories\UsuarioRepository;
use App\Models\Usuario;
use PDO;

class UsuarioFactory extends Factory
{
    protected string $model = Usuario::class;
    protected string $repository = UsuarioRepository::class;

    public function definition(): array
    {
        $username = strtoupper($this->faker->unique()->userName());

        return [
            'id'             => 0,
            'usuario'        => $username,
            'password'       => 'Test2026!', // Contraseña por defecto para testing
            'cedulaPersonal' => null,
        ];
    }

    public function create(array $attributes = []): array|object
    {
        $repo = new UsuarioRepository();
        $pdo = $repo->getPdo();
        $results = [];

        // Obtener IDs de todos los roles existentes (excluyendo al ADMINISTRADOR para evitar que haya muchos)
        $stmt = $pdo->query("SELECT id_rol FROM rol WHERE nombre != 'ADMINISTRADOR' AND eliminado = false");
        $rolesDisponibles = $stmt->fetchAll(PDO::FETCH_COLUMN);

        for ($i = 0; $i < $this->count; $i++) {
            $data = array_merge($this->definition(), $attributes);

            $repo->save([
                'usuario'         => $data['usuario'],
                'password'        => $data['password'],
                'cedula_personal' => $data['cedulaPersonal'],
            ]);

            $nuevoUsuario = $repo->findByUsername($data['usuario']);
            
            if ($nuevoUsuario && !empty($rolesDisponibles)) {
                // Asignar 1 a 3 roles aleatorios
                shuffle($rolesDisponibles);
                $numRoles = rand(1, min(3, count($rolesDisponibles)));
                $rolesAleatorios = array_slice($rolesDisponibles, 0, $numRoles);
                
                $repo->syncRoles($nuevoUsuario->id, $rolesAleatorios);
                $results[] = $nuevoUsuario;
            }
        }

        return $this->count === 1 ? ($results[0] ?? null) : $results;
    }
}
