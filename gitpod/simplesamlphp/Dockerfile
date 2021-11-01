FROM unicon/simplesamlphp

COPY var-simplesamlphp /var/simplesamlphp/

RUN chown apache:apache /var/simplesamlphp/log/ \
    && chown -R apache:apache /var/simplesamlphp/cert/
