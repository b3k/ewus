<?php

namespace Ewus;

class Ewus {

    const WSDL_PROD_AUTH = "https://ewus.nfz.gov.pl/ws-broker-server-ewus/services/Auth?wsdl";
    const WSDL_PROD_BROK = "https://ewus.nfz.gov.pl/ws-broker-server-ewus/services/ServiceBroker?wsdl";
    const WSDL_TEST_AUTH = "https://ewus.nfz.gov.pl/ws-broker-server-ewus-auth-test/services/Auth?wsdl";
    const WSDL_TEST_BROK = "https://ewus.nfz.gov.pl/ws-broker-server-ewus-auth-test/services/ServiceBroker?wsdl";

    protected $is_production = true;
    protected $domain = 15;
    protected $login_type;
    protected $ident_string = '';
    protected $username = 'TEST1';
    protected $password = 'qwerty!@#';
    
    private $session;

    public function __construct() {
        $parametry = array('credentials' =>
            array(
                array('name' => 'domain', 'value' => array('stringValue' => $this->domain)),
                array('name' => 'login', 'value' => array('stringValue' => $this->username))
            ),
            'password' => $this->password);
        
        $client=$this->getClient();
        
        $this->session = $client->login($parametry);

        var_dump($this->session);

        $sesja = $client->__soapCall('login', array($parametry), null, null, $header);

        var_dump($this->session);

        var_dump($header);

    }
    
    public function getClient() {
        return new \SoapClient(self::WSDL_PROD_AUTH, array('trace' => true));
    }

}

$c = new Ewus();