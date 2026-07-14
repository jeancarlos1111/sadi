# CLI SADI - Documentación Oficial

Bienvenidos a la documentación oficial de la Interfaz de Línea de Comandos (CLI) de SADI. 

La naturaleza de este sistema es **optimizar el flujo de trabajo de los desarrolladores**, estandarizando la creación de archivos y automatizando procesos como pruebas y migraciones. El CLI está diseñado basándose en la filosofía de arquitecturas limpias y el patrón DTO (Data Transfer Object) inmutable, garantizando el cumplimiento normativo exigido para este proyecto.

## Catálogo de Comandos Disponibles

A continuación, se presenta la lista de todos los comandos soportados por el sistema CLI, clasificados por su propósito. Haz clic en cada uno para ver su documentación detallada y ejemplos de uso:

### 🏗️ Scaffolding (Generación Automática de Código)
El objetivo principal del scaffolding es evitar el código repetitivo (*boilerplate*) y asegurar que cada nuevo archivo cumpla las buenas prácticas.

* [make:section](make_section.md) - Genera el scaffold completo de una sección (Módulo/CRUD completo).
* [make:model](make_model.md) - Genera un nuevo Modelo (Readonly DTO).
* [make:repository](make_repository.md) - Genera un nuevo Repositorio de base de datos.
* [make:controller](make_controller.md) - Genera un nuevo Controlador web.
* [make:test](make_test.md) - Genera un archivo de pruebas automatizadas (Feature/Unit Test).

### 🗄️ Base de Datos
Herramientas para sincronizar la estructura y los datos maestros.

* [db:migrate](db_migrate.md) - Aplica el esquema SQL a la base de datos configurada.
* [db:seed](db_seed.md) - Ejecuta los scripts de poblado inicial (seeders).

### 🛠️ Utilidades y Testing
Herramientas de diagnóstico y control de calidad.

* [route:list](route_list.md) - Analiza y lista las rutas disponibles en la aplicación.
* [test](test.md) - Dispara la suite de pruebas automatizadas con Pest PHP.
