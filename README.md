# Kong Publisher for Lumen Application

## Installation:

- Add KWRI satis host to your composer.json's repositories
- Run composer require kwri/lumen-kong-route-publisher on your lumen project
- Register KongPublisherServiceProvider on your lumen application


## Run
Run this on your console:
```
$ php artisan kong:publish-route contacts --upstream-host=http://mockbin.com/request --remove-uri-prefix=api/v1  --with-request-transformer
```

Arguments:

- `contacts` is your name of microservices
- `--upstream-host` value is hostname where your microservice server life
- `--remove-uri-prefix` is prefix to be removed
- `--with-request-transformer` Run publisher with request transformer. If you have dynamic path you need to call use this argument
