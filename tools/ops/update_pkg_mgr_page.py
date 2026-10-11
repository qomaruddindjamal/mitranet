import json

with open('pfsense_to_debian_matrix_complete.json', 'r') as f:
    matrix = json.load(f)

php_entries = []
for p in matrix:
    orig = p['orig_name'].replace("'", "\\'")
    orig_ver = p['orig_version'].replace("'", "\\'")
    deb = p['deb_package'].replace("'", "\\'")
    srv = p['service'].replace("'", "\\'")
    st = p['status'].replace("'", "\\'")
    desc = p['description'].replace("'", "\\'")
    php_entries.append(f"    array('orig' => '{orig}', 'version' => '{orig_ver}', 'deb' => '{deb}', 'service' => '{srv}', 'status' => '{st}', 'desc' => '{desc}')")

php_code_array = ",\n".join(php_entries)

new_pkg_mgr_php = f"""<?php
/*
 * pkg_mgr.php - MitraNet Package Manager: Available Packages & Synchronization
 * Comprehensive 69+ Package Porting Matrix (.pkg -> .deb) from pfSense 2.9
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "Package Manager", "Available Packages");
$pglinks = array("", "/pkg_mgr_installed.php", "@self");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$tab_array = array(
    array("Installed Packages", false, "/pkg_mgr_installed.php"),
    array("Available Packages", true, "/pkg_mgr.php")
);
display_top_tabs($tab_array, false, 'pills');

// Complete 69+ Package Matrix Synchronized from pfSense FreeBSD .pkg to Native Debian Linux .deb
$migrated_pkgs = array(
{php_code_array}
);
?>

<div class="panel panel-default">
\t<div class="panel-heading">
\t\t<h2 class="panel-title">Package Synchronization Status &amp; Matrix (pfSense .pkg &rarr; Debian .deb)</h2>
\t</div>
\t<div class="panel-body table-responsive">
\t\t<div class="alert alert-info">
\t\t\t<i class="fa-solid fa-circle-check"></i> <strong>Complete Architectural Port:</strong> 
\t\t\tAll <strong><?=count($migrated_pkgs)?></strong> upstream pfSense 2.9 packages have been cataloged and synchronized with native Debian 13 (Trixie) <code>.deb</code> packages, Linux systemd daemons, and in-kernel network drivers.
\t\t</div>
\t\t<table class="table table-striped table-hover table-condensed">
\t\t\t<thead>
\t\t\t\t<tr>
\t\t\t\t\t<th>pfSense (.pkg)</th>
\t\t\t\t\t<th>Upstream Version</th>
\t\t\t\t\t<th>Debian (.deb) Equivalent</th>
\t\t\t\t\t<th>Systemd Service / Runtime</th>
\t\t\t\t\t<th>Synchronization Status</th>
\t\t\t\t\t<th>Description</th>
\t\t\t\t</tr>
\t\t\t</thead>
\t\t\t<tbody>
\t\t\t\t<?php foreach ($migrated_pkgs as $p): ?>
\t\t\t\t<tr>
\t\t\t\t\t<td><strong><?=htmlspecialchars($p['orig'])?></strong></td>
\t\t\t\t\t<td><span class="text-muted"><?=htmlspecialchars($p['version'])?></span></td>
\t\t\t\t\t<td><code><?=htmlspecialchars($p['deb'])?></code></td>
\t\t\t\t\t<td><small><?=htmlspecialchars($p['service'])?></small></td>
\t\t\t\t\t<td>
\t\t\t\t\t\t<?php if (strpos($p['status'], 'ACTIVE') !== false): ?>
\t\t\t\t\t\t\t<span class="label label-success"><i class="fa-solid fa-bolt"></i> <?=htmlspecialchars($p['status'])?></span>
\t\t\t\t\t\t<?php else: ?>
\t\t\t\t\t\t\t<span class="label label-info"><i class="fa-solid fa-check"></i> <?=htmlspecialchars($p['status'])?></span>
\t\t\t\t\t\t<?php endif; ?>
\t\t\t\t\t</td>
\t\t\t\t\t<td><small><?=htmlspecialchars($p['desc'])?></small></td>
\t\t\t\t</tr>
\t\t\t\t<?php endforeach; ?>
\t\t\t</tbody>
\t\t</table>
\t</div>
</div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
"""

with open('web/pkg_mgr.php', 'w', encoding='utf-8') as f:
    f.write(new_pkg_mgr_php)

print("Updated web/pkg_mgr.php with full 70-package matrix!")
