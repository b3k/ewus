<?php

/*
 * This file is part of the Ewus package.
 *
 * (c) Bartosz Pietrzak <b3k@b3k.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ewus\SoapClient;

/**
 * Ewus SOAP Broker Service Client
 * 
 * @author Bartosz Pietrzak <b3k@b3k.pl>
 */
class EwusBrokerServiceSoapClient extends EwusSoapClient {

    const BROKER_SERVICE_XML_NAMESPACE = 'nfz.gov.pl/ws/broker/cwu';
    const BROKER_SERVICE_XML_CWU_NAMESPACE = 'https://ewus.nfz.gov.pl/ws/broker/ewus/status_cwu/v3';
    const BROKER_SERVICE_VERSION = '3.0';
    const BROKER_SERVICE_LOCALNAME_CHECKCWU = 'checkCWU';

    /**
     * Returns WSDL service location from WSDL
     * 
     * @return string
     */
    public function getBrokerServiceAddress() {
        $xml = simplexml_load_file($this->wsdl);
        $address = $xml->xpath('//wsdl:definitions/wsdl:service/wsdl:port/wsdlsoap:address');
        return (string) $address[0]->attributes()->location;
    }

    /**
     * Sends raw XML into ServiceBroker to get info
     * about given PESEL.
     * 
     * We solve this request in 'ugly-way' coz 
     * PHP SOAP Lib can't parse eWUS Broker WSDL properly (node "<any />").
     * 
     * 
     * @param array $params
     * @return EwusCheckCwuResponse
     */
    public function brokerCheckCwu($pesel, Array $params) {
        $xml = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:com="http://xml.kamsoft.pl/ws/common" xmlns:brok="http://xml.kamsoft.pl/ws/broker"><soapenv:Header>';
        $xml .= '<com:session id="' . $params['session_id'] . '" xmlns:ns1="http://xml.kamsoft.pl/ws/common"/><com:authToken id="' . $params['auth_token'] . '" xmlns:ns1="http://xml.kamsoft.pl/ws/common"/></soapenv:Header><soapenv:Body>';
        $xml .= '<brok:executeService><com:location><com:namespace>' . self::BROKER_SERVICE_XML_NAMESPACE . '</com:namespace><com:localname>' . self::BROKER_SERVICE_LOCALNAME_CHECKCWU . '</com:localname>';
        $xml .= '<com:version>' . self::BROKER_SERVICE_VERSION . '</com:version></com:location><brok:date>2008-09-12T09:37:36.406+01:00</brok:date>';
        $xml .= '<brok:payload><brok:textload><ewus:status_cwu_pyt xmlns:ewus="'.self::BROKER_SERVICE_XML_CWU_NAMESPACE.'"><ewus:numer_pesel>' . $pesel . '</ewus:numer_pesel><ewus:system_swiad nazwa="' . $params['system_name'] . '" wersja="' . $params['system_version'] . '" />';
        $xml .= '</ewus:status_cwu_pyt></brok:textload></brok:payload></brok:executeService></soapenv:Body></soapenv:Envelope>';
        $return = parent::__doRequest($xml, $this->getBrokerServiceAddress(), 'executeService', isset($this->options['soap_version']) ? $this->options['soap_version'] : SOAP_1_1);

        return $return;
    }

}