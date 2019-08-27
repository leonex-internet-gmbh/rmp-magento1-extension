<?php

/**
 * Class Api
 *
 * @package LxRmp\Components
 * @author  fseeger
 */
class Leonex_RiskManagementPlatform_Model_Component_Api
{
    /** @var string */
    const METHOD_GET = 'GET';

    /** @var string */
    const METHOD_PUT = 'PUT';

    /** @var string */
    const METHOD_POST = 'POST';

    /** @var string */
    const METHOD_DELETE = 'DELETE';

    /** @var array|mixed */
    protected $_validMethods = array(
        self::METHOD_POST
    );

    /** @var string */
    protected $_apiUrl;

    /** @var resource */
    protected $_cURL;

    /**
     * Leonex_RiskManagementPlatform_Model_Component_Api constructor.
     *
     * @param array $array
     */
    public function __construct(array $array)
    {
        $this->_apiUrl = rtrim($array['api_url'], '/');

        $this->_cURL = curl_init();
        curl_setopt($this->_cURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_cURL, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($this->_cURL, CURLOPT_HTTPHEADER,
            array(
                'X-AUTH-KEY: '.$array['api_key'],
                'Content-Type: application/json; charset=utf-8'
            )
        );
    }

    /**
     * Call the api with given data and parameters.
     *
     * @param string $method
     * @param array  $data
     * @param array  $params
     *
     * @return Leonex_RiskManagementPlatform_Model_Component_Response
     * @throws Exception
     */
    protected function _call($method = self::METHOD_GET, $data = array(), $params = array())
    {
        if (!in_array($method, $this->_validMethods)) {
            Mage::throwException(new Exception('Invalid HTTP-Methode: ' . $method));
        }

        $queryString = '';
        if (!empty($params)) {
            $queryString = '?' . http_build_query($params);
        }

        $url = rtrim($this->_apiUrl, '?');
        $url = $url . $queryString;
        $dataString = json_encode($data);
        curl_setopt($this->_cURL, CURLOPT_URL, $url);
        curl_setopt($this->_cURL, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->_cURL, CURLOPT_POSTFIELDS, $dataString);
        $result = curl_exec($this->_cURL);

        return $this->_prepareResponse($result);
    }

    /**
     * Init a new call via GET-Method.
     *
     * @param array $params
     *
     * @return Leonex_RiskManagementPlatform_Model_Component_Response
     * @throws Exception
     */
    public function get($params = array())
    {
        return $this->_call(self::METHOD_GET, array(), $params);
    }

    /**
     * Init a new call via POST-Method.
     *
     * @param array $data
     * @param array $params
     *
     * @return Leonex_RiskManagementPlatform_Model_Component_Response
     * @throws Exception
     */
    public function post($data = array(), $params = array())
    {
        return $this->_call(self::METHOD_POST, $data, $params);
    }

    /**
     * Init a new call via PUT-Method.
     *
     * @param array $data
     * @param array $params
     *
     * @return Leonex_RiskManagementPlatform_Model_Component_Response
     */
    public function put($data = array(), $params = array())
    {
        return $this->_call(self::METHOD_PUT, $data, $params);
    }

    /**
     * Init a new call via DELETE-Method.
     *
     * @param array $params
     *
     * @return Leonex_RiskManagementPlatform_Model_Component_Response
     * @throws Exception
     */
    public function delete($params = array())
    {
        return $this->_call(self::METHOD_DELETE, array(), $params);
    }

    /**
     * Return the response from the api-call and implement an event to filter this response.
     *
     * @param $result
     *
     * @return Leonex_RiskManagementPlatform_Model_Component_Response
     */
    protected function _prepareResponse($result)
    {
        /** @var Leonex_RiskManagementPlatform_Model_Component_Response $response */
        $response = Mage::getModel('leonex_rmp/component_response', $result);
        return $response;
    }
}
