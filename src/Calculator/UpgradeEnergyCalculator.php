<?php

namespace One\CheckJeHuis\Calculator;

use One\CheckJeHuis\Entity\Config;
use One\CheckJeHuis\Entity\ConfigCategory;
use One\CheckJeHuis\Entity\Renewable;

/**
 * Calculates the different between the current configs and the upgraded configs
 * Also calculates the build costs and the subsidies
 *
 * @package One\CheckJeHuis
 */
class UpgradeEnergyCalculator extends EnergyCalculator
{
    /**
     * @var array|Diff[]
     */
    protected $diffs = array();

    /**
     * @param State $state
     */
    public function calculate(State $state)
    {
        if ($this->calculated) {
            return;
        }

        $this->state = $state;

        // when we have gas heating in the current situation
        // the electricity is all nonHeating
        // except if we have previously switched to a heatpump
        if (!$state->forceElectricity() && !$this->house->hasElectricHeating() && !$state->getOil()) {
            $state->setNonHeatingElectricity($state->getElectricity());
        }

        $configs = [];

        foreach ($this->house->getConfigs() as $defaultConfig) {
            // only allow 1 type of heating, elec or gas
            if ($this->house->hasElectricHeating() && $defaultConfig->isCategory(ConfigCategory::CAT_HEATING)) {
                continue;
            }
            if (!$this->house->hasElectricHeating() && $defaultConfig->isCategory(ConfigCategory::CAT_HEATING_ELEC)) {
                continue;
            }

            $chosenConfig = $this->house->getUpgradeConfig($defaultConfig->getCategory());
            if ($chosenConfig && $defaultConfig !== $chosenConfig) {
                $configs[$defaultConfig->getCategory()->getSlug()][100] = [ $defaultConfig, $chosenConfig ];
            }
        }

        $configs = $this->splitTheRoof($configs, $this->house->getExtraConfigRoof(true), $this->house->getExtraUpgradeRoof());

        $this->diff($configs, $this->house->getUpgradeRenewables());

        $this->calculated = true;
    }

    public function transform(Config $from, Config $to = null, $percent = 100, $forceElectricity = false)
    {
        $diff = new Diff($from->getCategory());
        $this->diffs[] = $diff;

        $diff->start($this->state);
        parent::transform($from, $to, $percent, $forceElectricity);
        $diff->end($this->state);
    }

    public function subtractRenewable(Renewable $renewable)
    {
        $diff = new Diff($renewable);
        $this->diffs[] = $diff;

        $diff->start($this->state);
        parent::subtractRenewable($renewable);
        $diff->end($this->state);
    }

    /**
     * @return array|Diff[]
     */
    public function getDiffs()
    {
        return $this->diffs;
    }
}
