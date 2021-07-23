FROM nextcloud

RUN yes | pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_host=host.docker.internal" >> /usr/local/etc/php/conf.d/xdebug.ini

COPY custom-entrypoint.sh /custom-entrypoint.sh
COPY after-install.sh /after-install.sh

RUN chmod +x /custom-entrypoint.sh
RUN chmod +x /after-install.sh

ENTRYPOINT ["/custom-entrypoint.sh"]

CMD ["/after-install.sh"]
