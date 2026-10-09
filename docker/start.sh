
#!/bin/sh
set -eu

mkdir -p /run/nginx /var/log/nginx

php-fpm -D

nginx -g 'daemon off;'