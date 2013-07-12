<?php

/*
 * This file is part of the Ewus package.
 *
 * (c) Bartosz Pietrzak <b3k@b3k.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ewus;

use Ewus\Exception\EwusBadCredentialsException;
use Ewus\SoapClient\EwusBrokerServiceSoapClient;
use Ewus\SoapClient\EwusAuthSoapClient;
use Ewus\BrokerResponse\CheckCwuBrokerResponse;

/**
 * Ewus connector lib
 * 
 * It contains a all functionality need to connect with
 * NFZ EWUS System and check given PESEL.
 * 
 * @author Bartosz Pietrzak <b3k@b3k.pl>
 */
class Ewus {

    /**
     * Production Auth WSDL (working)
     */
    const WSDL_PROD_AUTH = "https://ewus.nfz.gov.pl/ws-broker-server-ewus/services/Auth?wsdl";
    
    /**
     * Production Broker WSDL (working)
     */
    const WSDL_PROD_BROK = "https://ewus.nfz.gov.pl/ws-broker-server-ewus/services/ServiceBroker?wsdl";
    
    /**
     * Currently this WSDL is broken (sic) use "tests/fixed_auth.wsdl" instead for testing.
     */
    const WSDL_TEST_AUTH = "https://ewus.nfz.gov.pl/ws-broker-server-ewus-auth-test/services/Auth?wsdl";
    
    /**
     * Currently this WSDL is broken (sic) use "tests/fixed_broker.wsdl" instead for testing.
     */
    const WSDL_TEST_BROK = "https://ewus.nfz.gov.pl/ws-broker-server-ewus-auth-test/services/ServiceBroker?wsdl";

    /**
     * Is prodcution env ?
     *
     * @var boolean
     */
    protected $is_production = FALSE;

    /**
     * Domain 
     *
     * @var integer
     */
    protected $domain;

    /**
     * Username for authentication
     *
     * @var string
     */
    protected $username;

    /**
     * Password for authentication
     * 
     * @var string
     */
    protected $password;

    /**
     * Application name (something like USER_AGENT) for broker service
     *
     * @var string
     */
    protected $system_name;

    /**
     * Application version for broker service
     *
     * @var string
     */
    protected $system_version;

    /**
     * Authenticate response code
     * 
     * @var integer
     */
    protected $auth_response_code;

    /**
     * Authenticate response msg
     * 
     * @var string
     */
    protected $auth_response_msg;

    /**
     * Session ID from login request
     *
     * @var string
     */
    protected $auth_session_id;

    /**
     * Is session registered?
     * 
     * @var string
     */
    protected $logged = FALSE;

    /**
     * Holds AuthToken from login request
     * 
     * @var string
     */
    protected $auth_token;

    /**
     * Soap client object for broker services
     * 
     * @var EwusSoapClient
     */
    protected $broker_client;

    /**
     * Soap client object for authentication
     * 
     * @var EwusAuthSoapClient
     */
    protected $auth_client;

    /**
     * Initalization options for SoapClient object
     * 
     * @var array 
     */
    protected $soap_client_options;

    /**
     * 
     * @param array $params
     * @param array $soap_client_options
     */
    public function __construct($params = array(), $soap_client_options = array()) {
        $params = array_merge(array('username' => 'TEST1', 'password' => 'qwerty!@#', 'domain' => 15, 'is_production' => FALSE, 'system_name' => 'eWus PHP Lib', 'system_version' => '1.0'), $params);
        $this->soap_client_options = array_merge(array('trace' => 1, 'soap_version' => SOAP_1_1, 'exceptions' => true, 'cache_wsdl' => WSDL_CACHE_MEMORY), $soap_client_options);

        $this->username = $params['username'];
        $this->password = $params['password'];
        $this->system_name = $params['system_name'];
        $this->system_version = $params['system_version'];
        $this->domain = (int) $params['domain'];
        $this->is_production = (bool) $params['is_production'];
    }

    /**
     *  Destructor created to make logout from session.
     */
    public function __destruct() {
        $this->logout();
    }

    /**
     * Authenticate session
     * 
     * @return boolean
     * @throws EwusBadCredentialsException
     */
    public function authenticate() {
        $this->logged = FALSE;
        $params = array('domain' => $this->domain, 'username' => $this->username, 'password' => $this->password);

        $client = $this->getAuthClient();
        $response = $client->authLogin($params);
        $this->auth_session_id = $response['session_id'];
        $this->auth_token = $response['auth_token'];
        $this->auth_response_code = $response['response_code'];
        $this->auth_response_msg = $response['response_msg'];

        $this->logged = TRUE;

        return $this->logged;
    }

    /**
     * Logout from session
     * 
     * @return boolean
     */
    public function logout() {
        if (!$this->logged) {
            return TRUE;
        }
        return $this->getAuthClient()->authLogout(array('session_id' => $this->auth_session_id, 'auth_token' => $this->auth_token));
    }

    /**
     * Make checkCWU request to SOAP
     * 
     * @param integer $pesel
     * @throws NoLoggedException
     */
    public function checkPesel($pesel) {
        if (!$this->logged) {
            throw new NoLoggedException();
        }
        $client = $this->getBrokerClient();
        $return = $client->brokerCheckCwu($pesel, array(
            'session_id' => $this->auth_session_id,
            'auth_token' => $this->auth_token,
            'system_name' => $this->system_name,
            'system_version' => $this->system_version
                )
        );
        return CheckCwuBrokerResponse::createFromXml($return);
    }

    /**
     * Returns session auth_token (after login)
     * 
     * @return string
     */
    public function getAuthToken() {
        return $this->auth_token;
    }

    /**
     * Returns session ID (after login)
     * 
     * @return string
     */
    public function getSessionId() {
        return $this->session_id;
    }

    /**
     * Response auth response code
     * 
     * @return string
     */
    public function getAuthResponseCode() {
        return $this->auth_response_code;
    }

    /**
     * Returns auth response msg
     * 
     * @return string
     */
    public function getAuthResponseMsg() {
        return $this->auth_response_msg;
    }

    /**
     * Returns Ewus SoapClient with Auth WSDL
     * 
     * @return EwusSoapClient
     */
    public function getAuthClient() {
        if (!$this->auth_client instanceof EwusAuthSoapClient) {
            $this->auth_client = new EwusAuthSoapClient($this->is_production ? self::WSDL_PROD_AUTH : self::WSDL_TEST_AUTH, $this->soap_client_options);
        }
        return $this->auth_client;
    }

    /**
     * Returns Ewus SoapClient with ServiceBroker WSDL
     * 
     * @return EwusSoapClient
     */
    public function getBrokerClient() {
        if (!$this->broker_client instanceof EwusBrokerServiceSoapClient) {
            $this->broker_client = new EwusBrokerServiceSoapClient($this->is_production ? self::WSDL_PROD_BROK : self::WSDL_TEST_BROK, $this->soap_client_options);
        }
        return $this->broker_client;
    }

}