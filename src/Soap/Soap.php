<?php

namespace NFePHP\NFSe\ISSNET\Soap;

use NFePHP\Common\Certificate;
use NFePHP\Common\Exception\InvalidArgumentException;
use NFePHP\NFSe\GINFE\Exception\SoapException;
use NFePHP\NFSe\ISSNET\Soap\SoapBase;

class Soap extends SoapBase{ 

    public function send(
        $url,
        $operation = '',
        $action = '',
        $soapver = SOAP_1_2,
        $parameters = [],
        $namespaces = [],
        $request = '',
        $soapheader = null
    ) {
        $headers = array(
            "Content-Type: application/soap+xml; charset='utf-8'",
            "SOAPAction: \"$operation\"",
            "Content-Length: " . strlen($request)
        ); 

        try {
            
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, $url);
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->soaptimeout);
            
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->soaptimeout + 20);
            
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            
            curl_setopt($ch, CURLOPT_POST, 1);
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            curl_setopt($ch, CURLOPT_HEADER, 0);
            
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            
            curl_setopt($ch, CURLOPT_SSLVERSION, 6);
            
            curl_setopt($ch, CURLOPT_SSLCERT, $this->tempdir . $this->certfile);
            
            curl_setopt($ch, CURLOPT_SSLKEY, $this->tempdir . $this->prifile);
            
            if (!empty($this->temppass)) {
                
                curl_setopt($ch, CURLOPT_KEYPASSWD, $this->temppass);
                
            }

            if (!$this->disablesec) {
                
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                
                if (is_file($this->casefaz)) {
                    
                    curl_setopt($ch, CURLOPT_CAINFO, $this->casefaz);
                    
                }
            }
            
            
            $response = curl_exec($ch);
            
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            $this->soaperror = curl_error($ch);
            
            $headsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            
            curl_close($ch);
            
            $this->responseHead = trim(substr($response, 0, $headsize));
            
        } catch (\Exception $e) {

            throw SoapException::unableToLoadCurl($e->getMessage());
        }
        
        if ($this->soaperror != '') {
            
            throw SoapException::soapFault($this->soaperror . " [$url]");
        }
        
        if ($httpcode != 200) {
            
            throw SoapException::soapFault(" [$url]" . $this->responseHead);
        }

        return $response;
    }

}
