<?php
/*
 * services_virtual.php - MitraNet DIRECT: KVM
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("DIRECT", "KVM");
$selected_menu = "direct";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/services_virtual.php" >Virtual Machines</a></li><li role="presentation"><a href="/services_virtual.php?act=images" >ISO & Images Manager</a></li><li role="presentation"><a href="/services_virtual.php?act=hypervisor" >Hypervisor Status</a></li><li role="presentation"><a href="/services_virtual.php?act=network" >Network & Bridge</a></li></ul>

<div class="panel panel-default">
        <div class="panel-heading">
            <h2 class="panel-title">
                <i class="fa-solid fa-server"></i> Daftar Virtual Machine (KVM / Bhyve Hypervisor)            </h2>
        </div>
        <div class="panel-body">
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <a href="services_virtual.php?act=add" class="btn btn-success">
                        <i class="fa-solid fa-plus"></i> Tambah Virtual Machine Baru                    </a>
                    <a href="services_virtual.php?act=images" class="btn btn-default" style="margin-left: 5px;">
                        <i class="fa-solid fa-compact-disc text-primary"></i> Kelola ISO / Images                    </a>
                    <form method="post" action="services_virtual.php" style="display: inline-block; margin-left: 5px;">
                        <input type="hidden" name="act" value="setup_hypervisor" />
                        <button type="submit" class="btn btn-primary" title="Muat modul kernel hypervisor">
                            <i class="fa-solid fa-shield-halved"></i> Aktifkan Hypervisor                        </button>
                    </form>
                </div>
                <div>
                    <a href="http://10.10.66.47:8888" target="_blank" class="btn btn-info">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka aaPanel Web (Port 8888)                    </a>
                </div>
            </div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
