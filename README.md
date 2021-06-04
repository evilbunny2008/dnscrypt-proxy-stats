# dnscrypt-proxy-stats
dig into your dns traffic via webstats

To make all this possible you need to install dnscrypt-proxy, to listen to your entire network in debian you need to tweak the sockets file to listen to 0.0.0.0:53

You also need to tell dnscrypt-proxy to log queries, if you are running this publically you will filter out source IPs and other non-useful things

# Empty listen_addresses to use systemd socket activation
listen_addresses = []

server_names = ['cloudflare']

ipv4_servers = true
ipv6_servers = true

doh_servers = true
dnscrypt_servers = true

use_syslog = true

# Cloudflare DNS server IPs
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
  file = '/var/log/dnscrypt-proxy/query.log'
  format = 'ltsv'

[sources]
  [sources.'public-resolvers']
  url = 'https://download.dnscrypt.info/resolvers-list/v2/public-resolvers.md'
  cache_file = '/var/cache/dnscrypt-proxy/public-resolvers.md'
  minisign_key = 'RWQf6LRCGA9i53mlYecO4IzT51TGPpvWucNSCh1CBM0QTaLn73Y7GFO3'
  refresh_delay = 72
  prefix = ''
