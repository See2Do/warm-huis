<?php

namespace One\CheckJeHuis\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class DefaultSurface
{
    protected $id;

    protected $type;

    protected $size;

    /**
     * @Assert\Type(type="numeric", message = "dit is geen geldige waarde")
     */
    protected $livingArea;

    /**
     * @Assert\Type(type="numeric", message = "dit is geen geldige waarde")
     */
    protected $floor;

    /**
     * @Assert\Type(type="numeric", message = "dit is geen geldige waarde")
     */
    protected $facade;

    /**
     * @Assert\Type(type="numeric", message = "dit is geen geldige waarde")
     */
    protected $window;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param string $size
     * @return $this
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLivingArea()
    {
        return $this->livingArea;
    }

    /**
     * @param float $livingArea
     * @return $this
     */
    public function setLivingArea($livingArea)
    {
        $this->livingArea = $livingArea;
        return $this;
    }

    /**
     * @return float
     */
    public function getFacade()
    {
        return $this->facade;
    }

    /**
     * @param float $facade
     * @return $this
     */
    public function setFacade($facade)
    {
        $this->facade = $facade;
        return $this;
    }

    /**
     * @return float
     */
    public function getFloor()
    {
        return $this->floor;
    }

    /**
     * @param float $floor
     * @return $this
     */
    public function setFloor($floor)
    {
        $this->floor = $floor;
        return $this;
    }

    /**
     * @return float
     */
    public function getWindow()
    {
        return $this->window;
    }

    /**
     * @param string $window
     * @return $this
     */
    public function setWindow($window)
    {
        $this->window = $window;
        return $this;
    }
}
