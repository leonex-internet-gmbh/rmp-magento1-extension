<?php

/**
 * Adminhtml system config source: Risk product mode
 *
 * @category    Leonex
 * @package     Leonex_RiskManagementPlatform
 * @author      Florian Seeger <Florian.Seeger@leonex.de>
 */
class Leonex_RiskManagementPlatform_Model_Adminhtml_System_Config_Source_CheckingTime
{
    const CHECKING_TIME_PRE = 'pre';
    const CHECKING_TIME_POST = 'post';

    /**
     * Get Times of Checking as option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::CHECKING_TIME_PRE, 'label' => Mage::helper('leonex_rmp')->__('Before payment method selection')),
            array('value' => self::CHECKING_TIME_POST, 'label' => Mage::helper('leonex_rmp')->__('After payment method selection')),
        );
    }
}