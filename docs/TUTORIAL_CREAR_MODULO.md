# 🎓 Tutorial: Creación de un Módulo CRUD desde Cero (Paso a Paso)

Este tutorial te guiará a través de la creación completa de un módulo funcional en SADI. Crearemos un módulo llamado **"Tutorial"** (`ModuloTutorial`) que tendrá un registro básico con título, y cubriremos desde la migración de base de datos hasta las vistas y los tests.

---

## 🏗️ Paso 1: Scaffolding (Generación de Archivos Base)

En SADI, usamos nuestra herramienta CLI para generar automáticamente la arquitectura limpia. Ejecuta el siguiente comando en la raíz del proyecto:

```bash
php cli/sadi make:section ModuloTutorial --table=modulo_tutorial --module=modulo_tutorial
```

**Archivos generados automáticamente:**
- `src/Models/ModuloTutorial.php` (DTO)
- `src/Repositories/ModuloTutorialRepository.php`
- `src/Controllers/ModuloTutorialController.php`
- `database/migrations/xxxx_create_table_modulo_tutorial.sql`
- `views/modulo_tutorial/index.phtml`
- `views/modulo_tutorial/create.phtml`
- `views/modulo_tutorial/edit.phtml`

---

## 🗄️ Paso 2: Base de Datos (Migración y Seeder)

### 2.1 La Migración
Abre el archivo generado en `database/migrations/xxxx_create_table_modulo_tutorial.sql` y define la estructura de la tabla.

```sql
CREATE TABLE IF NOT EXISTS modulo_tutorial (
    id SERIAL PRIMARY KEY,
    titulo TEXT NOT NULL,
    descripcion TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    eliminado BOOLEAN DEFAULT false
);
```

Para aplicar la migración, ejecuta:
```bash
php cli/sadi db:migrate
```

### 2.2 El Seeder (Datos de prueba)
Crea un archivo llamado `database/seed_modulo_tutorial.php` (o añade al final de `seed_maestros.php`) para registrar algunos datos falsos para desarrollo:

```php
// database/seed_modulo_tutorial.php
$pdo->exec("
    INSERT INTO modulo_tutorial (titulo, descripcion) 
    VALUES ('Primer Tutorial', 'Descripción de prueba')
");
```

---

## 📦 Paso 3: El Modelo (DTO)

Abre `src/Models/ModuloTutorial.php` y define las propiedades estrictas y readonly (Inmutabilidad).

```php
<?php
declare(strict_types=1);

namespace App\Models;

readonly class ModuloTutorial
{
    public function __construct(
        public ?int $id = null,
        public string $titulo = '',
        public ?string $descripcion = null,
        public ?string $fecha_creacion = null
    ) {}

    // Útil para enviar datos como JSON si hacemos una API
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'fecha_creacion' => $this->fecha_creacion
        ];
    }
}
```

---

## 🗃️ Paso 4: El Repositorio (Consultas a la BD)

En `src/Repositories/ModuloTutorialRepository.php`, definimos todo el CRUD usando PDO seguro.

```php
<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\ModuloTutorial;
use PDO;

class ModuloTutorialRepository extends Repository
{
    protected function getTable(): string { return 'modulo_tutorial'; }

    /** @return ModuloTutorial[] */
    public function all(string $busqueda = ''): array
    {
        $sql = "SELECT * FROM modulo_tutorial WHERE eliminado = false";
        $params = [];

        if ($busqueda) {
            $sql .= " AND titulo ILIKE ?";
            $params[] = '%' . $busqueda . '%';
        }

        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        
        return array_map(fn($row) => new ModuloTutorial(...$row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $id): ?ModuloTutorial
    {
        $stmt = $this->getPdo()->prepare("SELECT * FROM modulo_tutorial WHERE id = ? AND eliminado = false");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? new ModuloTutorial(...$row) : null;
    }

    public function create(ModuloTutorial $modelo): void
    {
        $sql = "INSERT INTO modulo_tutorial (titulo, descripcion) VALUES (?, ?)";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([$modelo->titulo, $modelo->descripcion]);
    }

    public function update(ModuloTutorial $modelo): void
    {
        $sql = "UPDATE modulo_tutorial SET titulo = ?, descripcion = ? WHERE id = ?";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([$modelo->titulo, $modelo->descripcion, $modelo->id]);
    }

    public function delete(int $id): void
    {
        $sql = "UPDATE modulo_tutorial SET eliminado = true WHERE id = ?";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([$id]);
    }
}
```

---

## 🧠 Paso 5: El Controlador (Lógica de Navegación)

El `src/Controllers/ModuloTutorialController.php` unirá el Repositorio con las vistas `.phtml`.

```php
<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ModuloTutorialRepository;
use App\Models\ModuloTutorial;

class ModuloTutorialController
{
    private ModuloTutorialRepository $repo;

    public function __construct() {
        $this->repo = new ModuloTutorialRepository();
    }

    // 1. Listar y Filtrar
    public function index() {
        $busqueda = $_GET['search'] ?? '';
        $registros = $this->repo->all($busqueda);
        require_once __DIR__ . '/../../views/modulo_tutorial/index.phtml';
    }

    // 2. Mostrar formulario Crear
    public function create() {
        require_once __DIR__ . '/../../views/modulo_tutorial/create.phtml';
    }

    // 3. Procesar formulario Guardar
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $modelo = new ModuloTutorial(
                titulo: $_POST['titulo'] ?? '',
                descripcion: $_POST['descripcion'] ?? null
            );
            $this->repo->create($modelo);
            header("Location: ?route=modulo_tutorial/index");
            exit;
        }
    }

    // 4. Mostrar formulario Editar
    public function edit() {
        $id = (int) ($_GET['id'] ?? 0);
        $registro = $this->repo->find($id);
        require_once __DIR__ . '/../../views/modulo_tutorial/edit.phtml';
    }

    // 5. Procesar formulario Actualizar
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $modelo = new ModuloTutorial(
                id: (int) $_POST['id'],
                titulo: $_POST['titulo'] ?? '',
                descripcion: $_POST['descripcion'] ?? null
            );
            $this->repo->update($modelo);
            header("Location: ?route=modulo_tutorial/index");
            exit;
        }
    }

    // 6. Eliminar (Soft Delete)
    public function delete() {
        $id = (int) ($_GET['id'] ?? 0);
        $this->repo->delete($id);
        header("Location: ?route=modulo_tutorial/index");
        exit;
    }
}
```

---

## 🎨 Paso 6: Las Vistas (Frontend PHTML)

### `views/modulo_tutorial/index.phtml`
```php
<?php 
/** @var App\Models\ModuloTutorial[] $registros */
/** @var string $busqueda */
?>
<h1>Módulo Tutorial</h1>
<form method="GET">
    <input type="hidden" name="route" value="modulo_tutorial/index">
    <input type="text" name="search" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar título...">
    <button type="submit">Filtrar</button>
</form>

<a href="?route=modulo_tutorial/create">Crear Nuevo</a>

<table>
    <tr>
        <th>ID</th><th>Título</th><th>Acciones</th>
    </tr>
    <?php foreach($registros as $row): ?>
    <tr>
        <td><?= htmlspecialchars((string) $row->id) ?></td>
        <td><?= htmlspecialchars($row->titulo) ?></td>
        <td>
            <a href="?route=modulo_tutorial/edit&id=<?= $row->id ?>">Editar</a>
            <a href="?route=modulo_tutorial/delete&id=<?= $row->id ?>" onclick="return confirm('¿Seguro?')">Eliminar</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
```

---

## 🧪 Paso 7: Las Pruebas Automatizadas (Tests con Pest)

Es obligatorio que todos los módulos nuevos estén probados. Ejecutamos el scaffolding para tests:
```bash
php cli/sadi make:test ModuloTutorial
```

Abre `tests/Feature/ModuloTutorialTest.php` y escribe lo siguiente:

```php
<?php
use App\Models\ModuloTutorial;
use App\Repositories\ModuloTutorialRepository;

it('puede crear y listar un modulo tutorial', function () {
    $repo = new ModuloTutorialRepository();
    
    // Test Create
    $nuevo = new ModuloTutorial(titulo: 'Test de prueba', descripcion: 'test');
    $repo->create($nuevo);
    
    // Test List/Filter
    $resultados = $repo->all('Test de prueba');
    expect($resultados)->toHaveCount(1);
    expect($resultados[0]->titulo)->toBe('Test de prueba');
    
    // Test Soft Delete
    $repo->delete($resultados[0]->id);
    $resultadosPostDelete = $repo->all('Test de prueba');
    expect($resultadosPostDelete)->toHaveCount(0); // Ya no aparece porque está eliminado
});
```

Finalmente, corre las pruebas para asegurar que tu módulo está funcionando a la perfección:
```bash
php cli/sadi test
```

**¡Felicidades! Has completado y probado un módulo CRUD 100% nativo y con Arquitectura Limpia en SADI.**

---

## ⚡ Paso 8: Optimización con Fibers (Consultas Concurrentes)

Si tu controlador necesita cargar múltiples catálogos o ejecutar varias consultas pesadas independientes al mismo tiempo (para evitar el cuello de botella secuencial de PDO), SADI provee integración nativa con `amphp/postgres` a través de Fibers en PHP 8.5+.

**1. Crea un método asíncrono en tu Repositorio:**
La clase base `Repository` provee un método `getAsyncPool()` que puedes usar en lugar de `$this->getPdo()` para obtener la conexión no bloqueante:

```php
    public function allAsync(): array
    {
        $result = $this->getAsyncPool()->query("SELECT * FROM modulo_tutorial WHERE eliminado = false");
        
        $results = [];
        foreach ($result as $row) {
            $results[] = new ModuloTutorial(...$row);
        }
        return $results;
    }
```

**2. Orquesta las llamadas concurrentes en el Controlador:**
En tu controlador, usa `Amp\async()` para despachar las tareas y `Amp\Future\await()` para esperar a que todas terminen en paralelo.

```php
    public function index() {
        try {
            // Disparamos múltiples consultas al mismo tiempo sin pasar pools explícitamente
            $fRegistros   = \Amp\async(fn() => $this->repo->allAsync());
            $fCategorias  = \Amp\async(fn() => $this->categoriasRepo->allAsync());
            $fEstadistica = \Amp\async(fn() => $this->repo->getEstadisticasAsync());

            // Esperamos que TODAS terminen simultáneamente
            [$registros, $categorias, $estadisticas] = \Amp\Future\await([$fRegistros, $fCategorias, $fEstadistica]);
            
        } catch (\Throwable $e) {
            // Manejo de errores asíncronos
            $registros = $categorias = $estadisticas = [];
            error_log("Error asíncrono: " . $e->getMessage());
        }

        require_once __DIR__ . '/../../views/modulo_tutorial/index.phtml';
    }
```
Con esto, si cada consulta tomaba 100ms, en lugar de tardar 300ms, el bloque completo tardará ~100ms.
