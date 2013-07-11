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
 * Ewus SOAP Client
 * 
 * @author Bartosz Pietrzak <b3k@b3k.pl>
 */
abstract class EwusSoapClient extends \SoapClient {

    /**
     * Contains options for SoapClient object constructor
     *
     * @var array 
     */
    protected $options;
    
    /**
     * Contains WSDL address
     *
     * @var string
     */
    protected $wsdl;

    /**
     * 
     * @param type $wsdl
     * @param type $options
     */
    public function __construct($wsdl, $options = array()) {
        echo $wsdl.PHP_EOL;
        $this->wsdl = $wsdl;
        $this->options = $options;
        parent::__construct($wsdl, $options);
    }

}