<?php

/**
 * RMP helper
 *
 * @category    Leonex
 * @package     Leonex_RiskManagementPlfatform
 * @author      Florian Seeger <Florian.Seeger@leonex.de>
 */
class Leonex_RiskManagementPlatform_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Return store config value for key
     * @param   null|int|Mage_Core_Model_Store $store
     * @param   bool $flag
     * @param   string $key
     * @return  string
     */
    public function getConfig($key, $flag = false, $store = null)
    {
        if ($flag) {
            return Mage::getStoreConfigFlag($key, $store);
        }
        else {
            return Mage::getStoreConfig($key, $store);
        }
    }

    /**
     * Check if payments shall be filtered
     *
     * @param null $store
     *
     * @return bool
     */
    public function isActive($store = null)
    {
        return (bool) $this->getConfig('leonex_rmp/settings/is_active', true, $store);
    }

    /**
     * Check if response shall be cached
     *
     * @param null $store
     *
     * @return bool
     */
    public function useCache($store = null)
    {
        return (bool) $this->getConfig('leonex_rmp/settings/enable_cache', true, $store);
    }

    /**
     * Get time of checking from plugin config
     * Pre | Post
     *
     * @param null $store
     *
     * @return string
     */
    public function getTimeOfChecking($store = null)
    {
        return $this->getConfig('leonex_rmp/settings/time_of_checking', false, $store);
    }

    /**
     * Get Api-Url from plugin config
     *
     * @param null $store
     *
     * @return string
     */
    public function getApiUrl($store = null)
    {
        return $this->getConfig('leonex_rmp/settings/apiurl', false, $store);
    }

    /**
     * Get Api-Key from plugin config
     *
     * @param null $store
     *
     * @return string
     */
    public function getApiKey($store = null)
    {
        return $this->getConfig('leonex_rmp/settings/apikey', false, $store);
    }

    public function getAllowedPaymentMethods($store = null)
    {
        $methods = $this->getConfig('leonex_rmp/settings/payment_methods', false, $store);
        if (!$methods) {
            return array();
        }
        return explode(',', $methods);
    }
}
