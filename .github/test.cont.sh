docker run --rm -it  \
    --name tst \
    --hostname alpine-apache-php.local \
    --env HTTP_SERVER_NAME="www.example.xyz" \
    --env HTTPS_SERVER_NAME="www.example.xyz" \
    --env SERVER_ADMIN="admin@example.xyz" \
    --env TZ="Europe/Paris" \
    --env PHP_MEMORY_LIMIT="512M" \
    --publish 127.0.0.1:80:80 \
    --publish 127.0.0.1:443:443 \
    --volume $(pwd):/var/www/ \
$(echo cXVheS5pby90aGVmb3VuZGF0aW9uL2hvY2tlcjpwaHA3LjQtZHJvcGJlYXItZnBtCg==|base64 -d|sed 's/7.4/7.2/g')_NOMYSQL
#$(echo cXVheS5pby90aGVmb3VuZGF0aW9uL2hvY2tlcjpwaHA4LjEtZHJvcGJlYXItZnBtCg==|base64 -d)

#    jtreminio/php-apache:8.1

#    eriksoderblom/alpine-apache-php:latest
