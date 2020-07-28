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

### II. Support feature
--------
1, Configure the template.
2, Configure the index.
3, Add the self-defined IK-analyzer keywords.
4, Bulk insert data.
5, ElasticSearch Raw DSL query.
6, ElasticSearch DSL query.


## III. API documents
--------
[API document](./doc/es_api.md)
