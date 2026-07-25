<?php
$files = [];
$dir = new RecursiveDirectoryIterator('views/');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), 'index.phtml')) {
        $files[] = $file->getPathname();
    }
}

// Regex to capture Edit Link and Delete Form, tolerating different ID access styles, urlencodes, and form styles.
// Captures:
// 1: The route base
// 2: The entire PHP block inside href="...&id=..."
// 3: The ID field name in the delete form
// 4: The entire PHP block inside value="..."
$pattern = '/<a href="\?route=([a-zA-Z0-9_\/]+)\/form&id=(<\?=.*?\?>)"[^>]*>\s*<i class="[^"]*fa-edit[^"]*"><\/i>\s*<\/a>\s*<form method="POST" action="\?route=\1\/(?:eliminar|delete)"[^>]*>\s*<input type="hidden" name="([a-zA-Z0-9_]+)" value="(<\?=.*?\?>)">\s*<button[^>]*>\s*<i class="[^"]*fa-trash[^"]*"><\/i>\s*<\/button>\s*<\/form>/is';

$count = 0;
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    $newContent = preg_replace_callback($pattern, function($matches) {
        $route = $matches[1];
        
        // Extract raw variable name from the PHP blocks
        // E.g., <?= urlencode((string)$c->id_comprobante) ? >  => $c->id_comprobante
        $itemId = '';
        if (preg_match('/\$[a-zA-Z0-9_]+(?:->[a-zA-Z0-9_]+|\[[\'"][a-zA-Z0-9_]+[\'"]\])/', $matches[2], $m)) {
            $itemId = $m[0];
        }
        
        $idField = $matches[3];
        $deleteAction = strpos($matches[0], '/delete"') !== false ? 'delete' : 'eliminar';
        
        if (!$itemId) return $matches[0]; // fail safe
        
        $props = [];
        $props[] = "'editUrl' => '?route={$route}/form&id=' . {$itemId}";
        $props[] = "'deleteUrl' => '?route={$route}/{$deleteAction}'";
        $props[] = "'deleteId' => {$itemId}";
        if ($idField !== 'id') {
            $props[] = "'deleteIdField' => '{$idField}'";
        }
        
        return "<?= render_component('table_actions', [\n                " . implode(",\n                ", $props) . "\n            ]) ?>";
    }, $content);
    
    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        $count++;
    }
}
echo "Updated $count files.";
