<?php

/**
 * RMP quote helper
 *
 * @category    Leonex
 * @package     Leonex_RiskManagementPlfatform
 * @author      Floriam Seeger <Florian.Seeger@leonex.de>
 */
class Leonex_RiskManagementPlatform_Model_Quote_Quote
{

    protected $_gender = array(
        1 => 'm',
        2 => 'f'
    );

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote;

    /**
     * @var array
     */
    protected $_normalizedQuote;

    /**
     * @var Mage_Sales_Model_Quote_Address
     */
    protected $_billingAddress;

    /**
     * @var Mage_Customer_Model_Customer
     */
    protected $_customer;

    /**
     * Leonex_RiskManagementPlatform_Model_Quote_Quote constructor.
     *
     * @param Mage_Sales_Model_Quote $quote
     */
    public function __construct(Mage_Sales_Model_Quote $quote)
    {
        $this->_quote = $quote;
        $this->_billingAddress = $quote->getBillingAddress();
        $this->_shippingAddress = $quote->getShippingAddress();
        $this->_customer = $quote->getCustomer();
        $this->_normalizedQuote = $this->_normalizeQuote();
    }

    /**
     * Check whether a billing address has been provided
     * @return bool
     */
    public function isAddressProvided()
    {
        $billingAddress = $this->_billingAddress;

        return $billingAddress->getLastname()
            || $billingAddress->getFirstname();
    }

    /**
     * Return the normalized quote and trigger a filter event.
     *
     * @return array
     */
    public function getNormalizedQuote()
    {
        return $this->_normalizedQuote;
    }

    /**
     * Structure the given information in a new structured way.
     * The structure correlate with required api-structure.
     *
     * @return array
     */
    protected function _normalizeQuote()
    {
        return array(
            'customerSessionId' => Mage::getSingleton("core/session")->getEncryptedSessionId(),
            'justifiableInterest'  => Leonex_RiskManagementPlatform_Helper_Connector::JUSTIFIABLE_INTEREST_BUSINESS_INITIATION,
            'consentClause'        => true,
            'billingAddress'       => $this->_getBillingAddress(),
            'shippingAddress'       => $this->_getShippingAddress(),
            'quote' => $this->_getQuote(),
            'customer' => $this->_getCustomerData(),
            'orderHistory' => $this->_getOrderHistory()
        );
    }

    /**
     * Adjust the data from the billing address.
     *
     * @return array
     */
    protected function _getBillingAddress()
    {
        $address = $this->_billingAddress;

        return array(
            'gender' => $this->_gender[$this->_quote->getCustomerGender()],
            'lastName' => $address->getLastname(),
            'firstName' => $address->getFirstname(),
            'dateOfBirth' => substr($this->_quote->getCustomerDob(), 0, 10),
            'birthName' => $address->getLastname(),
            'street' => $address->getStreet(1),
            'zip' => $address->getPostcode(),
            'city' => $address->getCity(),
            'country' => strtolower($address->getCountryId())
        );
    }

    /**
     * Adjust the data from the shipping address.
     *
     * @todo implement birthName
     * @return array
     */
    protected function _getShippingAddress()
    {
        $address = $this->_shippingAddress;

        return array(
            'gender'               => self::GENDER[$this->_quote->getCustomerGender()],//$customer->getOrigData('gender')
            'lastName'             => $address->getLastname(),
            'firstName'            => $address->getFirstname(),
            'street'               => $address->getStreet1(),
            'street2'              => $address->getStreet2(), // optional (needed for Packstation)
            'zip'                  => $address->getPostcode(),
            'city'                 => $address->getCity(),
            'country'              => strtolower($address->getCountryId()),
        );
    }

    /**
     * Get the item quote.
     * Includes the total amount and a array of basket items.
     *
     * @return array
     */
    protected function _getQuote()
    {
        return array(
            'items' => $this->_getQuoteItems(),
            'totalAmount' => $this->_quote->getGrandTotal(),
        );
    }

    /**
     * Get the items from the basket as array.
     *
     * @return array
     */
    protected function _getQuoteItems()
    {
        $quoteItems = array();

        /** @var Mage_Sales_Model_Quote_Item $item*/
        foreach ($this->_quote->getAllItems() as $item){
            if(is_null($item->getParentItemId())){
                $quoteItems[] = array(
                    'sku' => $item->getSku(),
                    'quantity' => $item->getQty(),
                    'price' => (float)$item->getPriceInclTax(),
                    'rowTotal' => (float)$item->getRowTotal()
                );
            }
        }

        return $quoteItems;
    }

    /**
     * Get the number and email from the customer.
     *
     * @return array
     */
    protected function _getCustomerData()
    {
        $customer = $this->_customer;
        if (!$customer->getId()) {
            return array(
                'email' => $this->_billingAddress->getEmail()
            );
        }

        return array(
            'number' => $customer->getId(), 'email' => $customer->getData('email')
        );
    }

    /**
     * Get the number of canceled orders
     *
     * @return int
     */
    protected function getNumberOfCanceledOrders()
    {
        return $this->getNumberOf([Mage_Sales_Model_Order::STATE_CANCELED]);
    }

    /**
     * Get the number of completed orders
     *
     * @return int
     */
    protected function getNumberOfCompletedOrders()
    {
        return $this->getNumberOf([Mage_Sales_Model_Order::STATE_COMPLETE]);
    }

    /**
     * Get the number of unpaid orders
     *
     * @return int
     */
    protected function getNumberOfUnpaidOrders()
    {
        return $this->getNumberOf([
            Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
            Mage_Sales_Model_Order::STATE_NEW,
            Mage_Sales_Model_Order::STATE_HOLDED,
            Mage_Sales_Model_Order::STATE_PROCESSING
        ]);
    }

    /**
     * Get The Number of orders by given state
     *
     * @param array $states
     *
     * @return int
     */
    protected function getNumberOf(array $states)
    {
        $col = Mage::getResourceModel('sales/order_collection');
        $col->addFieldToSelect('entity_id');
        $col->addFieldToFilter('state', $states);
        if ($this->_customer && $this->_customer->getId()) {
            $col->addFieldToFilter(array(
                'customer_email',
                'customer_id',
            ),
            array(
                array('like' => $this->_billingAddress->getEmail()),
                array('like' => $this->_customer->getId()),
            )
        );
        } else {
            $col->addFieldToFilter('customer_email', array('like' => $this->_billingAddress->getEmail()));
        }

        return $col->getSize();
    }

    /**
     * Get the customer history from the quote model.
     *
     * @return array
     */
    protected function _getOrderHistory()
    {
        return array(
            'numberOfCanceledOrders' => $this->getNumberOfCanceledOrders(),
            'numberOfCompletedOrders' => $this->getNumberOfCompletedOrders(),
            'numberOfUnpaidOrders' => $this->getNumberOfUnpaidOrders(),
        );
    }

    /**
     * Create a md5 from the basket and customer to block recurring events.
     *
     * @return string
     */
    public function getQuoteHash()
    {
        return hash('sh256', json_encode($this->_normalizedQuote));
    }
}
