<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;
use App\Repositories\PermisoRepository;
use App\Repositories\RolRepository;
use App\Repositories\UsuarioRepository;
use App\Repositories\InstitucionRepository;
use App\Models\Rol;
use App\Models\Institucion;

class AdminController extends BaseController
{
    public function __construct(
        private readonly UsuarioRepository $usuarioRepo,
        private readonly RolRepository     $rolRepo,
        private readonly PermisoRepository $permisoRepo,
        private readonly InstitucionRepository $institucionRepo,
    ) {
    }

    // ══════════════════════════════════════════════════════════════════════════
    // USUARIOS
    // ══════════════════════════════════════════════════════════════════════════

    public function usuarios(): void
    {
        $this->requirePermiso('admin.usuarios.ver');

        $search  = trim($_GET['search'] ?? '');
        $page    = (int)($_GET['page'] ?? 1);
        
        $paginator = $this->usuarioRepo->paginate($search, $page, 15);
        $usuarios  = $paginator['data'];

        // Añadir roles a cada usuario
        foreach ($usuarios as &$row) {
            $row['roles'] = $this->usuarioRepo->getRoles($row['entity']->id);
        }

        $this->renderView('admin/usuarios/index', [
            'titulo'    => 'Gestión de Usuarios',
            'usuarios'  => $usuarios,
            'search'    => $search,
            'paginator' => $paginator,
            'canCreate' => Gate::allows('admin.usuarios.crear'),
            'canEdit'   => Gate::allows('admin.usuarios.editar'),
            'canDelete' => Gate::allows('admin.usuarios.eliminar'),
        ]);
    }

    public function usuariosEditar(): void
    {
        $this->requirePermiso('admin.usuarios.editar');

        $id    = (int)($_GET['id'] ?? 0);
        $roles = $this->rolRepo->all();
        $error = null;

        $usuario      = $id ? $this->usuarioRepo->find($id) : null;
        $rolesUsuario = $id ? array_map(fn ($r) => $r->id, $this->usuarioRepo->getRoles($id)) : [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data    = $_POST;
            $nuevosRoles = array_map('intval', $_POST['roles'] ?? []);

            try {
                if ($id) {
                    $saveData = ['id' => $id, 'usuario' => trim($data['usuario'])];
                    if (!empty($data['password'])) {
                        $saveData['password'] = $data['password'];
                    }
                    $this->usuarioRepo->save($saveData);
                } else {
                    if (empty($data['password'])) {
                        throw new \InvalidArgumentException('La contraseña es obligatoria al crear un usuario.');
                    }
                    $this->usuarioRepo->save([
                        'usuario'  => trim($data['usuario']),
                        'password' => $data['password'],
                    ]);
                    // Obtener el nuevo usuario para el sync de roles
                    $nuevo = $this->usuarioRepo->findByUsername(trim($data['usuario']));
                    $id    = $nuevo?->id ?? 0;
                }

                if ($id) {
                    $this->usuarioRepo->syncRoles($id, $nuevosRoles);
                }

                header('Location: ?route=admin/usuarios&saved=1');
                exit;
            } catch (\InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
        }

        $this->renderView('admin/usuarios/editar', [
            'titulo'       => $id ? 'Editar Usuario' : 'Nuevo Usuario',
            'usuario'      => $usuario,
            'roles'        => $roles,
            'rolesUsuario' => $rolesUsuario,
            'error'        => $error,
        ]);
    }

    public function usuariosEliminar(): void
    {
        $this->requirePermiso('admin.usuarios.eliminar');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 1) { // Proteger al ADMINISTRADOR (id=1)
                $this->usuarioRepo->delete($id);
            }
        }

        header('Location: ?route=admin/usuarios');
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ROLES
    // ══════════════════════════════════════════════════════════════════════════

    public function roles(): void
    {
        $this->requirePermiso('admin.roles.ver');

        $search  = trim($_GET['search'] ?? '');
        $page    = (int)($_GET['page'] ?? 1);
        
        $paginator = $this->rolRepo->paginate($search, $page, 15);
        $roles     = $paginator['data'];

        // Añadir conteo de permisos por rol
        $rolesConPermisos = array_map(function ($rol) {
            return [
                'rol'       => $rol,
                'permisos'  => count($this->rolRepo->getPermisos($rol->id)),
            ];
        }, $roles);

        $this->renderView('admin/roles/index', [
            'titulo'           => 'Gestión de Roles',
            'rolesConPermisos' => $rolesConPermisos,
            'search'           => $search,
            'paginator'        => $paginator,
            'canCreate'        => Gate::allows('admin.roles.crear'),
            'canEdit'          => Gate::allows('admin.roles.editar'),
            'canDelete'        => Gate::allows('admin.roles.eliminar'),
        ]);
    }

    public function rolesVer(): void
    {
        $this->requirePermiso('admin.roles.ver');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: ?route=admin/roles');
            exit;
        }

        $rol             = $this->rolRepo->find($id);
        $permisosMarca   = array_map(fn ($p) => $p->id, $this->rolRepo->getPermisos($id));
        $todosLosPermisos = $this->permisoRepo->allGroupedByModuloSeccion();

        $this->renderView('admin/roles/ver', [
            'titulo'           => 'Detalles del Rol',
            'rol'              => $rol,
            'todosLosPermisos' => $todosLosPermisos,
            'permisosMarca'    => $permisosMarca,
        ]);
    }

    public function rolesEditar(): void
    {
        $this->requirePermiso('admin.roles.editar');

        $id    = (int)($_GET['id'] ?? 0);
        $error = null;

        $rol             = $id ? $this->rolRepo->find($id) : null;
        $permisosMarca   = $id ? array_map(fn ($p) => $p->id, $this->rolRepo->getPermisos($id)) : [];
        $todosLosPermisos = $this->permisoRepo->allGroupedByModuloSeccion();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre      = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $idsPermisos = array_map('intval', $_POST['permisos'] ?? []);

            try {
                if (empty($nombre)) {
                    throw new \InvalidArgumentException('El nombre del rol es obligatorio.');
                }

                $rolModel = Rol::fromArray([
                    'id'          => $id ?: null,
                    'nombre'      => strtoupper($nombre),
                    'descripcion' => $descripcion,
                ]);

                $this->rolRepo->save($rolModel);

                if (!$id) {
                    // Obtener el ID del nuevo rol
                    $stmt = $this->rolRepo->getPdo()->prepare("SELECT id_rol FROM rol WHERE nombre = ?");
                    $stmt->execute([strtoupper($nombre)]);
                    $id = (int)$stmt->fetchColumn();
                }

                $this->rolRepo->syncPermisos($id, $idsPermisos);

                header('Location: ?route=admin/roles&saved=1');
                exit;
            } catch (\InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
        }

        $this->renderView('admin/roles/editar', [
            'titulo'           => $id ? 'Editar Rol' : 'Nuevo Rol',
            'rol'              => $rol,
            'todosLosPermisos' => $todosLosPermisos,
            'permisosMarca'    => $permisosMarca,
            'error'            => $error,
        ]);
    }

    public function rolesEliminar(): void
    {
        $this->requirePermiso('admin.roles.eliminar');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $this->rolRepo->delete($id);
            }
        }

        header('Location: ?route=admin/roles');
        exit;
    }
    // ══════════════════════════════════════════════════════════════════════════
    // INSTITUCIÓN
    // ══════════════════════════════════════════════════════════════════════════

    public function institucion(): void
    {
        $this->requirePermiso('admin.usuarios.ver'); // Podemos reusar un permiso de admin o crear uno nuevo, por ahora usamos el base

        $config = $this->institucionRepo->getConfig();
        $error = null;
        $success = isset($_GET['saved']);

        $this->renderView('admin/institucion/index', [
            'titulo'  => 'Datos de la Institución',
            'config'  => $config,
            'error'   => $error,
            'success' => $success
        ]);
    }

    public function institucionEditar(): void
    {
        $this->requirePermiso('admin.usuarios.editar'); // Permiso para editar

        $config = $this->institucionRepo->getConfig();
        $error = null;

        $this->renderView('admin/institucion/editar', [
            'titulo'  => 'Editar Datos de la Institución',
            'config'  => $config,
            'error'   => $error
        ]);
    }

    public function institucionGuardar(): void
    {
        $this->requirePermiso('admin.usuarios.editar');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $config = $this->institucionRepo->getConfig();

            // Manejo de la subida del Logo
            $logoPath = $config->logo_path;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = 'logo_' . time() . '_' . basename($_FILES['logo']['name']);
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                    $logoPath = '/uploads/' . $fileName;
                }
            }

            $nuevaConfig = Institucion::fromArray([
                'id_institucion' => 1,
                'nombre' => $_POST['nombre'] ?? '',
                'rif' => $_POST['rif'] ?? '',
                'direccion' => $_POST['direccion'] ?? '',
                'telefono' => $_POST['telefono'] ?? null,
                'correo' => $_POST['correo'] ?? null,
                'maxima_autoridad' => $_POST['maxima_autoridad'] ?? null,
                'cargo_autoridad' => $_POST['cargo_autoridad'] ?? null,
                'base_legal' => $_POST['base_legal'] ?? null,
                'codigo_onapre' => $_POST['codigo_onapre'] ?? null,
                'logo_path' => $logoPath
            ]);

            $this->institucionRepo->saveConfig($nuevaConfig);

            header('Location: ?route=admin/institucion&saved=1');
            exit;
        }

        header('Location: ?route=admin/institucion');
        exit;
    }
}
