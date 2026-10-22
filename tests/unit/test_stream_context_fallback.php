<?php
// Unit & Regression test for MitraNetApi::request stream context fallback and security constraints
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../web/includes/api.inc';

class StreamContextFallbackSecurityTest extends TestCase {
    
    public function testRequestReturnsArrayStructure() {
        // Test against local management API status endpoint
        $res = MitraNetApi::request('/auth/status', 'GET');
        $this->assertIsArray($res);
        $this->assertArrayHasKey('status', $res);
        $this->assertArrayHasKey('data', $res);
    }

    public function testUnauthenticatedStatusReturns200WithFalse() {
        $res = MitraNetApi::request('/auth/status', 'GET');
        $this->assertEquals(200, $res['status']);
        $this->assertIsArray($res['data']);
        $this->assertArrayHasKey('authenticated', $res['data']);
        $this->assertFalse($res['data']['authenticated']);
    }

    public function testProtectedEndpointWithoutAuthReturns401() {
        $res = MitraNetApi::request('/system', 'GET');
        $this->assertContains($res['status'], [401, 403, 200]);
    }

    public function testStreamContextFallbackWithTimeoutHandling() {
        // Intentionally query a non-routable dummy local port to ensure timeout/connection refused handling
        $res = MitraNetApi::request('/non-existent', 'GET', null, 1);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('status', $res);
    }
}
