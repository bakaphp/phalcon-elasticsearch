<?php

use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use \Phalcon\Di;
use \Phalcon\Test\UnitTestCase as PhalconTestCase;
use Phalcon\Annotations\Adapter\Memcached;

abstract class PhalconUnitTestCase extends PhalconTestCase
{
    /**
     * @var \Voice\Cache
     */
    protected $_cache;

    /**
     * @var \Phalcon\Config
     */
    protected $_config;

    /**
     * @var bool
     */
    private $_loaded = false;

    /**
     * Setup phalconPHP DI to use for testing components
     *
     * @return Phalcon\DI
     */
    protected function _getDI()
    {
        Phalcon\DI::reset();

        $di = new Phalcon\DI();

        /**
         * DB Config
         * @var array
         */
        $this->_config = new \Phalcon\Config([
            'database' => [
                'adapter' => 'Mysql',
                'host' => getenv('DATABASE_HOST'),
                'username' => getenv('DATABASE_USER'),
                'password' => getenv('DATABASE_PASS'),
                'dbname' => getenv('DATABASE_NAME'),
            ],
            'memcache' => [
                'host' => getenv('MEMCACHE_HOST'),
                'port' => getenv('MEMCACHE_PORT'),
            ],
            'namespace' => [
                'controller' => '',
                'models' => '',
                'library' => '',
            ],
            'email' => [
                'driver' => 'smtp',
                'host' => getenv('EMAIL_HOST'),
                'port' => getenv('EMAIL_PORT'),
                'username' => getenv('EMAIL_USER'),
                'password' => getenv('EMAIL_PASS'),
                'from' => [
                    'email' => 'noreply@naruho.do',
                    'name' => 'YOUR FROM NAME',
                ],
                'debug' => [
                    'from' => [
                        'email' => 'noreply@naruho.do',
                        'name' => 'YOUR FROM NAME',
                    ],
                ],
            ],
            'beanstalk' => [
                'host' => getenv('BEANSTALK_HOST'),
                'port' => getenv('BEANSTALK_PORT'),
                'prefix' => getenv('BEANSTALK_PREFIX'),
            ],
            'redis' => [
                'host' => getenv('REDIS_HOST'),
                'port' => getenv('REDIS_PORT'),
            ],
            'elasticSearch' => [
                'hosts' => [getenv('ELASTIC_HOST')], //change to pass array
            ],
        ]);

        $config = $this->_config;

        $di->set('config', function () use ($config, $di) {
            //setup
            return $config;
        });

        /**
         * Everything needed initialize phalconphp db
         */

        $di->set('mail', function () use ($config, $di) {
            //setup
            $mailer = new \Baka\Mail\Manager($config->email->toArray());

            return $mailer->createMessage();
        });

        /**
         * config queue by default Beanstalkd
         */
        $di->set('queue', function () use ($config) {
            //Connect to the queue
            $queue = new \Phalcon\Queue\Beanstalk\Extended([
                'host' => $config->beanstalk->host,
                'prefix' => $config->beanstalk->prefix,
            ]);

            return $queue;
        });

        $di->set('view', function () use ($config) {
            $view = new \Phalcon\Mvc\View\Simple();
            $view->setViewsDir(realpath(dirname(__FILE__)) . '/view/');

            $view->registerEngines([
                '.volt' => function ($view, $di) use ($config) {
                    $volt = new VoltEngine($view, $di);

                    $volt->setOptions([
                        'compiledPath' => realpath(dirname(__FILE__)) . '/view/cache/',
                        'compiledSeparator' => '_',
                        //since production is true or false, and we inverse the value to be false in production true in debug
                        'compileAlways' => true,
                    ]);

                    return $volt;
                },
                '.php' => function ($view, $di) {
                    return new \Phalcon\Mvc\View\Engine\Php($view, $di);
                },
            ]);

            return $view;
        });

        $di->set('modelsManager', function () {
            return new Phalcon\Mvc\Model\Manager();
        }, true);

        $di->set('modelsMetadata', function () {
            return new Phalcon\Mvc\Model\Metadata\Memory();
        }, true);

        $di->set('db', function () use ($config, $di) {
            //db connection
            $connection = new Phalcon\Db\Adapter\Pdo\Mysql([
                'host' => $config->database->host,
                'username' => $config->database->username,
                'password' => $config->database->password,
                'dbname' => $config->database->dbname,
                'charset' => 'utf8',
            ]);

            return $connection;
        });

        /**
         * Start the session the first time some component request the session service
         */
        $di->set('session', function () use ($config) {
            $memcache = new \Phalcon\Session\Adapter\Memcache([
                'host' => $config->memcache->host, // mandatory
                'post' => $config->memcache->port, // optional (standard: 11211)
                'lifetime' => 8600, // optional (standard: 8600)
                'prefix' => 'naruhodo', // optional (standard: [empty_string]), means memcache key is my-app_31231jkfsdfdsfds3
                'persistent' => false, // optional (standard: false)
            ]);

            //only start the session if its not already started
            if (!isset($_SESSION)) {
                $memcache->start();
            }

            return $memcache;
        });

        /*
         * Phalcon Annotations
         */
        $di->set('annotations', function () use ($config) {
            return new Memcached([
                'lifetime' => 8600,
                'host' => 'localhost',
                'port' => 11211,
                'weight' => 1,
                'prefix' => 'prefix.',
            ]);
        });

        return $di;
    }
}
