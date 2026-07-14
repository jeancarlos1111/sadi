<?php

$dir = 'database/migrations';
$files = glob("$dir/*.sql");
sort($files);

$tables = [];

// Pass 1: Identify all tables and their exact primary keys
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (preg_match('/CREATE TABLE IF NOT EXISTS ([a-z0-9_]+)/i', $content, $m)) {
        $table = $m[1];
        if (preg_match('/([a-z0-9_]+)\s+(?:SERIAL|INTEGER)\s+PRIMARY KEY/i', $content, $m2)) {
            $pk = trim($m2[1]);
            $tables[$table] = $pk;
        }
    }
}

// Pass 2: Process files and inject foreign keys
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Skip if already has FOREIGN KEY definitions
    if (stripos($content, 'FOREIGN KEY') !== false) {
        continue;
    }

    if (preg_match('/CREATE TABLE IF NOT EXISTS ([a-z0-9_]+)\s*\((.*)\);/is', $content, $m)) {
        $tableName = $m[1];
        $body = $m[2];
        
        $lines = explode("\n", $body);
        $fks = [];
        
        foreach ($lines as $line) {
            // Find integer columns that might be FKs
            if (preg_match('/^\s*([a-z0-9_]+)\s+(?:INTEGER|TEXT)/i', $line, $lm)) {
                $col = $lm[1];
                
                // Match exact PK of another table
                foreach ($tables as $refTable => $refPk) {
                    if ($col === $refPk && $tableName !== $refTable) {
                        // Avoid forward references by checking if refTable exists in files array before current file
                        $refIndex = array_search(
                            current(array_filter($files, fn($f) => stripos($f, "create_table_{$refTable}.sql") !== false)),
                            $files
                        );
                        $currentIndex = array_search($file, $files);
                        
                        if ($refIndex !== false && $refIndex < $currentIndex) {
                            $fks[] = "    FOREIGN KEY ($col) REFERENCES $refTable($refPk) ON DELETE SET NULL";
                        }
                    }
                }
            }
        }
        
        if (!empty($fks)) {
            // Remove trailing comma from last column if necessary
            $body = rtrim($body);
            if (substr($body, -1) === ',') {
                $body = substr($body, 0, -1); // Just in case, but usually there's no trailing comma
            }
            // Actually, find the last line and make sure it has a comma
            $lines = explode("\n", rtrim($body));
            if (!empty($lines)) {
                $lastLine = array_pop($lines);
                if (trim($lastLine) !== '' && substr(trim($lastLine), -1) !== ',') {
                    $lastLine .= ',';
                }
                $lines[] = $lastLine;
            }
            
            $newBody = implode("\n", $lines) . "\n" . implode(",\n", $fks) . "\n";
            $newTable = "CREATE TABLE IF NOT EXISTS $tableName (\n$newBody);";
            
            $content = str_replace($m[0], $newTable, $content);
            file_put_contents($file, $content);
            echo "Injected FKs in $tableName\n";
        }
    }
}
