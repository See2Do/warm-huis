<?php

namespace One\CheckJeHuis\Calculator;

use One\CheckJeHuis\Entity\ConfigCategory;
use One\CheckJeHuis\Entity\House;
use One\CheckJeHuis\Entity\Renewable;

class CalculatorView
{
    /**
     * @var House
     */
    protected $house;

    /**
     * @var CurrentEnergyCalculator
     */
    protected $current;

    /**
     * @var UpgradeEnergyCalculator
     */
    protected $upgrade;

    /**
     * @var BuildCostCalculator
     */
    protected $buildCosts;

    /**
     * @var SubsidyCalculator
     */
    protected $subsidies;

    /**
     * @var Parameters
     */
    protected $parameters;

    /**
     * @param House $house
     * @param CurrentEnergyCalculator $current
     * @param UpgradeEnergyCalculator $upgrade
     * @param BuildCostCalculator $buildCosts
     * @param SubsidyCalculator $subsidies
     * @param Parameters $parameters
     */
    public function __construct(
        House $house,
        CurrentEnergyCalculator $current,
        UpgradeEnergyCalculator $upgrade,
        BuildCostCalculator $buildCosts,
        SubsidyCalculator $subsidies,
        Parameters $parameters
    ) {
        $this->house = $house;
        $this->current = $current;
        $this->upgrade = $upgrade;
        $this->buildCosts = $buildCosts;
        $this->subsidies = $subsidies;
        $this->parameters = $parameters;
    }

    /**
     * @param bool $current
     * @return float|int
     */
    public function getAvgScore($current = false)
    {
        $state = $current ? $this->current->getState(): $this->upgrade->getState();

        // if we base our average on electricity, we need to
        // deduct the non-heating electricity costs first
        if ($state->isHeatingElectric() || $state->forceElectricity() || !$state->getGasOrOil()) {
            $base = $state->getElectricity();
            $base -= $state->getNonHeatingElectricity();
        } else {
            $base = $state->getGasOrOil();
            // add any extra electric costs
            $base += ($state->getElectricity() - $state->getNonHeatingElectricity());
        }

        if ($base < 0) {
            return 0;
        }

        return round($base / $this->house->getSurfaceLivingArea());
    }

    /**
     * @param bool $current
     * @return array
     */
    public function getAvgScoreConfig($current = false)
    {
        $score = $this->getAvgScore($current);

        $maxVal = 150;
        $centerVal = 70;
        $minVal = 30;

        $centerGutter = 0;

        $minLeft = 18;
        $maxLeft = 51;
        $center = 55.5;
        $minRight = 59;
        $maxRight = 94;

        $value = $score;
        if ($value > $maxVal) {
            $value = $maxVal;
        }
        if ($value < $minVal) {
            $value = $minVal;
        }
        if ($value < ($centerVal + $centerGutter) && $value > ($centerVal - $centerGutter)) {
            $value = $centerVal;
        }

        // calculate the position

        $position = $center;
        $isCentered = true;
        $align = 'right';

        $points = null;
        $maxPoints = null;

        if ($value > $centerVal) {
            $isCentered = false;
            $maxPoints = $maxVal - $centerVal;
            $points = $value - $centerVal;

            $position = $maxLeft - round(($maxLeft - $minLeft) * ($points / $maxPoints));
        }
        if ($value < $centerVal) {
            $isCentered = false;
            $align = 'left';
            $maxPoints = $centerVal - $minVal;
            $points = $centerVal - $value;

            $position = $minRight + round(($maxRight - $minRight) * ($points / $maxPoints));
        }

        return array(
            'score'     => $score,
            'position'  => $position,
            'centered'  => $isCentered,
            'align'     => $align,
            'label'     => $this->getAvgScoreLabel($current),
        );
    }

    /**
     * @param bool $current
     * @return string
     */
    public function getAvgScoreLabel($current = false)
    {
        $score = $this->getAvgScore($current);
        if ($score <= 30) {
            return 'Zo wil ik wonen!';
        }
        if ($score <= 70) {
            return 'ik ben goed bezig, wat kan ik nog doen?';
        } else {
            return 'Mijn huis verliest teveel warmte';
        }
    }

    /**
     * @return float|int
     */
    public function getPriceDiff()
    {
        $total = 0;

        $from = $this->current->getState();
        $to = $this->upgrade->getState();

        if ($from->getOil()) {
            $total += ($from->getOil() - $to->getOil()) * $this->parameters->getPriceOil();
        } else {
            $total += ($from->getGas() - $to->getGas()) * $this->parameters->getPriceGas();
        }

        $total += ($from->getElectricity() - $to->getElectricity()) * $this->parameters->getPriceElec();

        return $total;
    }

    /**
     * @return float
     */
    public function getEnergyDiff()
    {
        $from = $this->current->getState();
        $to = $this->upgrade->getState();

        return ($from->getGasOrOil() - $to->getGasOrOil()) + ($from->getElectricity() - $to->getElectricity());
    }

    /***
     * @param string|ConfigCategory $cat
     * @return float
     */
    public function getEnergyDiffForCategory($cat)
    {
        if ($cat instanceof ConfigCategory) {
            $cat = $cat->getSlug();
        }

        $total = 0;

        foreach ($this->upgrade->getDiffs() as $diff) {
            if ($diff->getSubject()->getSlug() === $cat) {
                $total += $diff->getGasOrOil() + $diff->getElec();
            }
        }

        return $total;
    }

    /***
     * @param string|ConfigCategory $cat
     * @return float
     */
    public function getPriceDiffForCategory($cat)
    {
        if ($cat instanceof ConfigCategory) {
            $cat = $cat->getSlug();
        }

        $total = 0;

        foreach ($this->upgrade->getDiffs() as $diff) {
            if ($diff->getSubject()->getSlug() === $cat) {
                if ($diff->getOil()) {
                    $total += $diff->getOil() * $this->parameters->getPriceOil();
                } else {
                    $total += $diff->getGas() * $this->parameters->getPriceGas();
                }
                $total += ($diff->getElec() * $this->parameters->getPriceElec());

                if ($cat === ConfigCategory::CAT_ROOF) {
                    $total += $this->getPriceDiffForCategory(ConfigCategory::CAT_WIND_ROOF);
                }
            }
        }

        return $total;
    }

    /***
     * @return float
     */
    public function getCo2Diff()
    {
        return $this->upgrade->getState()->getCo2();
    }

    /***
     * @param string|ConfigCategory $cat
     * @return float
     */
    public function getCo2DiffForCategory($cat)
    {
        return $this->getEnergyDiffForCategory($cat) * $this->parameters->getCo2PerKwh();
    }

    public function getEnergyDiffForRenewables()
    {
        $total = 0;

        foreach ($this->upgrade->getDiffs() as $diff) {
            if ($diff->getSubject() instanceof Renewable) {
                $total += $diff->getGasOrOil() + $diff->getElec();
            }
        }

        return $total;
    }

    public function getEnergyDiffForRenewable($renewable)
    {
        if ($renewable instanceof Renewable) {
            $renewable = $renewable->getSlug();
        }

        $total = 0;

        foreach ($this->upgrade->getDiffs() as $diff) {
            if ($diff->getSubject() instanceof Renewable && $diff->getSubject()->getSlug() === $renewable) {
                $total += $diff->getGasOrOil() + $diff->getElec();
            }
        }

        return $total;
    }

    public function getPriceDiffForRenewables()
    {
        $total = 0;

        foreach ($this->upgrade->getDiffs() as $diff) {
            if ($diff->getSubject() instanceof Renewable) {
                $price = $this->parameters->getPriceGas();
                if ($diff->getOil()) {
                    $price = $this->parameters->getPriceOil();
                }
                $total += ($diff->getGasOrOil() * $price)
                    + ($diff->getElec() * $this->parameters->getPriceElec());
            }
        }

        return $total;
    }

    public function getPriceDiffForRenewable($renewable)
    {
        if ($renewable instanceof Renewable) {
            $renewable = $renewable->getSlug();
        }

        $total = 0;

        foreach ($this->upgrade->getDiffs() as $diff) {
            if ($diff->getSubject() instanceof Renewable && $diff->getSubject()->getSlug() === $renewable) {
                $price = $this->parameters->getPriceGas();
                if ($diff->getOil()) {
                    $price = $this->parameters->getPriceOil();
                }
                $total += ($diff->getGasOrOil() * $price)
                    + ($diff->getElec() * $this->parameters->getPriceElec());
            }
        }

        return $total;
    }

    public function getCo2DiffForRenewable($renewable)
    {
        if ($renewable instanceof Renewable) {
            $renewable = $renewable->getSlug();
        }

        $total = 0;

        foreach ($this->upgrade->getDiffs() as $diff) {
            if ($diff->getSubject() instanceof Renewable && $diff->getSubject()->getSlug() === $renewable) {
                $total += $diff->getCo2();
            }
        }

        return $total;
    }

    public function getBuildCostTotal()
    {
        return $this->buildCosts->getTotalPrice();
    }

    public function getEnergyLoanTotal()
    {
        $duration = $this->parameters->getMortgageDuration();
        $interestRate =$this->parameters->getMortgageInterestRate();
        $totalBuildCost = $this->buildCosts->getTotalPrice();
        if ($this->buildCosts->getTotalPrice() <= $this->parameters->getEnergyLoanThreshold()) {
            $duration = $this->parameters->getEnergyLoanDuration();
            $interestRate = $this->parameters->getEnergyLoanInterestRate();
        }
        $duration *= 12;
        $interestRate = pow(1 + $interestRate / 100, (1 / 12)) - 1;

        return ($totalBuildCost * pow(1 + $interestRate, $duration)) / 1 / ((pow(1 + $interestRate, $duration) - 1) / $interestRate);
    }

    public function getBuildCostTotalForCategory($cat)
    {
        if ($cat instanceof ConfigCategory) {
            $cat = $cat->getSlug();
        }

        $total = 0;

        foreach ($this->buildCosts->getCategories() as $category => $price) {
            if ($category === $cat) {
                $total += $price;
            }

            if ($cat === ConfigCategory::CAT_ROOF && $category === $cat) {
                $total += $price;
            }
        }

        return $total;
    }

    public function getBuildCostForRenewable($renewable)
    {
        if ($renewable instanceof Renewable) {
            $renewable = $renewable->getSlug();
        }

        $total = 0;

        foreach ($this->buildCosts->getRenewables() as $slug => $price) {
            if ($renewable === $slug) {
                $total += $price;
            }
        }

        return $total;
    }

    public function getSubsidyTotal()
    {
        return $this->subsidies->getTotalPrice();
    }

    public function getSubsidyTotalForCategory($cat)
    {
        if ($cat instanceof ConfigCategory) {
            $cat = $cat->getSlug();
        }

        $total = 0;

        foreach ($this->subsidies->getCategories() as $category => $subsidies) {
            if ($category === $cat) {
                $total += array_sum($subsidies);
            }

            if ($cat === ConfigCategory::CAT_ROOF && $category === ConfigCategory::CAT_WIND_ROOF) {
                $total += array_sum($subsidies);
            }
        }

        return $total;
    }

    public function getSubsidiesForCategory($cat)
    {
        if ($cat instanceof ConfigCategory) {
            $cat = $cat->getSlug();
        }

        foreach ($this->subsidies->getCategories() as $category => $subsidies) {
            if ($category === $cat) {
                foreach ($subsidies as $id => $price) {
                    yield $id => $price;
                }
            }
        }
    }

    public function getSubsidiesForRenewable($renewable)
    {
        if ($renewable instanceof Renewable) {
            $renewable = $renewable->getSlug();
        }

        $result = [];

        foreach ($this->subsidies->getRenewables() as $category => $subsidies) {
            if ($category === $renewable) {
                foreach ($subsidies as $id => $price) {
                    $result[$id] = $price;
                }
            }
        }

        return $result;
    }

    public function getSubsidyTotalForRenewable($renewable)
    {
        return array_sum($this->getSubsidiesForRenewable($renewable));
    }

    public function getSubsidies()
    {
        $this->subsidies->getCategories()[ConfigCategory::CAT_WIND_ROOF];
    }

    /**
     * @return CurrentEnergyCalculator
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * @return UpgradeEnergyCalculator
     */
    public function getUpgrade()
    {
        return $this->upgrade;
    }
}
