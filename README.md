# dnscrypt-proxy-stats
dig into your dns traffic via webstats

The directions below are for Debian/11 Bullseye, but should work for most current debian based systems.

```
apt update && apt install dnscrypt-proxy resolvconf
```

To make all this possible you need to install dnscrypt-proxy, to listen to your entire network in debian you need to tweak the sockets file to listen to 0.0.0.0:53

edit /etc/systemd/system/sockets.target.wants/dnscrypt-proxy.socket, the important bit is ListenStream and ListenDatagram lines
```
[Unit]
Description=dnscrypt-proxy listening socket
Documentation=https://github.com/DNSCrypt/dnscrypt-proxy/wiki
Before=nss-lookup.target
Wants=nss-lookup.target
Wants=dnscrypt-proxy-resolvconf.service

[Socket]
ListenStream=0.0.0.0:53
ListenDatagram=0.0.0.0:53
NoDelay=true
DeferAcceptSec=1

[Install]
WantedBy=sockets.target
```

next edit /etc/systemd/system/multi-user.target.wants/dnscrypt-proxy-resolvconf.service, unfortunately you'll need to hardcode the IP of your system in this script, because of the way dnscrypt-proxy usually obtains the IP it's not possible to automate it
```
[Unit]
Description=DNSCrypt proxy resolvconf support
Documentation=https://github.com/DNSCrypt/dnscrypt-proxy/wiki
After=dnscrypt-proxy.socket
Requires=dnscrypt-proxy.socket
ConditionFileIsExecutable=/sbin/resolvconf

[Service]
Type=oneshot
RemainAfterExit=true
ExecStart=/bin/sh -c "echo 'nameserver 192.168.1.1' | /sbin/resolvconf -a lo.dnscrypt-proxy"
ExecStop=/sbin/resolvconf -d lo.dnscrypt-proxy

[Install]
WantedBy=multi-user.target
Also=dnscrypt-proxy.socket
```

You also need to tell dnscrypt-proxy to log queries, if you are running this publically you will need to filter out source IPs and other non-useful things

edit /etc/dnscrypt-proxy/dnscrypt-proxy.toml

```
# Empty listen_addresses to use systemd socket activation, due to the way debain packages dnscrypt-proxy
listen_addresses = []

server_names = ['cloudflare']

ipv4_servers = true
ipv6_servers = true

doh_servers = true
dnscrypt_servers = true

use_syslog = true

# Cloudflare DNS server IPs, alternatively these can be set to your ISPs DNS servers as fall back
fallback_resolvers = ['1.1.1.1:53', '1.0.0.1:53']
ignore_system_dns = true

cache = true
cache_size = 4096
cache_min_ttl = 2400
cache_max_ttl = 86400
cache_neg_min_ttl = 60
cache_neg_max_ttl = 600

log_files_max_size = 10
log_files_max_age = 7
log_files_max_backups = 1

[query_log]
  file = '/var/run/query.log.pipe'
  format = 'ltsv'
```

Edit /etc/systemd/system/multi-user.target.wants/import-ltsv.service
```
[Unit]
Description=DNSCrypt-proxy to mysql logging software
Documentation=https://github.com/evilbunny2008/dnscrypt-proxy-stats/blob/main/README.md
Before=dnscrypt-proxy.service
After=network.target mariadb.service

[Service]
Type=simple
ExecStart=/var/www/html/import-ltsv.php

[Install]
WantedBy=multi-user.target
```

You now need to install a web server and mariadb-server, phpmyadmin is also helpful
```
apt install lighttpd mariadb-server phpmyadmin git php-cli php-cgi
```

Now clone this repo on your system, the details below assume a single use system without any useful data existing:
```
mkdir -p /var/www
cd /var/www
rm -rf html
git clone https://github.com/evilbunny2008/dnscrypt-proxy-stats.git html
cd html
cp -a mysql-example.php mysql.php
```

Next make a database and import the schema
```
mysql
CREATE DATABASE dnsstats;
CREATE USER 'dnsstats'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON dnsstats.* TO 'dnsstats'@'localhost';
FLUSH PRIVILEGES;
EXIT;
mysql dnsstats < schema.sql
```

Next edit mysql.php and replace the placeholder details with the actual mariadb account details

Finally you need to reload the scripts in systemd
```
systemctl daemon-reload
systemctl stop systemd-resolved.service
systemctl disable systemd-resolved.service

systemctl enable import-ltsv.service
systemctl start import-ltsv.service

systemctl restart dnscrypt-proxy.socket
systemctl restart dnscrypt-proxy-resolvconf.service
```

The next thing needed is to set your shiny new dnscrypt-proxy system as the default in your DHCP server, but due to the number of routers out there that is beyond the scope of this project. You might want to also firewall your instance of dnscrypt-proxy to prevent the world from using your system as a recursive system, but again this is beyond the scope of this document. I've set mine up inside my network behind NAT and port 53 isn't forwarded.
