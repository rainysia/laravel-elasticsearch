Elastic Search
=========
TFF Elastic Search project.

```
PHP >= 5.6
Elastic Search >= 5.6
Laravel >= 5.4
```

## I. How to Use
-------
1, Configure you php with nginx as laravel.

nginx es.conf example
```
# ES services
server {
    listen       80;
    server_name  es.tom.com;
    root /home/www/elastic_search/public;
    location / {
        index index.php index.html;
        try_files $uri $uri/ /index.php?$args;
        autoindex on;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9955;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /home/www/elastic_search/public$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

2, Intial the .env
```
cp .env.template .env
```

3, Setup the hosts,`vim /etc/hosts` and add the new line with below content.
```
127.0.0.1 es.tom.com
```

4, Visit `es.tom.com` to verify via browser.

## II. Support feature
--------
1. Configure the template.(API document 1.1)
2. Configure the index.(API document 1.2)
3. Add the self-defined IK-analyzer keywords.(API document 1.3)
4. Bulk insert data.(API document 2)
5. ElasticSearch Raw DSL query.(API document 2.5)
6. ElasticSearch DSL query.(API document 2.6)
7. Laravel-scount support.


## IV. API documents
--------
[API document](./doc/es_api.md)
| Laravel-Elastic Search API                                         |  Request Method |    Desc    |
| ------------------------------------------- |:--------:|:-------------:|
| [1.1.1 Create Template](./doc/es_api.md#user-content-111-create-template)        |   POST   |   Create ElasticSearch Template |
| [1.1.2 Get Template](./doc/es_api.md#user-content-112-get-specified-template)        |   GET   |   Get Specified ElasticSearch Template |
| [1.1.3 Delete Template](./doc/es_api.md#user-content-113-delete-specified-template)        |   POST   |   Delete Specified ElasticSearch Template |
| [1.2.1 Create Index](./doc/es_api.md#user-content-121-create-index)        |   POST   |   Create ElasticSearch Index |
| [1.2.2 Get Index](./doc/es_api.md#user-content-122-get-specified-index)        |   GET   |   Get ElasticSearch Index |
| [1.3.1 Get IK](./doc/es_api.md#user-content-131-query-current-ik-custom-dict)        |   GET   |   Get ElasticSearch IK custom dict |
| [1.3.2 Add IK](./doc/es_api.md#user-content-132-add-new-ik-custom-string-or-array-into-dict)        |   GET   |   Add ElasticSearch IK custom dict |
| [2.1 Insert Doc](./doc/es_api.md#user-content-21-insert-single-doc)        |   POST   |   Add ElasticSearch doc |
| [2.2 Bulk Insert Doc](./doc/es_api.md#user-content-22-bulk-insert-doc)        |   POST   |   Bulk Add ElasticSearch doc |
| [2.3 Delete Doc](./doc/es_api.md#user-content-23-delete-single-doc)        |   POST   |   Delete ElasticSearch single doc |
| [2.4 Get Doc](./doc/es_api.md#user-content-24-get-single-doc-by-id)        |   POST   |   Get ElasticSearch single doc By `_index`|
| [2.5 Search Doc With ES raw DSL](./doc/es_api.md#user-content-25-search-data-via-raw-query)        |   POST   |   Search ElasticSearch doc via ES raw DSL|
| [2.5 Search Doc with json DSL](./doc/es_api.md#user-content-26-search-data-via-json)        |   POST   |   Search ElasticSearch doc via structured json|

## V. Contact
---------------------------------
Follow me @[rainy_sia](https://twitter.com/rainy_sia) in twitter, [@rainysia](http://weibo.com/rainysia) in weibo, mail me at rainysia#gmail.com

## VI. License
---------------------------------
Copyright by rainy.sia, 2020 Licensed under the [MIT license](http://www.opensource.org/licenses/mit-license.php)

