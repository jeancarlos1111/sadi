<?php
$content = file_get_contents('database/schema.sql');
$statements = preg_split('/;\s*$/m', $content);
$tables = 0;
$inserts = 0;
foreach ($statements as $stmt) {
    if (stripos(trim($stmt), 'CREATE TABLE') !== false) $tables++;
    if (stripos(trim($stmt), 'INSERT INTO') !== false) $inserts++;
}
echo "Tables: $tables, Inserts: $inserts\n";
