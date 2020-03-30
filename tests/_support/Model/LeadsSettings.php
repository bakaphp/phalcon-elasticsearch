<?php

namespace Test\Model;

use Baka\Database\Model;

class LeadsSettings extends Model
{

    /**
     * Specify the table.
     *
     * @return void
     */
    public function getSource()
    {
        return 'leads_settings';
    }
}
