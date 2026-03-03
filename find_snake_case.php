<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('src/'));
$results = [];
foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        $tokens = token_get_all(file_get_contents($file->getPathname()));
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION) {
                // skip whitespace to next string
                $j = $i + 1;
                while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) { $j++; }
                if ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $funcName = $tokens[$j][1];
                    if (strpos($funcName, '_') !== false && strpos($funcName, '__') !== 0) { // ignoring magic methods
                        $results[] = $file->getPathname() . " -> " . $funcName;
                    }
                }
            }
        }
    }
}
print_r($results);
