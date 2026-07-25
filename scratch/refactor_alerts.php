<?php
$files = [];
$dir = new RecursiveDirectoryIterator('views/');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.phtml')) {
        $files[] = $file->getPathname();
    }
}

$count = 0;
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Replace Error Alerts
    $patternError = '/<div class="alert alert-danger[^>]*>.*?<\?= htmlspecialchars\(\$error(?:\s*\?\?\s*\'\')?\) \?>\s*<\/div>/is';
    $newContent = preg_replace($patternError, '<?= render_component(\'alert\', [\'type\' => \'danger\', \'message\' => $error]) ?>', $content);
    
    // Replace Success Alerts
    $patternSuccess = '/<div class="alert alert-success[^>]*>.*?<\?= htmlspecialchars\(\$success(?:\s*\?\?\s*\'\')?\) \?>\s*<\/div>/is';
    $newContent = preg_replace($patternSuccess, '<?= render_component(\'alert\', [\'type\' => \'success\', \'message\' => $success]) ?>', $newContent);
    
    // Replace Info Alerts
    $patternInfo = '/<div class="alert alert-info[^>]*>.*?<\?= htmlspecialchars\(\$info(?:\s*\?\?\s*\'\')?\) \?>\s*<\/div>/is';
    $newContent = preg_replace($patternInfo, '<?= render_component(\'alert\', [\'type\' => \'info\', \'message\' => $info]) ?>', $newContent);

    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        $count++;
    }
}
echo "Updated $count files with alerts.\n";
