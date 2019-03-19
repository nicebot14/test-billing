#!/bin/sh

docker-compose exec --user `id -u`:`id -g` php bash -c 'cd /var/www/backend && php bin/console app:worker'
