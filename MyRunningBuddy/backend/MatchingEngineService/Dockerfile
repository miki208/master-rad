FROM myrunningbuddy-base:latest

ENV WEB_SERVER_PORT=80

RUN echo "Creating database..." && \
    service mysql start && \
    mysql -u root -e "CREATE DATABASE MatchingEngineService" && \
    mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'nekasifra208';" && \
    service mysql stop

ADD ./ $HOME/

RUN echo "Setting the project" && \
    service mysql start && \
    php ./bin/composer install && \
    php artisan migrate && \
    service mysql stop

EXPOSE $WEB_SERVER_PORT

ENTRYPOINT rm -f /var/run/mysqld/* && service mysql start && php -S 0.0.0.0:$WEB_SERVER_PORT -t ./public
