---
description: Reglas y convenciones del proyecto SADI (Migración desde SIGAFS)
---

# Reglas del Proyecto SADI

## Contexto del Proyecto

Estamos **migrando** el sistema **SIGAFS** (Sistema Integrado de Gestión Administrativa y Financiera del Sector Público, ubicado en `~/Descargas/sigafs`) al sistema **SADI** (Sistema Administrativo Integrado, ubicado en `~/Descargas/sadi`).

- **Sistema origen:** SIGAFS — aplicación PHP legacy con PostgreSQL, sin namespaces ni patrón MVC formal
- **Sistema destino:** SADI — PHP 8.1+, SQLite, arquitectura MVC estricta con PSR
- **Objetivo:** Migración fiel de funcionalidad + mejoras de UX donde sea posible (ej. fusionar pasos redundantes de SIGAFS en uno solo en SADI)

---

## Arquitectura de SADI (Patrón MVC)

```
sadi/
├── public/index.php           # Router automático por convención de nombres
├── src/
│   ├── Controllers/           # Lógica de negocio y flujo de pantallas
│   ├── Models/                # Entidades/DTOs del dominio
│   ├── Repositories/          # Acceso a datos (extienden Repository base)
│   ├── Services/              # Servicios reutilizables (PDF, fórmulas, etc.)
│   └── Libs/                  # Librerías externas (fpdf.php, etc.)
├── views/
│   ├── layouts/main.phtml     # Layout principal de la aplicación
│   └── [modulo]/              # Vistas de cada módulo
└── database/
    ├── schema.sql             # Schema completo (SQLite)
    └── sadi.sqlite            # Base de datos
```

### Router Automático

El router en `public/index.php` convierte la URL `?route=nombre_modulo/accion` en:

- `nombre_modulo` → `NombreModuloController` (ucwords + strip underscores)
- `accion` → método de la clase

Ejemplos:

- `?route=apertura_cuentas/index` → `AperturaCuentasController::index()`
- `?route=comprobante_presupuesto/guardar` → `ComprobantePresupuestoController::guardar()`

**No hay archivo de rutas explícito** — el mapeo es 100% por convención.

---

## Estándares PSR Aplicados

| PSR | Descripción | Cómo se aplica en SADI |
|-----|-------------|------------------------|
| **PSR-1** | Basic Coding Standard | Archivos PHP con `<?php` puro, un namespace por archivo |
| **PSR-4** | Autoloading | `App\Controllers`, `App\Models`, `App\Repositories`, `App\Services` |
| **PSR-12** | Extended Coding Style | Indentación 4 espacios, llaves en línea nueva para clases/métodos |

### Convenciones adicionales

- **Tipos estrictos:** `declare(strict_types=1);` al inicio de cada archivo PHP
- **Tipos en firmas:** Todos los métodos deben tener tipado explícito de parámetros y retorno
- **Null safety:** Usar `?int`, `?string` para opcionales; nunca `mixed` sin justificación
- **Return types:** Siempre declarar el tipo de retorno (`void`, `array`, `string`, etc.)

---

## Patrón de Controllers

Todos los Controllers extienden `HomeController` (que extiende `BaseController`):

```php
<?php
declare(strict_types=1);
namespace App\Controllers;

class MiModuloController extends HomeController
{
    private MiModuloRepository $repo;

    public function __construct()
    {
        parent::__construct(); // Verifica sesión activa
        $this->repo = new MiModuloRepository();
    }

    public function index(): void
    {
        $this->renderView('mi_modulo/index', [
            'titulo' => 'Título de la Pantalla',
        ]);
    }
}
```

- **Nunca** usar inyección de dependencias por constructor (el DI container tiene bugs con PDO)
- Instanciar repositorios directamente en el constructor
- Usar `$_SESSION['error']` y `$_SESSION['success']` para mensajes entre redirecciones
- Limpiar mensajes de sesión al inicio del método que los muestra

---

## Patrón de Repositories

Todos los Repositories extienden `App\Database\Repository`:

```php
<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Database\Repository;
use PDO;

class MiEntidadRepository extends Repository
{
    protected function getTable(): string
    {
        return 'nombre_tabla';
    }

    public function all(): array { ... }
    public function findById(int $id): ?MiEntidad { ... }
    public function save(MiEntidad $item): int { ... }
    public function delete(int $id): bool { ... }
}
```

- Usar `$this->getPdo()` para acceder a la instancia PDO (nunca `new PDO()` directamente)
- Siempre usar **prepared statements** con parámetros nombrados (`:param`)
- El soft delete usa columna `eliminado = 1` en lugar de DELETE físico
- SQLite: usar `ON CONFLICT(...) DO UPDATE SET` para UPSERT

---

## Patrón de Modelos

Los Models son DTOs simples (sin lógica de negocio compleja):

```php
<?php
declare(strict_types=1);
namespace App\Models;

class MiEntidad
{
    public ?int $id;

    public function __construct(
        public string $campo1,
        public int    $campo2,
        ?int $id = null
    ) {
        $this->id = $id;
    }
}
```

---

## Patrón de Vistas

Las vistas están en `views/[modulo]/[accion].phtml`. El layout `main.phtml` las incluye.

### Estilo UI de SADI (NO usar Bootstrap)

SADI usa **HTML nativo con estilos inline**. Patrón de las vistas:

```html
<!-- Listados -->
<h2><?= htmlspecialchars($titulo) ?></h2>
<table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse;">
    <thead style="background-color:#0056b3; color:white;">
        <tr><th>...</th></tr>
    </thead>
    <tbody>...</tbody>
</table>

<!-- Formularios -->
<fieldset style="margin-bottom:15px; padding:15px; border:1px solid #ccc;">
    <legend style="font-weight:bold;">Datos del Registro</legend>
    ...
</fieldset>
```

**Reglas de UI:**

- Color principal: `#0056b3` (azul SADI)
- Color éxito: `#28a745` / `#198754`
- Color peligro: `#dc3545`
- Siempre usar `htmlspecialchars()` al imprimir variables en vistas
- Formularios siempre con `method="GET"` usando `<input type="hidden" name="route" value="...">` para preservar la ruta
- Formularios de creación/edición: `method="POST" action="?route=modulo/guardar"`

---

## Servicios PDF

SADI usa **FPDF** via `App\Services\PdfService` (que extiende FPDF):

```php
use App\Services\PdfService; // O crear un servicio específico extendiendo FPDF directamente

// IMPORTANTE: Antes de hacer Output(), limpiar buffers activos:
while (ob_get_level() > 0) {
    ob_end_clean();
}
$pdf->Output('I', 'nombre_archivo.pdf'); // 'I' = inline, 'D' = descarga
exit;
```

- `mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8')` para caracteres especiales en FPDF
- Orientación landscape (`'L'`) para reportes con muchas columnas

---

## Base de Datos

- **Motor objetivo (producción):** **PostgreSQL** — igual que SIGAFS
- **Motor desarrollo rápido:** SQLite 3 (`database/sadi.sqlite`) — solo para pruebas locales sin servidor
- **Schema:** `database/schema.sql` — mantener compatible con ambos motores donde sea posible
- **Aplicar en SQLite (dev):** `sqlite3 database/sadi.sqlite < database/schema.sql`
- **Soft delete:** Todas las tablas tienen columna `eliminado BOOLEAN DEFAULT 0`
- **Timestamps:** Usar `TEXT` con formato `'Y-m-d'` (SQLite) / `DATE` (PostgreSQL)

### Consideraciones de compatibilidad SQLite → PostgreSQL

| SQLite | PostgreSQL equivalente |
|--------|----------------------|
| `INTEGER PRIMARY KEY AUTOINCREMENT` | `SERIAL PRIMARY KEY` o `BIGSERIAL` |
| `ON CONFLICT(...) DO UPDATE SET` | `ON CONFLICT(...) DO UPDATE SET` (igual, PostgreSQL 9.5+) |
| `strftime('%Y', fecha)` | `EXTRACT(YEAR FROM fecha::date)` o `DATE_PART('year', fecha)` |
| `date('now')` | `CURRENT_DATE` |
| `BOOLEAN DEFAULT 0` | `BOOLEAN DEFAULT FALSE` |

> **Nota:** Al escribir SQL en Repositories, preferir sintaxis compatible con PostgreSQL. Evitar funciones exclusivas de SQLite.

---

## Flujo de Migración desde SIGAFS

Cuando migremos funcionalidad de SIGAFS:

1. **Analizar** la función en `~/Descargas/sigafs/modulo_*/class_Fachada*.php`
2. **Identificar** las tablas PostgreSQL usadas (inferir de las consultas SQL en el código PHP)
3. **Crear** tabla equivalente en `database/schema.sql` con convenciones SQLite
4. **Crear** Model → Repository → Controller → Views siguiendo los patrones SADI
5. **Mejorar UX** donde SIGAFS tenía pasos innecesariamente separados (fusionar cuando tenga sentido)
6. **Verificar sintaxis** siempre con `php -l archivo.php` antes de entregar

### Módulos migrados hasta ahora

- ✅ Fase 1-4: Personal, Nómina, Contabilidad, Almacén, Bancos
- ✅ Fase 5.1: Catálogos de Presupuesto (Proyectos, Acciones Centralizadas, Plan Único de Cuentas)
- ✅ Fase 5.2: Procesos Presupuestarios (Apertura de Cuentas, CG/CA/TR, Períodos, Disponibilidad, Reformulación)
- ✅ Fase 5.3: Reportes (Mayor Analítico PDF)

---

## Convenciones de Nombres

| Elemento | Convención | Ejemplo |
|----------|-----------|---------|
| Clases | PascalCase | `AperturaCuentasController` |
| Métodos | camelCase | `getCuentasAperturables()` |
| Variables PHP | camelCase | `$id_estruc` (excepción: variables de BD usan snake_case) |
| Tablas BD | snake_case | `comprobante_presupuestario` |
| Columnas BD | snake_case | `id_estruc_presupuestaria` |
| Rutas URL | snake_case | `?route=apertura_cuentas/index` |
| Vistas | snake_case directorio/archivo | `views/apertura_cuentas/index.phtml` |
