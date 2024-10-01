FROM php:8.2-apache AS survey

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y vim build-essential less curl wget sqlite3
RUN wget https://gist.githubusercontent.com/TheDanielPBerry/ffb6dbe950f3b2205ec80c322c6075fd/raw/97d62d3f15dcab52bff48c4fd9f7234dd61dd75b/.vimrc -O ~/.vimrc

RUN service apache2 start

CMD ["apachectl", "-D", "FOREGROUND"]
