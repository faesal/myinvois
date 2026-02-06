<?php

namespace App\Services\MyInvois\Signer;

class XadesSigner
{
    protected string $certPath;
    protected string $privateKeyPath;

    public function __construct(string $certPath, string $privateKeyPath)
    {
        $this->certPath = base_path('cert/certificate.crt');
        $this->privateKeyPath = base_path('cert/private.key');
    }

    public function sign(string $xmlString): string
    {
        $xml = new \DOMDocument();
        $xml->loadXML($xmlString);

        // ===== Get Password from ENV =====
        $password = env('PKCS12_PASSWORD');

        // ===== Load Private Key WITH Password =====
        $privateKey = openssl_pkey_get_private(
            file_get_contents($this->privateKeyPath),
            $password
        );

        if (!$privateKey) {
            throw new \Exception("Unable to load private key. Check PKCS12_PASSWORD or key file.");
        }

        // ===== Load certificate =====
        $certContent = file_get_contents($this->certPath);
        $certClean = $this->cleanCert($certContent);

        // ===== Get certificate details for XAdES =====
        $certInfo = openssl_x509_parse($certContent);

        // Format issuerName: CN, OU, O, C
        $issuerParts = $certInfo['issuer'];
        $issuerName = sprintf(
            'CN=%s, OU=%s, O=%s, C=%s',
            $issuerParts['CN'] ?? '',
            $issuerParts['OU'] ?? '',
            $issuerParts['O'] ?? '',
            $issuerParts['C'] ?? ''
        );

        // Convert serial number hex to decimal string using BCMath
        $serialNumber = $this->hexToDecimal($certInfo['serialNumber']);

        $signingTime = gmdate('Y-m-d\TH:i:s\Z');

        // ===== Calculate digests =====
        $docDigest = $this->calculateDocumentDigest($xml);
        $certDigest = base64_encode(hash('sha256', base64_decode($certClean), true));

        // ===== Build SignedInfo =====
        $signedInfo = <<<XML
<ds:SignedInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
<ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
<ds:Reference Id="id-doc-signed-data" URI="">
<ds:Transforms>
<ds:Transform Algorithm="http://www.w3.org/TR/1999/REC-xpath-19991116">
<ds:XPath>not(//ancestor-or-self::ext:UBLExtensions)</ds:XPath>
</ds:Transform>
<ds:Transform Algorithm="http://www.w3.org/TR/1999/REC-xpath-19991116">
<ds:XPath>not(//ancestor-or-self::cac:Signature)</ds:XPath>
</ds:Transform>
<ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
</ds:Transforms>
<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
<ds:DigestValue>{$docDigest}</ds:DigestValue>
</ds:Reference>
<ds:Reference Type="http://www.w3.org/2000/09/xmldsig#SignatureProperties" URI="#id-xades-signed-props">
<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
<ds:DigestValue>{$certDigest}</ds:DigestValue>
</ds:Reference>
</ds:SignedInfo>
XML;

        // ===== Sign SignedInfo =====
        openssl_sign($signedInfo, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signatureValue = base64_encode($signature);

        // ===== Update the XML with new signature values =====
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $xpath->registerNamespace('xades', 'http://uri.etsi.org/01903/v1.3.2#');

        // Update DigestValue for document
        $docDigestNodes = $xpath->query("//ds:Reference[@Id='id-doc-signed-data']/ds:DigestValue");
        if ($docDigestNodes->length > 0) {
            $docDigestNodes->item(0)->nodeValue = $docDigest;
        }

        // Update DigestValue for signature properties
        $propsDigestNodes = $xpath->query("//ds:Reference[@Type='http://www.w3.org/2000/09/xmldsig#SignatureProperties']/ds:DigestValue");
        if ($propsDigestNodes->length > 0) {
            $propsDigestNodes->item(0)->nodeValue = $certDigest;
        }

        // Update SignatureValue
        $sigValueNodes = $xpath->query("//ds:SignatureValue");
        if ($sigValueNodes->length > 0) {
            $sigValueNodes->item(0)->nodeValue = $signatureValue;
        }

        // Update X509Certificate
        $certNodes = $xpath->query("//ds:X509Certificate");
        if ($certNodes->length > 0) {
            $certNodes->item(0)->nodeValue = $certClean;
        }

        // Update SigningTime
        $signingTimeNodes = $xpath->query("//xades:SigningTime");
        if ($signingTimeNodes->length > 0) {
            $signingTimeNodes->item(0)->nodeValue = $signingTime;
        }

        // Update XAdES certificate digest
        $xadesDigestNodes = $xpath->query("//xades:CertDigest/ds:DigestValue");
        if ($xadesDigestNodes->length > 0) {
            $xadesDigestNodes->item(0)->nodeValue = $certDigest;
        }

        // Update IssuerName
        $issuerNodes = $xpath->query("//ds:X509IssuerName");
        if ($issuerNodes->length > 0) {
            $issuerNodes->item(0)->nodeValue = $issuerName;
        }

        // Update SerialNumber
        $serialNodes = $xpath->query("//ds:X509SerialNumber");
        if ($serialNodes->length > 0) {
            $serialNodes->item(0)->nodeValue = $serialNumber;
        }

        return $xml->saveXML();
    }

    protected function calculateDocumentDigest(\DOMDocument $xml): string
    {
        $tempDoc = new \DOMDocument();
        $tempDoc->loadXML($xml->saveXML());

        $xpath = new \DOMXPath($tempDoc);
        $xpath->registerNamespace('ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

        foreach ($xpath->query('//ext:UBLExtensions') as $ext) {
            $ext->parentNode->removeChild($ext);
        }

        foreach ($xpath->query('//cac:Signature') as $sig) {
            $sig->parentNode->removeChild($sig);
        }

        $canonical = $tempDoc->C14N(true, false);
        return base64_encode(hash('sha256', $canonical, true));
    }

    protected function cleanCert($cert)
    {
        return str_replace([
            '-----BEGIN CERTIFICATE-----',
            '-----END CERTIFICATE-----',
            "\n",
            "\r",
            ' '
        ], '', $cert);
    }

    // ===== Hex to Decimal (for serial number) =====
    protected function hexToDecimal(string $hex): string
    {
        $hex = strtolower($hex);
        $dec = '0';
        $len = strlen($hex);

        for ($i = 0; $i < $len; $i++) {
            $current = hexdec($hex[$i]);
            $dec = bcmul($dec, '16', 0);
            $dec = bcadd($dec, $current, 0);
        }

        return $dec;
    }
}
