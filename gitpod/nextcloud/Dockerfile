FROM nextcloud

RUN apt install -y php-xdebug

COPY custom-entrypoint.sh /custom-entrypoint.sh
COPY after-install.sh /after-install.sh

RUN chmod +x /custom-entrypoint.sh
RUN chmod +x /after-install.sh

ENTRYPOINT ["/custom-entrypoint.sh"]

CMD ["/after-install.sh"]
