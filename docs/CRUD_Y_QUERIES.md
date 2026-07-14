# Guía Arquitectónica: Operaciones CRUD y Queries (SQL)

En SADI, interactuamos con la base de datos de manera pura y explícita utilizando **PDO (PHP Data Objects)**. 

Al no contar con un ORM complejo, toda la responsabilidad de crear, leer, actualizar y eliminar datos (CRUD) recae en la capa de **Repositorios** (`src/Repositories`).

## 🛡️ Regla de Oro: Consultas Preparadas (Prepared Statements)
**NUNCA** debes concatenar variables directamente en una cadena SQL. 
**Incorrecto (Vulnerable a Inyección SQL):**
```php
// 🚫 ¡NUNCA HAGAS ESTO!
$sql = "SELECT * FROM usuarios WHERE email = '" . $email . "'";
```
**Correcto (Seguro):**
```php
// ✅ SIEMPRE USA PARÁMETROS PREPARADOS (?)
$sql = "SELECT * FROM usuarios WHERE email = ?";
$stmt = $this->pdo->prepare($sql);
$stmt->execute([$email]);
```

---

## 1. CREATE (Insertar Datos)

Para insertar datos, recibimos el DTO (Data Transfer Object) en el método del repositorio, preparamos el `INSERT` y lo ejecutamos. 

En PostgreSQL, si necesitas recuperar el ID generado automáticamente, usamos la cláusula `RETURNING id`.

```php
// src/Repositories/ArticuloRepository.php

public function create(Articulo $articulo): int
{
    $sql = "
        INSERT INTO articulo (denominacion_a, observacion_a, id_tipo_de_articulo) 
        VALUES (?, ?, ?) 
        RETURNING id_articulo
    ";

    $stmt = $this->pdo->prepare($sql);
    
    // Ejecutamos pasando los valores en el mismo orden que los "?"
    $stmt->execute([
        $articulo->denominacion_a,
        $articulo->observacion_a,
        $articulo->id_tipo_de_articulo
    ]);

    // Obtenemos el ID generado por PostgreSQL
    return (int) $stmt->fetchColumn();
}
```

---

## 2. READ (Consultar Datos - SELECT)

### A. Consultar un único registro (find)
```php
public function find(int $id): ?Articulo
{
    $sql = "SELECT * FROM articulo WHERE id_articulo = ? AND eliminado = false";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$id]);
    
    // FETCH_ASSOC devuelve un array asociativo
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null; // No existe
    }

    // "Hidratamos" y retornamos el DTO
    return new Articulo(
        id_articulo:         $row['id_articulo'],
        denominacion_a:      $row['denominacion_a'],
        observacion_a:       $row['observacion_a'],
        id_tipo_de_articulo: $row['id_tipo_de_articulo']
    );
}
```

### B. Consultar múltiples registros (all / listar)
```php
/**
 * @return Articulo[]
 */
public function all(string $busqueda = ''): array
{
    $sql = "SELECT * FROM articulo WHERE eliminado = false";
    $params = [];

    // Búsqueda dinámica segura
    if ($busqueda !== '') {
        $sql .= " AND denominacion_a ILIKE ?";
        // Añadimos comodines % para buscar por coincidencia parcial
        $params[] = '%' . $busqueda . '%'; 
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $articulos = [];
    foreach ($rows as $row) {
        $articulos[] = new Articulo(
            id_articulo:         $row['id_articulo'],
            denominacion_a:      $row['denominacion_a'],
            observacion_a:       $row['observacion_a'],
            id_tipo_de_articulo: $row['id_tipo_de_articulo']
        );
    }

    return $articulos;
}
```

---

## 3. UPDATE (Actualizar Datos)

Para actualizar, usamos la misma lógica del `INSERT`, mapeando los nuevos valores del DTO a la consulta `UPDATE`.

```php
public function update(Articulo $articulo): bool
{
    $sql = "
        UPDATE articulo 
        SET 
            denominacion_a = ?, 
            observacion_a = ?, 
            id_tipo_de_articulo = ?
        WHERE id_articulo = ?
    ";

    $stmt = $this->pdo->prepare($sql);
    
    return $stmt->execute([
        $articulo->denominacion_a,
        $articulo->observacion_a,
        $articulo->id_tipo_de_articulo,
        $articulo->id_articulo // El ID va al final para el WHERE
    ]);
}
```

---

## 4. DELETE (Eliminar Datos - Soft Delete)

En la mayoría de los sistemas financieros y administrativos (como SADI), **rara vez eliminamos físicamente un registro** de la base de datos (Hard Delete) por motivos de auditoría.
En su lugar, hacemos un **Soft Delete** (Eliminación lógica), que en realidad es un `UPDATE` al campo `eliminado`.

```php
public function delete(int $id): bool
{
    // Soft Delete: Marcamos el registro como eliminado
    $sql = "UPDATE articulo SET eliminado = true WHERE id_articulo = ?";
    
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$id]);
}

// Si necesitas un Hard Delete (Borrado físico y destructivo), hazlo así:
public function hardDelete(int $id): bool
{
    $sql = "DELETE FROM articulo WHERE id_articulo = ?";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$id]);
}
```

---

## 🧱 Resumen de Patrones de PDO

- `$stmt->execute([...])` -> Corre la consulta mapeando los `?` con el arreglo.
- `$stmt->fetch(PDO::FETCH_ASSOC)` -> Obtiene **una sola fila** como arreglo asociativo.
- `$stmt->fetchAll(PDO::FETCH_ASSOC)` -> Obtiene **todas las filas** como un arreglo de arreglos.
- `$stmt->fetchColumn()` -> Obtiene el valor de **una sola columna** (útil para `COUNT(*)` o `RETURNING id`).
