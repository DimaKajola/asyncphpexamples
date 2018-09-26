FROM phpdockerio/php72-fpm:latest
WORKDIR "/src"

RUN apt-get update && \
	apt-get install -y apt-utils && \
    apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

COPY ./src /src

EXPOSE 9090

CMD php-fpm
