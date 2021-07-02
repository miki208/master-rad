FROM myrunningbuddy-base:latest

ENV WEB_SERVER_PORT=80

RUN echo "Creating database..." && \
    service mysql start && \
    mysql -u root -e "CREATE DATABASE RunnerManagementService" && \
    mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'nekasifra208';" && \
    service mysql stop

ADD ./ $HOME/

RUN echo "Setting the project" && \
    service mysql start && \
    php ./bin/composer install && \
    php artisan migrate && \
    mysql -u root --password=nekasifra208 -e "USE RunnerManagementService; INSERT INTO ExternalServices(service_name, human_friendly_name) VALUES('StravaGatewayService', 'Strava');" && \
    service mysql stop && \
    touch /var/spool/cron/crontabs/root && \
    { crontab -l; echo "* * * * * cd / && php artisan schedule:run >> /dev/null 2>&1"; } | crontab -

EXPOSE $WEB_SERVER_PORT

ENTRYPOINT rm -f /var/run/mysqld/* && service mysql start && service cron start && php -S 0.0.0.0:$WEB_SERVER_PORT -t ./public
