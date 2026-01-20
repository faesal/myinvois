<?php
namespace App\Libraries\MyInvois\Converter;

use DOMDocument;

class UblJsonToXml
{
    public function convert(array $ubl): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $invoice = $dom->createElement('Invoice');
        $invoice->setAttribute('xmlns', $ubl['_D']);
        $invoice->setAttribute('xmlns:cac', $ubl['_A']);
        $invoice->setAttribute('xmlns:cbc', $ubl['_B']);
        $dom->appendChild($invoice);

        return $dom->saveXML();
    }
}
