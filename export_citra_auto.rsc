# 2026-09-29 02:26:34 by RouterOS 7.24.4
# software id = 4MZF-SFTR
#
/disk
add parent=usb1 partition-number=1 partition-offset=17408 partition-size=\
    33554432 type=partition
add parent=usb1 partition-number=2 partition-offset=33571840 partition-size=\
    15483255808 type=partition
/interface bridge
add name=NT-DIST protocol-mode=none
/interface ethernet
set [ find default-name=ether2 ] disable-running-check=no loop-protect=on \
    name=NT1-WAN
set [ find default-name=ether3 ] disable-running-check=no loop-protect=on \
    name=NT2-RB3011
set [ find default-name=ether4 ] disable-running-check=no loop-protect=on \
    name=NT3-VLAN
set [ find default-name=ether5 ] disable-running-check=no loop-protect=on \
    name=NT4-HOME
set [ find default-name=ether6 ] disable-running-check=no loop-protect=on \
    name=NT5-STATIC
set [ find default-name=ether1 ] disable-running-check=no loop-protect=on \
    name=NT6-SUPPORT
/interface vlan
add interface=NT3-VLAN loop-protect=on name="VLAN 66" vlan-id=66
add interface=NT3-VLAN loop-protect=on name=VLAN16 vlan-id=16
add interface=NT3-VLAN loop-protect=on name=VLAN92 vlan-id=92
/interface list
add name=lokal
/ip pool
add name=STATIC_POOL ranges=172.16.92.1-172.16.92.253
add name=PPOE_POOL ranges=192.168.92.1-192.168.92.253
add name=NT-HOME ranges=10.10.66.1-10.10.66.253
add name=dhcp_pool3 ranges=172.16.66.1-172.16.66.253
/ip dhcp-server
add address-pool=STATIC_POOL disabled=yes interface=NT-DIST name=dhcp1
add address-pool=PPOE_POOL disabled=yes interface=VLAN16 name=dhcp2
add address-pool=NT-HOME interface=NT4-HOME name=dhcp3
add address-pool=dhcp_pool3 disabled=yes interface="VLAN 66" name=dhcp4
/ppp profile
set *0 only-one=yes
add dns-server=8.8.8.8,8.8.4.4 local-address=192.168.92.254 name=\
    "Paket 100Rb" only-one=yes remote-address=PPOE_POOL use-encryption=no
add dns-server=8.8.8.8,8.8.4.4 local-address=192.168.92.254 name=\
    "Paket 150Rb" only-one=yes remote-address=PPOE_POOL
add dns-server=8.8.8.8,8.8.4.4 local-address=192.168.92.254 name=\
    "Paket 200Rb" only-one=yes remote-address=PPOE_POOL
add dns-server=8.8.8.8,8.8.4.4 local-address=192.168.92.254 name=\
    "Paket 250Rb" only-one=yes remote-address=PPOE_POOL
add dns-server=8.8.8.8,8.8.4.4 local-address=192.168.92.254 name=\
    "Paket 300Rb" only-one=yes remote-address=PPOE_POOL
add dns-server=8.8.8.8,8.8.4.4 local-address=192.168.92.254 name=\
    "Paket 350Rb" only-one=yes remote-address=PPOE_POOL
add local-address=192.168.92.254 name="Tidak Bayar" only-one=yes \
    remote-address=PPOE_POOL
add local-address=192.168.92.254 name="Paket 500Rb" only-one=yes \
    remote-address=PPOE_POOL
add bridge=NT-DIST change-tcp-mss=yes dns-server=8.8.8.8,8.8.4.4 name=VPN \
    only-one=yes use-ipv6=no
set *FFFFFFFE only-one=yes
/queue simple
add max-limit=500M/500M name="ALL TRAFIC" queue=\
    pcq-upload-default/pcq-download-default target="192.168.92.0/24,10.10.66.0\
    /24,10.10.92.0/24,172.16.92.0/24,192.168.192.0/24"
add max-limit=300M/300M name="A. DISTRIBUSI" parent="ALL TRAFIC" queue=\
    pcq-upload-default/pcq-download-default target=\
    192.168.92.0/24,172.16.92.0/24,192.168.192.0/24
add disabled=yes max-limit=10M/10M name="B. HOME" parent="ALL TRAFIC" queue=\
    pcq-upload-default/pcq-download-default target=10.10.66.0/24
add burst-time=30s/30s name="C. X-ULOH" parent="ALL TRAFIC" queue=\
    pcq-upload-default/pcq-download-default target=10.10.92.0/24
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="1. Aga Singkel" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.1/32
add burst-limit=20M/20M burst-threshold=20M/20M burst-time=30s/30s max-limit=\
    10M/10M name="2. MI MU1" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.2/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="3. Fais Singkel" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.3/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="4. Aziz Makam Do'a" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.4/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="5. Ilham" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.5/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="6. Inur" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.6/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    5M/5M name="7. Ely Mbomo" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.7/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    4M/4M name="8. Kak Hisyam" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.8/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="9. Dwi Singkel" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.9/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    1M/1M name="10. Pak Asrofi" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.10/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="11. Rahayu Segawe" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.11/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="12. Derut" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.12/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="13. Makruf" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.13/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="14. Putra Geneng" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.14/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="15. Ses Geneng" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.15/32
add burst-limit=7M/7M burst-threshold=7M/7M burst-time=1m/1m max-limit=5M/5M \
    name="16. Mustafid" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=172.16.92.16/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="17. Firoh" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.17/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="18. Mif Pethel 2" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.18/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="19. Ririn SPP" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.19/32
add burst-limit=7M/7M burst-threshold=7M/7M burst-time=1m/1m max-limit=5M/5M \
    name="20. Mad Nurul" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.66.20/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="21. Mariyam Kajok" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.21/32
add burst-limit=20M/20M burst-threshold=20M/20M burst-time=1m/1m limit-at=\
    10M/10M max-limit=15M/15M name="22. Mala Tamansari" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.22/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=1m/1m max-limit=3M/3M \
    name="23. Dayat Suwang" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.23/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="24.Waiting Client" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=172.16.92.24/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="25. Hana makam Do'a" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.25/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="26. Refano Jemblong" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.26/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="27. Naura Geneng" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.27/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="28. Faiq Suwot" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.28/32
add burst-time=30s/30s max-limit=5M/5M name="29. Pak Rozaq" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.29/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="30. Waiting Client" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.30/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="31. Adah Kali Cilik" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.31/32
add burst-time=30s/30s max-limit=2M/2M name="32. TK Study" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    172.16.92.32/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="33. Susi Zahro" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.33/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="34. Hendri Geneng" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.66.34/32
add burst-time=30s/30s max-limit=5M/5M name="35. Kang Manan" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.35/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="36. Topek Iva" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.36/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="37. Firman" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.37/32
add burst-time=30s/30s max-limit=5M/5M name="38. Salem Suwang" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.38/32
add burst-time=30s/30s max-limit=2M/2M name="39. RA MU2" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.39/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=1m/1m max-limit=3M/3M \
    name="40. Huda Mbomo" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.40/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=1m/1m max-limit=3M/3M \
    name="41. Saifur Mbomo" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.41/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="42. Roup Mbomo" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.42/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="43. Rizka Mala" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.43/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=1m/1m max-limit=3M/3M \
    name="46. Said Mbomo" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.46/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=1m/1m max-limit=3M/3M \
    name="44. Arliana Mbomo" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.44/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=1m/1m max-limit=3M/3M \
    name="45. Kak Noor Mbomo" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.45/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=1m/1m max-limit=3M/3M \
    name="47. Yahtar Mbomo" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.47/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=1m/1m max-limit=3M/3M \
    name="48. Ardian Mbomo" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.48/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=1m/1m max-limit=3M/3M \
    name="49. Nadzir Mbomo" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.49/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=1m/1m max-limit=3M/3M \
    name="50. Mujib" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.50/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="51. Waiting Client" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.51/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="52. Kamal" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.52/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="53. Waiting Client" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.53/32
add burst-limit=3M/3M burst-threshold=3M/3M burst-time=30s/30s max-limit=\
    2M/2M name="54. Khilmiyah" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.99.54/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="55. Waiting Client" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.55/32
add burst-time=30s/30s max-limit=5M/5M name="56. Humam" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.56/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    5M/5M name="57. Dayah Singkil" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.57/32
add burst-time=30s/30s max-limit=5M/5M name="58. Faiz Singkil" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.58/32
add burst-time=30s/30s max-limit=5M/5M name="59. Handoko Jemblong" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.59/32
add burst-time=30s/30s max-limit=5M/5M name="60. Farid kalicilek" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.60/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="61. Darul" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.61/32
add burst-time=30s/30s max-limit=5M/5M name="62. Kumasir" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.62/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="63. Feri suwang Kidol" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.63/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="64. Denanda" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=172.16.92.64/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="66. Fendy" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.66/32
add burst-time=30s/30s max-limit=5M/5M name="65. Suyanto" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.65/32
add burst-limit=3M/3M burst-threshold=3M/3M burst-time=30s/30s max-limit=\
    2M/2M name="67. Umam" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.67/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="68. Hamid Makam Do'a" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.68/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="69. Mbak Is Makam Do'a" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.69/32
add burst-time=30s/30s max-limit=3M/3M name="70. Rifa'i SUwot" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.70/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="71. Khamid Makam Do'a" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.71/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="72. Anis Fitriyah" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.72/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="73. Fifi Makam Do'a" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.73/32
add burst-time=30s/30s max-limit=3M/3M name="74. Farid Mbomo" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.74/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    5M/5M name="75. Supri Geneng" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.75/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    5M/5M name="76. Amir Geneng" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.76/32
add burst-time=30s/30s max-limit=5M/5M name="77. Rokis" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.77/32
add burst-time=30s/30s max-limit=5M/5M name="78. Laila Geneng" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.78/32
add burst-time=30s/30s max-limit=5M/5M name="79. Agus Mbomo" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.79/32
add burst-time=30s/30s max-limit=3M/3M name="80. Abil Farka" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.80/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="81. Dina Makam Do'a" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.81/32
add burst-limit=3M/3M burst-threshold=3M/3M burst-time=30s/30s max-limit=\
    2M/2M name="82. Imbank Kajok" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.82/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="83. Sagi" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.83/32
add burst-time=30s/30s max-limit=3M/3M name="84. Rifai Stropong" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.84/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="85. Lek Rokah" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.85/32
add burst-time=30s/30s max-limit=3M/3M name="86. Zainal Kurmo" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.86/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="87. Edy Segawe" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.87/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="88. Defanda" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.88/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="89. Syifa Suwang Kidul" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.89/32
add burst-time=30s/30s max-limit=3M/3M name="90. Lilis Dodot" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.90/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="91. Talkis Tamansari" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.91/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="92. Masdi Kajok" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.92/32
add burst-time=30s/30s max-limit=5M/5M name="93. Zul Suwang" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.93/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="94. Londo Faid" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.94/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="95. Ulkasan Geneng" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.95/32
add burst-time=30s/30s max-limit=5M/5M name="96. Abu Marlo" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.96/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="97. Farid segawe" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.97/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=1m/1m disabled=yes \
    max-limit=4M/4M name="98. Anik Mbomo" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.98/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="99. Damayanti" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.99/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="100. Mukhlis" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.100/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="101. iPUS" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.101/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="102. Surono Makam Do'a" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.102/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    5M/5M name="103. Saroh Makam Do'a" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.103/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    5M/5M name="104. Johan" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.104/32
add burst-time=30s/30s max-limit=3M/3M name="105. Amer suwang kidul" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.105/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="106. Banggok" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.106/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="107. Tokomasripah" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.107/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="108. Fathur Sinasi" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.108/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="109. Rofik Geneng" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.109/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="110. Sifa Kajok" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.110/32
add burst-time=30s/30s max-limit=3M/3M name="111. Tiara" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.111/32
add burst-time=30s/30s max-limit=3M/3M name="112. Lana Kajok" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.112/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="113. Elbara" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.113/32
add burst-time=30s/30s max-limit=3M/3M name="114. Siti Aliyah" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.114/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="115. Jarwo Kajok Icha" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.115/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="116. Frida Segawe" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.116/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="117. Firman2" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.117/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="118. Hudi Singkil" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.118/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="119. Putri Makam Do'a" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.119/32
add burst-time=30s/30s max-limit=3M/3M name="120. Azhalea" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.120/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    3M/3M name=121.Yani parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.121/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="123. Aldo" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.123/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="124. Gofur" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.124/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="125. Wulan" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.125/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="126. Indah Kajok" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.126/32
add burst-time=30s/30s max-limit=3M/3M name="127. Uswatun" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.127/32
add burst-time=30s/30s max-limit=3M/3M name="128. Siti Suwot" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.128/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="129. Mudhofar" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.129/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="130. Edi Geneng" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.130/32
add burst-time=30s/30s max-limit=10M/10M name="131. Cipit Wafa" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.131/32
add burst-time=30s/30s max-limit=5M/5M name="132. Jalal" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.132/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="133. Sesha Kajok" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.133/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="134. Konyak" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.134/32
add burst-time=30s/30s max-limit=3M/3M name="135. Zairi" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.135/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="136. Taqim Mebel" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.136/32
add burst-time=30s/30s max-limit=3M/3M name="137. Cipet Ipul" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.137/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="138. Said Makam Do'a" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.138/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="139. Khusnul" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.139/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="140. Bu Susi Kajok" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.140/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="141. Heri Suwang Kidul" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.141/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="142. Didik Geneng" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.142/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="143. Iyan Kurmo" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.143/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="144. Udin Faid" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.144/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="145. Ridwan" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.145/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="146. Indah Persada" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.146/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="147. Ali Kajok" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.147/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="149. Subhan" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.149/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=30s/30s max-limit=\
    5M/5M name="148. Evis Geneng " parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.148/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name=150.Kholil parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.150/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="151. Nina" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.151/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="152. Reza-Sagara" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.152/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="153. Lina Didik" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.153/32
add max-limit=5M/5M name="155. Nanang Suwang" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.155/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name=154.Erham parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.154/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="156. Zainal Kajok" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.156/32
add burst-time=30s/30s max-limit=5M/5M name="157. Nadhifah Mbomo" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.157/32
add burst-time=30s/30s max-limit=3M/3M name="158. TK Nawakartika" parent=\
    "A. DISTRIBUSI" queue=pcq-upload-default/pcq-download-default target=\
    192.168.92.158/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="159. Roise Tamansari" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.159/32
add burst-limit=5M/5M burst-threshold=5M/5M burst-time=30s/30s max-limit=\
    3M/3M name="160. Wainting Client" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.160/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="161. Mif Pethel" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=172.16.92.161/32
add burst-limit=10M/10M burst-threshold=10M/10M burst-time=1m/1m max-limit=\
    5M/5M name="252. Nilam nizam" parent="A. DISTRIBUSI" queue=\
    pcq-upload-default/pcq-download-default target=192.168.92.252/32
/system logging action
set 0 memory-lines=100
/system script
add dont-require-permissions=no name=CekMaintenance owner=citramedia policy=\
    ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon source="#\
    \_Script CekMaintenance Pelanggan Berkala (Format Ber-Ikon Rapi Citra Medi\
    a)\r\
    \n:global mntRunning;\r\
    \n:if ([:typeof \$mntRunning] != \"nil\" && \$mntRunning) do={\r\
    \n    :log warning \"Notif maintenance: masih berjalan, dilewati\";\r\
    \n    :error \"overlap\";\r\
    \n};\r\
    \n:set mntRunning true;\r\
    \n\r\
    \n:local botToken \"8017630477:AAEEYLSJrXYbeL3UDX1xnArmFcaubH-wV1U\";\r\
    \n:local chatId \"-1003902643840\";\r\
    \n:local pushToken \"GANTI_TOKEN\";\r\
    \n:local targetGroup \"GANTI_GRUP\";\r\
    \n\r\
    \n:local currentTime [/system clock get time];\r\
    \n:local currentDate [/system clock get date];\r\
    \n\r\
    \n# 1. Ambil daftar user aktif sekali saja ke string ber-delimiter\r\
    \n:local activeStr \"|\";\r\
    \n:foreach a in=[/ppp active find] do={\r\
    \n    :set activeStr (\$activeStr . [/ppp active get \$a name] . \"|\");\r\
    \n};\r\
    \n\r\
    \n# 2. Susun blok pelanggan nonaktif dengan ikon & indentasi rapi\r\
    \n:local inactBlock \"\";\r\
    \n:local countInact 0;\r\
    \n:foreach s in=[/ppp secret find where disabled=no] do={\r\
    \n    :local sName [/ppp secret get \$s name];\r\
    \n    :if ([:typeof [:find \$activeStr (\"|\" . \$sName . \"|\")]] = \"nil\
    \") do={\r\
    \n        :local sCmt \"N/A\";\r\
    \n        :do { :set sCmt [/ppp secret get \$s comment]; } on-error={ };\r\
    \n        :if ([:len \$sCmt] = 0) do={ :set sCmt \"N/A\"; };\r\
    \n\r\
    \n        :local sIp \"N/A\";\r\
    \n        :do { :set sIp [/ppp secret get \$s remote-address]; } on-error=\
    { };\r\
    \n        :if ([:len \$sIp] = 0) do={ :set sIp \"IP-Pool\"; };\r\
    \n        :if ([:typeof [:find \$sIp \".\"]] != \"nil\") do={ :set sIp (\"\
    http://\" . \$sIp); };\r\
    \n\r\
    \n        :set inactBlock (\$inactBlock . \"   \F0\9F\91\A4 <b>User       \
    :</b> \" . \$sName . \"%0A\" .                                        \"  \
    \_\F0\9F\93\8D <b>Alamat     :</b> \" . \$sCmt . \"%0A\" .                \
    \_                       \"   \F0\9F\93\A1 <b>IP Address :</b> \" . \$sIp \
    . \"%0A%0A\");\r\
    \n        :set countInact (\$countInact + 1);\r\
    \n    };\r\
    \n};\r\
    \n\r\
    \n# 3. Kirim notifikasi jika ada pelanggan nonaktif\r\
    \n:if (\$countInact > 0) do={\r\
    \n    :local header (\"\E2\9D\8C <b>MAINTENANCE \" . \$countInact . \" PEL\
    ANGGAN</b>%0A%0A\");\r\
    \n    :local footer (\"%0A\F0\9F\95\92 <b>Time:</b> \" . \$currentTime . \
    \" - \" . \$currentDate);\r\
    \n    :local message (\$header . \$inactBlock . \$footer);\r\
    \n\r\
    \n    :do {\r\
    \n        /tool fetch url=\"https://api.telegram.org/bot\$botToken/sendMes\
    sage\" http-method=post http-data=\"chat_id=\$chatId&text=\$message&parse_\
    mode=HTML\" keep-result=no check-certificate=no;\r\
    \n    } on-error={ :log error \"Gagal kirim maintenance ke Telegram\"; };\
    \r\
    \n\r\
    \n    :if (\$pushToken != \"GANTI_TOKEN\") do={\r\
    \n        :do {\r\
    \n            /tool fetch url=\"https://dash.pushwa.com/api/kirimPesan\" h\
    ttp-method=post http-data=\"token=\$pushToken&target=\$targetGroup&type=te\
    xt&delay=1&message=\$message\" keep-result=no check-certificate=no;\r\
    \n        } on-error={ :log error \"Gagal kirim maintenance ke WhatsApp\";\
    \_};\r\
    \n    };\r\
    \n};\r\
    \n\r\
    \n:set mntRunning false;"
add dont-require-permissions=no name=NotifUserDown owner=citramedia policy=\
    read,write,policy,test source="# Script Notifikasi Pelanggan Terputus & Ak\
    tif (Format Khusus Citra Media)\r\
    \n:global pppActiveStrPrev;\r\
    \n:global pppActiveArrayPrev;\r\
    \n:local botToken \"8017630477:AAEEYLSJrXYbeL3UDX1xnArmFcaubH-wV1U\";\r\
    \n:local chatId \"-1003902643840\";\r\
    \n\r\
    \n:local currentTime [/system clock get time];\r\
    \n:local currentDate [/system clock get date];\r\
    \n\r\
    \n# 1. Ambil daftar user aktif saat ini ke string ber-delimiter & array me\
    mori\r\
    \n:local pppActiveStrNow \"|\";\r\
    \n:local pppActiveArrayNow [:toarray \"\"];\r\
    \n:foreach a in=[/ppp active find] do={\r\
    \n    :local uName [/ppp active get \$a name];\r\
    \n    :set pppActiveStrNow (\$pppActiveStrNow . \$uName . \"|\");\r\
    \n    :set pppActiveArrayNow (\$pppActiveArrayNow, \$uName);\r\
    \n};\r\
    \n\r\
    \n# 2. Jika data sebelumnya ada di memori, bandingkan perubahan\r\
    \n:if ([:typeof \$pppActiveStrPrev] = \"str\" && [:len \$pppActiveStrPrev]\
    \_> 1) do={\r\
    \n\r\
    \n    :local needStats false;\r\
    \n    :local downUsers [:toarray \"\"];\r\
    \n    :local upUsers [:toarray \"\"];\r\
    \n\r\
    \n    # A. Cek siapa yang TERPUTUS\r\
    \n    :foreach oldUser in=\$pppActiveArrayPrev do={\r\
    \n        :if ([:typeof [:find \$pppActiveStrNow (\"|\" . \$oldUser . \"|\
    \")]] = \"nil\") do={\r\
    \n            :set downUsers (\$downUsers, \$oldUser);\r\
    \n            :set needStats true;\r\
    \n        };\r\
    \n    };\r\
    \n\r\
    \n    # B. Cek siapa yang BARU AKTIF\r\
    \n    :foreach newUser in=\$pppActiveArrayNow do={\r\
    \n        :if ([:typeof [:find \$pppActiveStrPrev (\"|\" . \$newUser . \"|\
    \")]] = \"nil\") do={\r\
    \n            :set upUsers (\$upUsers, \$newUser);\r\
    \n            :set needStats true;\r\
    \n        };\r\
    \n    };\r\
    \n\r\
    \n    # C. Jika ada perubahan status (DOWN atau UP), susun data\r\
    \n    :if (\$needStats = true) do={\r\
    \n        :local totalSecret [/ppp secret print count-only];\r\
    \n        :local totalActive [/ppp active print count-only];\r\
    \n        :local totalDisabled [/ppp secret print count-only where disable\
    d=yes];\r\
    \n        :local totalInactive (\$totalSecret - \$totalActive - \$totalDis\
    abled);\r\
    \n        :if (\$totalInactive < 0) do={ :set totalInactive 0; };\r\
    \n\r\
    \n        # List Nonaktif (hanya username)\r\
    \n        :local inactOnlyUser \"\";\r\
    \n        :local countInact 0;\r\
    \n        :foreach s in=[/ppp secret find where disabled=no] do={\r\
    \n            :local sName [/ppp secret get \$s name];\r\
    \n            :if ([:typeof [:find \$pppActiveStrNow (\"|\" . \$sName . \"\
    |\")]] = \"nil\") do={\r\
    \n                :if (\$countInact < 15) do={\r\
    \n                    :set inactOnlyUser (\$inactOnlyUser . \"%0A   - \" .\
    \_\$sName);\r\
    \n                };\r\
    \n                :set countInact (\$countInact + 1);\r\
    \n            };\r\
    \n        };\r\
    \n        :if (\$countInact > 15) do={\r\
    \n            :set inactOnlyUser (\$inactOnlyUser . \"%0A   <i>...dan \" .\
    \_(\$countInact - 15) . \" user lainnya</i>\");\r\
    \n        };\r\
    \n\r\
    \n        # List Di-Non Aktifkan (nama + comment/alamat)\r\
    \n        :local disWithCmt \"\";\r\
    \n        # List Di-Non Aktifkan (hanya username untuk UP)\r\
    \n        :local disOnlyUser \"\";\r\
    \n        :local countDis 0;\r\
    \n        :foreach d in=[/ppp secret find where disabled=yes] do={\r\
    \n            :if (\$countDis < 15) do={\r\
    \n                :local dName [/ppp secret get \$d name];\r\
    \n                :local dCmt \"N/A\";\r\
    \n                :do { :set dCmt [/ppp secret get \$d comment]; } on-erro\
    r={ };\r\
    \n                :if ([:len \$dCmt] = 0) do={ :set dCmt \"N/A\"; };\r\
    \n                :set disWithCmt (\$disWithCmt . \"%0A   - \" . \$dName .\
    \_\" | \" . \$dCmt);\r\
    \n                :set disOnlyUser (\$disOnlyUser . \"%0A   - \" . \$dName\
    );\r\
    \n            };\r\
    \n            :set countDis (\$countDis + 1);\r\
    \n        };\r\
    \n        :if (\$countDis > 15) do={\r\
    \n            :set disWithCmt (\$disWithCmt . \"%0A   <i>...dan \" . (\$co\
    untDis - 15) . \" user lainnya</i>\");\r\
    \n            :set disOnlyUser (\$disOnlyUser . \"%0A   <i>...dan \" . (\$\
    countDis - 15) . \" user lainnya</i>\");\r\
    \n        };\r\
    \n\r\
    \n        # --- KIRIM NOTIFIKASI DOWN (PELANGGAN NONAKTIF - PERSIS CONTOH \
    USER) ---\r\
    \n        :if ([:len \$downUsers] > 0) do={\r\
    \n            :if ([:len \$downUsers] <= 5) do={\r\
    \n                :foreach u in=\$downUsers do={\r\
    \n                    :local uAddr \"Tidak ada alamat\";\r\
    \n                    :do { :set uAddr [/ppp secret get [find name=\$u] co\
    mment]; } on-error={ };\r\
    \n                    :if ([:len \$uAddr] = 0) do={ :set uAddr \"Tidak ada\
    \_alamat\"; };\r\
    \n\r\
    \n                    :local uIp \"N/A\";\r\
    \n                    :do { :set uIp [/ppp secret get [find name=\$u] remo\
    te-address]; } on-error={ };\r\
    \n                    :if ([:len \$uIp] = 0) do={ :set uIp \"IP-Pool\"; };\
    \r\
    \n\r\
    \n                    :local uIpDisplay \$uIp;\r\
    \n                    :if ([:typeof [:find \$uIp \".\"]] != \"nil\") do={\
    \r\
    \n                        :set uIpDisplay (\"http://\" . \$uIp);\r\
    \n                    };\r\
    \n\r\
    \n                    :local msg (\"\E2\9D\8C <b>PELANGGAN NONAKTIF</b>%0A\
    \" .                                 \"\F0\9F\91\A4 <b>User:</b> \" . \$u \
    . \"%0A\" .                                 \"\F0\9F\93\8D <b>Alamat:</b> \
    \" . \$uAddr . \"%0A\" .                                 \"\F0\9F\93\A1 <b\
    >IP Address:</b> \" . \$uIpDisplay . \"%0A%0A\" .                         \
    \_       \"\F0\9F\93\89 <b>Pelanggan Nonaktif:</b> \" . \$totalInactive . \
    \" user\" . \$inactOnlyUser . \"%0A%0A\" .                                \
    \_\"\F0\9F\9A\AB <b>Di-Non Aktifkan:</b> \" . \$totalDisabled . \" user\" \
    . \$disWithCmt . \"%0A%0A\" .                                 \"\F0\9F\93\
    \8A <b>Total Pelanggan:</b> \" . \$totalSecret . \" user%0A\" .           \
    \_                     \"\F0\9F\93\88 <b>Total Aktif:</b> \" . \$totalActi\
    ve . \" user%0A\" .                                 \"\F0\9F\95\92 <b>Time\
    :</b> \" . \$currentTime . \" - \" . \$currentDate);\r\
    \n\r\
    \n                    :do {\r\
    \n                        /tool fetch url=\"https://api.telegram.org/bot\$\
    botToken/sendMessage\" http-method=post http-data=\"chat_id=\$chatId&text=\
    \$msg&parse_mode=HTML\" keep-result=no check-certificate=no;\r\
    \n                    } on-error={ };\r\
    \n                    :delay 1s;\r\
    \n                };\r\
    \n            } else={\r\
    \n                # Massal (> 5 user terputus)\r\
    \n                :local listStr \"\";\r\
    \n                :foreach u in=\$downUsers do={\r\
    \n                    :set listStr (\$listStr . \"%0A   - \" . \$u);\r\
    \n                };\r\
    \n                :local msg (\"\F0\9F\9A\A8 <b>\" . [:len \$downUsers] . \
    \" PELANGGAN TERPUTUS SEKALIGUS</b>%0A\" .                             \$l\
    istStr . \"%0A%0A\" .                             \"\F0\9F\93\89 <b>Pelang\
    gan Nonaktif:</b> \" . \$totalInactive . \" user\" . \$inactOnlyUser . \"%\
    0A%0A\" .                             \"\F0\9F\9A\AB <b>Di-Non Aktifkan:</\
    b> \" . \$totalDisabled . \" user\" . \$disWithCmt . \"%0A%0A\" .         \
    \_                   \"\F0\9F\93\8A <b>Total Pelanggan:</b> \" . \$totalSe\
    cret . \" user%0A\" .                             \"\F0\9F\93\88 <b>Total \
    Aktif:</b> \" . \$totalActive . \" user%0A\" .                            \
    \_\"\F0\9F\95\92 <b>Time:</b> \" . \$currentTime . \" - \" . \$currentDate\
    );\r\
    \n\r\
    \n                :do {\r\
    \n                    /tool fetch url=\"https://api.telegram.org/bot\$botT\
    oken/sendMessage\" http-method=post http-data=\"chat_id=\$chatId&text=\$ms\
    g&parse_mode=HTML\" keep-result=no check-certificate=no;\r\
    \n                } on-error={ };\r\
    \n            };\r\
    \n        };\r\
    \n\r\
    \n        # --- KIRIM NOTIFIKASI UP (PELANGGAN AKTIF - PERSIS CONTOH USER)\
    \_---\r\
    \n        :if ([:len \$upUsers] > 0) do={\r\
    \n            :if ([:len \$upUsers] <= 5) do={\r\
    \n                :foreach u in=\$upUsers do={\r\
    \n                    :local uAddr \"Tidak ada alamat\";\r\
    \n                    :do { :set uAddr [/ppp secret get [find name=\$u] co\
    mment]; } on-error={ };\r\
    \n                    :if ([:len \$uAddr] = 0) do={ :set uAddr \"Tidak ada\
    \_alamat\"; };\r\
    \n\r\
    \n                    :local msg (\"\E2\9C\85 <b>PELANGGAN AKTIF</b>%0A\" \
    .                                 \"\F0\9F\91\A4 <b>User:</b> \" . \$u . \
    \"%0A\" .                                 \"\F0\9F\93\8D <b>Alamat:</b> \"\
    \_. \$uAddr . \"%0A%0A\" .                                 \"\F0\9F\93\89 \
    <b>Pelanggan Nonaktif:</b> \" . \$totalInactive . \" user\" . \$inactOnlyU\
    ser . \"%0A%0A\" .                                 \"\F0\9F\9A\AB <b>Di-No\
    n Aktifkan:</b> \" . \$totalDisabled . \" user\" . \$disOnlyUser . \"%0A%0\
    A\" .                                 \"\F0\9F\93\8A <b>Total Pelanggan:</\
    b> \" . \$totalSecret . \" user%0A\" .                                 \"\
    \F0\9F\93\88 <b>Total Aktif:</b> \" . \$totalActive . \" user%0A\" .      \
    \_                          \"\F0\9F\95\92 <b>Time:</b> \" . \$currentTime\
    \_. \" - \" . \$currentDate);\r\
    \n\r\
    \n                    :do {\r\
    \n                        /tool fetch url=\"https://api.telegram.org/bot\$\
    botToken/sendMessage\" http-method=post http-data=\"chat_id=\$chatId&text=\
    \$msg&parse_mode=HTML\" keep-result=no check-certificate=no;\r\
    \n                    } on-error={ };\r\
    \n                    :delay 1s;\r\
    \n                };\r\
    \n            } else={\r\
    \n                # Massal (> 5 user reconnect)\r\
    \n                :local listStr \"\";\r\
    \n                :foreach u in=\$upUsers do={\r\
    \n                    :set listStr (\$listStr . \"%0A   - \" . \$u);\r\
    \n                };\r\
    \n                :local msg (\"\E2\9C\85 <b>\" . [:len \$upUsers] . \" PE\
    LANGGAN TERHUBUNG KEMBALI</b>%0A\" .                             \$listStr\
    \_. \"%0A%0A\" .                             \"\F0\9F\93\89 <b>Pelanggan N\
    onaktif:</b> \" . \$totalInactive . \" user\" . \$inactOnlyUser . \"%0A%0A\
    \" .                             \"\F0\9F\9A\AB <b>Di-Non Aktifkan:</b> \"\
    \_. \$totalDisabled . \" user\" . \$disOnlyUser . \"%0A%0A\" .            \
    \_                \"\F0\9F\93\8A <b>Total Pelanggan:</b> \" . \$totalSecre\
    t . \" user%0A\" .                             \"\F0\9F\93\88 <b>Total Akt\
    if:</b> \" . \$totalActive . \" user%0A\" .                             \"\
    \F0\9F\95\92 <b>Time:</b> \" . \$currentTime . \" - \" . \$currentDate);\r\
    \n\r\
    \n                :do {\r\
    \n                    /tool fetch url=\"https://api.telegram.org/bot\$botT\
    oken/sendMessage\" http-method=post http-data=\"chat_id=\$chatId&text=\$ms\
    g&parse_mode=HTML\" keep-result=no check-certificate=no;\r\
    \n                } on-error={ };\r\
    \n            };\r\
    \n        };\r\
    \n    };\r\
    \n};\r\
    \n\r\
    \n# 3. Update status untuk siklus pengecekan berikutnya\r\
    \n:set pppActiveStrPrev \$pppActiveStrNow;\r\
    \n:set pppActiveArrayPrev \$pppActiveArrayNow;"
add dont-require-permissions=no name=NotifStartupReboot owner=citramedia \
    policy=ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon \
    source="\r\
    \n    :local i 0;\r\
    \n    :while ([/ping count=1 1.1.1.1] = 0 && \$i < 30) do={\r\
    \n        :delay 1s;\r\
    \n        :set i (\$i + 1);\r\
    \n    };\r\
    \n    \r\
    \n    :local jam [/system clock get time];\r\
    \n    :local tgl [/system clock get date];\r\
    \n    :local upt [/system resource get uptime];\r\
    \n    :local cpu [/system resource get cpu-load];\r\
    \n    :local freeMem ([/system resource get free-memory] / 1048576);\r\
    \n    :local totalMem ([/system resource get total-memory] / 1048576);\r\
    \n    :local botToken \"8017630477:AAEEYLSJrXYbeL3UDX1xnArmFcaubH-wV1U\";\
    \r\
    \n    :local chatId \"-1003902643840\";\r\
    \n    \r\
    \n    :local text (\"%F0%9F%94%84+<b>ROUTER+CITRA-NETWORK+TELAH+MENYALA+%2\
    8BOOTING%29</b>%0A%0A%E2%8F%B1+<b>Uptime</b>%3A+\" . \$upt . \"%0A%F0%9F%9\
    3%8A+<b>CPU+Load</b>%3A+\" . \$cpu . \"%25%0A%F0%9F%92%BE+<b>Free+RAM</b>%\
    3A+\" . \$freeMem . \"MB+%2F+\" . \$totalMem . \"MB%0A%0A%F0%9F%94%93+<b>T\
    ime</b>%3A+\" . \$jam . \"+-+\" . \$tgl . \"%0A%E2%84%B9%EF%B8%8F+<i>Syste\
    m+startup+selesai.+Layanan+PPPoE+%26+jaringan+telah+aktif.</i>\");\r\
    \n    \r\
    \n    :do {\r\
    \n        /tool fetch url=(\"https://api.telegram.org/bot\" . \$botToken .\
    \_\"/sendMessage\") http-method=post http-data=(\"chat_id=\" . \$chatId . \
    \"&parse_mode=HTML&text=\" . \$text) check-certificate=no keep-result=no;\
    \r\
    \n    } on-error={ :log warning \"Gagal kirim notif startup ke Telegram\";\
    \_};\r\
    \n"
/interface bridge port
add bridge=NT-DIST interface=NT5-STATIC
add bridge=NT-DIST interface=NT6-SUPPORT
add bridge=NT-DIST disabled=yes interface=*F
add bridge=NT-DIST interface=NT2-RB3011
/ip settings
set allow-fast-path=no
/interface l2tp-server server
set default-profile=default enabled=yes keepalive-timeout=disabled mrru=1600 \
    use-ipsec=yes
/interface list member
add interface=NT-DIST list=lokal
add interface=VLAN16 list=lokal
add interface=NT4-HOME list=lokal
/interface pppoe-server server
add disabled=no interface=VLAN16 keepalive-timeout=60 service-name=PPOE
add disabled=no interface=NT-DIST keepalive-timeout=60 service-name=\
    PPOE_NT_DIST
/interface pptp-server server
# PPTP connections are considered unsafe, it is suggested to use a more modern VPN protocol instead
set enabled=yes keepalive-timeout=disabled mrru=1600
/ip address
add address=10.20.27.22/30 interface=NT1-WAN network=10.20.27.20
add address=192.168.92.254/24 comment=DISTRIBUSI-VLAN interface=VLAN16 \
    network=192.168.92.0
add address=172.16.92.254/24 comment=STATIK interface=NT-DIST network=\
    172.16.92.0
add address=103.247.13.9 comment="IP-PUBLIC TERABIT" interface=NT1-WAN \
    network=103.247.13.9
add address=10.10.66.254/24 comment=HOME interface=NT4-HOME network=\
    10.10.66.0
add address=172.16.66.254/24 comment="VLAN MNG" interface="VLAN 66" network=\
    172.16.66.0
add address=10.10.16.1/24 comment=LINK-TO-BACKBONE interface=NT-DIST network=\
    10.10.16.0
add address=10.10.99.1/24 comment="WireGuard Admin Subnet" interface=*D \
    network=10.10.99.0
/ip arp
add address=172.16.66.252 comment="OLT-HIOSO 4 PON" interface="VLAN 66" \
    mac-address=78:5C:72:A9:C9:84
add address=172.16.66.253 comment="OLT-HIOSO 2 PON" interface="VLAN 66" \
    mac-address=78:5C:72:A4:D5:54
add address=10.10.66.250 comment="AP-ZTE HOME" interface=NT4-HOME \
    mac-address=8C:DC:02:89:F2:F0
add address=10.10.66.252 comment="AP-ZTE KAKAK" interface=NT4-HOME \
    mac-address=60:31:97:36:A7:50
/ip dhcp-server network
add address=10.10.66.0/24 comment=HOME gateway=10.10.66.254
add address=172.16.66.0/24 gateway=172.16.66.254
add address=172.16.92.0/24 comment=NT-DIST gateway=172.16.92.254
add address=192.168.92.0/24 comment=VLAN-PPOE gateway=192.168.92.254
/ip dns
set allow-remote-requests=yes servers=8.8.8.8,8.8.4.4
/ip firewall filter
add action=add-src-to-address-list address-list=potential_scraper \
    address-list-timeout=1h chain=input comment="TRAP POTENTIAL WEB SCRAPPER" \
    connection-state=new dst-port=80,443 in-interface-list=lokal limit=\
    30,5:packet protocol=tcp
/ip firewall nat
add action=masquerade chain=srcnat comment=NAT-TO-BACKBONE dst-address=\
    10.10.16.0/24
add action=masquerade chain=srcnat comment=NAT-MNG-NT-DIST dst-address=\
    172.16.92.0/24
add action=src-nat chain=srcnat comment=NAT-TERABIT out-interface=NT1-WAN \
    src-address=!103.247.13.9 to-addresses=103.247.13.9
add action=dst-nat chain=dstnat comment="PORT FORWARDING - OLT 2 PON" \
    dst-address=103.247.13.9 dst-port=6653 protocol=tcp to-addresses=\
    172.16.66.253 to-ports=80
add action=dst-nat chain=dstnat comment="PORT FORWARDING - OLT 4 PON" \
    dst-address=103.247.13.9 dst-port=6652 protocol=tcp to-addresses=\
    172.16.66.252 to-ports=80
add action=dst-nat chain=dstnat comment="PORT FORWARDING - DLINK" \
    dst-address=103.247.13.9 dst-port=6651 protocol=tcp to-addresses=\
    172.16.66.251 to-ports=80
add action=dst-nat chain=dstnat comment=http://office.nexgensolutions.asia \
    dst-address=103.247.13.9 dst-port=80 protocol=tcp to-addresses=\
    10.10.66.150 to-ports=80
add action=dst-nat chain=dstnat comment=https://office.nexgensolution.asia \
    dst-address=103.247.13.9 dst-port=443 protocol=tcp to-addresses=\
    10.10.66.150 to-ports=443
add action=dst-nat chain=dstnat comment="CMT-WEB SRV" dst-address=\
    103.247.13.9 dst-port=80 protocol=tcp to-addresses=10.10.66.152 to-ports=\
    80
add action=dst-nat chain=dstnat comment="CMT-WEB-MITRA SRV" dst-address=\
    103.247.13.9 dst-port=80 protocol=tcp to-addresses=10.10.66.151 to-ports=\
    80
add action=dst-nat chain=dstnat comment=http://voffice.nexgensolution.asia \
    dst-address=103.247.13.9 dst-port=80 protocol=tcp socks5-port=1 \
    socks5-server=0.0.0.0 to-addresses=10.10.66.151 to-ports=80
add action=dst-nat chain=dstnat comment=https://voffice.nexgensolution.asia \
    dst-address=103.247.13.9 dst-port=443 protocol=tcp to-addresses=\
    10.10.66.151 to-ports=443
add action=dst-nat chain=dstnat comment=http://apollo.citramedia.group \
    dst-address=103.247.13.9 dst-port=80 protocol=tcp to-addresses=\
    10.10.66.161 to-ports=80
add action=masquerade chain=srcnat comment="Acept Voffice" dst-address=\
    10.10.66.151 dst-port=443 out-interface=NT4-HOME protocol=tcp \
    src-address=10.10.66.0/24
add action=masquerade chain=srcnat dst-address=10.10.66.151 dst-port=80 \
    out-interface=NT4-HOME protocol=tcp src-address=10.10.66.0/24
/ip firewall raw
add action=drop chain=prerouting comment=\
    "DROP DNS POISONING | JANGAN DISABLE" dst-port=53 in-interface=NT1-WAN \
    protocol=udp
/ip route
add disabled=no distance=1 dst-address=0.0.0.0/0 gateway=10.20.27.21 \
    routing-table=main scope=30 target-scope=10
/ip service
set ftp disabled=yes
set telnet disabled=yes
set www disabled=yes
set api disabled=yes port=6692
set api-ssl disabled=yes
/ppp secret
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    faridkalicilik@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.60 routes=192.168.92.254 service=pppoe
add comment=Segawe local-address=192.168.92.254 name=\
    abilrafka@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.80 routes=192.168.92.254 service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    anis@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.72 \
    routes=192.168.92.254 service=pppoe
add comment=Geneng local-address=192.168.92.254 name=\
    ahmadsuyanto@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.65 routes=192.168.92.254 service=pppoe
add comment=Suwang local-address=192.168.92.254 name=dayat@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.23 routes=192.168.92.254 \
    service=pppoe
add comment=Singkel local-address=192.168.92.254 name=dayah@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.57 routes=192.168.92.254 \
    service=pppoe
add comment=Kajok local-address=192.168.92.254 name=sagi@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.83 routes=192.168.92.254 \
    service=pppoe
add comment=Segawe local-address=192.168.92.254 name=iyan@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.143 routes=192.168.92.254 \
    service=pppoe
add comment=Suwot local-address=192.168.92.254 name=faiq@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.28 routes=192.168.92.254 \
    service=pppoe
add comment=Singkel local-address=192.168.92.254 name=faiz@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.58 routes=192.168.92.254 \
    service=pppoe
add comment=Kajok local-address=192.168.92.254 name=maryam@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.21 routes=192.168.92.254 \
    service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    fifi@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.73 \
    routes=192.168.92.254 service=pppoe
add comment=Kajok local-address=192.168.92.254 name=firoh@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.17 routes=192.168.92.254 \
    service=pppoe
add comment=Singkel local-address=192.168.92.254 name=umam@citramedia.tech \
    profile=VPN remote-address=192.168.92.67 routes=192.168.92.254 service=\
    pppoe
add comment=Geneng local-address=192.168.92.254 name=supri@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.75 routes=192.168.92.254 \
    service=pppoe
add comment=Kajok local-address=192.168.92.254 name=derut@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.12 routes=192.168.92.254 \
    service=pppoe
add comment=Tamansari local-address=192.168.92.254 name=\
    rifai_stropong@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.84 routes=192.168.92.254 service=pppoe
add comment=Singkel local-address=192.168.92.254 name=\
    faissingkil@citramedia.tech profile="Paket 100Rb" remote-address=\
    192.168.92.3 routes=192.168.92.254 service=pppoe
add comment=Geneng local-address=192.168.92.254 name=naura@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.27 routes=192.168.92.254 \
    service=pppoe
add comment=Singkel local-address=192.168.92.254 name=mimu1@citramedia.tech \
    profile="Paket 200Rb" remote-address=192.168.92.2 routes=192.168.92.254 \
    service=pppoe
add comment=kajok local-address=192.168.92.254 name=imbank@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.82 routes=192.168.92.254 \
    service=pppoe
add comment=Geneng local-address=192.168.92.254 name=laila@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.78 routes=192.168.92.254 \
    service=pppoe
add comment=Suwang local-address=192.168.92.254 name=darul@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.61 routes=192.168.92.254 \
    service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    khamid@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.71 \
    routes=192.168.92.254 service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    dina@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.81 \
    routes=192.168.92.254 service=pppoe
add comment=Suwot local-address=192.168.92.254 name=kamal@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.52 routes=192.168.92.254 \
    service=pppoe
add comment=Kajok local-address=192.168.92.254 name=ilham@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.5 routes=192.168.92.254 \
    service=pppoe
add comment=Singkel local-address=192.168.92.254 name=handoko@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.59 routes=192.168.92.254 \
    service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    hamid@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.68 \
    routes=192.168.92.254 service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    istiqomah@citramedia.tech profile="Paket 100Rb" remote-address=\
    192.168.92.69 routes=192.168.92.254 service=pppoe
add comment="Syfa Kajok" local-address=192.168.92.254 name=\
    syifa@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.89 \
    routes=192.168.92.254 service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    hana@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.25 \
    routes=192.168.92.254 service=pppoe
add comment=Geneng local-address=192.168.92.254 name=londo@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.94 routes=192.168.92.254 \
    service=pppoe
add comment=Segawe local-address=192.168.92.254 name=\
    faridsegawe@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.97 routes=192.168.92.254 service=pppoe
add comment=Kajok local-address=192.168.92.254 name=damayanti@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.99 routes=192.168.92.254 \
    service=pppoe
add comment="Roup Mbomo" local-address=192.168.92.254 name=\
    roup@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.42 \
    routes=192.168.92.254 service=pppoe
add comment="Huda Mbomo" local-address=192.168.92.254 name=\
    huda@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.40 \
    routes=192.168.92.254 service=pppoe
add comment=Mbomo local-address=192.168.92.254 name=ramu@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.39 routes=192.168.92.254 \
    service=pppoe
add comment=Mbomo local-address=192.168.92.254 name=saifur@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.41 routes=192.168.92.254 \
    service=pppoe
add comment=Singkel local-address=192.168.92.254 name=rizka@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.43 routes=192.168.92.254 \
    service=pppoe
add comment=Mbomo local-address=192.168.92.254 name=arliana@citramedia.tech \
    profile="Paket 100Rb" remote-address=192.168.92.44 routes=192.168.92.254 \
    service=pppoe
add comment=Mbomo local-address=192.168.92.254 name=nurmbomo@citramedia.tech \
    profile="Paket 100Rb" remote-address=192.168.92.45 routes=192.168.92.254 \
    service=pppoe
add comment=Mbomo local-address=192.168.92.254 name=saidmbomo@citramedia.tech \
    profile="Paket 100Rb" remote-address=192.168.92.46 routes=192.168.92.254 \
    service=pppoe
add comment=Mbomo local-address=192.168.92.254 name=yahtar@citramedia.tech \
    profile="Paket 100Rb" remote-address=192.168.92.47 routes=192.168.92.254 \
    service=pppoe
add comment=Mbomo local-address=192.168.92.254 name=ardian@citramedia.tech \
    profile="Paket 100Rb" remote-address=192.168.92.48 routes=192.168.92.254 \
    service=pppoe
add comment=Mbomo local-address=192.168.92.254 name=nadzir@citramedia.tech \
    profile="Paket 100Rb" remote-address=192.168.92.49 routes=192.168.92.254 \
    service=pppoe
add comment="Mujib Mbomo" local-address=192.168.92.254 name=\
    mujib@citramedia.tech profile="Paket 100Rb" remote-address=192.168.92.50 \
    routes=192.168.92.254 service=pppoe
add comment="Firman 1 |  Tamansari" local-address=192.168.92.254 name=\
    firman@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.37 \
    routes=192.168.92.254 service=pppoe
add comment=Singkel local-address=192.168.92.254 name=mukhlis@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.100 routes=192.168.92.254 \
    service=pppoe
add comment=Mbomo local-address=192.168.92.254 name=\
    agus_mbomo@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.79 routes=192.168.92.254 service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    surono@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.102 routes=192.168.92.254 service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    saroh@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.103 \
    routes=192.168.92.254 service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    johan@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.104 \
    routes=192.168.92.254 service=pppoe
add comment=Kajok local-address=192.168.92.254 name=banggok@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.106 routes=192.168.92.254 \
    service=pppoe
add comment=Kajok local-address=192.168.92.254 name=\
    tokomasripah@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.107 routes=192.168.92.254 service=pppoe
add comment=Geneng local-address=192.168.92.254 name=rofik@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.109 routes=192.168.92.254 \
    service=pppoe
add comment="Sifa Suwang" local-address=192.168.92.254 name=\
    sifa@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.110 \
    routes=192.168.92.254 service=pppoe
add comment=Kajok local-address=192.168.92.254 name=elbara@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.113 routes=192.168.92.254 \
    service=pppoe
add comment=Suwang local-address=192.168.92.254 name=colak@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.114 routes=192.168.92.254 \
    service=pppoe
add comment="Firman 2 | Segawe" local-address=192.168.92.254 name=\
    firman2@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.117 routes=192.168.92.254 service=pppoe
add comment="Hudi Singkel" local-address=192.168.92.254 name=\
    hudi@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.118 \
    routes=192.168.92.254 service=pppoe
add comment=Tamansari local-address=192.168.92.254 name=manan@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.35 routes=192.168.92.254 \
    service=pppoe
add comment="Suwang kidul" local-address=192.168.92.254 name=\
    amer@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.105 \
    routes=192.168.92.254 service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    putri@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.119 \
    routes=192.168.92.254 service=pppoe
add comment=Kajok local-address=192.168.92.254 name=aldo@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.123 routes=192.168.92.254 \
    service=pppoe
add comment=Geneng disabled=yes local-address=192.168.92.254 name=\
    gofur@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.124 \
    routes=192.168.92.254 service=pppoe
add comment=Geneng disabled=yes local-address=192.168.92.254 name=\
    wulan@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.125 \
    routes=192.168.92.254 service=pppoe
add comment=Kajok local-address=192.168.92.254 name=indah@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.126 routes=192.168.92.254 \
    service=pppoe
add comment=Suwot local-address=192.168.92.254 name=\
    ayukzhwtna@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.127 routes=192.168.92.254 service=pppoe
add comment=Suwot local-address=192.168.92.254 name=iza@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.128 routes=192.168.92.254 \
    service=pppoe
add comment=Tamansari local-address=192.168.92.254 name=mala@citramedia.tech \
    profile="Paket 350Rb" remote-address=192.168.92.22 routes=192.168.92.254 \
    service=pppoe
add comment=Geneng local-address=192.168.92.254 name=mudhofar@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.129 routes=192.168.92.254 \
    service=pppoe
add comment="Edi Geneng" local-address=192.168.92.254 name=\
    edi@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.130 \
    routes=192.168.92.254 service=pppoe
add comment=Kajok local-address=192.168.92.254 name=sesha@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.133 routes=192.168.92.254 \
    service=pppoe
add comment=Segawe local-address=192.168.92.254 name=frida@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.116 routes=192.168.92.254 \
    service=pppoe
add comment=Kajok local-address=192.168.92.254 name=konyak@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.134 routes=192.168.92.254 \
    service=pppoe
add comment=Suwang disabled=yes local-address=192.168.92.254 name=\
    tiara@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.111 \
    routes=192.168.92.254 service=pppoe
add comment=Suwang disabled=yes local-address=192.168.92.254 name=\
    zairi@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.135 \
    routes=192.168.92.254 service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    saidsuwot@citramedia.tech profile="Paket 100Rb" remote-address=\
    192.168.92.138 routes=192.168.92.254 service=pppoe
add comment=Kajok local-address=192.168.92.254 name=bususi@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.140 routes=192.168.92.254 \
    service=pppoe
add comment="Heri Suwang" local-address=192.168.92.254 name=\
    herisuwangkidol@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.141 routes=192.168.92.254 service=pppoe
add comment=Segawe local-address=192.168.92.254 name=udinfaid@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.144 routes=192.168.92.254 \
    service=pppoe
add comment=Geneng local-address=192.168.92.254 name=inulbomo@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.142 routes=192.168.92.254 \
    service=pppoe
add comment=Geneng local-address=192.168.92.254 name=ridwan@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.145 routes=192.168.92.254 \
    service=pppoe
add comment=Kajok local-address=192.168.92.254 name=ali@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.147 routes=192.168.92.254 \
    service=pppoe
add comment=Geneng local-address=192.168.92.254 name=evis@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.148 routes=192.168.92.254 \
    service=pppoe
add comment=Segawe local-address=192.168.92.254 name=rahayu@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.11 routes=192.168.92.254 \
    service=pppoe
add comment=Geneng local-address=192.168.92.254 name=subhan@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.149 routes=192.168.92.254 \
    service=pppoe
add comment=Suwot local-address=192.168.92.254 name=kholil@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.150 routes=192.168.92.254 \
    service=pppoe
add comment=Suwang local-address=192.168.92.254 name=\
    ferisuwang@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.63 routes=192.168.92.254 service=pppoe
add comment=Sagara local-address=192.168.92.254 name=reza@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.152 routes=192.168.92.254 \
    service=pppoe
add comment=Kajok local-address=192.168.92.254 name=linadidik@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.153 routes=192.168.92.254 \
    service=pppoe
add comment=Singkel local-address=192.168.92.254 name=erham@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.154 routes=192.168.92.254 \
    service=pppoe
add comment=Kajok local-address=192.168.92.254 name=jarwo@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.115 routes=192.168.92.254 \
    service=pppoe
add comment=Segawe local-address=192.168.92.254 name=\
    edysegawe@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.87 routes=192.168.92.254 service=pppoe
add comment=Segawe local-address=192.168.92.254 name=\
    indahsegawe@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.146 routes=192.168.92.254 service=pppoe
add comment=Geneng local-address=192.168.92.254 name=Khusnul@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.139 routes=192.168.92.254 \
    service=pppoe
add comment="Ses geneng" local-address=192.168.92.254 name=\
    sesgeneng@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.15 routes=192.168.92.254 service=pppoe
add comment=Geneng local-address=192.168.92.254 name=sinasi@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.108 routes=192.168.92.254 \
    service=pppoe
add comment=Kajok local-address=192.168.92.254 name=khilmiyah@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.54 routes=192.168.92.254 \
    service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    fendy@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.66 \
    routes=192.168.92.254 service=pppoe
add comment=Suwang local-address=192.168.92.254 name=khomisah@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.53 routes=192.168.92.254 \
    service=pppoe
add comment=Segawe local-address=192.168.92.254 name=humam@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.56 routes=192.168.92.254 \
    service=pppoe
add comment="Anik Susanti | Makam Do'a" local-address=192.168.92.254 name=\
    zahra@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.33 \
    routes=192.168.92.254 service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    adahkalicilik@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.31 routes=192.168.92.254 service=pppoe
add comment=Suwang local-address=192.168.92.254 name=zul@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.93 routes=192.168.92.254 \
    service=pppoe
add comment=Suwang local-address=192.168.92.254 name=\
    nilamnizam@citramedia.tech profile="Paket 200Rb" remote-address=\
    192.168.92.252 routes=192.168.92.254 service=pppoe
add comment=Tamansari local-address=192.168.92.254 name=rozaq@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.29 routes=192.168.92.254 \
    service=pppoe
add comment=Segawe local-address=192.168.92.254 name=topek@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.36 routes=192.168.92.254 \
    service=pppoe
add comment=Singkel local-address=192.168.92.254 name=refano@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.26 routes=192.168.92.254 \
    service=pppoe
add comment=Suwang local-address=192.168.92.254 name=yani@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.121 routes=192.168.92.254 \
    service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    kumasir@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.62 routes=192.168.92.254 service=pppoe
add comment=Suwang local-address=192.168.92.254 name=nanang@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.155 routes=192.168.92.254 \
    service=pppoe
add comment=Mbomo local-address=192.168.92.254 name=anik@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.98 routes=192.168.92.254 \
    service=pppoe
add comment=Suwang local-address=192.168.92.254 name=salem@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.38 routes=192.168.92.254 \
    service=pppoe
add comment=Kajok disabled=yes local-address=192.168.92.254 name=\
    zainal@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.156 routes=192.168.92.254 service=pppoe
add comment=Mbomo local-address=192.168.92.254 name=nadifah@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.157 routes=192.168.92.254 \
    service=pppoe
add comment="Makam Do'a" local-address=192.168.92.254 name=\
    tknawakartika@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.158 routes=192.168.92.254 service=pppoe
add comment=Tamansari local-address=192.168.92.254 name=roise@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.159 routes=192.168.92.254 \
    service=pppoe
add comment=Tamansari local-address=192.168.92.254 name=\
    hisyam@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.8 \
    routes=192.168.92.254 service=pppoe
add comment="Makam Do'a" disabled=yes local-address=192.168.92.254 name=\
    lilisdodot@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.90 routes=192.168.92.254 service=pppoe
add comment=SPP local-address=192.168.92.254 name=ririn@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.19 routes=192.168.92.254 \
    service=pppoe
add comment=SPP local-address=192.168.92.254 name=putrageneng@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.14 routes=192.168.92.254 \
    service=pppoe
add comment=SPP local-address=192.168.92.254 name=makruf@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.13 routes=192.168.92.254 \
    service=pppoe
add comment=Singkel local-address=192.168.92.254 name=dwiopp1@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.9 routes=192.168.92.254 \
    service=pppoe
add comment=Mbomo disabled=yes local-address=192.168.92.254 name=\
    rizal@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.7 \
    routes=192.168.92.254 service=pppoe
add comment=Suwot local-address=192.168.92.254 name=rifai@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.70 routes=192.168.92.254 \
    service=pppoe
add comment=Kajok local-address=192.168.92.254 name=abu@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.96 routes=192.168.92.254 \
    service=pppoe
add comment="Rokis Singkel" local-address=192.168.92.254 name=\
    mustofa@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.77 routes=192.168.92.254 service=pppoe
add comment=Tamansari local-address=192.168.92.254 name=\
    miftah2@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.18 routes=192.168.92.254 service=pppoe
add comment=Tamansari local-address=192.168.92.254 name=\
    talkis@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.91 \
    routes=192.168.92.254 service=pppoe
add comment=Geneng local-address=192.168.92.254 name=hendri@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.34 routes=192.168.92.254 \
    service=pppoe
add comment=Suwang disabled=yes local-address=192.168.92.254 name=\
    jalal@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.132 \
    routes=192.168.92.254 service=pppoe
add comment=Suwang local-address=192.168.92.254 name=cipit@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.131 routes=192.168.92.254 \
    service=pppoe
add comment=Mbomo local-address=192.168.92.254 name=inur@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.6 routes=192.168.92.254 \
    service=pppoe
add comment=Suwang local-address=192.168.92.254 name=ipus@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.101 routes=192.168.92.254 \
    service=pppoe
add comment=Singkel local-address=192.168.92.254 name=\
    agasingkil@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.1 routes=192.168.92.254 service=pppoe
add comment=Suwang disabled=yes local-address=192.168.92.254 name=\
    nina@citramedia.tech profile="Paket 150Rb" remote-address=192.168.92.151 \
    routes=192.168.92.254 service=pppoe
add comment="Roup Mbomo" local-address=192.168.92.254 name=\
    azizmakamdoa@citramedia.tech profile="Paket 150Rb" remote-address=\
    192.168.92.4 routes=192.168.92.254 service=pppoe
add comment=Geneng local-address=192.168.92.254 name=ulkasan@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.95 routes=192.168.92.254 \
    service=pppoe
add comment=Kajok local-address=192.168.92.254 name=lek_rokah@citramedia.tech \
    profile="Paket 150Rb" remote-address=192.168.92.85 routes=192.168.92.254 \
    service=pppoe
/system clock
set time-zone-name=Asia/Jakarta
/system identity
set name=CITRA-NETWORK
/system logging
add action=disk topics=critical
add action=disk topics=system
add action=disk topics=error
/system ntp client
set enabled=yes
/system ntp server
set enabled=yes
/system ntp client servers
add address=0.id.pool.ntp.org
add address=1.id.pool.ntp.org
/system scheduler
add !days interval=1h name=JadwalCekMaintenance on-event=\
    "/system script run CekMaintenance" policy=\
    ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon \
    start-date=2026-08-20 start-time=20:51:45
add !days interval=30s name=JadwalCekDown on-event=\
    "/system script run NotifUserDown" policy=\
    ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon \
    start-date=2026-09-19 start-time=00:00:00
add comment="Notifikasi Booting / Startup Router" !days name=JadwalStartup \
    on-event="/system script run NotifStartupReboot" policy=\
    ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon \
    start-time=startup
/system watchdog
set watchdog-timer=no
/tool netwatch
add disabled=yes down-script=pppoe-logout host=192.168.92.254 http-codes="" \
    name=Profile-PPPOE test-script="" type=simple up-script=pppoe-up
/tool romon
set enabled=yes
/tool romon port
add interface=NT2-RB3011
/tool sniffer
set filter-interface=NT2-RB3011
