# Guía Arquitectónica: Manejo de Relaciones entre Modelos

En SADI, **no utilizamos un ORM de tipo "Active Record"** (como Eloquent en Laravel o ActiveRecord en Ruby on Rails). 

Esto significa que **no existe la "carga perezosa" (Lazy Loading)**. Si tienes un modelo `OrdenCompra`, no puedes hacer `$orden->proveedor->nombre` mágicamente en tu vista, porque los modelos en SADI son **Data Transfer Objects (DTOs)** puros (`readonly class`) que no tienen conexión con la base de datos.

## 🎯 ¿Por qué lo hacemos así?

1. **Rendimiento extremo:** Evitamos el famoso "Problema N+1". Al usar un ORM tradicional, listar 100 órdenes y pedir el proveedor de cada una generaría 101 consultas a la base de datos. Aquí haces 1 sola consulta con un `JOIN`.
2. **Arquitectura Limpia:** Los modelos no saben qué es una base de datos. Solo transportan información (DTO). Toda la lógica de extracción de datos está estrictamente aislada en los **Repositorios**.
3. **Inmutabilidad:** Al usar clases `readonly`, los datos que llegan a las vistas son seguros y no pueden ser alterados accidentalmente.

---

## 🛠️ Cómo Cargar Relaciones (Ejemplos Prácticos)

Toda la carga de relaciones debe hacerse mediante consultas explícitas en tu **Repository**.

### Caso 1: Relación "Uno a Uno" o "Muchos a Uno" (Ej: Orden -> Proveedor)

Supongamos que queremos mostrar una orden de compra junto con el nombre y RIF de su proveedor.

**Paso 1: Preparar el Modelo (DTO)**
Debes asegurarte de que tu modelo soporte recibir los datos del proveedor, ya sea como propiedades separadas o como un objeto anidado.

```php
// src/Models/OrdenCompra.php
declare(strict_types=1);

namespace App\Models;

readonly class OrdenCompra
{
    public function __construct(
        public int $id_orden_de_compra,
        public int $id_proveedor,
        public string $concepto_odc,
        // Agregamos propiedades opcionales para la relación
        public ?string $nombre_proveedor = null,
        public ?string $rif_proveedor = null
    ) {}
}
```

**Paso 2: Escribir el JOIN en el Repositorio**

```php
// src/Repositories/OrdenCompraRepository.php
public function findConProveedor(int $id): ?OrdenCompra
{
    // 1. Escribimos el SQL explícito con el JOIN
    $sql = "
        SELECT 
            O.id_orden_de_compra, 
            O.id_proveedor, 
            O.concepto_odc,
            P.compania_proveedor, 
            P.rif_proveedor 
        FROM orden_de_compra O
        INNER JOIN proveedor P ON O.id_proveedor = P.id_proveedor
        WHERE O.id_orden_de_compra = :id
    ";
    
    // 2. Ejecutamos la consulta
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    // 3. Mapeamos (Hidratamos) el Modelo
    return new OrdenCompra(
        id_orden_de_compra: $row['id_orden_de_compra'],
        id_proveedor:       $row['id_proveedor'],
        concepto_odc:       $row['concepto_odc'],
        nombre_proveedor:   $row['compania_proveedor'],
        rif_proveedor:      $row['rif_proveedor']
    );
}
```

---

### Caso 2: Relación "Uno a Muchos" (Ej: Orden -> Detalles/Artículos)

Si una orden tiene muchos artículos, no agregamos un arreglo de artículos al SQL (porque el SQL devuelve tablas planas). En este caso, hacemos dos consultas en el controlador/servicio, o usamos métodos auxiliares en el repositorio.

**Opción recomendada (Llamadas desde el Controlador o Servicio):**

```php
// src/Controllers/OrdenCompraController.php

public function show(int $id)
{
    // 1. Buscamos la cabecera de la orden
    $orden = $this->ordenRepo->findConProveedor($id);
    
    // 2. Buscamos los detalles en el repositorio correspondiente
    // (Este repositorio ejecutará: SELECT * FROM articulo_orden_de_compra WHERE id_orden = X)
    $detalles = $this->detalleOrdenRepo->findByOrdenId($id);
    
    // 3. Enviamos ambas variables separadas a la vista
    require_once __DIR__ . '/../../views/orden_compra/show.phtml';
}
```

Luego, en tu vista (`show.phtml`), iteras normalmente:

```php
<!-- views/orden_compra/show.phtml -->
<h1>Orden #<?= htmlspecialchars((string) $orden->id_orden_de_compra) ?></h1>
<p>Proveedor: <?= htmlspecialchars($orden->nombre_proveedor) ?></p>

<h3>Artículos:</h3>
<ul>
    <?php foreach ($detalles as $detalle): ?>
        <li><?= htmlspecialchars($detalle->nombre_articulo) ?> - Cant: <?= $detalle->cantidad ?></li>
    <?php endforeach; ?>
</ul>
```

---

## ⚠️ Lo que NUNCA debes hacer

🚫 **Nunca instancies el PDO dentro de un Modelo:**
Los modelos en SADI no pueden tener métodos como `$orden->guardar()` o `$orden->getProveedor()`.

🚫 **Nunca uses Lazy Loading accidental:**
Si estás en una vista `.phtml` y te das cuenta de que te falta un dato relacional, **no hagas consultas SQL desde la vista**. 
Debes ir al Repositorio, actualizar la instrucción `SELECT ... JOIN` para incluir la columna faltante, y pasarla a través del DTO.

## Conclusión
Aunque este enfoque requiere escribir un poco más de código SQL ("Boilerplate") en comparación con Laravel, garantiza que la aplicación sea **drásticamente más rápida y predecible**, ya que tú tienes control absoluto y transparente sobre cuántas consultas se ejecutan en todo momento.
