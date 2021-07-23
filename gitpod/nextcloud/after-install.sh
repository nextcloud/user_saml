#!/bin/sh

echo "after install !!!!!"

run_as() {
    if [ "$(id -u)" = 0 ]; then
        su -p www-data -s /bin/sh -c "$1"
    else
        sh -c "$1"
    fi
}

run_as "php /var/www/html/occ config:system:set debug --value='true' --type=boolean"

# Weirdly the Nextcloud docker activates user_saml app automatically but doesn't run the migration steps necessary for it to work
run_as "php /var/www/html/occ migrations:migrate user_saml"

apache2-foreground