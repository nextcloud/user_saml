#!/bin/sh

# Both gitpod and Nextcloud need read/write acess
# Obviously not recommended for a production system
chmod -R 777 /var/www/html/custom_apps/

# mv /debug.config.php /var/www/html/config

# chmod -R 777 /var/www/html/config

/entrypoint.sh "$@"
