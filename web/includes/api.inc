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
}
