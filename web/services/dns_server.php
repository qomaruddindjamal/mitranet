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

    // Forward servers – dynamic input list (array or newline/comma separated string)
    $forward_servers = [];
    $raw_fwd = $_POST['forward_servers'] ?? [];
    if (is_array($raw_fwd)) {
        foreach ($raw_fwd as $s) {
            $s = trim((string)$s);
            if ($s !== '') {
                $forward_servers[] = $s;
            }
        }
    } elseif (is_string($raw_fwd)) {
        foreach (preg_split('/[\r\n,]+/', trim($raw_fwd)) as $s) {
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
?>

<div class="container-fluid mitranet-page-container">
	<div class="mitranet-window">
		<div class="mitranet-header">
			<div class="mitranet-title-badge">
				<i class="fa-solid fa-server"></i> DNS Server (dnsmasq)
			</div>
			<ul class="mitranet-tabs">
				<li class="active"><a href="/services/dns_server.php">Settings</a></li>
				<li><a href="/status/services.php">Service Status</a></li>
			</ul>
		</div>

		<div class="mitranet-window-body">
			<?php if (!empty($msg)): ?>
				<div class="alert alert-success alert-dismissible" role="alert" style="margin-bottom: 15px; border-radius: 3px;">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
				</div>
			<?php endif; ?>

			<?php if (!empty($err)): ?>
				<div class="alert alert-danger alert-dismissible" role="alert" style="margin-bottom: 15px; border-radius: 3px;">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<i class="fa-solid fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?>
				</div>
			<?php endif; ?>

			<form method="post" action="/services/dns_server.php" class="form-horizontal" id="dns-form">
				<!-- General DNS Configuration Panel -->
				<div class="panel panel-default">
					<div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center;">
						<h2 class="panel-title">
							<i class="fa-solid fa-sliders"></i> <?=gettext("General Configuration")?>
						</h2>
						<span class="badge <?=$service_active ? 'badge-success' : 'badge-danger'?>" style="font-size: 11px; padding: 4px 8px;">
							<i class="fa-solid <?=$service_active ? 'fa-circle-check' : 'fa-circle-xmark'?>"></i> <?=$service_active ? 'RUNNING' : 'STOPPED'?>
						</span>
					</div>
					<div class="panel-body">

						<!-- Enable -->
						<div class="form-group">
							<label class="col-sm-3 control-label"><?=gettext("Enable")?></label>
							<div class="col-sm-7">
								<div class="checkbox">
									<label>
										<input type="checkbox" name="enable" value="1" id="dns_enable" <?=$is_enabled ? 'checked' : ''?> />
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

						<!-- Upstream DNS Servers (Dynamic Input Fields) -->
						<div class="form-group">
							<label class="col-sm-3 control-label"><?=gettext("Upstream DNS Servers")?></label>
							<div class="col-sm-7">
								<div id="upstream-dns-container" style="display: flex; flex-direction: column; gap: 6px;">
									<?php 
									$initial_servers = !empty($fwd_servers) ? $fwd_servers : ['10.10.66.254', '8.8.8.8', '8.8.4.4', 'fe80::1%enp1s0'];
									foreach ($initial_servers as $idx => $srv): 
									?>
									<div class="input-group upstream-dns-row" style="width: 100%;">
										<input type="text" name="forward_servers[]" class="form-control" 
										       value="<?=htmlspecialchars($srv)?>" 
										       placeholder="e.g. 8.8.8.8 atau fe80::1%enp1s0" 
										       style="font-family: monospace; font-size: 11px;" />
										<span class="input-group-btn">
											<button type="button" class="btn btn-default btn-sm btn-remove-dns" title="<?=gettext("Hapus IP ini")?>" style="height: 26px; padding: 2px 10px; color: #dc2626;">
												<i class="fa-solid fa-trash-can"></i>
											</button>
										</span>
									</div>
									<?php endforeach; ?>
								</div>
								<div style="margin-top: 8px;">
									<button type="button" class="btn btn-xs btn-primary" id="btn-add-dns" style="font-weight: 600; padding: 3px 10px;">
										<i class="fa-solid fa-plus"></i> <?=gettext("Tambah DNS Server")?>
									</button>
								</div>
								<span class="help-block" style="margin-top: 6px;">
									<?=gettext("IP DNS upstream (IPv4 atau IPv6 scoped seperti fe80::1%enp1s0) untuk meneruskan query yang tidak ada di cache lokal.")?>
								</span>
							</div>
						</div>

						<!-- DNS Options -->
						<div class="form-group">
							<label class="col-sm-3 control-label"><?=gettext("DNS Options")?></label>
							<div class="col-sm-7">
								<div class="checkbox">
									<label>
										<input type="checkbox" name="domain_needed" value="1" <?=$domain_needed ? 'checked' : ''?> />
										<strong>domain-needed</strong> &mdash; <?=gettext("Jangan teruskan query nama tanpa domain ke upstream.")?>
									</label>
								</div>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="bogus_priv" value="1" <?=$bogus_priv ? 'checked' : ''?> />
										<strong>bogus-priv</strong> &mdash; <?=gettext("Jangan teruskan reverse lookup IP private ke upstream.")?>
									</label>
								</div>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="strict_order" value="1" <?=$strict_order ? 'checked' : ''?> />
										<strong>strict-order</strong> &mdash; <?=gettext("Query ke upstream sesuai urutan yang ditetapkan.")?>
									</label>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Host Overrides Panel -->
				<div class="panel panel-default">
					<div class="panel-heading">
						<h2 class="panel-title"><i class="fa-solid fa-network-wired"></i> <?=gettext("Host Overrides")?></h2>
					</div>
					<div class="panel-body">
						<div class="table-responsive" style="margin-bottom: 12px; border: 1px solid #d4e2ef; border-radius: 3px;">
							<table class="table table-striped table-condensed table-hover" style="margin-bottom: 0;">
								<thead>
									<tr style="background: linear-gradient(to bottom, #f1f6fb 0%, #e2edf7 100%);">
										<th style="width: 40%; font-size: 11px; color: #1e3c5f;"><?=gettext("IP Address")?></th>
										<th style="width: 45%; font-size: 11px; color: #1e3c5f;"><?=gettext("Hostname")?></th>
										<th style="width: 15%; text-align: center; font-size: 11px; color: #1e3c5f;"><?=gettext("Aksi")?></th>
									</tr>
								</thead>
								<tbody id="host-overrides-body">
									<?php if (empty($host_overrides)): ?>
										<tr id="empty-host-row">
											<td colspan="3" class="text-center text-muted" style="padding: 14px;">
												<i class="fa-solid fa-circle-info"></i> <?=gettext("Belum ada Host Override yang dikonfigurasi.")?>
											</td>
										</tr>
									<?php else: ?>
										<?php foreach ($host_overrides as $ho): ?>
										<tr>
											<td><code style="background: #f1f5f9; color: #0369a1; padding: 2px 5px; border-radius: 3px; font-size: 11px;"><?=htmlspecialchars($ho['ip'] ?? '')?></code></td>
											<td><strong style="color: #1e293b;"><?=htmlspecialchars($ho['host'] ?? '')?></strong></td>
											<td style="text-align: center;">
												<button type="button" class="btn btn-xs btn-danger btn-remove-host" title="Hapus">
													<i class="fa-solid fa-trash"></i>
												</button>
											</td>
										</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>

						<div style="background: #f4f8fc; border: 1px solid #d0e1f2; border-radius: 4px; padding: 8px 12px; margin-bottom: 6px;">
							<div class="row" style="margin-left: -5px; margin-right: -5px;">
								<div class="col-sm-5 col-xs-12" style="padding-left: 5px; padding-right: 5px; margin-bottom: 4px;">
									<input type="text" class="form-control" id="new-host-ip" placeholder="192.168.1.50 (IP Address)" />
								</div>
								<div class="col-sm-5 col-xs-12" style="padding-left: 5px; padding-right: 5px; margin-bottom: 4px;">
									<input type="text" class="form-control" id="new-host-name" placeholder="server.lan (Hostname)" />
								</div>
								<div class="col-sm-2 col-xs-12" style="padding-left: 5px; padding-right: 5px;">
									<button type="button" class="btn btn-sm btn-success btn-block" id="btn-add-host" style="font-weight: 600; height: 26px; padding: 2px 8px; font-size: 11px;">
										<i class="fa-solid fa-plus"></i> <?=gettext("Tambah")?>
									</button>
								</div>
							</div>
						</div>
						<input type="hidden" name="host_overrides_json" id="host-overrides-json" value="" />
						<span class="help-block" style="margin-top: 4px;">
							<?=gettext("Pemetaan hostname ke IP statis lokal yang langsung di-resolve oleh dnsmasq tanpa query upstream.")?>
						</span>
					</div>
				</div>

				<!-- Domain Overrides Panel -->
				<div class="panel panel-default">
					<div class="panel-heading">
						<h2 class="panel-title"><i class="fa-solid fa-diagram-project"></i> <?=gettext("Domain Overrides")?></h2>
					</div>
					<div class="panel-body">
						<div class="table-responsive" style="margin-bottom: 12px; border: 1px solid #d4e2ef; border-radius: 3px;">
							<table class="table table-striped table-condensed table-hover" style="margin-bottom: 0;">
								<thead>
									<tr style="background: linear-gradient(to bottom, #f1f6fb 0%, #e2edf7 100%);">
										<th style="width: 50%; font-size: 11px; color: #1e3c5f;"><?=gettext("Domain")?></th>
										<th style="width: 35%; font-size: 11px; color: #1e3c5f;"><?=gettext("DNS Server IP")?></th>
										<th style="width: 15%; text-align: center; font-size: 11px; color: #1e3c5f;"><?=gettext("Aksi")?></th>
									</tr>
								</thead>
								<tbody id="domain-overrides-body">
									<?php if (empty($domain_overrides)): ?>
										<tr id="empty-domain-row">
											<td colspan="3" class="text-center text-muted" style="padding: 14px;">
												<i class="fa-solid fa-circle-info"></i> <?=gettext("Belum ada Domain Override yang dikonfigurasi.")?>
											</td>
										</tr>
									<?php else: ?>
										<?php foreach ($domain_overrides as $do): ?>
										<tr>
											<td><strong style="color: #1e293b;"><?=htmlspecialchars($do['domain'] ?? '')?></strong></td>
											<td><code style="background: #f1f5f9; color: #0369a1; padding: 2px 5px; border-radius: 3px; font-size: 11px;"><?=htmlspecialchars($do['ip'] ?? '')?></code></td>
											<td style="text-align: center;">
												<button type="button" class="btn btn-xs btn-danger btn-remove-domain" title="Hapus">
													<i class="fa-solid fa-trash"></i>
												</button>
											</td>
										</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>

						<div style="background: #f4f8fc; border: 1px solid #d0e1f2; border-radius: 4px; padding: 8px 12px; margin-bottom: 6px;">
							<div class="row" style="margin-left: -5px; margin-right: -5px;">
								<div class="col-sm-5 col-xs-12" style="padding-left: 5px; padding-right: 5px; margin-bottom: 4px;">
									<input type="text" class="form-control" id="new-domain-name" placeholder="corp.local (Domain)" />
								</div>
								<div class="col-sm-5 col-xs-12" style="padding-left: 5px; padding-right: 5px; margin-bottom: 4px;">
									<input type="text" class="form-control" id="new-domain-ip" placeholder="10.0.0.1 (Target DNS IP)" />
								</div>
								<div class="col-sm-2 col-xs-12" style="padding-left: 5px; padding-right: 5px;">
									<button type="button" class="btn btn-sm btn-success btn-block" id="btn-add-domain" style="font-weight: 600; height: 26px; padding: 2px 8px; font-size: 11px;">
										<i class="fa-solid fa-plus"></i> <?=gettext("Tambah")?>
									</button>
								</div>
							</div>
						</div>
						<input type="hidden" name="domain_overrides_json" id="domain-overrides-json" value="" />
						<span class="help-block" style="margin-top: 4px;">
							<?=gettext("Meneruskan query resolusi domain tertentu ke server DNS otoritatif khusus (misal: Active Directory / Domain internal).")?>
						</span>
					</div>
				</div>

				<!-- Action Buttons -->
				<div class="action-buttons" style="border: 1px solid #8faecf; border-radius: 3px; margin-bottom: 25px; padding: 10px 14px;">
					<button type="submit" class="btn btn-primary btn-sm" style="min-width: 140px; font-weight: 600;">
						<i class="fa-solid fa-save icon-embed-btn"></i> <?=gettext("Simpan &amp; Terapkan")?>
					</button>
					<button type="button" class="btn btn-default btn-sm ml-1" id="btn-restart-dns" style="font-weight: 600;">
						<i class="fa-solid fa-rotate-right icon-embed-btn"></i> <?=gettext("Restart Service")?>
					</button>
				</div>

			</form>

			<!-- Hidden form for dnsmasq service-only restart -->
			<form method="post" action="/services/dns_server.php" id="form-restart-dnsmasq" style="display:none;">
				<input type="hidden" name="action" value="restart_dnsmasq" />
			</form>
		</div>

		<div class="mitranet-statusbar">
			<div>
				<span><strong>Service:</strong> dnsmasq (Port: <?=htmlspecialchars($listen_port)?>)</span>
			</div>
			<div>
				<span class="text-muted">MitraNet Local Resolver &amp; Cache</span>
			</div>
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
        var emptyRow = document.getElementById('empty-host-row');
        if (emptyRow) { emptyRow.remove(); }
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><code>' + escHtml(ip) + '</code></td><td><strong>' + escHtml(host) + '</strong></td><td style="text-align: center;">' +
                       makeRemoveBtn('btn-remove-host') + '</td>';
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
        var emptyRow = document.getElementById('empty-domain-row');
        if (emptyRow) { emptyRow.remove(); }
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><strong>' + escHtml(domain) + '</strong></td><td><code>' + escHtml(ip) + '</code></td><td style="text-align: center;">' +
                       makeRemoveBtn('btn-remove-domain') + '</td>';
        document.getElementById('domain-overrides-body').appendChild(tr);
        document.getElementById('new-domain-name').value = '';
        document.getElementById('new-domain-ip').value = '';
    });

    // Add Upstream DNS Input Row
    var btnAddDns = document.getElementById('btn-add-dns');
    if (btnAddDns) {
        btnAddDns.addEventListener('click', function () {
            var container = document.getElementById('upstream-dns-container');
            var row = document.createElement('div');
            row.className = 'input-group upstream-dns-row';
            row.style.width = '100%';
            row.innerHTML = '<input type="text" name="forward_servers[]" class="form-control" placeholder="e.g. 1.1.1.1 atau 2001:4860:4860::8888" style="font-family: monospace; font-size: 11px;" />' +
                            '<span class="input-group-btn">' +
                            '<button type="button" class="btn btn-default btn-sm btn-remove-dns" title="Hapus IP ini" style="height: 26px; padding: 2px 10px; color: #dc2626;">' +
                            '<i class="fa-solid fa-trash-can"></i>' +
                            '</button>' +
                            '</span>';
            container.appendChild(row);
            var input = row.querySelector('input');
            if (input) input.focus();
        });
    }

    // Delegated remove buttons
    document.addEventListener('click', function (e) {
        var removeHost   = e.target.closest('.btn-remove-host');
        var removeDomain = e.target.closest('.btn-remove-domain');
        var removeDns    = e.target.closest('.btn-remove-dns');
        if (removeHost)   removeHost.closest('tr').remove();
        if (removeDomain) removeDomain.closest('tr').remove();
        if (removeDns) {
            var dnsRows = document.querySelectorAll('.upstream-dns-row');
            if (dnsRows.length <= 1) {
                // If only 1 row left, just clear the value instead of removing row completely
                var input = removeDns.closest('.upstream-dns-row').querySelector('input');
                if (input) input.value = '';
            } else {
                removeDns.closest('.upstream-dns-row').remove();
            }
        }
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
