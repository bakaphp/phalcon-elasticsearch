<?php

class Vehicle
{
    public $id;
    public $date;
    public $name;

    public function setId(int $id): void
    {
        $this->id = $id;
    }
    public function setDate(string $date): void
    {
        $this->date = $date;
    }
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    public function getId(): ?int
    {
        return $this->id = $id;
    }
    public function getDate(): ?string
    {
        return $this->date = $date;
    }
    public function getName(): ?string
    {
        return $this->name = $name;
    }
}
