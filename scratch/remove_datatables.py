import os
import re

views_dir = '/home/jean/Descargas/sadi/views'

count = 0
for root, dirs, files in os.walk(views_dir):
    for filename in files:
        if filename.endswith('.phtml'):
            filepath = os.path.join(root, filename)
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
                
            if 'dataTable' in content:
                # Replace 'dataTable ' or ' dataTable' or 'dataTable'
                new_content = re.sub(r'\bdataTable\b', '', content)
                # Cleanup double spaces that might result
                new_content = new_content.replace('  ', ' ')
                
                if new_content != content:
                    with open(filepath, 'w', encoding='utf-8') as f:
                        f.write(new_content)
                    count += 1
                    print(f"Removed dataTable from: {filepath}")

print(f"Done. Processed {count} files.")
