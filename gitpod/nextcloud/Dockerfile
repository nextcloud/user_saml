FROM nextcloud

COPY custom-entrypoint.sh /custom-entrypoint.sh
COPY after-install.sh /after-install.sh

RUN chmod +x /custom-entrypoint.sh
RUN chmod +x /after-install.sh

ENTRYPOINT ["/custom-entrypoint.sh"]

# Needs to be set again, even though it is already in base image
# reference: https://docs.docker.com/engine/reference/builder/#understand-how-cmd-and-entrypoint-interact
CMD ["/after-install.sh"]
