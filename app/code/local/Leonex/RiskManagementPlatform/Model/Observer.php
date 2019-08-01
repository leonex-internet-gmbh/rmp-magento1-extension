<?php

/**
 * RMP observer
 *
 * @category    Leonex
 * @package     Leonex_CreditRatingCeg
 * @author      Florian Seeger <Florian.Seeger@leonex.de>
 */
class Leonex_RiskManagementPlatform_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Event: payment_method_is_available
     * Check if Payment is available
     *
     * @param Varien_Event_Observer $observer
     */
    public function isAvailable($observer)
    {
        /** @var Leonex_RiskManagementPlatform_Helper_Connector $connector */
        $connector = Mage::helper('leonex_rmp/connector');
        $checkingTime = Leonex_RiskManagementPlatform_Model_Adminhtml_System_Config_Source_CheckingTime::CHECKING_TIME_PRE;
        if($connector->verifyInterest($observer, $checkingTime) || (!is_null($observer->getQuote()) && $connector->verifyInterest($observer, $observer->getQuote()->getData('checking_time')))){
            $event = $observer->getEvent();
            $result = $event->getResult();
            $result->isAvailable = $connector->checkPaymentPre($observer);
        }
    }

    /**
     * Event: payment_method_is_available
     * Check if Payment is available
     *
     * @param Varien_Event_Observer $observer
     */
    public function isAvailableAfter($observer)
    {
        $event = $observer->getEvent();
        $payment = $event->getPayment();
        $payment->getQuote()->setData('checking_time', Leonex_RiskManagementPlatform_Model_Adminhtml_System_Config_Source_CheckingTime::CHECKING_TIME_POST);
    }
}