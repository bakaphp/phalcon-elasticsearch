# Baka Phalcon Elastic Search

Phalcon Elastic Search package to index / query model with relationship easily

## Table of Contents
1. [Indexing](#indexing)
    1. [Create](#indexing-create)
    1. [Insert](#indexing-insert)
2. [Search](#markdown-header-QueryParser)
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
To create a Index in Elastic search first you will need to configure a CLI project and extend it from `IndexTasksBuilder` , after doing that just run the following command

` php cli/app.php IndexBuilder createIndex ModelName 3`

Where `4` is the normal of levels you want the relationships to index for example
```
Level 1
 Class A 
 - Relation BelongsTo Class B

 Level 2
 Class A 
 - Relation BelongsTo Class B
 - - Class B
 - - - Relation HasMany Class C

Level 3
 Class A 
 - Relation BelongsTo Class B
 - - Class B
 - - - Relation HasMany Class C
 - - - - Class C
 - - - - - Relation HasMany Class D
``` 

I wont recommend going beyond 4 levels if it not neede, it will use a lot of space.

If you get a error related to `nestedLimit` , you can use a 4th param to specify the amount the index limit

` php cli/app.php IndexBuilder createIndex ModelName 3 100`

### Indexing Queue

Now that you created a Index we need to index the data, for that your model will need to extend from `\Baka\Elasticsearch\Model` . After every update | save we will send the information to a queue where the process will insert or update the info in elastic

```php

<?php


class Users extends \Baka\Elasticsearch\Model
{

}
```

Queue

`php cli/app.php IndexBuilder queue ModelName`

Example:
`php cli/app.php IndexBuilder queue Users`


## Search

In order to simply searching en elastic search with elastic you most install this extension https://github.com/NLPchina/elasticsearch-sql

Now your search controller must use our trait

```php

<?php

/**
 * Search controller
 */
class SearchController extends BaseController
{
    use \Baka\Elasticsearch\SearchTrait
}
```

And Follow the same query structure has Baka Http

`https://api.dev/v1/search/indexName?sort=id|asc&q=(is_deleted:0,relationship.type_id:1)&fields=id,first_name,last_name,relationship.name,relationship.relationshipb.name`

Example

`https://api.dev/v1/search/users?sort=first_name|asc&q=(is_deleted:0,users_statuses_id:,first_name:,last_name:)&fields=id,first_name,last_name,potentiality,classification,userssprograms.id,events_satisfaction,is_prospect,gifts.name,is_key_users,dob,companies.name,companies.companiesstatuses.name,companies.rnc,position`

## Testing

```
codecept run
```