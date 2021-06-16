# dnscrypt-proxy-stats
dig into your dns traffic via webstats

<img width="900px" src="https://raw.githubusercontent.com/evilbunny2008/dnscrypt-proxy-stats/main/screenshot.jpg">

The directions below are for Debian/11 Bullseye, but should work for most current debian based systems, these instructions don't use Debian packages because they're intentionally limited to localhost lookups only.

To make all this possible you need to install dnscrypt-proxy, you can grab the latest version of dnscrypt-proxy from [Here](https://github.com/jedisct1/dnscrypt-proxy/releases/latest) then extract it and copy the main binary to /usr/bin/dnscrypt-proxy

```
apt update
apt dist-upgrade
apt install wget
wget https://github.com/DNSCrypt/dnscrypt-proxy/releases/download/2.0.46-beta3/dnscrypt-proxy-linux_arm64-2.0.46-beta3.tar.gz
tar xzvf dnscrypt-proxy-linux_arm64-2.0.46-beta3.tar.gz
cd linux-arm64
cp -a dnscrypt-proxy /usr/bin/
mkdir /etc/dnscrypt-proxy
```

You also need to tell dnscrypt-proxy to log queries, if you are running this publically you will need to filter out source IPs and other non-useful things

edit /etc/dnscrypt-proxy/dnscrypt-proxy.toml

```
server_names = ['cloudflare']
listen_addresses = ['[::]:53']
max_clients = 250
ipv4_servers = true
ipv6_servers = true
dnscrypt_servers = true
doh_servers = true
require_dnssec = false
force_tcp = false
timeout = 5000
keepalive = 30
cert_refresh_delay = 240
bootstrap_resolvers = ['1.1.1.1:53', '1.0.0.1:53']
ignore_system_dns = true
netprobe_timeout = 60
netprobe_address = '1.1.1.1:53'
log_files_max_size = 10
log_files_max_age = 7
log_files_max_backups = 1
block_ipv6 = false
block_unqualified = true
block_undelegated = true
reject_ttl = 600

cache = true
cache_size = 4096
cache_min_ttl = 2400
cache_max_ttl = 86400
cache_neg_min_ttl = 60
cache_neg_max_ttl = 600

[query_log]

  file = '/var/run/query.log.pipe'
  format = 'ltsv'
```

Next install the dnscrypt-proxy.service systemd file

```
/usr/bin/dnscrypt-proxy -service install
```

Edit /etc/systemd/system/import-ltsv.service
```
[Unit]
Description=DNSCrypt-proxy to mysql logging software
Documentation=https://github.com/evilbunny2008/dnscrypt-proxy-stats/blob/main/README.md
Before=dnscrypt-proxy.service
After=network.target mariadb.service

[Service]
Type=simple
ExecStart=/var/www/html/import-ltsv.php
Restart=always

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

One footnote, when dealing with virtuals you may need to manually reconfigure the timezone:
```
dpkg-reconfigure tzdata
```

Finally you need to reload the scripts in systemd
```
systemctl daemon-reload
systemctl stop systemd-resolved.service
systemctl disable systemd-resolved.service

systemctl enable import-ltsv.service
systemctl start import-ltsv.service

systemctl restart dnscrypt-proxy.service
```

The next thing needed is to set your shiny new dnscrypt-proxy system as the default in your DHCP server, but due to the number of routers out there that is beyond the scope of this project. You might want to also firewall your instance of dnscrypt-proxy to prevent the world from using your system as a recursive system, but again this is beyond the scope of this document. I've set mine up inside my network behind NAT and port 53 isn't forwarded.
