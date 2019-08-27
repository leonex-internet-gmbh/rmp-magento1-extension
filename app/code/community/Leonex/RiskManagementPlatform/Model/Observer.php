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

        $result = $observer->getResult();
        if (!$result->isAvailable) {
            return;
        }

        /** @var Leonex_RiskManagementPlatform_Helper_Data $helper */
        $helper = Mage::helper('leonex_rmp');
        $allowedPaymentMethods = $helper->getAllowedPaymentMethods();
        /** @var Mage_Payment_Model_Method_Abstract $paymentMethod */
        $paymentMethod = $observer->getData('method_instance');
        $paymentMethodCode = $paymentMethod->getCode();
        if (!emtpy($allowedPaymentMethods) && !in_array($paymentMethodCode, $allowedPaymentMethods)) {
            // do not aks for this payment method
            return;
        }

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getQuote();
        // do not ask for companies
        if ($quote && $quote->getBillingAddress() && $quote->getBillingAddress()->getCompany()) {
            return;
        }

        if ($connector->verifyInterest($observer, $checkingTime)
            || ($observer->getQuote() !== null)
            && $connector->verifyInterest($observer, $observer->getQuote()->getData('checking_time'))) {
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
