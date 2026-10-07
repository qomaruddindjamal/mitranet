import requests

s = requests.Session()
r_login = s.post('http://192.168.56.101:8443/login.php', data={'username': 'admin', 'password': 'mitranet'})
print('Login status:', r_login.status_code)

r_post = s.post('http://192.168.56.101:8443/services_virtual.php', data={'act': 'stop', 'id': 'aapanel'})
print('Stop status:', r_post.status_code)
print('Response text:', r_post.text[:1000])
