import requests

session = requests.Session()
# Login with correct password: admin / mitranet
login_url = "http://192.168.56.101:8443/login.php"
res_login = session.post(login_url, data={"username": "admin", "password": "mitranet"}, allow_redirects=True)
print("Login status:", res_login.status_code, "Final URL:", res_login.url)
print("Cookies:", session.cookies.get_dict())

# Now fetch interfaces_wifi.php
wifi_url = "http://192.168.56.101:8443/interfaces_wifi.php"
res_wifi = session.get(wifi_url)
print("WiFi page status:", res_wifi.status_code)

body = res_wifi.text
print("Has wlan0:", "wlan0" in body)
print("Has wlan1:", "wlan1" in body)
print("Has WLAN1:", "WLAN1" in body)
print("Has WLAN2:", "WLAN2" in body)

for line in body.splitlines():
    if "Total:" in line or "wlan" in line.lower():
        print("MATCH:", line.strip())
