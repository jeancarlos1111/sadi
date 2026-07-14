<?php

$dir = 'database/migrations';
$files = glob("$dir/*.sql");

$tables = [];

// 1. Gather all tables and their primary keys
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (preg_match('/CREATE TABLE IF NOT EXISTS ([a-z0-9_]+)/i', $content, $m1)) {
        $table = $m1[1];
        if (preg_match('/([a-z0-9_]+)\s+(?:SERIAL|INTEGER)\s+PRIMARY KEY/i', $content, $m2)) {
            $pk = $m2[1];
            $tables[$table] = $pk;
        } else {
            // Default ID fallback
            $tables[$table] = 'id';
        }
    }
}

// 2. Identify potential foreign keys
$fks = [];
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (preg_match('/CREATE TABLE IF NOT EXISTS ([a-z0-9_]+)/i', $content, $m1)) {
        $table = $m1[1];
        preg_match_all('/^\s+([a-z0-9_]+)\s+INTEGER/mi', $content, $matches);
        
        foreach ($matches[1] as $col) {
            // If the column matches any table's PK (like id_tipo_organizacion -> tipo_organizacion.id_tipo_organizacion)
            $foundMatch = false;
            foreach ($tables as $refTable => $refPk) {
                if ($col === $refPk && $table !== $refTable) {
                    $fks[$table][] = [
                        'col' => $col,
                        'ref_table' => $refTable,
                        'ref_col' => $refPk
                    ];
                    $foundMatch = true;
                    break;
                }
            }
            if (!$foundMatch) {
                // Try guessing by stripping 'id_' or similar conventions
                // Just log it
                $fks[$table][] = [
                    'col' => $col,
                    'ref_table' => '???',
                    'ref_col' => '???'
                ];
            }
        }
    }
}

print_r($fks);
