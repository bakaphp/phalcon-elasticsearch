<?php

class Entity
{
    private $id;
    private $description;
    private $date;
    private $money;
    private $anotherMoney;
    private $photos;

    public function setId(int $id): void
    {
        $this->id = $id;
    }
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
    public function setDate(string $date): void
    {
        $this->date = $date;
    }
    public function setMoney(float $money): void
    {
        $this->money = $money;
    }
    public function setAnotherMoney(float $anotherMoney): void
    {
        $this->anotherMoney = $anotherMoney;
    }
    public function addPhoto(Photo $photo): void
    {
        $this->photos[] = $photo;
    }
    public function getId(): int
    {
        return $this->id;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function getDate(): ?string
    {
        return $this->date;
    }
    public function getMoney(): ?float
    {
        return $this->money;
    }
    public function getAnotherMoney(): ?float
    {
        return $this->anotherMoney;
    }
    public function getPhotos(): ?array
    {
        return $this->photos;
    }
}
