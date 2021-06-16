#base image
FROM ubuntu:18.04

MAINTAINER Milos Samardzija <miloss208@gmail.com>

#environment
ENV DEBIAN_FRONTEND=noninteractive

#install dependencies
RUN apt-get -yqq update && \
    apt-get -yqq install software-properties-common && \
    add-apt-repository ppa:ondrej/php && \
    apt-get -yqq update

RUN echo "Installing PHP..." && \
    apt-get -yqq install tzdata && \
    apt-get -yqq install php7.4 && \
    apt-get -yqq install php7.4-cli php7.4-fpm php7.4-json php7.4-common php7.4-mysql php7.4-zip php7.4-gd php7.4-mbstring php7.4-curl php7.4-xml php7.4-bcmath

RUN echo "Installing MySQL..." && \
    apt-get -yqq install mysql-server

RUN echo "Installing Composer..." && \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php --install-dir=bin --filename=composer && \
    php -r "unlink('composer-setup.php');"
