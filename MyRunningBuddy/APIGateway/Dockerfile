FROM myrunningbuddy-base:latest

RUN echo "Creating database..." && \
    service mysql start && \
    mysql -u root -e "CREATE DATABASE APIGateway" && \
    mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'nekasifra208';"

ADD ./ $HOME/

RUN echo "Setting the project" && \
    service mysql start && \
    php ./bin/composer install && \
    php artisan migrate && \
    php artisan passport:install

ENTRYPOINT service mysql start && \
    php -S 0.0.0.0:8000 -t ./public
