<?php

namespace Test\Model;

use Baka\Database\Model;
use Baka\Database\Contracts\CustomFields\CustomFieldsTrait;

class Leads extends Model
{
    use CustomFieldsTrait;
    
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
