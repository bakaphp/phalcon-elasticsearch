<?php

namespace Baka\Elasticsearch\Contracts;

use Baka\Database\Contracts\CustomFields\CustomFieldsTrait;

class ElasticModelTrait
{
    use CustomFieldsTrait;
    
    protected $elasticMaxDepth = 3;

    /**
     * Fields we want to have excluded from the audits
     *
     * @var array
     */
    public $auditExcludeFields = [
        'id',
        'created_at',
        'updated_at'
    ];

    /**
     * With this variable we tell elasticsearch to not analyze string fields in order to allow us
     * to perform wildcard matches.
     *
     * @var boolean
     */
    public $elasticSearchNotAnalyzed = true;

    /**
     * Prepare data to send to queue
     *
     * @return void
     */
    protected function jobData(): array
    {
        return [
            'model' => get_class($this),
            'id' => $this->getId(),
            'maxDepth' => $this->elasticMaxDepth
        ];
    }

}
