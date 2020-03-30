<?php

namespace Baka\Elasticsearch\Contracts;

use Phalcon\Queue\Beanstalk\Extended as BeanstalkExtended;
use Phalcon\Queue\Beanstalk\Job;
use Throwable;
use Baka\Elasticsearch\IndexBuilder;
use Phalcon\Di;

/**
 * This is the CLI to create you index based on this package.
 *
 */
trait IndexBuilderTaskTrait
{
    /**
     * Main function.
     *
     * @return void
     */
    public function mainAction(): void
    {
        echo 'This is the cli to index using elasticsearch' . PHP_EOL;
    }

    /**
     * Action Descriptor.
     *
     * Command: indices
     * Description: Create the elasticsearch index for a model.
     *
     * php cli/app.php elasticsearch createIndex index_name 4 (model relationship length)
     *
     * @param string $model
     * @param int $maxDepth
     * @param int $nestedLimit
     *
     * @return void
     */
    public function createIndexAction(array $params): void
    {
        //for participant u need 500 depth
        list($model, $maxDepth, $nestedLimit) = $params + ['', 3, 75];

        if (is_object($model)) {
            // Get elasticsearch class handler instance
            $elasticsearch = new IndexBuilder();

            $elasticsearch->createIndices($model, $maxDepth, $nestedLimit);
        }
    }

    /**
     * Action Descriptor.
     *
     * Command: index
     * Description: Create the elasticsearch index and insert all the model data
     *
     * php cli/app.php elasticsearch index modelName 0 1
     *
     * @param string $model
     * @param int $maxDepth
     *
     * @return void
     */
    public function indexAction($params): void
    {
        list($model, $maxDepth) = $params + ['', 3];

        if (is_object($model)) {
            // Get model's records
            $records = $model::find('is_deleted = 0');
            // Get elasticsearch class handler instance
            $elasticsearch = new IndexBuilder();

            foreach ($records as $record) {
                $elasticsearch->indexDocument($record, $maxDepth);
            }
        }
    }

    /**
      * Elastic Search insert Queue.
      *
      *  php cli/app.php elasticsearch queue queueName
      *
      * @return void
      */
    public function queueAction(array $queueName): void
    {
        try {
            // Check that a queue name has been provided
            if (empty($queueName)) {
                throw new Throwable('You have to define a queue name.');
            }

            // Start the queue
            $queue = new BeanstalkExtended([
                'host' => $this->config->beanstalk->host,
                'prefix' => $this->config->beanstalk->prefix,
            ]);

            // Variables needed by the anonymous function
            $config = $this->config;
            $di = Di::getDefault();

            //call queue tube
            $queue->addWorker($queueName[0], function (Job $job) use ($di, $config) {
                try {
                    // Get the job
                    $record = $job->getBody();

                    $model = $record['model'];
                    $id = $record['id'];
                    $maxDepth = isset($record['maxDepth']) ? $record['maxDepth'] : 1;

                    if (!class_exists($model)) {
                        $this->log->error('Queue Elastic class doesn\'t exit ' . $model);
                        return;
                    }

                    $this->log->info('Processing ' . $model . ' Id: ' . $id);
                    // Get model's records
                    if ($record = $model::findFirst($id)) {
                        // Get elasticsearch class handler instance
                        $elasticsearch = new IndexBuilder();

                        //insert into elastic
                        $elasticsearch->indexDocument($record, $maxDepth);
                    }
                } catch (Throwable $e) {
                    echo $e->getTraceAsString();
                    $this->log->error($e->getTraceAsString());
                }

                // It's very important to send the right exit code!
                exit(0);
            });

            // Start processing queues
            $queue->doWork();
        } catch (Throwable $e) {
            $this->log->error($e->getMessage());
        }
    }
}
