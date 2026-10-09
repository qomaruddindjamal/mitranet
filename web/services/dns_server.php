<?php
/*
 * dns_server.php - MitraNet Services: DNS Server (dnsmasq)
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */
require_once(__DIR__ . '/../includes/api.inc');

$msg = '';
$err = '';

// ── Handle form POST ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enabled       = !empty($_POST['enable']);
    $listen_port   = (int) ($_POST['listen_port'] ?? 53);
    $cache_size    = (int) ($_POST['cache_size'] ?? 1000);
    $strict_order  = !empty($_POST['strict_order']);
    $bogus_priv    = !empty($_POST['bogus_priv']);
    $domain_needed = !empty($_POST['domain_needed']);

    // Forward servers – one IP per line or comma-separated
    $raw_fwd = trim($_POST['forward_servers'] ?? '');
    $forward_servers = [];
    if (!empty($raw_fwd)) {
        foreach (preg_split('/[\r\n,]+/', $raw_fwd) as $s) {
            $s = trim($s);
            if ($s !== '') {
                $forward_servers[] = $s;
            }
        }
    }

    // Host overrides – sent as JSON from JS
    $host_overrides = [];
    $raw_hosts = trim($_POST['host_overrides_json'] ?? '');
    if ($raw_hosts) {
        $decoded = json_decode($raw_hosts, true);
        if (is_array($decoded)) {
            $host_overrides = $decoded;
        }
    }

    // Domain overrides – sent as JSON from JS
    $domain_overrides = [];
    $raw_domains = trim($_POST['domain_overrides_json'] ?? '');
    if ($raw_domains) {
        $decoded = json_decode($raw_domains, true);
        if (is_array($decoded)) {
            $domain_overrides = $decoded;
        }
    }

    $payload = [
        'enabled'          => $enabled,
        'listen_port'      => $listen_port,
        'cache_size'       => $cache_size,
        'strict_order'     => $strict_order,
        'bogus_priv'       => $bogus_priv,
        'domain_needed'    => $domain_needed,
        'forward_servers'  => $forward_servers,
        'host_overrides'   => $host_overrides,
        'domain_overrides' => $domain_overrides,
    ];

    $res = MitraNetApi::saveDnsConfig($payload);
    if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
        $msg = $res['data']['message'] ?? 'Konfigurasi DNS Server berhasil disimpan dan aktif.';
    } else {
        $err = $res['data']['error'] ?? 'Gagal menyimpan konfigurasi DNS Server.';
    }
}

// ── Handle dnsmasq service-only restart (D1 fix) ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'restart_dnsmasq') {
    $res = MitraNetApi::restartDnsmasq();
    if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
        $msg = $res['data']['message'] ?? 'Service dnsmasq berhasil di-restart.';
    } else {
        $err = $res['data']['error'] ?? 'Gagal merestart service dnsmasq.';
    }
}

// ── Load current DNS config ───────────────────────────────────────────
$dns = MitraNetApi::getDnsConfig();

$is_enabled       = (bool)  ($dns['enabled']         ?? false);
$listen_port      = (int)   ($dns['listen_port']     ?? 53);
$cache_size       = (int)   ($dns['cache_size']      ?? 1000);
$strict_order     = (bool)  ($dns['strict_order']    ?? false);
$bogus_priv       = (bool)  ($dns['bogus_priv']      ?? true);
$domain_needed    = (bool)  ($dns['domain_needed']   ?? true);
$fwd_servers      =          $dns['forward_servers']  ?? [];
$host_overrides   =          $dns['host_overrides']   ?? [];
$domain_overrides =          $dns['domain_overrides'] ?? [];
$service_active   = (bool)  ($dns['service_active']  ?? false);
$fwd_text         = implode("\n", $fwd_servers);

$pgtitle       = array("Services", "DNS Server");
$selected_menu = "services";
require_once(__DIR__ . '/../includes/head.inc');

if (!empty($msg)) { print_info_box($msg, "success"); }
if (!empty($err)) { print_info_box($err, "danger"); }
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title">
            <i class="fa-solid fa-server"></i>
            <?=gettext("DNS Server &mdash; dnsmasq")?>
            <span class="badge <?=$service_active ? 'badge-success' : 'badge-danger'?> pull-right">
                <?=$service_active ? 'RUNNING' : 'STOPPED'?>
            </span>
        </h2>
    </div>
    <div class="panel-body">
        <form method="post" action="/services/dns_server.php" class="form-horizontal" id="dns-form">

            <!-- Enable -->
            <div class="form-group">
                <label class="col-sm-3 control-label"><?=gettext("Enable")?></label>
                <div class="col-sm-6">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="enable" value="1" id="dns_enable"
                                   <?=$is_enabled ? 'checked' : ''?> />
                            <strong><?=gettext("Aktifkan DNS Server (dnsmasq) pada sistem ini")?></strong>
                        </label>
                    </div>
                    <span class="help-block">
                        <?=gettext("Bila diaktifkan, dnsmasq akan mendengarkan port DNS dan melayani query dari client lokal.")?>
                    </span>
                </div>
            </div>

            <!-- Listen Port -->
            <div class="form-group">
                <label class="col-sm-3 control-label"><?=gettext("Listen Port")?></label>
                <div class="col-sm-3">
                    <input type="number" name="listen_port" class="form-control"
                           value="<?=htmlspecialchars($listen_port)?>"
                           min="1" max="65535" placeholder="53" />
                    <span class="help-block"><?=gettext("Port yang digunakan dnsmasq. Default: 53.")?></span>
                </div>
            </div>

            <!-- Cache Size -->
            <div class="form-group">
                <label class="col-sm-3 control-label"><?=gettext("Cache Size")?></label>
                <div class="col-sm-3">
                    <input type="number" name="cache_size" class="form-control"
                           value="<?=htmlspecialchars($cache_size)?>"
                           min="0" max="100000" placeholder="1000" />
                    <span class="help-block"><?=gettext("Jumlah entri DNS di-cache (0 = nonaktifkan cache).")?></span>
                </div>
            </div>

            <!-- Upstream DNS Servers -->
            <div class="form-group">
                <label class="col-sm-3 control-label"><?=gettext("Upstream DNS Servers")?></label>
                <div class="col-sm-6">
                    <textarea name="forward_servers" class="form-control" rows="4"
                              placeholder="8.8.8.8&#10;1.1.1.1&#10;9.9.9.9"><?=htmlspecialchars($fwd_text)?></textarea>
                    <span class="help-block">
                        <?=gettext("Satu IP per baris. Server DNS upstream untuk meneruskan query yang tidak ada di cache lokal.")?>
                    </span>
                </div>
            </div>

            <!-- DNS Options -->
            <div class="form-group">
                <label class="col-sm-3 control-label"><?=gettext("DNS Options")?></label>
                <div class="col-sm-6">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="domain_needed" value="1"
                                   <?=$domain_needed ? 'checked' : ''?> />
                            <strong>domain-needed</strong> &mdash;
                            <?=gettext("Jangan teruskan query nama tanpa domain ke upstream.")?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="bogus_priv" value="1"
                                   <?=$bogus_priv ? 'checked' : ''?> />
                            <strong>bogus-priv</strong> &mdash;
                            <?=gettext("Jangan teruskan reverse lookup IP private ke upstream.")?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="strict_order" value="1"
                                   <?=$strict_order ? 'checked' : ''?> />
                            <strong>strict-order</strong> &mdash;
                            <?=gettext("Query ke upstream sesuai urutan yang ditetapkan.")?>
                        </label>
                    </div>
                </div>
            </div>

            <hr />

            <!-- Host Overrides -->
            <div class="form-group">
                <label class="col-sm-3 control-label"><?=gettext("Host Overrides")?></label>
                <div class="col-sm-9">
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed table-hover">
                            <thead>
                                <tr>
                                    <th><?=gettext("IP Address")?></th>
                                    <th><?=gettext("Hostname")?></th>
                                    <th style="width:80px"><?=gettext("Aksi")?></th>
                                </tr>
                            </thead>
                            <tbody id="host-overrides-body">
                                <?php foreach ($host_overrides as $ho): ?>
                                <tr>
                                    <td><?=htmlspecialchars($ho['ip'] ?? '')?></td>
                                    <td><?=htmlspecialchars($ho['host'] ?? '')?></td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-danger btn-remove-host"
                                                title="Hapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="row mb-1">
                        <div class="col-sm-4">
                            <input type="text" class="form-control input-sm" id="new-host-ip"
                                   placeholder="192.168.1.50" />
                        </div>
                        <div class="col-sm-5">
                            <input type="text" class="form-control input-sm" id="new-host-name"
                                   placeholder="server.lan" />
                        </div>
                        <div class="col-sm-3">
                            <button type="button" class="btn btn-sm btn-success" id="btn-add-host">
                                <i class="fa-solid fa-plus"></i> <?=gettext("Tambah")?>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="host_overrides_json" id="host-overrides-json" value="" />
                    <span class="help-block">
                        <?=gettext("Pemetaan hostname ke IP statis yang dikembalikan dnsmasq.")?>
                    </span>
                </div>
            </div>

            <!-- Domain Overrides -->
            <div class="form-group">
                <label class="col-sm-3 control-label"><?=gettext("Domain Overrides")?></label>
                <div class="col-sm-9">
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed table-hover">
                            <thead>
                                <tr>
                                    <th><?=gettext("Domain")?></th>
                                    <th><?=gettext("DNS Server IP")?></th>
                                    <th style="width:80px"><?=gettext("Aksi")?></th>
                                </tr>
                            </thead>
                            <tbody id="domain-overrides-body">
                                <?php foreach ($domain_overrides as $do): ?>
                                <tr>
                                    <td><?=htmlspecialchars($do['domain'] ?? '')?></td>
                                    <td><?=htmlspecialchars($do['ip'] ?? '')?></td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-danger btn-remove-domain"
                                                title="Hapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="row mb-1">
                        <div class="col-sm-4">
                            <input type="text" class="form-control input-sm" id="new-domain-name"
                                   placeholder="corp.local" />
                        </div>
                        <div class="col-sm-5">
                            <input type="text" class="form-control input-sm" id="new-domain-ip"
                                   placeholder="10.0.0.1" />
                        </div>
                        <div class="col-sm-3">
                            <button type="button" class="btn btn-sm btn-success" id="btn-add-domain">
                                <i class="fa-solid fa-plus"></i> <?=gettext("Tambah")?>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="domain_overrides_json" id="domain-overrides-json" value="" />
                    <span class="help-block">
                        <?=gettext("Teruskan query domain tertentu ke DNS server khusus (contoh: internal AD).")?>
                    </span>
                </div>
            </div>

            <!-- Submit -->
            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-6">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> <?=gettext("Simpan &amp; Terapkan")?>
                    </button>
                    <button type="button" class="btn btn-default ml-1" id="btn-restart-dns">
                        <i class="fa-solid fa-rotate-right"></i> <?=gettext("Restart Service")?>
                    </button>
                </div>
            </div>

        </form>

        <!-- Hidden form for dnsmasq service-only restart (D1 fix) -->
        <form method="post" action="/services/dns_server.php" id="form-restart-dnsmasq" style="display:none;">
            <input type="hidden" name="action" value="restart_dnsmasq" />
        </form>
    </div>
</div>

<div class="infoblock">
    <div class="alert alert-info clearfix" role="alert">
        <div class="pull-left">
            <p><strong>DNS Server</strong> pada MitraNet ditenagai oleh <code>dnsmasq</code>.</p>
            <p>Konfigurasi DNS upstream sistem dikelola di
               <a href="/system/system.php">System &rsaquo; General Setup</a>.</p>
            <p>Status service: <a href="/status/services.php">Status &rsaquo; Services</a>.</p>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    // Serialize host override table rows → JSON hidden field
    function serializeHostTable() {
        var rows = [];
        document.querySelectorAll('#host-overrides-body tr').forEach(function (tr) {
            var tds = tr.querySelectorAll('td');
            if (tds.length >= 2) {
                rows.push({ ip: tds[0].textContent.trim(), host: tds[1].textContent.trim() });
            }
        });
        document.getElementById('host-overrides-json').value = JSON.stringify(rows);
    }

    // Serialize domain override table rows → JSON hidden field
    function serializeDomainTable() {
        var rows = [];
        document.querySelectorAll('#domain-overrides-body tr').forEach(function (tr) {
            var tds = tr.querySelectorAll('td');
            if (tds.length >= 2) {
                rows.push({ domain: tds[0].textContent.trim(), ip: tds[1].textContent.trim() });
            }
        });
        document.getElementById('domain-overrides-json').value = JSON.stringify(rows);
    }

    // Safe HTML escaping for dynamically built table cells
    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function makeRemoveBtn(cls) {
        return '<td><button type="button" class="btn btn-xs btn-danger ' + cls + '" title="Hapus">' +
               '<i class="fa-solid fa-trash"></i></button></td>';
    }

    // Add Host Override
    document.getElementById('btn-add-host').addEventListener('click', function () {
        var ip   = document.getElementById('new-host-ip').value.trim();
        var host = document.getElementById('new-host-name').value.trim();
        if (!ip || !host) {
            MitraNet.toast('error', 'IP Address dan Hostname wajib diisi.');
            return;
        }
        var tr = document.createElement('tr');
        tr.innerHTML = '<td>' + escHtml(ip) + '</td><td>' + escHtml(host) + '</td>' +
                       makeRemoveBtn('btn-remove-host');
        document.getElementById('host-overrides-body').appendChild(tr);
        document.getElementById('new-host-ip').value = '';
        document.getElementById('new-host-name').value = '';
    });

    // Add Domain Override
    document.getElementById('btn-add-domain').addEventListener('click', function () {
        var domain = document.getElementById('new-domain-name').value.trim();
        var ip     = document.getElementById('new-domain-ip').value.trim();
        if (!domain || !ip) {
            MitraNet.toast('error', 'Domain dan IP DNS wajib diisi.');
            return;
        }
        var tr = document.createElement('tr');
        tr.innerHTML = '<td>' + escHtml(domain) + '</td><td>' + escHtml(ip) + '</td>' +
                       makeRemoveBtn('btn-remove-domain');
        document.getElementById('domain-overrides-body').appendChild(tr);
        document.getElementById('new-domain-name').value = '';
        document.getElementById('new-domain-ip').value = '';
    });

    // Delegated remove buttons
    document.addEventListener('click', function (e) {
        var removeHost   = e.target.closest('.btn-remove-host');
        var removeDomain = e.target.closest('.btn-remove-domain');
        if (removeHost)   removeHost.closest('tr').remove();
        if (removeDomain) removeDomain.closest('tr').remove();
    });

    // Pre-submit: serialize tables; confirm when disabling
    document.getElementById('dns-form').addEventListener('submit', function (e) {
        serializeHostTable();
        serializeDomainTable();
        if (!document.getElementById('dns_enable').checked) {
            e.preventDefault();
            MitraNet.confirmDelete(
                'Menonaktifkan DNS Server akan menghentikan service dnsmasq. Lanjutkan?',
                function () { document.getElementById('dns-form').submit(); }
            );
        }
    });

    // Restart dnsmasq service only (D1 fix — does NOT reboot the system)
    document.getElementById('btn-restart-dns').addEventListener('click', function () {
        MitraNet.confirmDelete(
            'Restart hanya service dnsmasq (tidak mereboot perangkat). Lanjutkan?',
            function () {
                MitraNet.toast('info', 'Merestart service dnsmasq...');
                document.getElementById('form-restart-dnsmasq').submit();
            }
        );
    });

}());
</script>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
