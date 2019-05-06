<?php

namespace Test\Model;

use Baka\Database\Model;
use Baka\Database\Contracts\CustomFields\CustomFieldsTableInterface;

class LeadsCustomFields extends Model implements CustomFieldsTableInterface
{
    
    /**
     * Specify the table.
     *
     * @return void
     */
    public function getSource()
    {
        return 'leads_custom_fields';
    }

    
    /**
     * Set the custom primary field id
     *
     * @param int $id
     */
    public function setCustomId($id)
    {
        $this->leads_id = $id;
    }
}
