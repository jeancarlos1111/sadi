<?php
$files = [];
$dir = new RecursiveDirectoryIterator('views/');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), 'index.phtml')) {
        $files[] = $file->getPathname();
    }
}

// Regex to capture Edit Link and Delete Form exactly, allowing variable whitespace.
// Captures:
// 1: The route base (e.g. "tipos_operacion_presupuesto")
// 2: The item variable (e.g. "$item->id")
$pattern = '/<a href="\?route=([a-zA-Z0-9_\/]+)\/form&id=<\?= htmlspecialchars\(\(string\)(\$item->[a-zA-Z0-9_]+)\) \?>"[^>]*>\s*<i class="[^"]*fa-edit[^"]*"><\/i>\s*<\/a>\s*<form method="POST" action="\?route=\1\/(?:eliminar|delete)"[^>]*>\s*<input type="hidden" name="([a-zA-Z0-9_]+)" value="<\?= htmlspecialchars\(\(string\)\2\) \?>">\s*<button[^>]*>\s*<i class="[^"]*fa-trash[^"]*"><\/i>\s*<\/button>\s*<\/form>/is';

$count = 0;
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    $newContent = preg_replace_callback($pattern, function($matches) {
        $route = $matches[1];
        $itemId = $matches[2];
        $idField = $matches[3];
        
        $deleteAction = strpos($matches[0], '/delete"') !== false ? 'delete' : 'eliminar';
        
        $props = [];
        $props[] = "'editUrl' => '?route={$route}/form&id=' . {$itemId}";
        $props[] = "'deleteUrl' => '?route={$route}/{$deleteAction}'";
        $props[] = "'deleteId' => {$itemId}";
        if ($idField !== 'id') {
            $props[] = "'deleteIdField' => '{$idField}'";
        }
        
        return "<?= render_component('table_actions', [\n                " . implode(",\n                ", $props) . "\n            ]) ?>";
    }, $content);
    
    // Also try to match the case with a "View" button
    $patternWithView = '/<a href="\?route=([a-zA-Z0-9_\/]+)\/ver&id=<\?= htmlspecialchars\(\(string\)(\$item->[a-zA-Z0-9_]+)\) \?>"[^>]*>\s*<i class="[^"]*fa-eye[^"]*"><\/i>\s*<\/a>\s*<a href="\?route=\1\/form&id=<\?= htmlspecialchars\(\(string\)\2\) \?>"[^>]*>\s*<i class="[^"]*fa-edit[^"]*"><\/i>\s*<\/a>\s*<form method="POST" action="\?route=\1\/(?:eliminar|delete)"[^>]*>\s*<input type="hidden" name="([a-zA-Z0-9_]+)" value="<\?= htmlspecialchars\(\(string\)\2\) \?>">\s*<button[^>]*>\s*<i class="[^"]*fa-trash[^"]*"><\/i>\s*<\/button>\s*<\/form>/is';
    
    $newContent = preg_replace_callback($patternWithView, function($matches) {
        $route = $matches[1];
        $itemId = $matches[2];
        $idField = $matches[3];
        $deleteAction = strpos($matches[0], '/delete"') !== false ? 'delete' : 'eliminar';
        
        $props = [];
        $props[] = "'viewUrl' => '?route={$route}/ver&id=' . {$itemId}";
        $props[] = "'editUrl' => '?route={$route}/form&id=' . {$itemId}";
        $props[] = "'deleteUrl' => '?route={$route}/{$deleteAction}'";
        $props[] = "'deleteId' => {$itemId}";
        if ($idField !== 'id') {
            $props[] = "'deleteIdField' => '{$idField}'";
        }
        
        return "<?= render_component('table_actions', [\n                " . implode(",\n                ", $props) . "\n            ]) ?>";
    }, $newContent);

    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        $count++;
    }
}
echo "Updated $count files.";
