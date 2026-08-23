#!/bin/sh
# Zorgt dat de twee mappen bestaan en beschrijfbaar zijn voordat Apache
# start. Bij een verse server zijn de volumes nog leeg; de website maakt
# zijn eigen .htaccess-bestanden dan bij het eerste bezoek aan.
set -e

for map in /var/www/html/uploads /var/www/html/uploads/thumbs /var/www/html/data; do
    mkdir -p "$map"
    chown -R www-data:www-data "$map"
    chmod -R 775 "$map"
done

# In de repo staat alleen config-voorbeeld.php (zonder wachtwoorden).
# De echte waarden komen uit de omgevingsvariabelen van Coolify.
if [ ! -f /var/www/html/config.php ]; then
    cp /var/www/html/config-voorbeeld.php /var/www/html/config.php
    echo "borgerbreeveld: config.php aangemaakt uit het voorbeeld"
fi

echo "borgerbreeveld: mappen klaar, Apache start"
exec apache2-foreground
