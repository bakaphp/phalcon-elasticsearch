<?php

namespace Baka\Elasticsearch;

use stdClass;

abstract class Indices extends \Baka\Database\Model
{
    protected $text = 'text';
    protected $integer = 'integer';
    protected $bigInt = 'long';
    protected $dateNormal = ['date', 'yyyy-MM-dd'];
    protected $dateTime = ['date', 'yyyy-MM-dd HH:mm:ss'];
    protected $decimal = 'float';

    /**
     * Set the Id.
     *
     * @param integer $id
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Define de structure for this index in elastic search.
     *
     * @return array
     */
    abstract public function structure() : array;

    /**
     * Set the data of the current index.
     *
     * @return stdClass
     */
    abstract public function data() : stdClass;

    /**
     * Given the object of the class we return a array document.
     *
     * @return array
     */
    public function document() : array
    {
        return (array) $this->data();
    }
}
