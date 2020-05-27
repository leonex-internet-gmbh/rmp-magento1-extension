<?php

/**
 * RMP Connector
 *
 * @category    Leonex
 * @package     Leonex_RiskManagementPlfatform
 * @author      Floriam Seeger <Florian.Seeger@leonex.de>
 */
class Leonex_RiskManagementPlatform_Helper_Connector extends Mage_Core_Helper_Abstract
{

    /** Dt.: Kreditentscheidung */
    const JUSTIFIABLE_INTEREST_LOAN_DECISION = 1;

    /** Dt.: Geschäftsanbahnung */
    const JUSTIFIABLE_INTEREST_BUSINESS_INITIATION = 3;

    /** Dt.: Forderung */
    const JUSTIFIABLE_INTEREST_CLAIM = 4;

    /** Dt.: Versicherungsvertrag */
    const JUSTIFIABLE_INTEREST_INSURANCE_CONTRACT = 5;

    /** Dt.: Beteiligungsverhältnisse */
    const JUSTIFIABLE_INTEREST_SHARING_STATUS = 6;

    /** Dt.: Überfällige Forderung */
    const JUSTIFIABLE_INTEREST_OVERDUE_CLAIM = 7;

    /** Dt.: Vollstreckungsauskunft */
    const JUSTIFIABLE_INTEREST_ENFORCEMENT_CLAIM = 8;

    /** Dt.: Konditionenanfrage (BDSG, §28a Abs. 2 Satz 4) (nur Finanzdienstleistungssektor) */
    const JUSTIFIABLE_INTEREST_TERMS_REQUEST = 9;

    protected $cachedResponse = null;

    /**
     * Check if payment is available
     *
     * @param Varien_Event_Observer $observer
     *
     * @return bool
     * @throws Exception
     */
    public function checkPaymentPre(Varien_Event_Observer $observer)
    {
        /** @var Varien_Event $event */
        $event = $observer->getEvent();

        /** @var Leonex_RiskManagementPlatform_Model_Quote_Quote $quote */
        $quote = Mage::getModel('leonex_rmp/quote_quote', $event->getQuote());

        if (!$quote->isAddressProvided()) {
            return $event->getResult()->isAvailable;
        }

        $response = $this->cachedResponse;

        if (!$response) {
            $content = $quote->getNormalizedQuote();

            /** @var Leonex_RiskManagementPlatform_Model_Component_Api $api */
            $api = Mage::getModel('leonex_rmp/component_api', array(
                    'api_url' => $this->_getApiUrl(),
                    'api_key' => $this->_getApiKey()
            ));

            /** @var Leonex_RiskManagementPlatform_Model_Component_Response $response */
            $response = $api->post($content);
            $this->cachedResponse = $response;
        }



        if ($response) {
            return $response->filterPayment($event->getMethodInstance()->getCode());
        }

        return $event->getResult()->isAvailable;
    }

    /**
     * Check if it's necessary to check payments.
     * Conditions:
     *
     * @param Varien_Event_Observer $observer
     *
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    public function verifyInterest(Varien_Event_Observer $observer, $timeOfChecking)
    {
        /** @var Leonex_RiskManagementPlatform_Helper_Data $helper */
        $helper = Mage::helper('leonex_rmp');
        $event = $observer->getEvent();

        if (!Mage::app()->getStore()->isAdmin() && $helper->isActive()) {
            if ($helper->getTimeOfChecking() == $timeOfChecking) {
                if ($event->getQuote() instanceof Mage_Sales_Model_Quote) {
                    if ($event->getResult()->isAvailable) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get Api-Url from plugin config
     *
     * @return mixed
     */
    protected function _getApiUrl()
    {
        return Mage::helper('leonex_rmp')->getApiUrl();
    }

    /**
     * Get Api-Key from plugin config
     *
     * @return mixed
     */
    protected function _getApiKey()
    {
        return Mage::helper('leonex_rmp')->getApiKey();
    }

}
