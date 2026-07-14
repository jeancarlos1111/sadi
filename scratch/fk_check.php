<?php
$dir = 'database/migrations';
$files = glob("$dir/*.sql");
$tableOrder = [];
foreach ($files as $i => $file) {
    if (preg_match('/CREATE TABLE IF NOT EXISTS ([a-z0-9_]+)/i', file_get_contents($file), $m)) {
        $tableOrder[$m[1]] = $i;
    }
}

require_once 'scratch/fk_finder.php';
// Re-run fk_finder logic to get $fks, but I'll just write it manually here.
