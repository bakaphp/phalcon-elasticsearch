<?php


class Indices extends Baka\Elasticsearch\Indices
{
    /**
     * @ES\Property(type="integer")
     */
    public $id;

    /**
     * @ES\Property(type="string")
     */
    public $name;

    /**
     * @ES\Property(type="string")
     */
    public $description;

    /**
     * @ES\Property(type="date")
     */
    public $date;

    /**
     * @ES\Property(type="float")
     */
    public $money;

    /**
     * @ES\Property(type="long")
     */
    public $anotherMoney;

    public $photos = [];

    public function data(): stdClass
    {
        $object = new stdClass();
        $object->id = 1;
        $this->setId(1);

        $object->description = 'tetada';
        $object->date = '2018-01-01';
        $object->money = 10.1;
        $object->anotherMoney = 10.1;
        $object->photo = [
            'name' => 'test',
            'url' => 'http://mctekk.com',
            'vehicles' => [
                'id' => 2,
                'date' => '2018-01-02',
                'name' => 'wtf',
            ]
        ];

        return $object;
    }

    /**
     * Define the structure of thies index
     *
     * @return array
     */
    public function structure(): array
    {
        return [
                'id' => $this->integer,
                'name' => $this->text,
                'description' => $this->text,
                'date' => $this->dateNormal,
                'money' => $this->decimal,
                'anotherMoney' => $this->bigInt,
                'photos' => [
                    'name' => $this->text,
                    'url' => $this->text,
                    'vehicles' => [
                        'id' => $this->integer,
                        'date' => $this->dateNormal,
                        'name' => $this->text,
                    ]
                ]
            ];
    }
}
