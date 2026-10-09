import requests
import json

s = requests.Session()
r = s.get('http://127.0.0.1:8443/api/v1/services/kvm', headers={'Authorization': 'Bearer mitranet_secure_token'})
print('STATUS:', r.status_code)
try:
    print('DATA:', json.dumps(r.json(), indent=2))
except Exception as e:
    print('TEXT:', r.text)
