<?php

namespace Baka\Elasticsearch;

use Phalcon\Cli\Task;
use Phalcon\Queue\Beanstalk\Extended as BeanstalkExtended;
use Phalcon\Queue\Beanstalk\Job;

/**
 * This is the CLI to create you index based on this package
 *
 */
class IndexBuilderTask extends Task
{
    /**
     * Main function
     *
     * @return void
     */
    public function mainAction(): void
    {
        echo 'This is the cli to index using elasticsearch' . PHP_EOL;
    }

    /**
     * Action Descriptor
     *
     * Command: indices
     * Description: Create the elasticsearch index for a model.
     *
     * php cli/app.php elasticsearch createIndex indexname 4 (model relationship lenght)
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

        if (!empty($model)) {
            // Get elasticsearch class handler instance
            $elasticsearch = new IndexBuilder();

            $elasticsearch->createIndices($model, $maxDepth, $nestedLimit);
        }
    }

    /**
     * Action Descriptor
     *
     * Command: insert
     * Description: Create the elasticsearch index all info for a model.
     *
     * php cli/app.php elasticsearch insert modelName 0 1
     *
     * @param string $model
     * @param int $maxDepth
     *
     * @return void
     */
    public function insertAction($params): void
    {
        list($model, $maxDepth) = $params + ['', 3];

        if (!empty($model)) {
            // Get model
            $model = $this->config->namespace->models . '\\' . $model;
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
      * Elastic Search insert Queue
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

            // Variables needed by the annonymous function
            $config = $this->config;
            $di = \Phalcon\DI\FactoryDefault::getDefault();

            //call queue tube
            $queue->addWorker($queueName[0], function (Job $job) use ($di, $config) {
                try {
                    // Get the job
                    $record = $job->getBody();

                    $model = $record['model'];
                    $id = $record['id'];
                    $maxDepth = isset($record['maxDepth']) ? $record['maxDepth'] : 1;

                    // Get model
                    $model = $this->config->namespace->models . '\\' . $model;

                    if (!class_exists($model)) {
                        $this->log->error('Queue Elastic class doesnt exit ' . $model);
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
                    $this->log->error($e->getMessage());
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
