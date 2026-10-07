<?php
/*
 * interfaces_wifi.php - MitraNet DIRECT: Wifi
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("DIRECT", "Wifi");
$selected_menu = "direct";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/interfaces_wifi.php?tab=overview" >Overview & Status</a></li><li role="presentation"><a href="/interfaces_wifi.php?tab=scan" >Scan & Connect (Client)</a></li><li role="presentation"><a href="/interfaces_wifi.php?tab=ap" >Access Point (AP Mode)</a></li><li role="presentation"><a href="/interfaces_wifi.php?tab=detect" >Hardware & Auto-Detect</a></li><li role="presentation"><a href="/interfaces_wifi.php?tab=bridge" >VirtualBox / Bridged Wi-Fi</a></li></ul>

<div id="notices" class="modal fade" role="dialog">

	<div class="modal-dialog">

		<div class="modal-content">

			<div class="modal-header">

				<button type="button" class="close" data-dismiss="modal" aria-label="Close">

					<span aria-hidden="true">&times;</span>

				</button>



				<h3 class="modal-title" id="myModalLabel">Notices</h3>

			</div>



			<div class="modal-body">

				<h4>Upgrade</h4>

				<ul>

					<li>

						<b>

						</b>

						check_upgrade: &quot;&quot; returned error code 1						<i>@ 2026-10-07 09:09:03</i>

					</li>

					<li>

						<b>

						</b>

						check_upgrade: &quot;&quot; returned error code 1						<i>@ 2026-10-07 09:43:12</i>

					</li>

				</ul>

			</div>



			<div class="modal-footer">

				<button type="button" class="btn btn-info" data-dismiss="modal"><i class="fa-solid fa-times icon-embed-btn"></i>Close</button>

				<button type="button" id="clearallnotices" class="btn btn-primary"><i class="fa-regular fa-trash-can icon-embed-btn"></i>Mark All as Read</button>

			</div>

		</div>

	</div>

</div>



<script type="text/javascript">

//<![CDATA[

	events.push(function() {

	    $('#clearallnotices').click(function() {

			ajaxRequest = $.ajax({

				url: "/index.php",

				type: "post",

				data: { closenotice: "all"},

				success: function() {

					window.location = window.location.href;

				},

				failure: function() {

					alert("Error clearing notices!");

				}

			});

		});

	});

//]]>

</script>



<ul class="nav nav-pills"><li role="presentation" class="active"><a href="interfaces_wifi.php?tab=overview" >Overview & Status</a></li><li role="presentation"><a href="interfaces_wifi.php?tab=scan" >Scan & Connect (Client)</a></li><li role="presentation"><a href="interfaces_wifi.php?tab=ap" >Access Point (AP Mode)</a></li><li role="presentation"><a href="interfaces_wifi.php?tab=detect" >Hardware & Auto-Detect</a></li><li role="presentation"><a href="interfaces_wifi.php?tab=bridge" >VirtualBox / Bridged Wi-Fi</a></li></ul>
<style>
.wifi-card {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 20px;
    background: #fff;
    border: 1px solid #e5e9ec;
}
.wifi-card .panel-heading {
    border-top-left-radius: 7px;
    border-top-right-radius: 7px;
    font-weight: 600;
    padding: 12px 18px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e9ec;
}
.wifi-signal-bar {
    height: 8px;
    border-radius: 4px;
    background: #e9ecef;
    overflow: hidden;
    margin-top: 6px;
}
.wifi-signal-fill {
    height: 100%;
    transition: width 0.3s ease;
}
.badge-wifi-connected {
    background-color: #28a745;
    color: #fff;
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 12px;
}
.badge-wifi-disconnected {
    background-color: #6c757d;
    color: #fff;
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 12px;
}
.badge-wifi-ap {
    background-color: #007bff;
    color: #fff;
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 12px;
}
.badge-wifi-bridge {
    background-color: #17a2b8;
    color: #fff;
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 12px;
}
.table-wifi td, .table-wifi th {
    vertical-align: middle !important;
}
.iface-selector-bar {
    background: #edf2f7;
    border: 1px solid #e2e8f0;
    padding: 12px 18px;
    border-radius: 8px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
</style>

<div class="container-fluid" style="padding-top: 15px;">

    <!-- TOP SELECTOR: AUTO-DETECTED WIRELESS & NETWORK INTERFACES -->
    <div class="iface-selector-bar">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <label style="margin: 0; font-weight: 600; font-size: 14px;">
                <i class="fa-solid fa-network-wired text-primary"></i> Antarmuka Aktif (Auto-Detected):            </label>
            <select id="select-active-iface" class="form-control" style="width: auto; min-width: 320px; font-weight: 600;" onchange="changeActiveInterface(this.value)">
                <option value="vtnet0" selected>vtnet0 (Network Interface - IP: 10.10.66.47)</option><option value="vtnet1" >vtnet1 (Network Interface - IP: 192.168.1.1)</option>            </select>
            <span id="iface-switch-msg" class="text-success" style="display: none; font-weight: 600;">
                <i class="fa-solid fa-circle-check"></i> Interface updated            </span>
        </div>
        <div>
            <button class="btn btn-sm btn-info" onclick="triggerHardwareDetect()">
                <i class="fa-solid fa-microchip"></i> Re-Detect Hardware & Interfaces            </button>
        </div>
    </div>

    <!-- TAB 1: OVERVIEW & STATUS -->
    <div class="row">
        <div class="col-md-7">
            <div class="panel panel-default wifi-card">
                <div class="panel-heading">
                    <i class="fa-solid fa-wifi text-primary" style="margin-right: 8px;"></i>
                    Wireless & Network Interface Status                    <button class="btn btn-xs btn-default pull-right" onclick="refreshStatus()">
                        <i class="fa-solid fa-arrows-rotate"></i> Refresh                    </button>
                </div>
                <div class="panel-body">
                    <table class="table table-striped table-hover table-wifi">
                        <tbody>
                            <tr>
                                <th style="width: 35%;">Operating Mode</th>
                                <td>
                                                                            <span class="badge badge-wifi-bridge"><i class="fa-solid fa-link"></i> VirtualBox Bridged Wi-Fi Uplink</span>
                                                                    </td>
                            </tr>
                            <tr>
                                <th>Active Interface</th>
                                <td><code id="stat-iface" style="font-size: 14px;">vtnet0</code></td>
                            </tr>
                            <tr>
                                <th>Connection State</th>
                                <td>
                                    <strong id="stat-state" class="text-success">
                                        Active (Bridged to Host Wi-Fi)                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <th>IP Address</th>
                                <td><strong id="stat-ip" style="font-size: 14px;">10.10.66.47</strong></td>
                            </tr>
                            <tr>
                                <th>Subnet Mask</th>
                                <td><span id="stat-netmask">0xffffff00</span></td>
                            </tr>
                            <tr>
                                <th>Default Gateway</th>
                                <td><span id="stat-gateway">10.10.66.254</span></td>
                            </tr>
                            <tr>
                                <th>MAC Address</th>
                                <td><code id="stat-mac">08:00:27:27:30:9e</code></td>
                            </tr>
                            <tr>
                                <th>Link Media & Speed</th>
                                <td><span id="stat-media">Ethernet autoselect (10Gbase-T <full-duplex>)</span></td>
                            </tr>
                                                    </tbody>
                    </table>

                    <div style="margin-top: 15px;">
                        <a href="interfaces_wifi.php?tab=scan" class="btn btn-primary">
                            <i class="fa-solid fa-satellite-dish"></i> Scan & Connect Networks                        </a>
                        <a href="interfaces_wifi.php?tab=detect" class="btn btn-info pull-right">
                            <i class="fa-solid fa-microchip"></i> Hardware & Auto-Detect                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="panel panel-default wifi-card">
                <div class="panel-heading">
                    <i class="fa-solid fa-circle-info text-info" style="margin-right: 8px;"></i>
                    System & Hardware Auto-Detection                </div>
                <div class="panel-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Environment Platform                            <span class="badge">kvm</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Physical 802.11 Radios (net.wlan.devices)                            <span class="badge">None (Virtual NIC)</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Cloned 802.11 Interfaces                            <span class="badge">None</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Bridged Uplink Candidates                            <span class="badge">vtnet0, vtnet1</span>
                        </li>
                    </ul>

                                        <div class="alert alert-info" style="margin-top: 15px; margin-bottom: 0; font-size: 13px;">
                        <i class="fa-solid fa-lightbulb"></i>
                        <strong>VirtualBox Bridged Wi-Fi Terdeteksi</strong><br>
                        pfSense berjalan di dalam VirtualBox dengan antarmuka <code>em1</code> dijembatani ke kartu Wi-Fi Host PC. Lalu lintas data internet & LAN tersambung secara otomatis melalui Wi-Fi Host.                    </div>
                                    </div>
            </div>
        </div>
    </div>


</div>

<script>
function changeActiveInterface(iface) {
    $.post('interfaces_wifi.php', {
        ajax: 1,
        act: 'set_iface',
        iface: iface
    }, function(res) {
        $('#iface-switch-msg').fadeIn().delay(1500).fadeOut();
        setTimeout(function() {
            window.location.reload();
        }, 500);
    }, 'json');
}

function refreshStatus() {
    $.getJSON('interfaces_wifi.php?ajax=1&act=status', function(res) {
        if (!res) return;
        $('#stat-iface').text(res.active_interface || 'em1');
        $('#stat-state').text(res.state || 'Active');
        $('#stat-ip').text(res.ip || 'No IP');
        $('#stat-netmask').text(res.netmask || 'N/A');
        $('#stat-gateway').text(res.gateway || 'None');
        $('#stat-mac').text(res.mac || 'N/A');
        $('#stat-media').text(res.media || 'Auto');
        if (res.is_wireless) {
            $('#stat-ssid').text(res.ssid || 'None');
            var pct = res.signal_percent || 0;
            $('#stat-signal-text').text(pct + '% (' + (res.signal_dbm || -100) + ' dBm)');
            $('#stat-signal-bar').css('width', pct + '%');
        }
    });
}

function triggerScan() {
    var iface = $('#scan-iface').val();
    $('#scan-loading').show();
    $('#scan-tbody').empty();
    $('#scan-alert-box').empty();
    $('#btn-scan').prop('disabled', true);

    $.getJSON('interfaces_wifi.php?ajax=1&act=scan&iface=' + encodeURIComponent(iface), function(resp) {
        $('#scan-loading').hide();
        $('#btn-scan').prop('disabled', false);

        if (!resp || !resp.status) {
            var msg = resp ? resp.msg : 'Scan gagal dijalankan.';
            $('#scan-alert-box').html('<div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> ' + msg + '</div>');
            $('#scan-tbody').html('<tr><td colspan="7" class="text-center text-muted" style="padding: 20px;">' + msg + '</td></tr>');
            return;
        }

        var networks = resp.networks || [];
        if (networks.length === 0) {
            $('#scan-alert-box').html('<div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> Tidak ada jaringan Wi-Fi terdeteksi pada jangkauan radio adapter ini.</div>');
            $('#scan-tbody').html('<tr><td colspan="7" class="text-center text-muted" style="padding: 20px;">Tidak ada jaringan Wi-Fi ditemukan.</td></tr>');
            return;
        }

        var html = '';
        $.each(networks, function(i, net) {
            var badgeSec = (net.security === 'Open') ? 'badge-wifi-disconnected' : 'badge-wifi-connected';
            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td><strong>' + $('<div>').text(net.ssid).html() + '</strong></td>';
            html += '<td><code>' + net.bssid + '</code></td>';
            html += '<td>' + net.channel + '</td>';
            html += '<td>';
            html += '<div style="display:flex; justify-content:space-between; font-size:12px;"><span>' + net.signal_percent + '%</span></div>';
            html += '<div class="wifi-signal-bar"><div class="wifi-signal-fill bg-success" style="width:' + net.signal_percent + '%;"></div></div>';
            html += '</td>';
            html += '<td><span class="badge ' + badgeSec + '">' + net.security + '</span></td>';
            html += '<td style="text-align:center;">';
            html += '<button class="btn btn-xs btn-primary" onclick="openConnectModal(\'' + escape(net.ssid) + '\', \'' + net.security + '\')">';
            html += '<i class="fa-solid fa-plug"></i> Connect</button>';
            html += '</td>';
            html += '</tr>';
        });
        $('#scan-tbody').html(html);
    }).fail(function() {
        $('#scan-loading').hide();
        $('#btn-scan').prop('disabled', false);
        $('#scan-tbody').html('<tr><td colspan="7" class="text-center text-danger">Gagal memanggil fungsi scan.</td></tr>');
    });
}

function openConnectModal(escapedSsid, sec) {
    var ssid = unescape(escapedSsid);
    $('#connect-ssid').val(ssid);
    $('#connect-security').val(sec);
    $('#connect-password').val('');
    $('#connect-status-msg').empty();
    if (sec === 'Open') {
        $('#group-password').hide();
    } else {
        $('#group-password').show();
    }
    $('#modal-connect').modal('show');
}

function togglePassView() {
    var inp = $('#connect-password');
    var icon = $('#eye-icon');
    if (inp.attr('type') === 'password') {
        inp.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        inp.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
}

function submitConnect() {
    var ssid = $('#connect-ssid').val();
    var sec = $('#connect-security').val();
    var pwd = $('#connect-password').val();
    var iface = $('#scan-iface').val();

    $('#btn-do-connect').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Menghubungkan...');
    $('#connect-status-msg').html('<div class="alert alert-info"><i class="fa-solid fa-spinner fa-spin"></i> Mengirim permintaan asosiasi Wi-Fi...</div>');

    $.post('interfaces_wifi.php', {
        ajax: 1,
        act: 'connect',
        ssid: ssid,
        security: sec,
        password: pwd,
        iface: iface
    }, function(res) {
        $('#btn-do-connect').prop('disabled', false).html('<i class="fa-solid fa-plug"></i> Sambungkan');
        if (res && res.status) {
            $('#connect-status-msg').html('<div class="alert alert-success"><i class="fa-solid fa-check"></i> ' + res.msg + '</div>');
            setTimeout(function() {
                $('#modal-connect').modal('hide');
                window.location.href = 'interfaces_wifi.php?tab=overview';
            }, 1800);
        } else {
            $('#connect-status-msg').html('<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> ' + (res ? res.msg : 'Gagal menyambung.') + '</div>');
        }
    }, 'json').fail(function() {
        $('#btn-do-connect').prop('disabled', false).html('<i class="fa-solid fa-plug"></i> Sambungkan');
        $('#connect-status-msg').html('<div class="alert alert-danger">Permintaan koneksi gagal.</div>');
    });
}

function submitAP() {
    var ssid = $('#ap-ssid').val();
    var pwd = $('#ap-password').val();
    var chan = $('#ap-channel').val();
    var ip = $('#ap-ip').val();

    $('#btn-ap-start').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Memulai...');
    $('#ap-alert-box').html('<div class="alert alert-info"><i class="fa-solid fa-spinner fa-spin"></i> Mengaktifkan layanan Access Point...</div>');

    $.post('interfaces_wifi.php', {
        ajax: 1,
        act: 'ap_start',
        ap_ssid: ssid,
        ap_password: pwd,
        ap_channel: chan,
        ap_ip: ip
    }, function(res) {
        $('#btn-ap-start').prop('disabled', false).html('<i class="fa-solid fa-play"></i> Mulai Access Point');
        if (res && res.status) {
            $('#ap-alert-box').html('<div class="alert alert-success"><i class="fa-solid fa-check"></i> ' + res.msg + '</div>');
        } else {
            $('#ap-alert-box').html('<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> ' + (res ? res.msg : 'Gagal memulai AP') + '</div>');
        }
    }, 'json');
}

function stopAP() {
    $.post('interfaces_wifi.php', { ajax: 1, act: 'ap_stop' }, function(res) {
        $('#ap-alert-box').html('<div class="alert alert-info">' + res.msg + '</div>');
    }, 'json');
}

function triggerHardwareDetect() {
    window.location.reload();
}

function submitBridge() {
    var iface = $('#bridge-iface').val();
    $('#btn-bridge-apply').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Menerapkan...');
    $('#bridge-alert-box').html('<div class="alert alert-info"><i class="fa-solid fa-spinner fa-spin"></i> Menghubungkan interface ' + iface + '...</div>');

    $.post('interfaces_wifi.php', {
        ajax: 1,
        act: 'bridge_setup',
        bridge_iface: iface
    }, function(res) {
        $('#btn-bridge-apply').prop('disabled', false).html('<i class="fa-solid fa-check"></i> Apply Bridged Wi-Fi Uplink');
        if (res && res.status) {
            $('#bridge-alert-box').html('<div class="alert alert-success"><i class="fa-solid fa-check"></i> ' + res.msg + '</div>');
            setTimeout(function() {
                window.location.href = 'interfaces_wifi.php?tab=overview';
            }, 1800);
        } else {
            $('#bridge-alert-box').html('<div class="alert alert-danger">' + (res ? res.msg : 'Gagal') + '</div>');
        }
    }, 'json');
}
</script>

	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
