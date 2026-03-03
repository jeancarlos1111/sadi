<?php

namespace App\Controllers;

use App\Models\CuentaBancaria;
use App\Repositories\BancoRepository;
use App\Repositories\CuentaBancariaRepository;
use Exception;

class CuentasBancariasController extends BaseController
{
    private CuentaBancariaRepository $repo;
    private BancoRepository $bancoRepo;

    public function __construct(CuentaBancariaRepository $repo, BancoRepository $bancoRepo)
    {
        $this->repo = $repo;
        $this->bancoRepo = $bancoRepo;
        if (!isset($_SESSION['usuario'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        $cuentas = $this->repo->all();
        $this->renderView('banco/catalogos/cuentas/index', [
            'titulo' => 'Cuentas Bancarias',
            'cuentas' => $cuentas,
            'success' => $_GET['success'] ?? null,
            'error'   => $_GET['error'] ?? null,
        ]);
    }

    public function form(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $cuenta = $id ? $this->repo->find($id) : null;
        $bancos = $this->bancoRepo->all();

        $this->renderView('banco/catalogos/cuentas/form', [
            'titulo' => $id ? 'Editar Cuenta' : 'Nueva Cuenta',
            'cuenta' => $cuenta,
            'bancos' => $bancos,
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=cuentas_bancarias/index');
            exit;
        }

        $id     = (int)($_POST['id'] ?? 0);
        $idBanco = (int)($_POST['id_banco'] ?? 0);
        $numero = trim($_POST['numero_cta_bancaria'] ?? '');

        if (!$idBanco || empty($numero)) {
            header("Location: ?route=cuentas_bancarias/form&id=$id&error=Todos los campos son obligatorios");
            exit;
        }

        try {
            $cta = new CuentaBancaria($id, $idBanco, $numero, '');
            if ($this->repo->save($cta)) {
                header('Location: ?route=cuentas_bancarias/index&success=Cuenta guardada correctamente');
            } else {
                throw new Exception("No se pudo guardar la cuenta");
            }
        } catch (Exception $e) {
            header("Location: ?route=cuentas_bancarias/form&id=$id&error=" . urlencode($e->getMessage()));
        }
    }

    public function eliminar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?route=cuentas_bancarias/index');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($this->repo->delete($id)) {
            header('Location: ?route=cuentas_bancarias/index&success=Cuenta eliminada');
        } else {
            header('Location: ?route=cuentas_bancarias/index&error=Error al eliminar la cuenta');
        }
    }
}
