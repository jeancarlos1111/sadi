<?php

declare(strict_types=1);

$schemaPath = 'database/schema.sql';
$seedFile = 'database/seed_maestros.php';

$content = file_get_contents($schemaPath);
$blocks = array_filter(array_map('trim', explode(';', $content)));
$seedInserts = [];

foreach ($blocks as $block) {
    if (empty($block)) continue;
    $block .= ';';
    if (stripos($block, 'INSERT INTO') !== false) {
        $seedInserts[] = trim($block);
    }
}

$seedContent = "<?php\n\n// Archivo autogenerado con datos maestros\n\nuse App\Database\Connection;\n\n\$pdo = Connection::getInstance();\n\ntry {\n    \$pdo->beginTransaction();\n";
foreach ($seedInserts as $i => $insert) {
    $seedContent .= "\n    \$pdo->exec(<<<SQL\n{$insert}\nSQL\n    );\n";
}
$seedContent .= "\n    \$pdo->commit();\n    echo \"Datos maestros insertados correctamente.\\n\";\n} catch (Exception \$e) {\n    \$pdo->rollBack();\n    throw \$e;\n}\n";

file_put_contents($seedFile, $seedContent);
echo "Seed reconstruido.\n";
