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

    /**
     * Check if payment is available
     *
     * @param Varien_Event_Observer $observer
     *
     * @return bool
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

        $response = false;

        if ($this->_justifyInterest($quote)) {
            $content = $quote->getNormalizedQuote();

            /** @var Leonex_RiskManagementPlatform_Model_Component_Api $api */
            $api = Mage::getModel('leonex_rmp/component_api', array(
                    'api_url' => $this->_getApiUrl(), 'api_key' => $this->_getApiKey()
                ));

            /** @var Leonex_RiskManagementPlatform_Model_Component_Response $response */
            $response = $api->post($content);

            if ($this->useCaching()) {
                $response->setHash($quote);
                $this->_storeResponse($response);
            }
        }

        if ($this->useCaching()) {
            $response = $this->_loadResponse($quote->getQuoteHash());
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

    /**
     * Check if the basket and customer data has any changes.
     * If not then load the old response from the session.
     *
     * @param Leonex_RiskManagementPlatform_Model_Quote_Quote $quote
     *
     * @return bool
     */
    protected function _justifyInterest(Leonex_RiskManagementPlatform_Model_Quote_Quote $quote)
    {
        if (!$this->useCaching()) {
            return true;
        }

        return !(bool)$this->_loadResponse($quote->getQuoteHash());
    }

    /**
     * Store the response from the api-call.
     *
     * @param $response
     */
    protected function _storeResponse(Leonex_RiskManagementPlatform_Model_Component_Response $response)
    {
        $cache = $this->_getCache();
        $cache->save($response->getCleanResponse(), $response->getHash(), array(), 60 * 60 * 2);
    }

    /**
     * Get the response from the session and create a new Response object.
     *
     * @param $hash
     *
     * @return bool|Leonex_RiskManagementPlatform_Model_Component_Response
     */
    protected function _loadResponse($hash)
    {
        $cache = $this->_getCache();
        $response = $cache->load($hash);

        return $response ? Mage::getModel('leonex_rmp/component_response', $response) : false;
    }

    /**
     * Get Cache-Object
     *
     * @return Zend_Cache_Core
     */
    protected function _getCache()
    {
        return Mage::app()->getCache();
    }

    protected function useCaching()
    {
        return Mage::helper('leonex_rmp')->useCache();
    }
}
