<?php

namespace One\CheckJeHuis\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class ConfigTransformation
{
    protected $id;

    protected $fromConfig;

    protected $toConfig;

    /**
     * @Assert\Type(type="numeric", message = "dit is geen geldige waarde")
     */
    protected $value;

    protected $unit;

    protected $inverse;

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
     * @return Config
     */
    public function getFromConfig()
    {
        return $this->fromConfig;
    }

    /**
     * @param mixed $fromChoice
     * @return $this
     */
    public function setFromConfig($fromChoice)
    {
        $this->fromConfig = $fromChoice;
        return $this;
    }

    /**
     * @return Config
     */
    public function getToConfig()
    {
        return $this->toConfig;
    }

    /**
     * @param mixed $toChoice
     * @return $this
     */
    public function setToConfig($toChoice)
    {
        $this->toConfig = $toChoice;
        return $this;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     * @return $this
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isInverse()
    {
        return $this->inverse;
    }

    /**
     * @param boolean $inverse
     * @return $this
     */
    public function setInverse($inverse)
    {
        $this->inverse = $inverse;
        return $this;
    }

    public function getDiff($base, &$formula = null)
    {
        switch (strtolower($this->unit)) {
            case '%':
                $relevantPart = $base * ($this->getFromConfig()->getCategory()->getPercent() / 100);
                $percent = $this->getValue() / 100;
                $diff = $relevantPart * abs($percent);
                if ($percent < 0) {
                    $diff = $diff * -1;
                }
                $formula = sprintf('%s * %s%% * %s%%', $base, $this->getFromConfig()->getCategory()->getPercent(), $this->getValue());
                break;
            case 'kwh/jaar':
            case 'kwh / jaar':
            case 'kwh':
                $diff = $this->getValue();
                $formula = sprintf('+ %s %s', $this->getValue(), $this->unit);
                break;
            default:
                $diff = 0;
        }

        if ($this->inverse) {
            return 0 - $diff;
        } else {
            return $diff;
        }
    }

    public function transform($energy, $base)
    {
        $diff = $this->getDiff($base);

        return $energy - $diff;
    }
}
