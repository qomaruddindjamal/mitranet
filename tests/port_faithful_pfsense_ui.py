import os
import re
import json

PFSENSE_DIR = 'tmp/pfsense_pages'
WEB_DIR = 'web'

with open('pfsense_sidebar_complete_audit.json', 'r') as f:
    sidebar = json.load(f)

# Collect all pages and categories
all_pages = []
for entry in sidebar:
    if entry['type'] == 'direct':
        all_pages.append(('DIRECT', entry['name'], entry['url'].split('?')[0].lstrip('/')))
    else:
        for sub in entry['submenus']:
            all_pages.append((entry['name'], sub['name'], sub['url'].split('?')[0].lstrip('/')))

print(f"Porting faithful UI layout for {len(all_pages)} pages...")

# Pages that already have rich custom implementations
custom_preserve = {
    'index.php', 'system.php', 'system_advanced.php', 'system_gateways.php',
    'interfaces_assign.php', 'interfaces.php', 'firewall_rules.php', 'firewall_aliases.php',
    'firewall_nat.php', 'firewall_nat_out.php', 'firewall_nat_1to1.php', 'firewall_nat_npt.php',
    'status_interfaces.php', 'status_gateways.php', 'status_logs.php',
    'diag_arp.php', 'diag_ping.php', 'diag_traceroute.php', 'diag_backup.php', 'diag_dump_states.php', 'diag_routes.php',
    'pkg_mgr.php', 'pkg_mgr_installed.php', 'vpn_xray.php',
    'wg/vpn_wg_tunnels.php', 'wg/vpn_wg_peers.php', 'wg/vpn_wg_settings.php', 'wg/status_wireguard.php'
}

count_updated = 0

for cat, name, rel_path in all_pages:
    if rel_path in custom_preserve:
        continue

    src_html_file = os.path.join(PFSENSE_DIR, rel_path.replace('/', os.sep))
    if not os.path.exists(src_html_file):
        continue

    with open(src_html_file, 'r', encoding='utf-8', errors='ignore') as sf:
        html = sf.read()

    # Extract tabs (<ul class="nav nav-pills">...</ul> or nav-tabs)
    tabs_html = ""
    tabs_m = re.search(r'(<ul\s+class="nav\s+(?:nav-pills|nav-tabs)".*?</ul>)', html, re.DOTALL)
    if tabs_m:
        tabs_html = tabs_m.group(1).strip()
        # Clean relative links to start with /
        tabs_html = re.sub(r'href="([^"/#][^"]*)"', r'href="/\1"', tabs_html)

    # Extract main content between header/breadcrumb and footer
    # Usually inside <div id="pf-main-content"...> or before </div>\s*<footer
    main_body = ""
    
    # Try finding all panels
    panels = re.findall(r'(<div class="panel panel-default".*?</div>\s*</div>)', html, re.DOTALL)
    if panels:
        main_body = "\n\n".join(panels)
    else:
        # Try finding form or container
        cnt_m = re.search(r'(<form.*?</form>)', html, re.DOTALL)
        if cnt_m:
            main_body = cnt_m.group(1)
        else:
            # fallback: everything between tabs and footer
            start_idx = html.find('</header>')
            end_idx = html.find('<footer')
            if start_idx != -1 and end_idx != -1:
                main_body = html[start_idx+9:end_idx].strip()

    # Clean up CSRF and specific pfSense scripts
    main_body = re.sub(r'<input[^>]*__csrf_magic[^>]*>', '', main_body)
    main_body = re.sub(r'action="/[^"]*"', 'action="#"', main_body)
    # clean relative URLs in main_body
    main_body = re.sub(r'href="([^"/#][^"]*\.php)"', r'href="/\1"', main_body)

    # Action buttons if any
    action_btns = ""
    ab_m = re.search(r'(<nav class="action-buttons".*?</nav>)', html, re.DOTALL)
    if ab_m:
        action_btns = ab_m.group(1).strip()
        action_btns = re.sub(r'href="([^"/#][^"]*)"', r'href="/\1"', action_btns)

    # Infoblock if any
    info_block = ""
    ib_m = re.search(r'(<div class="infoblock".*?</div>\s*</div>)', html, re.DOTALL)
    if ib_m:
        info_block = ib_m.group(1).strip()

    dest_file = os.path.join(WEB_DIR, rel_path.replace('/', os.sep))
    os.makedirs(os.path.dirname(dest_file), exist_ok=True)

    php_code = f"""<?php
/*
 * {rel_path} - MitraNet {cat}: {name}
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("{cat}", "{name}");
$selected_menu = "{cat.lower()}";
require_once(__DIR__ . '/{'../' if '/' in rel_path else ''}includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

{tabs_html}

{main_body}

{action_btns}

{info_block}

<?php include(__DIR__ . '/{'../' if '/' in rel_path else ''}includes/foot.inc'); ?>
"""
    with open(dest_file, 'w', encoding='utf-8') as df:
        df.write(php_code)
    count_updated += 1

print(f"Successfully ported {count_updated} pages to exact pfSense HTML layout and controls!")
