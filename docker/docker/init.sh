#!/bin/sh

docker-compose exec --user `id -u`:`id -g` php bash -c 'cd /var/www/backend && composer install'
