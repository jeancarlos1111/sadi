<?php
$content = file_get_contents('database/seeders/CatalogosBasicosSeeder.php');

$search = "INSERT INTO usuario_rol (id_usuario, id_rol) VALUES (1, ?)";

$insert = <<<PHP
        // ── RBAC: Crear usuario de prueba ANALISTA_PRESUPUESTO ────────────────
        \$idAnalistaP = \$getRolId('ANALISTA_PRESUPUESTO');
        if (\$idAnalistaP) {
            \$stmt = \$this->pdo->prepare("
                INSERT INTO usuario (id_usuario, usuario, contrasenya, cedula_personal)
                VALUES (2, 'ANALISTA', :hash, NULL)
                ON CONFLICT (id_usuario) DO UPDATE SET contrasenya = EXCLUDED.contrasenya
            ");
            \$stmt->execute(['hash' => \$hashAdmin]);
            
            \$this->pdo->prepare("
                INSERT INTO usuario_rol (id_usuario, id_rol) VALUES (2, ?)
                ON CONFLICT DO NOTHING
            ")->execute([\$idAnalistaP]);
        }
PHP;

$content = str_replace($search, $search . "\n            \")->execute([\$idAdmin]);\n        }\n\n" . $insert, $content);
file_put_contents('database/seeders/CatalogosBasicosSeeder.php', $content);
echo "Added Analista user\n";
