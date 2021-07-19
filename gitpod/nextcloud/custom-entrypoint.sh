#!/bin/sh

# Both gitpod and Nextcloud need read/write acess
# Obviously not recommended for a production system
chmod -R 777 /var/www/html/custom_apps/

/entrypoint.sh "$@"
