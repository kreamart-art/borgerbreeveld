# De herdenkingspagina draait op Apache met php ingebouwd (mod_php).
# Dat is met opzet: de .htaccess-bestanden die de uploadmap beveiligen
# en de uploadlimieten verhogen werken alleen bij mod_php.
FROM php:8.3-apache

# GD is nodig om foto's te verkleinen, EXIF om telefoonfoto's rechtop
# te zetten. Allebei zitten ze niet standaard in de image.
#
# Let op: de ontwikkelpakketten NIET opruimen met purge --auto-remove.
# Dat haalt ook de gewone bibliotheken weg (libpng16.so.16 en zo), en
# dan laadt GD niet meer en worden foto's niet verkleind.
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      libjpeg62-turbo-dev libpng-dev libwebp-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install -j"$(nproc)" gd exif \
 && rm -rf /var/lib/apt/lists/*

# .htaccess werkt alleen als Apache hem mag lezen, en de galerij heeft
# mod_rewrite nodig voor de beveiliging van de uploadmap.
RUN a2enmod rewrite headers \
 && printf '<Directory /var/www/html>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/borger.conf \
 && a2enconf borger

# Grote telefoonvideo's moeten erdoor kunnen.
#
# display_errors staat uit: als er ooit iets misgaat hoort de bezoeker
# een nette pagina te zien, geen foutmelding met het pad op de server
# erin. De fout komt gewoon in het logboek van Apache terecht, daar kun
# je hem opzoeken met: docker logs <container>
RUN printf 'upload_max_filesize = 200M\n\
post_max_size = 220M\n\
max_execution_time = 600\n\
max_input_time = 600\n\
memory_limit = 256M\n\
expose_php = Off\n\
display_errors = Off\n\
display_startup_errors = Off\n\
log_errors = On\n' > /usr/local/etc/php/conf.d/borger.ini

# De website zelf.
COPY --chown=www-data:www-data . /var/www/html/

# uploads/ en data/ worden op de server aparte schijven (volumes). Ze
# moeten beschrijfbaar zijn voor Apache, anders komt er geen inzending
# binnen. Dit zet dat goed, ook als het volume nog leeg is.
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 80
CMD ["/usr/local/bin/start.sh"]
