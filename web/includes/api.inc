<?php
/*
 * MitraNet REST API Client & Helper Library
 * Connects PHP Presentation Layer directly to the MitraNet Management REST API.
 * Strict zero direct shell execution.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('API_BASE_URL', 'http://127.0.0.1:8443/api/v1');

class MitraNetApi {

    public static function request($endpoint, $method = 'GET', $data = null) {
        $url = API_BASE_URL . $endpoint;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $headers = ['Accept: application/json'];
        
        // Pass session cookie if available
        if (!empty($_COOKIE['mitranet_session'])) {
            curl_setopt($ch, CURLOPT_COOKIE, 'mitranet_session=' . $_COOKIE['mitranet_session']);
        } elseif (!empty($_SESSION['mitranet_session'])) {
            curl_setopt($ch, CURLOPT_COOKIE, 'mitranet_session=' . $_SESSION['mitranet_session']);
        }

        // Pass CSRF Token for mutation requests
        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/json';
            $csrf = !empty($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
            if ($csrf) {
                $headers[] = 'X-CSRF-Token: ' . $csrf;
            }
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['status' => $httpCode, 'error' => $error];
        }

        $decoded = json_decode($response, true);
        return ['status' => $httpCode, 'data' => $decoded];
    }

    public static function login($username, $password) {
        $res = self::request('/auth/login', 'POST', [
            'username' => $username,
            'password' => $password
        ]);

        if ($res['status'] === 200 && !empty($res['data']['success'])) {
            $_SESSION['username'] = $res['data']['username'];
            $_SESSION['csrf_token'] = $res['data']['csrf_token'];
            
            // Set cookie if received in response
            if (!empty($res['data']['session_token'])) {
                $_SESSION['mitranet_session'] = $res['data']['session_token'];
                setcookie('mitranet_session', $res['data']['session_token'], time() + 3600, '/', '', false, true);
            }
            return true;
        }
        return false;
    }

    public static function logout() {
        self::request('/auth/logout', 'POST', []);
        session_destroy();
        setcookie('mitranet_session', '', time() - 3600, '/');
    }

    public static function isAuthenticated() {
        if (!empty($_SESSION['username']) && !empty($_SESSION['csrf_token'])) {
            return true;
        }
        $res = self::request('/auth/status', 'GET');
        if ($res['status'] === 200 && !empty($res['data']['authenticated'])) {
            $_SESSION['username'] = $res['data']['username'];
            if (!empty($res['data']['csrf_token'])) {
                $_SESSION['csrf_token'] = $res['data']['csrf_token'];
            }
            return true;
        }
        return false;
    }

    public static function getSystem() {
        $res = self::request('/system');
        return $res['status'] === 200 ? $res['data'] : [];
    }

    public static function getInterfaces() {
        $res = self::request('/interfaces');
        return $res['status'] === 200 ? $res['data'] : [];
    }

    public static function getInterface($name) {
        $res = self::request('/interfaces/' . urlencode($name));
        return $res['status'] === 200 ? $res['data'] : null;
    }

    public static function setInterfaceState($name, $state) {
        return self::request('/interfaces/set-state', 'POST', ['name' => $name, 'state' => $state]);
    }

    public static function setInterfaceMtu($name, $mtu) {
        return self::request('/interfaces/set-mtu', 'POST', ['name' => $name, 'mtu' => intval($mtu)]);
    }

    public static function addInterfaceAddress($name, $cidr) {
        return self::request('/interfaces/address/add', 'POST', ['name' => $name, 'cidr' => $cidr]);
    }

    public static function removeInterfaceAddress($name, $cidr) {
        return self::request('/interfaces/address/remove', 'POST', ['name' => $name, 'cidr' => $cidr]);
    }

    public static function getRoutes() {
        $res = self::request('/routes');
        return $res['status'] === 200 ? $res['data'] : [];
    }

    public static function getFirewall() {
        $res = self::request('/firewall');
        return $res['status'] === 200 ? $res['data'] : [];
    }

    public static function getGateways() {
        $res = self::request('/gateways');
        return $res['status'] === 200 ? $res['data'] : [];
    }

    public static function getConfigStatus() {
        $res = self::request('/config/status');
        return $res['status'] === 200 ? $res['data'] : [];
    }

    public static function getLogs($category = 'system') {
        $res = self::request('/logs?category=' . urlencode($category));
        return $res['status'] === 200 ? ($res['data']['lines'] ?? []) : [];
    }

    public static function getVlans() {
        $res = self::request('/vlans');
        return $res['status'] === 200 ? $res['data'] : [];
    }

    public static function getBridges() {
        $res = self::request('/bridges');
        return $res['status'] === 200 ? $res['data'] : [];
    }

    public static function getBonds() {
        $res = self::request('/bonds');
        return $res['status'] === 200 ? $res['data'] : [];
    }

    public static function getVrfs() {
        $res = self::request('/vrfs');
        return $res['status'] === 200 ? $res['data'] : [];
    }

    public static function getArpTable() {
        $res = self::request('/arp');
        return $res['status'] === 200 ? $res['data'] : [];
    }

    public static function getConntrackStates() {
        $res = self::request('/conntrack');
        return $res['status'] === 200 ? $res['data'] : ['total' => 0, 'states' => []];
    }

    public static function ping($host, $count = 3) {
        return self::request('/diag/ping', 'POST', ['host' => $host, 'count' => $count]);
    }

    public static function traceroute($host) {
        return self::request('/diag/traceroute', 'POST', ['host' => $host]);
    }

    public static function updateHostname($hostname) {
        return self::request('/system/update', 'POST', ['hostname' => $hostname]);
    }

    public static function updateSystemSettings($data) {
        return self::request('/system/update', 'POST', $data);
    }


    public static function getPackages() {
        $res = self::request('/packages');
        return $res['status'] === 200 ? ($res['data']['packages'] ?? []) : [];
    }

    public static function getSystemUpdateCheck() {
        $res = self::request('/system/update/check');
        return $res['status'] === 200 ? $res['data'] : [
            'current_version' => '1.0.2-RELEASE',
            'latest_version' => '1.0.2-RELEASE',
            'up_to_date' => true,
            'branch' => 'stable'
        ];
    }


    public static function getWireGuard() {
        $res = self::request('/wireguard');
        return $res['status'] === 200 ? $res['data'] : ['running' => false, 'tunnels' => []];
    }

    public static function controlWireGuard($action) {
        return self::request('/wireguard/service', 'POST', ['action' => $action]);
    }

    public static function generateWireGuardKeys() {
        return self::request('/wireguard/keygen', 'POST', []);
    }

    public static function saveWireGuardTunnel($name, $address, $listen_port, $private_key, $descr = '') {
        return self::request('/wireguard/tunnel/save', 'POST', [
            'name' => $name,
            'address' => $address,
            'listen_port' => $listen_port,
            'private_key' => $private_key,
            'descr' => $descr
        ]);
    }

    public static function saveWireGuardPeer($tunnel, $pubkey, $allowed_ips, $endpoint = '', $keepalive = '25', $preshared = '', $descr = '') {
        return self::request('/wireguard/peer/save', 'POST', [
            'tunnel' => $tunnel,
            'public_key' => $pubkey,
            'allowed_ips' => $allowed_ips,
            'endpoint' => $endpoint,
            'keepalive' => $keepalive,
            'preshared_key' => $preshared,
            'descr' => $descr
        ]);
    }

    public static function deleteWireGuardPeer($tunnel, $pubkey) {
        return self::request('/wireguard/peer/delete', 'POST', [
            'tunnel' => $tunnel,
            'public_key' => $pubkey
        ]);
    }

    public static function getXray() {
        $res = self::request('/xray');
        return $res['status'] === 200 ? $res['data'] : ['running' => false, 'version' => '', 'config' => []];
    }

    public static function controlXray($action) {
        return self::request('/xray/service', 'POST', ['action' => $action]);
    }

    public static function getUsers() {
        $res = self::request('/users');
        return $res['status'] === 200 ? ($res['data']['users'] ?? []) : [];
    }

    public static function createUser($username, $password) {
        return self::request('/users/create', 'POST', ['username' => $username, 'password' => $password]);
    }

    public static function deleteUser($username) {
        return self::request('/users/delete', 'POST', ['username' => $username]);
    }

    public static function changeUserPassword($username, $new_password) {
        return self::request('/users/password', 'POST', ['username' => $username, 'password' => $new_password]);
    }

    public static function getCertificates() {
        $res = self::request('/certificates');
        return $res['status'] === 200 ? $res['data'] : ['authorities' => [], 'certificates' => []];
    }


    public static function getSpeedtestServers() {
        $res = self::request('/tools/speedtest/servers');
        return $res['status'] === 200 ? ($res['data']['servers'] ?? []) : [];
    }

    public static function getSpeedtestHistory() {
        $res = self::request('/tools/speedtest/history');
        return $res['status'] === 200 ? ($res['data']['history'] ?? []) : [];
    }

    public static function runSpeedtest($engine = 'ookla', $interface = '', $serverId = '') {
        return self::request('/tools/speedtest/run', 'POST', [
            'engine' => $engine,
            'interface' => $interface,
            'server_id' => $serverId
        ]);
    }

    public static function clearSpeedtestHistory() {
        return self::request('/tools/speedtest/clear-history', 'POST', []);
    }

    public static function execCommand($command, $cwd = '/root') {
        return self::request('/diagnostics/command', 'POST', ['command' => $command, 'cwd' => $cwd]);
    }

    public static function getKvmData() {
        $res = self::request('/services/kvm');
        return $res['status'] === 200 ? ($res['data'] ?? ['vms' => [], 'isos' => []]) : ['vms' => [], 'isos' => []];
    }

    public static function getKvmVms() {
        $data = self::getKvmData();
        return $data['vms'] ?? [];
    }

    public static function getKvmIsos() {
        $data = self::getKvmData();
        return $data['isos'] ?? [];
    }

    public static function vmAction($id, $action) {
        return self::request('/services/kvm/action', 'POST', ['id' => $id, 'action' => $action]);
    }

    public static function createVm($payload) {
        return self::request('/services/kvm/create', 'POST', $payload);
    }

    public static function updateVm($payload) {
        return self::request('/services/kvm/update', 'POST', $payload);
    }

    public static function deleteVm($id) {
        return self::request('/services/kvm/delete', 'POST', ['id' => $id]);
    }

    public static function deleteIso($filename) {
        return self::request('/services/kvm/iso/delete', 'POST', ['filename' => $filename]);
    }

    public static function getVethernets() {
        $res = self::request('/interfaces/vethernet');
        return $res['status'] === 200 ? ($res['data']['vethernet'] ?? []) : [];
    }

    public static function createVethernet($name, $ip_cidr, $description = '') {
        return self::request('/interfaces/vethernet/create', 'POST', [
            'name' => $name,
            'ip_cidr' => $ip_cidr,
            'description' => $description
        ]);
    }

    public static function deleteVethernet($name) {
        return self::request('/interfaces/vethernet/delete', 'POST', ['name' => $name]);
    }

    public static function getDhcpConfigs() {
        $res = self::request('/services/dhcp');
        return $res['status'] === 200 ? ($res['data']['dhcp'] ?? []) : [];
    }

    public static function saveDhcpConfig($payload) {
        return self::request('/services/dhcp/save', 'POST', $payload);
    }
}

