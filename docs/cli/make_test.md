# Comando: `make:test`

Genera un archivo base para pruebas automatizadas utilizando el framework Pest PHP. 

El archivo generado incluye automáticamente el `DatabaseSeeder::cleanBudgetTables()` en su hook `beforeEach()`, asegurando que la base de datos de pruebas (PostgreSQL en entorno aislado) se vacíe y se reinicie antes de cada prueba, garantizando determinismo.

## Ejemplos de Uso

### 1. Crear un Feature Test (Pruebas de Integración/Controladores)
```bash
php sadi make:test Formulacion
```
*Genera el archivo `tests/Feature/FormulacionTest.php`.*

### 2. Crear un Unit Test (Pruebas Aisladas/Modelos)
```bash
php sadi make:test CalculoImpuesto --unit
```
*Genera el archivo `tests/Unit/CalculoImpuestoTest.php`.*

[⬅ Volver al Índice](README.md)
