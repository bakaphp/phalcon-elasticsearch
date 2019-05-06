<?php

namespace Baka\Elasticsearch;

use Baka\Database\Contracts\CustomFields\CustomFieldsTrait;

class Model extends \Baka\Database\Model
{
    use CustomFieldsTrait;
    
    protected $elasticMaxDepth = 3;

    /**
     * Fields we want to have excluded from the audits
     *
     * @var array
     */
    public $auditExcludeFields = ['id', 'created_at', 'updated_at'];

    /**
     * With this variable we tell elasticsearch to not analyze string fields in order to allow us
     * to perform wildcard matches.
     *
     * @var boolean
     */
    public $elasticSearchNotAnalyzed = true;

    /**
     * Send the corrent objet to elastic to update or insert
     *
     * @return void
     */
    protected function sendToElastic(): bool
    {
        $reflection = new \ReflectionClass($this);
        $model = $reflection->getShortName();
        $id = $this->getId();

        $elasticMaxDepth = $this->elasticMaxDepth;

        return (bool) $this->di->getQueue()->putInTube($model, [
            'model' => $model,
            'id' => $id,
            'maxDepth' => $elasticMaxDepth
        ]);
    }

    /**
     * Call this after save and send to elastic
     *
     * @return void
     */
    public function afterSave()
    {
        parent::afterSave();

        $this->sendToElastic();
    }
}
