#!/usr/bin/env python3
# ==============================================================================
# MitraNet Network OS - Platform & ONLP Hardware Abstraction Engine
# Bridges Bare-Metal White-Box Switch Telemetry (ONLP) & Linux Sysfs to MitraNet
# ==============================================================================

import os
import json
import subprocess
from typing import Dict, Any, List

def run_cmd(cmd_list, capture=True) -> str:
    try:
        res = subprocess.run(cmd_list, stdout=subprocess.PIPE if capture else None,
                             stderr=subprocess.PIPE if capture else None,
                             text=True, shell=isinstance(cmd_list, str))
        return res.stdout.strip() if capture else ""
    except Exception as e:
        return f"Error: {str(e)}"

def get_onlp_platform_info() -> Dict[str, Any]:
    """Queries ONLP (Open Network Linux Platform) sysi or Linux sysfs."""
    onlp_sysi = run_cmd("onlp-sysi 2>/dev/null")
    if onlp_sysi and "Error" not in onlp_sysi and len(onlp_sysi) > 20:
        return {
            "platform_type": "Bare-Metal Whitebox Switch (ONLP Active)",
            "driver": "ONLP Hardware Subsystem",
            "raw_info": onlp_sysi.splitlines()
        }
    
    board = run_cmd("cat /sys/class/dmi/id/product_name 2>/dev/null") or "MitraNet Edge Appliance (x86_64)"
    vendor = run_cmd("cat /sys/class/dmi/id/sys_vendor 2>/dev/null") or "MitraNet Systems"
    bios = run_cmd("cat /sys/class/dmi/id/bios_version 2>/dev/null") or "UEFI 2.8.0"
    return {
        "platform_type": "MitraNet Standard Edge Appliance",
        "vendor": vendor,
        "product_name": board,
        "bios_version": bios,
        "onlp_support": bool(os.path.exists("/usr/bin/onlp-sysi"))
    }

def get_sfp_diagnostics(iface: str = "eth0") -> Dict[str, Any]:
    """Reads Optical SFP / SFP+ / QSFP DDM/DOM telemetry via ONLP or ethtool."""
    if os.path.exists("/usr/bin/onlp-sfp"):
        sfp_res = run_cmd(f"onlp-sfp show {iface} 2>/dev/null")
        if sfp_res and "Error" not in sfp_res:
            return {
                "interface": iface,
                "driver": "ONLP Optical Subsystem",
                "telemetry": sfp_res.splitlines()
            }

    sfp_raw = run_cmd(f"ethtool -m {iface} 2>/dev/null")
    if sfp_raw and "Cannot get" not in sfp_raw and len(sfp_raw) > 30:
        return {
            "interface": iface,
            "driver": "Linux Kernel DDM (ethtool)",
            "telemetry": sfp_raw.splitlines()
        }

    carrier = run_cmd(f"cat /sys/class/net/{iface}/carrier 2>/dev/null")
    operstate = run_cmd(f"cat /sys/class/net/{iface}/operstate 2>/dev/null") or "unknown"
    speed = run_cmd(f"cat /sys/class/net/{iface}/speed 2>/dev/null")
    speed_str = f"{speed} Mbps" if speed and speed.isdigit() else "Auto/Unknown"
    return {
        "interface": iface,
        "driver": "Standard Network PHY",
        "telemetry": [
            f"Interface {iface}: Operstate {operstate}, Link speed {speed_str}",
            "Optical DDM: Not an optical transceiver or SFP module not present."
        ]
    }

def get_thermal_and_fan_info() -> Dict[str, Any]:
    """Retrieves fan RPMs, thermal sensors, and governor status via ONLP / sysfs."""
    onlp_thermal = run_cmd("onlp-thermal 2>/dev/null")
    onlp_fan = run_cmd("onlp-fan 2>/dev/null")
    onlp_psu = run_cmd("onlp-psu 2>/dev/null")

    if onlp_thermal and "Error" not in onlp_thermal and len(onlp_thermal) > 20:
        return {
            "source": "ONLP Hardware Subsystem",
            "thermals": onlp_thermal.splitlines(),
            "fans": onlp_fan.splitlines() if onlp_fan else [],
            "psu": onlp_psu.splitlines() if onlp_psu else []
        }

    sensors = run_cmd("sensors 2>/dev/null")
    sensor_lines = []
    if sensors and "Error" not in sensors:
        sensor_lines = sensors.splitlines()
    else:
        # Query Linux sysfs thermal zones
        tz_base = "/sys/class/thermal"
        if os.path.exists(tz_base):
            for tz in sorted(os.listdir(tz_base)):
                if tz.startswith("thermal_zone"):
                    t_type = run_cmd(f"cat {tz_base}/{tz}/type 2>/dev/null") or tz
                    t_val = run_cmd(f"cat {tz_base}/{tz}/temp 2>/dev/null")
                    if t_val and t_val.isdigit():
                        deg = int(t_val) / 1000.0
                        sensor_lines.append(f"{t_type}: {deg:.1f} °C")

    gov = run_cmd("cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_governor 2>/dev/null") or "default"
    return {
        "source": "Linux Kernel Hardware Sensors",
        "sensors": sensor_lines if sensor_lines else ["Thermal sensors not present or kernel module lm-sensors not loaded"],
        "fan_control": "Hardware PWM Managed",
        "governor": gov
    }

def parse_mitranet_config(config_source: str = "/etc/mitranet/config.json") -> Dict[str, Any]:
    """Loads native MitraNet system configuration."""
    if os.path.exists(config_source):
        try:
            with open(config_source, "r", encoding="utf-8") as f:
                return json.load(f)
        except Exception:
            pass

    return {
        "os": "MitraNet Network OS",
        "version": "1.0.0-LTS",
        "codename": "Rinjani",
        "hostname": "mitranet-router",
        "platform": get_onlp_platform_info(),
        "qos_profile": "Unlimited Wire-Speed (Hardware Line-Rate)",
        "firewall": "NFTables FastPath Active"
    }

if __name__ == "__main__":
    print("[*] Platform Info:")
    print(json.dumps(get_onlp_platform_info(), indent=2))
