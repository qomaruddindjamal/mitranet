import json

with open('package_relations_complete.json', 'r', encoding='utf-8') as f:
    rels = json.load(f)

php_entries = []
for p in rels:
    name = p['pkg_name'].replace("'", "\\'")
    ver = p['pkg_version'].replace("'", "\\'")
    cat = p['category'].replace("'", "\\'")
    desc = p['description'].replace("'", "\\'")
    
    # dependencies array
    deps = p['freebsd_pkg_dependencies']
    deps_php = ", ".join([f"'{d.replace(chr(39), chr(92)+chr(39))}'" for d in deps])
    
    deb = p['debian_deb_packages'].replace("'", "\\'")
    srv = p['runtime_daemon'].replace("'", "\\'")
    
    # conflicts
    confs = p['conflicts_with']
    confs_php = ", ".join([f"'{c.replace(chr(39), chr(92)+chr(39))}'" for c in confs])
    
    menu = p['webui_menu_integration'].replace("'", "\\'")
    status = p['sync_status'].replace("'", "\\'")
    
    entry = f"""    array(
        'name' => '{name}',
        'version' => '{ver}',
        'category' => '{cat}',
        'desc' => '{desc}',
        'deps' => array({deps_php}),
        'deb' => '{deb}',
        'service' => '{srv}',
        'conflicts' => array({confs_php}),
        'menu' => '{menu}',
        'status' => '{status}'
    )"""
    php_entries.append(entry)

php_array_code = ",\n".join(php_entries)

new_pkg_mgr_php = f"""<?php
/*
 * pkg_mgr.php - MitraNet Package Manager: Available Packages & Synchronization
 * Full Inter-Package Relations, Dependencies, Conflicts & Debian Mapping Matrix
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "Package Manager", "Available Packages");
$pglinks = array("", "/pkg_mgr_installed.php", "@self");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$tab_array = array(
    array("Installed Packages", false, "/pkg_mgr_installed.php"),
    array("Available Packages &amp; Relations", true, "/pkg_mgr.php")
);
display_top_tabs($tab_array, false, 'pills');

// Complete Package Relations Matrix from pfSense 2.9 to Debian 13 (Trixie)
$packages_relations = array(
{php_array_code}
);

// Grouping by Category for Summary Metrics
$categories = array();
foreach ($packages_relations as $pr) {{
    $cat = $pr['category'];
    if (!isset($categories[$cat])) {{
        $categories[$cat] = 0;
    }}
    $categories[$cat]++;
}}
?>

<div class="panel panel-default">
\t<div class="panel-heading">
\t\t<h2 class="panel-title"><i class="fa-solid fa-diagram-project"></i> Package Architecture &amp; Inter-Package Relations</h2>
\t</div>
\t<div class="panel-body">
\t\t<div class="alert alert-info">
\t\t\t<i class="fa-solid fa-circle-nodes"></i> <strong>Relasi Dependensi &amp; Ekosistem Paket:</strong> 
\t\t\tMenampilkan relasi lengkap antar paket (<strong>Dependencies</strong>, <strong>Debian .deb Mapping</strong>, <strong>Systemd Daemons</strong>, <strong>Conflicts / Mutual Exclusion</strong>, dan <strong>WebUI Menu Hook</strong>) untuk seluruh <strong><?=count($packages_relations)?></strong> paket.
\t\t</div>

\t\t<!-- Category Metrics Badges -->
\t\t<div style="margin-bottom: 20px;">
\t\t\t<strong>Kategori Subsystem:</strong><br/>
\t\t\t<?php foreach ($categories as $c_name => $c_count): ?>
\t\t\t\t<span class="label label-default" style="display: inline-block; margin: 3px 4px; font-size: 12px;">
\t\t\t\t\t<?=htmlspecialchars($c_name)?>: <strong><?=$c_count?></strong>
\t\t\t\t</span>
\t\t\t<?php endforeach; ?>
\t\t</div>

\t\t<div class="table-responsive">
\t\t\t<table class="table table-striped table-hover table-condensed table-bordered" style="vertical-align: middle;">
\t\t\t\t<thead>
\t\t\t\t\t<tr class="active">
\t\t\t\t\t\t<th style="min-width: 140px;">Paket Asal (pfSense)</th>
\t\t\t\t\t\t<th style="min-width: 130px;">Kategori</th>
\t\t\t\t\t\t<th style="min-width: 220px;">Relasi Dependensi (.pkg &rarr; .deb)</th>
\t\t\t\t\t\t<th style="min-width: 180px;">Daemon / Runtime Service</th>
\t\t\t\t\t\t<th style="min-width: 180px;">WebUI Menu Hook</th>
\t\t\t\t\t\t<th style="min-width: 140px;">Status Sinkronisasi</th>
\t\t\t\t\t</tr>
\t\t\t\t</thead>
\t\t\t\t<tbody>
\t\t\t\t\t<?php foreach ($packages_relations as $p): ?>
\t\t\t\t\t<tr>
\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t<strong style="font-size: 13px;"><?=htmlspecialchars($p['name'])?></strong><br/>
\t\t\t\t\t\t\t<span class="text-muted"><small>v<?=htmlspecialchars($p['version'])?></small></span>
\t\t\t\t\t\t\t<p style="margin-top: 5px; font-size: 11px; color: #555;"><?=htmlspecialchars($p['desc'])?></p>
\t\t\t\t\t\t</td>
\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t<span class="label label-primary"><?=htmlspecialchars($p['category'])?></span>
\t\t\t\t\t\t</td>
\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t<strong>Debian Package:</strong><br/>
\t\t\t\t\t\t\t<code><?=htmlspecialchars($p['deb'])?></code><br/>
\t\t\t\t\t\t\t<?php if (!empty($p['deps'])): ?>
\t\t\t\t\t\t\t\t<div style="margin-top: 6px;">
\t\t\t\t\t\t\t\t\t<small class="text-muted"><i class="fa-solid fa-link"></i> Upstream Dependencies:</small><br/>
\t\t\t\t\t\t\t\t\t<?php foreach ($p['deps'] as $dep): ?>
\t\t\t\t\t\t\t\t\t\t<span class="label label-info" style="display: inline-block; margin: 1px;"><i class="fa-solid fa-paperclip"></i> <?=htmlspecialchars($dep)?></span>
\t\t\t\t\t\t\t\t\t<?php endforeach; ?>
\t\t\t\t\t\t\t\t</div>
\t\t\t\t\t\t\t<?php endif; ?>
\t\t\t\t\t\t\t<?php if (!empty($p['conflicts'])): ?>
\t\t\t\t\t\t\t\t<div style="margin-top: 5px;">
\t\t\t\t\t\t\t\t\t<small class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Konflik / Mutual Exclusion:</small><br/>
\t\t\t\t\t\t\t\t\t<?php foreach ($p['conflicts'] as $conf): ?>
\t\t\t\t\t\t\t\t\t\t<span class="label label-danger"><?=htmlspecialchars($conf)?></span>
\t\t\t\t\t\t\t\t\t<?php endforeach; ?>
\t\t\t\t\t\t\t\t</div>
\t\t\t\t\t\t\t<?php endif; ?>
\t\t\t\t\t\t</td>
\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t<code><?=htmlspecialchars($p['service'])?></code>
\t\t\t\t\t\t</td>
\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t<i class="fa-solid fa-arrow-up-right-from-square"></i> <small><?=htmlspecialchars($p['menu'])?></small>
\t\t\t\t\t\t</td>
\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t<?php if (strpos($p['status'], 'ACTIVE') !== false): ?>
\t\t\t\t\t\t\t\t<span class="label label-success" style="font-size: 11px;"><i class="fa-solid fa-bolt"></i> <?=htmlspecialchars($p['status'])?></span>
\t\t\t\t\t\t\t<?php else: ?>
\t\t\t\t\t\t\t\t<span class="label label-info" style="font-size: 11px;"><i class="fa-solid fa-check"></i> <?=htmlspecialchars($p['status'])?></span>
\t\t\t\t\t\t\t<?php endif; ?>
\t\t\t\t\t\t</td>
\t\t\t\t\t</tr>
\t\t\t\t\t<?php endforeach; ?>
\t\t\t\t</tbody>
\t\t\t</table>
\t\t</div>
\t</div>
</div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
"""

with open('web/pkg_mgr.php', 'w', encoding='utf-8') as f:
    f.write(new_pkg_mgr_php)

print("web/pkg_mgr.php successfully updated with full inter-package relations!")
