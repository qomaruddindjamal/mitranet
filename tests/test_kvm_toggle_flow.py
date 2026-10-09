import requests
import json
import re

session = requests.Session()

# 1. Login to WebUI via login.php
login_resp = session.post("http://192.168.56.101:8443/login.php", data={
    "username": "admin",
    "password": "mitranet"
}, allow_redirects=True)
print("Login status:", login_resp.status_code)

# 2. Check initial KVM service state via API
status_resp = session.get("http://192.168.56.101:8443/api/v1/services/kvm")
initial_enabled = status_resp.json().get("enabled", False)
print("Initial KVM enabled status:", initial_enabled)

# 3. Request services_kvm.php page
kvm_page_resp = session.get("http://192.168.56.101:8443/services_kvm.php")
print("services_kvm.php status:", kvm_page_resp.status_code)
assert "KVM Services Configuration" in kvm_page_resp.text, "Title not found in services_kvm.php"
assert "Wake-on-LAN" in kvm_page_resp.text, "Wake-on-LAN not found in menu"
assert "KVM Services" in kvm_page_resp.text, "KVM Services not found in menu"

print("Menu contains KVM Services under Services: OK")

# 4. Toggle KVM Service to ON (Enable)
toggle_on = session.post("http://192.168.56.101:8443/services_kvm.php", data={
    "act": "toggle_kvm",
    "target_state": "enable"
})
print("Toggled to ON, status:", toggle_on.status_code)
assert "AKTIF (Enabled)" in toggle_on.text, "Enabled status badge not found"
assert "Nonaktifkan Layanan KVM" in toggle_on.text, "Disable button not found when active"
assert "direct-link\" title=\"KVM\"" in toggle_on.text, "KVM sidebar link not visible when active"

print("KVM Sidebar is visible when ENABLED: OK")

# 5. Access services_virtual.php
virt_resp = session.get("http://192.168.56.101:8443/services_virtual.php")
print("services_virtual.php status:", virt_resp.status_code)
assert "Virtual Machines" in virt_resp.text, "Virtual Machines not found"
assert "Manage aaPanel" in virt_resp.text, "Manage aaPanel tab not found"
print("services_virtual.php fully accessible with all tabs: OK")

# 6. Toggle KVM Service to OFF (Disable)
toggle_off = session.post("http://192.168.56.101:8443/services_kvm.php", data={
    "act": "toggle_kvm",
    "target_state": "disable"
})
print("Toggled to OFF, status:", toggle_off.status_code)
assert "NONAKTIF (Disabled)" in toggle_off.text, "Disabled status badge not found"
assert "Aktifkan Layanan KVM" in toggle_off.text, "Enable button not found when inactive"
assert "direct-link\" title=\"KVM\"" not in toggle_off.text, "KVM sidebar link should NOT be present when disabled"

print("KVM Sidebar is hidden when DISABLED: OK")
print("\nALL VERIFICATIONS PASSED SUCCESSFULLY!")
