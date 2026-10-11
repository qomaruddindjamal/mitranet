import os

web_dir = r"c:\mitranet\web"
subfolders = ['diagnostics', 'firewall', 'packages', 'qos', 'services', 'status', 'system', 'terminal', 'tools', 'vOlt', 'vpn', 'xray']

modified_count = 0
for folder in subfolders:
    target_path = os.path.join(web_dir, folder)
    if not os.path.isdir(target_path):
        continue
    for root, dirs, files in os.walk(target_path):
        for f in files:
            if f.endswith('.php'):
                filepath = os.path.join(root, f)
                with open(filepath, 'r', encoding='utf-8', errors='ignore') as fp:
                    content = fp.read()
                
                new_content = content
                old_patterns = [
                    "require_once(__DIR__ . '/includes/",
                    "require_once(__DIR__ . \"/includes/",
                    "require(__DIR__ . '/includes/",
                    "include_once(__DIR__ . '/includes/",
                    "include(__DIR__ . '/includes/",
                    "require_once('includes/",
                    "require('includes/",
                    "include_once('includes/",
                    "include('includes/"
                ]
                
                new_content = new_content.replace("require_once(__DIR__ . '/includes/", "require_once(__DIR__ . '/../includes/")
                new_content = new_content.replace('require_once(__DIR__ . "/includes/', 'require_once(__DIR__ . "/../includes/')
                new_content = new_content.replace("require(__DIR__ . '/includes/", "require(__DIR__ . '/../includes/")
                new_content = new_content.replace("include_once(__DIR__ . '/includes/", "include_once(__DIR__ . '/../includes/")
                new_content = new_content.replace("include(__DIR__ . '/includes/", "include(__DIR__ . '/../includes/")

                if new_content != content:
                    with open(filepath, 'w', encoding='utf-8') as fp:
                        fp.write(new_content)
                    modified_count += 1

print(f"Fixed include paths in {modified_count} PHP files!")
