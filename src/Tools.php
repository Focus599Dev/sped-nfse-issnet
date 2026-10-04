<?php

namespace NFePHP\NFSe\ISSNET;

use NFePHP\NFSe\ISSNET\Common\Tools as ToolsBase;
use NFePHP\NFSe\ISSNET\Common\Signer;
use NFePHP\Common\Strings;
use NFePHP\NFSe\ISSNET\Make;
use Mpdf\Mpdf;
use chillerlan\QRCode\{QRCode, QROptions};

class Tools extends ToolsBase
{
    public function enviaRPS($xml)
    {

        if (empty($xml)) {
            throw new InvalidArgumentException('$xml');
        }

        $service = 'RecepcionarLoteRps';

        $soapAction = 'http://nfse.abrasf.org.br/RecepcionarLoteRps';
        
        $xml = Signer::sign(
            $this->certificate,
            $xml,
            'Rps',
            '',
            $this->algorithm,
            $this->canonical
        );

        $xml = Signer::sign(
            $this->certificate,
            $xml,
            'EnviarLoteRpsEnvio',
            'Id',
            $this->algorithm,
            $this->canonical
        );

        $xml = Strings::clearXmlString($xml, true);

        $xsd = 'servico_enviar_lote_rps_envio.xsd';

        $this->isValid($xml, $xsd);

        $this->lastRequest = htmlspecialchars_decode($xml);

        $request = $this->envelopSOAP($xml, $service);

        $response = $this->sendRequest($this->soapUrl, $soapAction, 'RecepcionarLoteRps', 3, [], [], $request);

        $response = $this->removeStuffs($response);

        return $response;
    }

    public function CancelaNfse($std)
    {

        $make = new Make();

        $service = 'CancelarNfse';

        $soapAction = 'http://nfse.abrasf.org.br/CancelarNfse';

        $xml = $make->cancelamento($std);

        $xml = Signer::sign(
            $this->certificate,
            $xml,
            'Pedido',
            'Id',
            $this->algorithm,
            $this->canonical
        );

        $xsd = 'servico_cancelar_nfse_envio.xsd';

        $this->isValid($xml, $xsd);

        $request = $this->envelopSOAP($xml, $service);

        $response = $this->sendRequest($this->soapUrl, $soapAction, $service, 3, [], [], $request);

        $response = $this->removeStuffs($response);

        return $response;
    }

    public function consultaSituacaoLoteRPS($nprot, \stdClass $std)
    {

        $std->protocolo = $nprot;

        $make = new Make();

        $service = 'ConsultarSituacaoLoteRPS';

        $soapAction = "http://www.issnetonline.com.br/webservice/nfd/ConsultarSituacaoLoteRPS";

        $xml = $make->consultaSituacao($std);

        $xml = Strings::clearXmlString($xml);

        $request = $this->envelopSOAP($xml, $service);

        $response = $this->sendRequest($this->soapUrl, $soapAction, $service, 3, [], [], $request);

        $response = html_entity_decode($response);

        $response = trim(preg_replace("/<\?xml.*?\?>/", "", $response));

        $response = $this->removeStuffs($response);

        return $response;
    }

    public function ConsultarNfsePorRps($indenRPS, $data)
    {

        $make = new Make();

        $service = 'ConsultarNfsePorRps';

        $soapAction = 'http://nfse.abrasf.org.br/ConsultarNfsePorRps';

        $xml = $make->consultaNFSePorRPS($indenRPS, $data);

        $xml = Signer::sign(
            $this->certificate,
            $xml,
            'ConsultarNfseRpsEnvio',
            'Id',
            $this->algorithm,
            $this->canonical
        );

        $xsd = 'servico_consultar_nfse_rps_envio.xsd';
        // XSD do abraf não esta deacordo com exemplo fornecido pelo nota control
        // $this->isValid($xml, $xsd);

        $xml = Strings::clearXmlString($xml, true);

        $request = $this->envelopSOAP($xml, $service);

        $response = $this->sendRequest($this->soapUrl, $soapAction, $service, 3, [], [], $request);

        $response = $this->removeStuffs($response);

        return $response;
    }

    public function consultaLoteRPS($nprot, $data)
    {

        $make = new Make();

        $service = 'ConsultarLoteRps';

        $soapAction = 'http://nfse.abrasf.org.br/ConsultarLoteRps';

        $xml = $make->consultaLoteRPS($nprot, $data);

        $xml = Strings::clearXmlString($xml, true);

        $xsd = 'servico_consultar_lote_rps_envio.xsd';

        $this->isValid($xml, $xsd);

        $this->lastRequest = htmlspecialchars_decode($xml);

        $request = $this->envelopSOAP($xml, $service);

        $response = $this->sendRequest($this->soapUrl, $soapAction, 'RecepcionarLoteRps', 3, [], [], $request);

        $response = $this->removeStuffs($response);

        return $response;
    }

    public function get_endereco($cep){


        // formatar o cep removendo caracteres nao numericos
        $cep = preg_replace("/[^0-9]/", "", $cep);
        $url = "http://viacep.com.br/ws/$cep/xml/";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $xml = simplexml_load_string($data);
        return $xml;
    }

    public static function replaceSpecialsChars($string){
        $string = trim($string);
        $aFind = ['&','á','à','ã','â','é','ê','í','ó','ô','õ','ú','ü',
            'ç','Á','À','Ã','Â','É','Ê','Í','Ó','Ô','Õ','Ú','Ü','Ç','nº', '£'];
        $aSubs = ['&', '&aacute;', '&aacute;', '&atilde;', '&acirc;','&eacute;', '&ecirc;', '&iacute;','&oacute;','&ocirc;','&otilde;', '&uacute;', '&uuml;',
            '&ccedil;', '&Aacute;', '&Aacute;', '&Atilde;', '&Acirc;', '&Eacute;', '&Ecirc;', '&Iacute;', '&Oacute;', '&Ocirc;', '&Otilde;', '&Uacute;', '&Uuml;', '&Ccedil;', 'n&deg;', ''];
        $newstr = str_replace($aFind, $aSubs, $string);

        return $newstr;
    }

    public function getCidade($cep){
       $response =  $this->get_endereco($cep);
       try {
          if($response)
              return $this->replaceSpecialsChars($response->localidade) . '/' . $response->uf;
       } catch (Exception $exception) {
           return  '';
       }
    }

    public function generatePDFNfse($xml, $tpAmb, $status, $logoPath)
    {
        $template = file_get_contents(realpath(__DIR__ . '/../template') . '/nfse.html');

        $contentlogoPres = '';

        if (is_file($logoPath)) {

            $contentlogoPres = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $codeTrib = array(
            '1401' => 'Lubrifica&ccedil;&atilde;o, limpeza, lustra&ccedil;&atilde;o, revis&atilde;o, carga e recarga, conserto, restaura&ccedil;&atilde;o, blindagem, manuten&ccedil;&atilde;o e conserva&ccedil;&atilde;o de m&aacute;quinas, ve&iacute;culos, aparelhos, equipamentos, motores, elevadores ou de qualquer objeto (exceto pe&ccedil;as e partes empregadas, que ficam sujeitas ao ICMS)'
        );

        $url = 'https://www.notaeletronica.com.br/ribeiraopreto/NotaDigital/VerificaAutenticidade.aspx';

        $options = new QROptions([
            'version'    => 5,
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel'   => QRCode::ECC_L,
        ]);

        $qrcode = new QRCode($options);

        $img = $qrcode->render($url);


        $replace = array(
            'logo' =>  'data:image/png;base64,' . base64_encode(file_get_contents(realpath(__DIR__ . '/../template') . '/brasao.png')),
            'logoNota' =>  'data:image/jpg;base64,' . base64_encode(file_get_contents(realpath(__DIR__ . '/../template') . '/LogoNotaFiscalEletronica.jpg')),
            'nfenum' => $xml->Nfse->InfNfse->IdentificacaoRps->Numero,
            'serie' => $xml->Nfse->InfNfse->IdentificacaoRps->Serie,
            'dhemi' => (new \DateTime($xml->Nfse->InfNfse->DataEmissao))->format('d/m/Y'),
            'dhEmisec' => (new \DateTime($xml->Nfse->InfNfse->DataEmissao))->format('d/m/Y H:i'),
            'dhcomp' => (new \DateTime($xml->Nfse->InfNfse->DataEmissao))->format('m/Y'),
            'xMun' => $xml->Nfse->InfNfse->PrestadorServico->Endereco->Estado,
            'regimeTrib' => $xml->Nfse->InfNfse->RegimeEspecialTributacao ? 'Nenhum' : 'Esp&eacute;cial',
            'naturesaop' => $xml->Nfse->InfNfse->NaturezaOperacao == 1 ? 'Trib. no munic&#237;pio de Ribeirão Preto' : 'Trib. fora do munic&#237;pio de Ribeirão Preto',
            'nfsserie' => substr($xml->Nfse->InfNfse->Numero, 0, 7),
            'nfsnum' => substr($xml->Nfse->InfNfse->Numero, 7),
            'codveri' => $xml->Nfse->InfNfse->CodigoVerificacao,
            'emirazao' => $xml->Nfse->InfNfse->PrestadorServico->RazaoSocial,
            'emicnpj' => $this->formatCNPJ($xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Prestador->CpfCnpj->Cnpj ?? ''),
            'email' => $xml->Nfse->InfNfse->PrestadorServico->Contato->Email,
            'logoPres' => $contentlogoPres,
            'inscMuniEmi' => $xml->Nfse->InfNfse->PrestadorServico->IdentificacaoPrestador->InscricaoMunicipal,
            'FoneEmi' => isset($xml->Nfse->InfNfse->PrestadorServico->Contato->Telefone) ? $xml->Nfse->InfNfse->PrestadorServico->Contato->Telefone : '',
            'OpSimpleNaciEmi' => $xml->Nfse->InfNfse->OptanteSimplesNacional == 1 ? 'Sim' : 'Não',
            'IncetCultEmi' => $xml->Nfse->InfNfse->IncentivadorCultural == 1 ? 'Sim' : 'Não',
            'EnderecoEmi' => $xml->Nfse->InfNfse->PrestadorServico->Endereco->Endereco . ', ' . $xml->Nfse->InfNfse->PrestadorServico->Endereco->Numero . ' Bairro ' . $xml->Nfse->InfNfse->PrestadorServico->Endereco->Bairro ,
            'EnderecoEmiCep' => $xml->Nfse->InfNfse->PrestadorServico->Endereco->Cep,
            'EnderecoEmiCidade' => ' Ribeirão Preto ' . $xml->Nfse->InfNfse->PrestadorServico->Endereco->Estado,
            'destrazao' => $xml->Nfse->InfNfse->TomadorServico->RazaoSocial,
            'destCNPJ' => isset($xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->TomadorServico->IdentificacaoTomador->CpfCnpj->Cnpj) ? $this->formatCNPJ($xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->TomadorServico->IdentificacaoTomador->CpfCnpj->Cnpj) : $this->formatCPF($xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->TomadorServico->IdentificacaoTomador->CpfCnpj->Cpf),
            'inscMuniDest' => isset($xml->Nfse->InfNfse->TomadorServico->IdentificacaoTomador->InscricaoMunicipal) ? $xml->Nfse->InfNfse->TomadorServico->IdentificacaoTomador->InscricaoMunicipal : '',
            'FoneDest' => $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->TomadorServico->Contato->Telefone ? $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->TomadorServico->Contato->Telefone : '' ,
            'EmailDest' => $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->TomadorServico->Contato->Email ? $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->TomadorServico->Contato->Email : '' ,
            'EnderecoDest' => $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->TomadorServico->Endereco->Endereco,
            'EnderecoDestComplemento'=> $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->TomadorServico->Endereco->Complemento,
            'EnderecoDestNumero' =>$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->TomadorServico->Endereco->Numero,
            'EnderecoDestBairro' => $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->TomadorServico->Endereco->Bairro,
            'EnderecoDestCep'=> $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->TomadorServico->Endereco->Cep,
            'EnderecoDestCidade'=> $this->getCidade($xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->TomadorServico->Endereco->Cep),
            'OutrasInformacoes' => $xml->Nfse->InfNfse->OutrasInformacoes,
            'codTrib' => $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->CodigoTributacaoMunicipio,
            'textCodeTrib' => isset($codeTrib[(string)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->CodigoTributacaoMunicipio]) ? $codeTrib[(string)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->CodigoTributacaoMunicipio] : '',
            'vPIS' => isset($xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorPis) ? number_format((string)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorPis, 2, ',', '.') : '0,00',
            'vCOFINS' => isset($xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorCofins) ? number_format((string)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorCofins, 2, ',', '.') : '0,00',
            'vINSS' => isset($xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorInss) ? number_format((string)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorInss, 2, ',', '.') : '0,00',
            'vIR' => isset($xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorIr) ? number_format((string)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorIr, 2, ',', '.') : '0,00',
            'vCSLL' => isset($xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorCsll) ? number_format((string)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorCsll, 2, ',', '.') : '0,00',
            'vOthers' => '0,00',
            'Discriminacao' => $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Discriminacao,
            'IssRetido'=> $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->IssRetido == 1 ? 'Sim' : 'Não' ,
            'valorServ' => number_format((string)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorServicos, 2, ',', '.'),
            'valorDedu' => '0,00',
            'valorIncod' => '0,00',
            'valorBasecalc' => $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->BaseCalculo ? number_format((string)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->BaseCalculo, 2, ',', '.') : '' ,
            'Aliquota' => number_format(((float)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->Aliquota), 2, ',', '.'),
            'valorISS' => number_format((string)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorIss, 2, ',', '.'),
            'valorISSR' => isset($xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorIssRetido) ? number_format((string)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorIssRetido, 2, ',', '.') : '0,00',
            'valorPis' => number_format(((float)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorPis), 2, ',', '.'),
            'valorConfins' =>number_format(((float)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorCofins), 2, ',', '.'),
            'valorInss' => number_format(((float)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorInss), 2, ',', '.'),
            'valorIrrf' => number_format(((float)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorIr), 2, ',', '.'),
            'valorCsll' => number_format(((float)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorCsll), 2, ',', '.'),
            'valorCond' => '0,00',
            'valorLiquido' => $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorLiquidoNfse ? number_format((string)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorLiquidoNfse, 2, ',', '.') : '',
            'valorTotal' => number_format((string)$xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->Valores->ValorServicos, 2, ',', '.'),
            'OutrosR' => '0,00',
            'img' => $img,
            'CodigoCnae' => $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->CodigoCnae,
            'ItemListaServico' => $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico->ItemListaServico
        );



        foreach ($replace as $key => $value) {

            $template = str_replace("{{%$key}}", $value, $template);
        }

        $mpdf = new Mpdf();

        $mpdf->SetDisplayMode(100, 'default');

        $mpdf->allow_charset_conversion = true;

        $mpdf->charset_in = 'iso-8859-4';

        $mpdf->SetMargins(0, 0, 0);

        $mpdf->WriteHTML(utf8_decode($template));

        $mpdf->Output();
    }

    private function queryPath($path, $xpath){

        $nodes = $xpath->query('//' . $path);

        if (!$nodes->length)
            return null;

        foreach($nodes as $node){

            return $node->nodeValue;
        }

        return null;
    }

    public function ConsultarUrlNfse($NumeroNfse, $data)
    {

        $make = new Make();

        $service = 'ConsultarUrlNfse';

        $soapAction = 'http://nfse.abrasf.org.br/ConsultarUrlNfse';

        $xml = $make->ConsultarUrlNfseEnvio($NumeroNfse, $data);

        $xml = Signer::sign(
            $this->certificate,
            $xml,
            'ConsultarUrlNfseEnvio',
            'Id',
            $this->algorithm,
            $this->canonical
        );

        $xml = Strings::clearXmlString($xml, true);

        // Nota control não tem xsd para esse serviço
        // $xsd = 'servico_consultar_lote_rps_envio.xsd';

        // $this->isValid($xml, $xsd);

        $this->lastRequest = htmlspecialchars_decode($xml);

        $request = $this->envelopSOAP($xml, $service);

        $response = $this->sendRequest($this->soapUrl, $soapAction, 'ConsultarLoteRps', 3, [], [], $request);

        $response = $this->removeStuffs($response);

        return $response;
    }
}
