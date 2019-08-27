<?php
/**
 * Class Response
 *
 * Manage the response from the api-call.
 * Main goal of this class is a storage of the response in a structured way.
 * Beneath the structuring of the data the class implements a function to filter the payments from the main event.
 *
 * @package LxRmp\Components\Data
 * @author fseeger
 */
class Leonex_RiskManagementPlatform_Model_Component_Response
{
    /** @var string */
    protected $_status;

    /** @var \stdClass */
    protected $_payments;

    /** @var  mixed $_hash */
    protected $_hash;

    /** @var mixed $_response  */
    protected $_response;

    /**
     * Leonex_RiskManagementPlatform_Model_Component_Response constructor.
     * @param $response
     */
    public function __construct(
        $response
    ){
        $this->_response = $response;
        $response = json_decode($response);
        $this->_status = $response->status;
        $this->_payments = $response->payment_methods;
    }

    /**
     * Get the payments given by the main event as argument and filter them with new conditions from the response.
     *
     * When a payment is marked as unavailable (available != true) then remove this payment from the array.
     * A array with
     *
     * @param $payment
     *
     * @return bool
     */
    public function filterPayment($payment)
    {
        if (is_object($this->_payments->$payment)) {
            $obj = $this->_payments->$payment;
            if (!$obj->available) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Set Hash from given Quote
     *
     * @param Leonex_RiskManagementPlatform_Model_Quote_Quote $quote
     */
    public function setHash(Leonex_RiskManagementPlatform_Model_Quote_Quote $quote)
    {
        $this->_hash = $quote->getQuoteHash();
    }

    /**
     * Return Hash from Quote
     *
     * @return mixed
     */
    public function getHash()
    {
        return $this->_hash;
    }

    /**
     * Return Response as json
     *
     * @return mixed
     */
    public function getCleanResponse()
    {
        return $this->_response;
    }
}
