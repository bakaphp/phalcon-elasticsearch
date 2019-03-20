<?php

class Photo
{
    public $name;
    public $url;
    public $vehicles;

    public function setName(string $name): void
    {
        $this->name = $name;
    }
    public function setUrl(string $url):void
    {
        $this->url = $url;
    }
    public function addVehicle(Vehicle $vehicle):void
    {
        $this->vehicles[] = $vehicle;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
    public function getUrl(): ?url
    {
        return $this->url;
    }
    public function getVehicles(): ?array
    {
        return $this->vehicles;
    }
}
