# Manual Técnico: Interfaz de Línea de Comandos (CLI) SADI

**Documento elaborado bajo los estándares y lineamientos aplicables al desarrollo de Software Libre en la Administración Pública Nacional (APN) - CONATI / CNTI.**

---

## 1. Introducción

El presente documento describe las características, arquitectura y manual de uso de la Interfaz de Línea de Comandos (CLI) integrada en el Sistema Administrativo De Información (SADI). Esta herramienta fue diseñada para optimizar los tiempos de desarrollo, garantizar la estandarización del código fuente y automatizar tareas repetitivas de mantenimiento y pruebas, cumpliendo con las buenas prácticas de la APN.

## 2. Objetivos de la Herramienta

*   **Estandarización:** Garantizar que todo nuevo componente (Controladores, Modelos, Repositorios) cumpla con el tipado estricto, inmutabilidad (DTOs) y separación de responsabilidades exigidas en la normativa interna del proyecto SADI.
*   **Automatización (Scaffolding):** Reducir el trabajo manual mediante la generación automática de código base ("stubs").
*   **Gestión de Base de Datos:** Centralizar y facilitar la ejecución de migraciones y scripts de inicialización (seeders).
*   **Calidad de Software:** Facilitar la ejecución continua de pruebas automatizadas (Testing) sobre el código fuente.

## 3. Arquitectura del CLI

La herramienta CLI de SADI está inspirada en arquitecturas modernas de consola y se estructura de la siguiente manera:

*   `cli/sadi`: Archivo ejecutable principal (Entry Point). Registra los comandos e invoca el despachador.
*   `cli/src/Application.php`: Gestor central de la consola.
*   `cli/src/Command.php`: Clase abstracta de la cual heredan todos los comandos.
*   `cli/src/Console/`: Clases de soporte para la entrada/salida (I/O) en consola, parseo de argumentos y aplicación de colores ANSI.
*   `cli/src/Commands/`: Directorio modular donde reside la lógica individual de cada comando.
*   `cli/stubs/`: Plantillas predefinidas de código fuente que cumplen con los estándares de seguridad e inmutabilidad.

El binario se encuentra mapeado globalmente dentro del ecosistema del proyecto a través de `composer.json` (`"bin": ["cli/sadi"]`).

## 4. Requisitos y Dependencias

Para la correcta ejecución del CLI, el entorno del servidor o estación de desarrollo debe contar con:
*   **PHP:** Versión 8.4 o superior (Ejecutable CLI disponible en el `$PATH`).
*   **Composer:** Gestor de dependencias de PHP.
*   **PostgreSQL:** Cliente `psql` instalado para la ejecución del comando `db:migrate`.

## 5. Manual de Uso (Comandos Disponibles)

El CLI se invoca desde la raíz del proyecto SADI. A continuación se detallan los comandos disponibles:

### 5.1. Listado y Ayuda General
Muestra el catálogo completo de comandos y la versión de la herramienta.
```bash
php cli/sadi list
```

### 5.2. Generación de Componentes Individuales
Permite la creación de archivos base aislados para el desarrollo de software.

*   **Generar Controlador:**
    ```bash
    php cli/sadi make:controller NombreModulo
    ```
*   **Generar Modelo (Readonly DTO):**
    ```bash
    php cli/sadi make:model Entidad
    ```
*   **Generar Repositorio de Base de Datos:**
    ```bash
    php cli/sadi make:repository Entidad --table=nombre_de_tabla
    ```
*   **Generar Prueba Automatizada (Feature Test):**
    ```bash
    php cli/sadi make:test NombrePrueba
    ```

### 5.3. Generación de Módulos Completos (Scaffolding)
El comando más robusto del sistema. Genera una vertical completa de software ("Sección" o "Módulo") lista para ser programada, incluyendo: Modelo, Repositorio, Controlador, Migración SQL y Vistas (Index/Form).

```bash
php cli/sadi make:section NombreEntidad --table=nombre_tabla --module=ruta_vistas
```
*Parámetros opcionales:*
*   `--table`: Define el nombre físico de la tabla en PostgreSQL. Si se omite, se generará en formato *snake_case*.
*   `--module`: Define el prefijo del directorio dentro de la carpeta `views/` y el prefijo de la URL en el enrutador.

### 5.4. Gestión de Base de Datos
Comandos administrativos para la estructura de datos.

*   **Ejecutar Migraciones:**
    Aplica el archivo estructurado `database/schema.sql` a la base de datos configurada en el archivo `.env`.
    ```bash
    php cli/sadi db:migrate
    ```
    *Nota: Se puede apuntar a entornos distintos usando el parámetro `--env=.env.testing`.*

*   **Ejecutar Sembradores (Seeders):**
    Ejecuta los scripts de inserción de datos maestros y catálogos base ubicados en `database/seed_*.php`.
    ```bash
    php cli/sadi db:seed
    ```

### 5.5. Depuración y Pruebas
*   **Listar Rutas:**
    Analiza el enrutador principal (`public/index.php`) y despliega en consola una tabla con las rutas detectadas y sus respectivos controladores asignados.
    ```bash
    php cli/sadi route:list
    ```
*   **Ejecutar Pruebas Automatizadas:**
    Dispara la suite completa de pruebas bajo el framework Pest PHP.
    ```bash
    php cli/sadi test
    ```
    *Para aislar pruebas:* `php cli/sadi test --filter=NombreDeLaPrueba`

---
*Fin del Documento Técnico.*
