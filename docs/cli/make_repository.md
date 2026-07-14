# Comando: `make:repository`

Genera un nuevo **Repositorio** en la carpeta `src/Repositories/`.

En SADI, el patrón Repositorio es el encargado exclusivo de interactuar con la Base de Datos (PostgreSQL). Todo archivo de esta capa hereda de `App\Database\Repository` y utiliza directamente la instancia de PDO del sistema.

## Características del archivo generado
* Incluye los métodos CRUD básicos por defecto: `all()`, `findById()`, `save()` y `delete()`.
* Mapea automáticamente los resultados de la base de datos al DTO correspondiente mediante su método `fromArray()`.

## Ejemplos de Uso

### 1. Crear un repositorio básico
```bash
php sadi make:repository Proyecto
```
*Si no se especifica tabla, intentará inferirla usando el nombre en formato snake_case (ej. `proyecto`).*

### 2. Definir una tabla específica (Recomendado)
```bash
php sadi make:repository OrdenCompra --table=orden_compras
```
*Garantiza que el repositorio apunte directamente a la tabla `orden_compras` de PostgreSQL.*

[⬅ Volver al Índice](README.md)
