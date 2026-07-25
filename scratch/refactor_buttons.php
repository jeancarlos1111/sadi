<?php
$files = [];
$dir = new RecursiveDirectoryIterator('views/');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), 'index.phtml')) {
        $files[] = $file->getPathname();
    }
}

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Pattern to match the common button groups
    // <td>...<a href="?route=X/form&id=Y"><i fa-edit></i>... <form action="?route=X/delete"><input name="id" value="Y"><button><i fa-trash></button></form>...</td>
    // Because parsing HTML with regex is awful, let's look for a block that has Edit and Delete.
    $pattern = '/<td[^>]*>(.*?fa-edit.*?fa-trash.*?)<\/td>/is';
    
    $newContent = preg_replace_callback($pattern, function($matches) {
        $tdInner = $matches[1];
        
        $editUrl = '';
        $deleteUrl = '';
        $deleteId = '';
        $deleteIdField = 'id';
        
        // Find edit URL
        if (preg_match('/href=[\'"]([^\'"]+form[^\'"]*id=([^\'"]+))[\'"]/i', $tdInner, $editMatch)) {
            $editUrl = $editMatch[1];
            // Extract the variable string from editMatch 2, e.g., <?= htmlspecialchars((string)$item->id) ? >
            // We'll just construct it safely.
            if (preg_match('/\$item->([a-zA-Z0-9_]+)/', $editMatch[2], $varMatch)) {
                $deleteId = '$item->' . $varMatch[1];
            } else {
                // Cannot parse ID cleanly, return original
                return $matches[0];
            }
        }
        
        // Find delete URL
        if (preg_match('/action=[\'"]([^\'"]+(?:delete|eliminar)[^\'"]*)[\'"]/i', $tdInner, $delMatch)) {
            $deleteUrl = $delMatch[1];
        }
        
        if (preg_match('/name=[\'"]([^\'"]+)[\'"].*?value=[\'"](?:<\?=.*?\$item->(?:[a-zA-Z0-9_]+).*?\?>)[\'"]/is', $tdInner, $inputMatch)) {
            $deleteIdField = $inputMatch[1];
        }

        if ($editUrl && $deleteUrl && $deleteId) {
            $props = [];
            $props[] = "'editUrl' => '{$editUrl}'";
            $props[] = "'deleteUrl' => '{$deleteUrl}'";
            $props[] = "'deleteId' => {$deleteId}";
            if ($deleteIdField !== 'id') {
                $props[] = "'deleteIdField' => '{$deleteIdField}'";
            }
            
            $phpCode = "        <td class=\"text-center align-middle\">\n            <?= render_component('table_actions', [\n                " . implode(",\n                ", $props) . "\n            ]) ?>\n        </td>";
            return $phpCode;
        }
        
        return $matches[0];
    }, $content);
    
    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        echo "Updated: $file\n";
    }
}
