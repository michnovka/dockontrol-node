FROM nginx:1.26.2

# Install iproute2 for 'ip' command and gettext-base for 'envsubst'
RUN apt-get update && apt-get install -y iproute2 gettext-base && rm -rf /var/lib/apt/lists/*

# Copy template and entrypoint
COPY config/nginx/default.conf.tpl /etc/nginx/conf.d/default.conf.tpl
COPY docker/nginx-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/nginx-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/nginx-entrypoint.sh"]
