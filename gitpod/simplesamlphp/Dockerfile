FROM unicon/simplesamlphp

COPY etc-httpd /etc/httpd/
COPY var-simplesamlphp /var/simplesamlphp/
COPY var-www-html/ /var/www/html/

RUN chown apache:apache /var/simplesamlphp/log/ \
    && chown -R apache:apache /var/simplesamlphp/cert/
