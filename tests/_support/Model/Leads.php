<?php

namespace Test\Model;

use Baka\Database\Model;
use Baka\Database\Contracts\CustomFields\CustomFieldsTrait;
use Baka\Elasticsearch\Contracts\ElasticModelTrait;

class Leads extends Model
{
    use CustomFieldsTrait;
    use ElasticModelTrait;
    
    public function initialize()
    {
        $this->hasMany(
            'id',
            LeadsSettings::class,
            'leads_id',
            [
                'alias' => 'leads_settings'
            ]
        );
    }

    /**
     * Specify the table.
     *
     * @return void
     */
    public function getSource()
    {
        return 'leads';
    }
}
