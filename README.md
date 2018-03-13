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
- `--with-request-transformer` Run publisher with request transformer. If you have dynamic path as path parameter that need to call, You must use this argument (currently only support numerical value of path parameter).
- `--with-oidc` Run publisher with OIDC. value is `;` separator of OIDC client id, client secret, discovery, introspection endpoint and the token auth method.
- `--with-jwt` Run publisher with JWT. value is the onelogin consumer id (for corresponding env)

Env used:
- `KONG_ADMIN_HOST` value is your kong admin host. for example:
http://localhost:8001

You can alternatively put KONG_ADMIN_HOST in your lumen project .env file instead of set it when calling publish-route command

convention in contacts is:
/api/v1/contacts
/api/v1/contacts/emails

later when publishing route you can call
```
$ php artisan kong:publish-route contacts --upstream-host=http://mockbin.com/request --remove-uri-prefix=api/v1  --with-request-transformer --with-jwt=xxxx --with-oidc=someclientid;someclientsecret;https://onelogin.com/.well-known/discovery;https://onelogin.com/token/introspection;client_secret_basic
```

it will make route in apigateway become:
- apigateway-host.com/contacts
- apigateway-host.com/contacts/emails

And when access that it will be forwarded to:
- mockbin.com/request/api/v1/contacts
- mockbin.com/request/api/v1/contacts/emails

## Viewing Kong Payload for Add / Delete Endpoint

To use this feature please provide those env values

- `KONG_ADMIN_HOST` : example 'http://kong-dev:8001'
- `KONG_PUBLISHER_UPSTREAM_HOST` : example 'http://mailchimp-microservice.dev'
- `KONG_PUBLISHER_REMOVED_URI_PREFIX` : example 'api/v1'

Available endpoint:

- `/kong/delete-routes`: Display kong payload for deleting route
   Available parameter: -

- `/kong/publish-routes`: Display kong payload for adding endpoint along with activating plugin.
  Available request parameter: `with-request-transformer`, `with-oidc`, `with-jwt`
