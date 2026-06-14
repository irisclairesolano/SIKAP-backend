import os
for root, _, files in os.walk('app/Models'):
    for file in files:
        if file.endswith('.php'):
            path = os.path.join(root, file)
            with open(path, 'r', encoding='utf-8') as f:
                content = f.read()
            new_content = content.replace("protected $connection = 'pgsql';", "")
            if content != new_content:
                with open(path, 'w', encoding='utf-8') as f:
                    f.write(new_content)
