docker run --rm -it \
    --name alpine-apache-php-custom \
    --hostname alpine-apache-php.local \
    --env HTTP_SERVER_NAME="www.example.xyz" \
    --env HTTPS_SERVER_NAME="www.example.xyz" \
    --env SERVER_ADMIN="admin@example.xyz" \
    --env TZ="Europe/Paris" \
    --env PHP_MEMORY_LIMIT="512M" \
    --publish 127.0.0.1:80:80 \
    --publish 127.0.0.1:443:443 \
    --volume $(pwd):/var/www/ \
    jtreminio/php-apache:8.1

#    eriksoderblom/alpine-apache-php:latest
