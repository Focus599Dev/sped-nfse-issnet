<?php

namespace NFePHP\NFSe\ISSNET\Common;

use NFePHP\Common\Certificate;
use NFePHP\NFSe\ISSNET\Soap\Soap;
use NFePHP\Common\Validator;

class Tools
{

    public $soapUrl;

    public $config;

    public $soap;

    public $pathSchemas;

    protected $algorithm = OPENSSL_ALGO_SHA1;

    protected $canonical = [false, false, null, null];

    public function __construct($configJson, Certificate $certificate)
    {
        $this->pathSchemas = realpath(
            __DIR__ . '/../../schemas'
        ) . '/';

        $this->certificate = $certificate;

        $this->config = json_decode($configJson);

        if ($this->config->tpAmb == '1') {
            $this->soapUrl = 'https://abrasf.issnetonline.com.br/webserviceabrasf/ribeiraopreto/servicos.asmx';
        } else {
            $this->soapUrl = 'https://abrasf.issnetonline.com.br/webserviceabrasf/homologacao/servicos.asmx';
            // $this->soapUrl = 'https://www.issnetonline.com.br/webserviceabrasf/ribeiraopreto/servicos.asmx';
        }

        $this->soap = new Soap($this->certificate);
    }

    protected function sendRequest($url, $soapAction, $action, $soapEver, $paranmeters = [], $namespaces = [], $request)
    {

        if (!$this->soap)
            $this->soap = new Soap($this->certificate);

        $response = $this->soap->send($url, $soapAction, $action, $soapEver,  $paranmeters, $namespaces, $request);

        return (string) $response;
    }

    public function envelopSOAP($xml, $service)
    {
        $this->xml = '<?xml version="1.0" encoding="utf-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                <soap:Body>
                    <tns:' . $service . ' xmlns:tns="http://www.issnetonline.com.br/webservice/nfd">
                        <tns:xml>' . htmlspecialchars($xml) . '</tns:xml>
                    </tns:' . $service . '>
                </soap:Body>
            </soap:Envelope>';

        return $this->xml;
    }

    public function removeStuffs($xml)
    {

        if (preg_match('/<soap:Body>/', $xml)) {

            $tag = '<soap:Body>';
            $xml = substr($xml, (strpos($xml, $tag) + strlen($tag)), strlen($xml));

            $tag = '</soap:Body>';
            $xml = substr($xml, 0, strpos($xml, $tag));
        }

        $xml = trim($xml);

        return $xml;
    }

    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    protected function isValid($body, $method)
    {
        $pathschemes = realpath(__DIR__ . '/../../schemas/') . '/';

        $schema = $pathschemes . $method;

        if (!is_file($schema)) {
            return true;
        }

        return Validator::isValid(
            $body,
            $schema
        );
    }

    public function formatCNPJ($cnpj)
    {
        if (!$cnpj)
            return '';

        return substr($cnpj, 0, 2) . '.' .  substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    }

    public function formatCPF($cpf)
    {
        if (!$cpf)
            return '';

        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }

    public function getPdfPath($xml){

        $oldEfit = config('envs.OLD_EFIT_ABSOLUTE');

        $dtFolder = substr(str_replace("-", "", (new \DateTime($xml->Nfse->InfNfse->DataEmissao))->format('d/m/Y')), 0, 6);

        $dtFolder2 = substr(str_replace("-", "", (new \DateTime($xml->Nfse->InfNfse->DataEmissao))->format('d/m/Y')), 6, 2);

        if (trim($dtFolder) === "") {
            $dtFolder = "UNKNOW";
        }

        $path = $oldEfit . "files/nfs/pdf/{$dtFolder}{$dtFolder2}/";

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }
    

    public function getPdfFileName($xml){

        $num = $xml->Nfse->InfNfse->IdentificacaoRps->Numero;

        $rps = $xml->Nfse->InfNfse->Numero;

        $type = $xml->Nfse->InfNfse->IdentificacaoRps->Tipo;

        $fileName = "{$num}" . "-{$rps}" . "-{$type}" . ".pdf";

        return $fileName;
    }
}
