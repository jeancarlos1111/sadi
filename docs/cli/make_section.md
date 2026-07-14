# Comando: `make:section`

El comando `make:section` es la herramienta de *scaffolding* más poderosa del CLI. Genera de una sola vez toda la estructura base ("sección" o módulo) necesaria para empezar a programar un requerimiento, evitando que el desarrollador tenga que crear múltiples archivos manualmente.

## ¿Qué archivos genera?
1. **Modelo:** (`src/Models/`) configurado como un DTO inmutable.
2. **Repositorio:** (`src/Repositories/`) configurado con los métodos CRUD base.
3. **Controlador:** (`src/Controllers/`) con los métodos `index`, `form`, `store`, `update`, y `eliminar`.
4. **Migración SQL:** (`database/migrations/`) plantilla para la creación de la tabla.
5. **Vistas:** (`views/`) plantillas `index.phtml` (tabla) y `form.phtml` (formulario).

## Ejemplos de Uso

### 1. Uso Básico
```bash
php sadi make:section Contrato
```
*Generará todo usando nombres estándar (ej. tabla `contrato`, vistas en `views/contrato/`).*

### 2. Uso con Tabla y Módulo Personalizado (Recomendado)
```bash
php sadi make:section PresupuestoContrato --table=presupuesto_contratos --module=presupuesto/contratos
```
*   `--table`: Le indica al Repositorio y a la Migración SQL qué nombre de tabla usar en Postgres.
*   `--module`: Le indica al Controlador dónde guardar las vistas (ej. `views/presupuesto/contratos/`) y qué prefijo usar en las rutas.

[⬅ Volver al Índice](README.md)
