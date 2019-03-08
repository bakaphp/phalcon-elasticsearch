<?php


class Document extends Baka\Elasticsearch\Indices
{
    public $id;
    private $entity;

    public function setEntity(Entity $entity)
    {
        $this->entity = $entity;
        $this->setId($entity->getId());
    }

    /**
     * Index data
     *
     * @return stdClass
     */
    public function data(): stdClass
    {
        $object = new stdClass();

        $object->id = $this->entity->getId();
        $object->description = $this->entity->getDescription();
        $object->date = $this->entity->getDate();
        $object->money = $this->entity->getMoney();
        $object->anotherMoney = $this->entity->getAnotherMoney();
        $object->photos = $this->entity->getPhotos();

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
