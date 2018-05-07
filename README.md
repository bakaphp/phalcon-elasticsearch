# Baka Phalcon Elastic Search

Phalcon Elastic Search package to index / query model with relationship easily

## Table of Contents
1. [Indexing](#indexing)
    1. [Create](#indexing-create)
    1. [Insert](#indexing-insert)
2. [Model's](#model)
3. [Search](#markdown-header-QueryParser)
4. [Testing](#markdown-header-QueryParser-Extended)

## Installing
Packages:
- `"elasticsearch/elasticsearch": "~2.0@beta"`
- `"baka/database": "dev-master"`
- `"phalcon/incubator": "~3.0","`

Add elastic configuration to config.php

```php

#config.php

'namespace' => [
    'controller' => 'Project\Controllers',
    'models' => 'Project\Models',
],

'elasticSearch' => [
    'hosts' => [getenv('ELASTIC_HOST')], //change to pass array
],

```

add queue to DI

```php

#service.php

$di->set('queue', function () use ($config) {
    //Connect to the queue
    $queue = new Phalcon\Queue\Beanstalk\Extended([
        'host' => $config->beanstalk->host,
        'prefix' => $config->beanstalk->prefix,
    ]);

    return $queue;
});

```

## Indexing
To create a Index in Elastic search first you need to configure a elastic search en your project




## Model's

## Search

## Testing
```
codecept run
```