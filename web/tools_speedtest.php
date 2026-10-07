<?php
/*
 * tools_speedtest.php - MitraNet Tools: Speedtest
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Tools", "Speedtest");
$selected_menu = "tools";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
                    <div class="panel-heading"><h3 class="panel-title"><i class="fa-solid fa-circle-info"></i> Connection Details</h3></div>
                    <div class="panel-body">
                        <div class="info-item">
                            <span class="info-title">ISP Provider:</span>
                            <span class="info-val" id="det-isp">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-title">Client IP:</span>
                            <span class="info-val" id="det-ip">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-title">Packet Loss:</span>
                            <span class="info-val" id="det-loss">-</span>
                        </div>
                    </div>

<div class="panel panel-default">
                    <div class="panel-heading"><h3 class="panel-title"><i class="fa-solid fa-server"></i> Target Server</h3></div>
                    <div class="panel-body">
                        <div class="info-item">
                            <span class="info-title">Server Sponsor:</span>
                            <span class="info-val" id="det-server">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-title">Tested Interface:</span>
                            <span class="info-val" id="det-if">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-title">Online Result:</span>
                            <span class="info-val" id="det-url">-</span>
                        </div>
                    </div>

<div class="panel panel-default" style="margin-top: 10px;">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa-solid fa-clock-rotate-left"></i> Speedtest History (Last 20 Tests)                    <button type="button" id="btn-clear-history" class="btn btn-xs btn-default pull-right">
                        <i class="fa-solid fa-trash"></i> Clear History                    </button>
                </h3>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Engine</th>
                            <th>Interface</th>
                            <th>Server</th>
                            <th>Ping</th>
                            <th>Download</th>
                            <th>Upload</th>
                            <th>Link</th>
                        </tr>
                    </thead>
                    <tbody id="history-tbody">
                                                    <tr><td colspan="8" class="text-center text-muted" style="padding: 20px; font-style: italic;">Belum ada data pengujian. Silakan klik tombol 'Start Test' untuk melakukan pengujian kecepatan riil.</td></tr>
                                            </tbody>
                </table>
            </div>
        </div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
