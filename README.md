# Kong Publisher for Lumen Application

## Installation:

- Add KWRI satis host to your composer.json's repositories
- Run composer require kwri/lumen-kong-route-publisher on your lumen project
- Register KongPublisherServiceProvider on your lumen application


## Run
Run this on your console:
```
$ KONG_ADMIN_HOST=yourkong-admin-host php artisan kong:publish-route contacts --upstream-host=http://mockbin.com/request --remove-uri-prefix=api/v1  --with-request-transformer
```

Arguments:

- `contacts` is your name of microservices
- `--upstream-host` value is hostname where your microservice server life
- `--remove-uri-prefix` is prefix to be removed
- `--with-request-transformer` Run publisher with request transformer. If you have dynamic path you need to call use this argument

Env used:
- `KONG_ADMIN_HOST` value is your kong admin host. for example:
http://localhost:8001

You can alternatively put KONG_ADMIN_HOST in your lumen project .env file instead of set it when calling publish-route command

convention in contacts is:
/api/v1/contacts
/api/v1/contacts/emails

later when publishing route you can call
```
$ php artisan kong:publish-route contacts --upstream-host=http://mockbin.com/request --remove-uri-prefix=api/v1  --with-request-transformer
```

it will make route in apigateway become:
- apigateway-host.com/contacts
- apigateway-host.com/contacts/emails

And when access that it will be forwarded to:
- mockbin.com/request/api/v1/contacts
- mockbin.com/request/api/v1/contacts/emails
