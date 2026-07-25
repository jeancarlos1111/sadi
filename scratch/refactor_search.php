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
    
    // Pattern to match the search form in the mb-3 div
    $pattern = '/<div class="mb-3">\s*<form method="GET" action="" class="form-inline">\s*<input type="hidden" name="route" value="([^"]+)">\s*<div class="input-group input-group-sm">\s*<input type="text" name="search" class="form-control" placeholder="([^"]+)" value="<\?= htmlspecialchars\(\$search \?\? \'\'\) \?>"[^>]*>\s*<span class="input-group-append">\s*<button type="submit" class="btn btn-primary btn-flat"><i class="fas fa-search"><\/i><\/button>\s*<\/span>\s*<\/div>\s*<a href="\?route=\1" class="btn btn-default btn-sm ml-2">Limpiar<\/a>\s*<\/form>\s*<\/div>/is';
    
    $newContent = preg_replace_callback($pattern, function($matches) {
        $route = $matches[1];
        $placeholder = $matches[2];
        
        $props = [];
        $props[] = "'route' => '{$route}'";
        $props[] = "'searchValue' => \$search ?? ''";
        if ($placeholder !== 'Buscar...') {
            $props[] = "'placeholder' => '{$placeholder}'";
        }
        
        return "<?= render_component('search_filter', [\n        " . implode(",\n        ", $props) . "\n      ]) ?>";
    }, $content);
    
    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        $count++;
    }
}
echo "Updated $count files with search filters.\n";
