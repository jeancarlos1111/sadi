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
    
    // Pattern to match the card header with a "Nuevo" button
    $pattern = '/<div class="card-header">\s*<h3 class="card-title[^>]*>(?:<i class="([^"]+) mr-1"><\/i>\s*)?<\?= htmlspecialchars\(\$titulo\) \?><\/h3>\s*<div class="card-tools">\s*<a href="\?route=([^"]+)" class="btn btn-(?:success|primary)[^"]*">\s*<i class="fas fa-plus"><\/i>\s*([^<]+)\s*<\/a>\s*<\/div>\s*<\/div>/is';
    
    $newContent = preg_replace_callback($pattern, function($matches) {
        $icon = trim($matches[1]);
        $newUrl = $matches[2];
        $newText = trim($matches[3]);
        
        $props = [];
        $props[] = "'title' => \$titulo";
        if ($icon) {
            $props[] = "'icon' => '{$icon}'";
        }
        $props[] = "'newUrl' => '?route={$newUrl}'";
        $props[] = "'newText' => '{$newText}'";
        
        return "<?= render_component('page_header', [\n        " . implode(",\n        ", $props) . "\n      ]) ?>";
    }, $content);
    
    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        $count++;
    }
}
echo "Updated $count files with page headers.\n";
