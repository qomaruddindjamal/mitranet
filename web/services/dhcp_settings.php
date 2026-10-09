<?php
/*
 * services_dhcp_settings.php - MitraNet Services: DHCP Server Management
 * Multi-interface DHCP management (vEthernet, Bridge, LAN, VLAN)
 * Powered by dnsmasq daemon on Debian 13 (Trixie) Appliance
 */

require_once(__DIR__ . '/../includes/api.inc');

$msg = "";
$err = "";

// 1. Fetch system interfaces, bridges, vethernets, and gateways
$all_interfaces = MitraNetApi::getInterfaces();
$all_bridges = MitraNetApi::getBridges();
$all_vethernets = MitraNetApi::getVethernets();
$all_vlans = MitraNetApi::getVlans();
$gateways = MitraNetApi::getGateways();

// Detect WAN interface to exclude from DHCP server
$wan_ifaces = [];
foreach ($gateways as $gw) {
    if (!empty($gw['default']) && !empty($gw['interface'])) {
        $wan_ifaces[] = $gw['interface'];
    }
}
$wan_ifaces = array_unique($wan_ifaces);

// Build list of valid internal interfaces eligible for DHCP Server
// Order Priority: 1. Physical LAN (non-WAN) -> 2. Bridges -> 3. vEthernet -> 4. VLANs
$eligible_ifaces = [];

// 1. Physical LAN Interfaces (Non-WAN) - Highest Priority
foreach ($all_interfaces as $it) {
    $n = $it['name'];
    if ($n === 'lo' || in_array($n, $wan_ifaces) || str_starts_with($n, 'veth') || str_starts_with($n, 'tap') || str_contains($n, '.') || str_starts_with($n, 'vlan') || str_starts_with($n, 'wg') || str_starts_with($n, 'tun') || str_starts_with($n, 'ppp') || str_starts_with($n, 'sit') || str_starts_with($n, 'gre')) {
        continue;
    }
    // Only ethernet/wireless physical interfaces
    $type = $it['type'] ?? '';
    if (!in_array($type, ['ether', 'wlan', ''])) {
        continue;
    }
    // Check if it's already a bridge
    $is_br = false;
    foreach ($all_bridges as $br) {
        if ($br['name'] === $n) { $is_br = true; break; }
    }
    if ($is_br) continue;

    $eligible_ifaces[$n] = [
        'name' => $n,
        'label' => "LAN: {$n}",
        'ip_cidr' => $it['ipv4_addresses'][0] ?? '',
        'type' => 'Physical LAN'
    ];
}

// 2. Bridges (Layer-2 Shared LAN Segment)
foreach ($all_bridges as $br) {
    $br_ip = '';
    // Look up IP from interfaces
    foreach ($all_interfaces as $it) {
        if ($it['name'] === $br['name']) {
            $br_ip = $it['ipv4_addresses'][0] ?? '';
            break;
        }
    }
    $eligible_ifaces[$br['name']] = [
        'name' => $br['name'],
        'label' => "Bridge: {$br['name']}",
        'ip_cidr' => $br_ip,
        'type' => 'Bridge'
    ];
}

// 3. vEthernet virtual interfaces (Internal VM Subnets)
foreach ($all_vethernets as $ve) {
    $eligible_ifaces[$ve['name']] = [
        'name' => $ve['name'],
        'label' => "vEthernet: {$ve['name']}",
        'ip_cidr' => $ve['ip_cidr'] ?? '',
        'type' => 'vEthernet'
    ];
}

// 4. 802.1Q VLANs
foreach ($all_vlans as $vl) {
    $vl_name = $vl['name'] ?? '';
    if ($vl_name && !isset($eligible_ifaces[$vl_name]) && !in_array($vl_name, $wan_ifaces)) {
        $vl_ip = '';
        foreach ($all_interfaces as $it) {
            if ($it['name'] === $vl_name) {
                $vl_ip = $it['ipv4_addresses'][0] ?? '';
                break;
            }
        }
        $eligible_ifaces[$vl_name] = [
            'name' => $vl_name,
            'label' => "VLAN: {$vl_name}",
            'ip_cidr' => $vl_ip,
            'type' => 'VLAN'
        ];
    }
}

// Selected interface tab
$current_if = $_GET['if'] ?? '';
if (!$current_if || !isset($eligible_ifaces[$current_if])) {
    $current_if = array_key_first($eligible_ifaces) ?: 'veth0';
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_if = trim($_POST['interface'] ?? '');
    $enabled = !empty($_POST['enable']);
    $range_start = trim($_POST['range_start'] ?? '');
    $range_end = trim($_POST['range_end'] ?? '');
    $gateway = trim($_POST['gateway'] ?? '');
    $dns_raw = trim($_POST['dns'] ?? '');
    $lease_time = trim($_POST['lease_time'] ?? '12h');

    $dns_arr = [];
    if (!empty($dns_raw)) {
        $dns_arr = array_filter(array_map('trim', explode(',', str_replace(' ', ',', $dns_raw))));
    }

    $payload = [
        'interface' => $post_if,
        'enabled' => $enabled,
        'range_start' => $range_start,
        'range_end' => $range_end,
        'gateway' => $gateway,
        'dns' => array_values($dns_arr),
        'lease_time' => $lease_time
    ];

    $res = MitraNetApi::saveDhcpConfig($payload);
    if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
        $msg = "Konfigurasi DHCP Server untuk interface '{$post_if}' berhasil disimpan dan aktif.";
        $current_if = $post_if;
    } else {
        $err = $res['data']['error'] ?? "Gagal menyimpan konfigurasi DHCP Server.";
    }
}

// Load active DHCP configs
$dhcp_configs = MitraNetApi::getDhcpConfigs();
$active_cfg = $dhcp_configs[$current_if] ?? [];

// Default auto-calculation if not yet configured
$curr_if_info = $eligible_ifaces[$current_if] ?? [];
$curr_ip_cidr = $curr_if_info['ip_cidr'] ?? '';
$calc_gateway = $curr_ip_cidr ? explode('/', $curr_ip_cidr)[0] : '';
$calc_start = '';
$calc_end = '';
if ($calc_gateway && substr_count($calc_gateway, '.') === 3) {
    $octets = explode('.', $calc_gateway);
    $prefix = "{$octets[0]}.{$octets[1]}.{$octets[2]}";
    $calc_start = "{$prefix}.10";
    $calc_end = "{$prefix}.240";
}

$is_enabled = isset($active_cfg['enabled']) ? $active_cfg['enabled'] : true;
$field_start = $active_cfg['range_start'] ?? $calc_start;
$field_end = $active_cfg['range_end'] ?? $calc_end;
$field_gateway = $active_cfg['gateway'] ?? $calc_gateway;
$field_dns = !empty($active_cfg['dns']) ? implode(', ', $active_cfg['dns']) : ($calc_gateway ? "{$calc_gateway}, 8.8.8.8, 1.1.1.1" : "8.8.8.8, 1.1.1.1");
$field_lease = $active_cfg['lease_time'] ?? '12h';

$pgtitle = array(gettext("Services"), gettext("DHCP Server"));
$selected_menu = "services";
require_once(__DIR__ . '/../includes/head.inc');

if (!empty($msg)) {
    print_info_box($msg, "success");
}
if (!empty($err)) {
    print_info_box($err, "danger");
}
?>

<ul class="nav nav-tabs mb-20">
    <?php foreach ($eligible_ifaces as $if_key => $if_data): ?>
        <li role="presentation" class="<?=$current_if === $if_key ? 'active' : ''?>">
            <a href="services_dhcp_settings.php?if=<?=urlencode($if_key)?>">
                <i class="fa-solid fa-network-wired"></i> <?=htmlspecialchars($if_data['label'])?>
                <?php if (!empty($dhcp_configs[$if_key]['enabled'])): ?>
                    <span class="badge badge-on-xs">ON</span>
                <?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title">
            <i class="fa-solid fa-server"></i> <?=gettext("DHCP Server Configuration for")?> <strong><?=htmlspecialchars($eligible_ifaces[$current_if]['label'] ?? $current_if)?></strong>
        </h2>
    </div>
    <div class="panel-body">
        <form method="post" action="services_dhcp_settings.php?if=<?=urlencode($current_if)?>" class="form-horizontal">
            <input type="hidden" name="interface" value="<?=htmlspecialchars($current_if)?>" />

            <div class="form-group">
                <label class="col-sm-3 control-label"><?=gettext("Enable")?></label>
                <div class="col-sm-6">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="enable" value="1" <?=$is_enabled ? 'checked' : ''?> />
                            <strong><?=gettext("Aktifkan DHCP Server pada interface ini")?></strong>
                        </label>
                    </div>
                    <span class="help-block"><?=gettext("Melayani permintaan IP otomatis (DHCP lease) untuk VM KVM dan perangkat client pada subnet ini.")?></span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label"><?=gettext("Subnet & IP Interface")?></label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" value="<?=htmlspecialchars($curr_ip_cidr ?: 'Belum ada IP pada interface ini')?>" readonly disabled />
                    <span class="help-block"><?=gettext("Alamat IP Host/Gateway yang terpasang pada interface sistem.")?></span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label"><span class="element-required">*</span><?=gettext("DHCP Address Pool Range")?></label>
                <div class="col-sm-6">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="input-group">
                                <span class="input-group-addon"><?=gettext("From:")?></span>
                                <input type="text" name="range_start" class="form-control" value="<?=htmlspecialchars($field_start)?>" placeholder="192.168.101.10" required />
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="input-group">
                                <span class="input-group-addon"><?=gettext("To:")?></span>
                                <input type="text" name="range_end" class="form-control" value="<?=htmlspecialchars($field_end)?>" placeholder="192.168.101.240" required />
                            </div>
                        </div>
                    </div>
                    <span class="help-block"><?=gettext("Rentang IP yang akan dibagikan secara dinamis kepada client/VM.")?></span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label"><?=gettext("Default Gateway")?></label>
                <div class="col-sm-6">
                    <input type="text" name="gateway" class="form-control" value="<?=htmlspecialchars($field_gateway)?>" placeholder="contoh: 192.168.101.254" />
                    <span class="help-block"><?=gettext("Alamat IP router yang diberikan ke client. Kosongkan untuk menggunakan IP interface ini.")?></span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label"><?=gettext("DNS Servers")?></label>
                <div class="col-sm-6">
                    <input type="text" name="dns" class="form-control" value="<?=htmlspecialchars($field_dns)?>" placeholder="contoh: 192.168.101.254, 8.8.8.8, 1.1.1.1" />
                    <span class="help-block"><?=gettext("Pisahkan dengan koma jika lebih dari satu server DNS.")?></span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label"><?=gettext("Default Lease Time")?></label>
                <div class="col-sm-6">
                    <input type="text" name="lease_time" class="form-control" value="<?=htmlspecialchars($field_lease)?>" placeholder="12h" />
                    <span class="help-block"><?=gettext("Durasi masa sewa IP (contoh: 1h, 12h, 24h, 7d).")?></span>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-6">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> <?=gettext("Simpan & Terapkan DHCP")?>
                    </button>
                    <a href="status_dhcp_leases.php" class="btn btn-default ml-1">
                        <i class="fa-solid fa-list-check"></i> <?=gettext("Lihat Status DHCP Leases")?>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
