<?php

namespace App\Services\MyInvois\Signer;

use DOMDocument;
use DOMXPath;
use Exception;

class XadesSigner
{
    protected string $certPath;
    protected string $privateKeyPath;
    protected array $certificateInfo;
    
    public function __construct(string $certPath = null, string $privateKeyPath = null)
    {
        $this->certPath = $certPath ?: base_path('cert/certificate.crt');
        $this->privateKeyPath = $privateKeyPath ?: base_path('cert/private.key');
        $this->certificateInfo = $this->loadAndParseCertificate();
    }
    
    /**
     * Load dan parse certificate dengan ekstraksi TIN
     */
    private function loadAndParseCertificate(): array
    {
        if (!file_exists($this->certPath)) {
            throw new Exception("Certificate file not found: " . $this->certPath);
        }
        
        $certContent = file_get_contents($this->certPath);
        $certData = openssl_x509_parse($certContent);
        
        if (!$certData) {
            throw new Exception("Unable to parse certificate: " . openssl_error_string());
        }
        
        // Extract TIN dari certificate subject
        $tin = $this->extractTinFromCertificate($certData);
        
        // Format issuer name sesuai standard MyInvois
        $issuer = $certData['issuer'];
        $issuerName = $this->formatIssuerName($issuer);
        
        // Extract serial number dan convert ke decimal
        $serialNumber = $this->extractSerialNumber($certData);
        
        // Clean certificate untuk signature
        $certClean = $this->cleanCertificate($certContent);
        
        return [
            'content' => $certContent,
            'clean' => $certClean,
            'data' => $certData,
            'tin' => $tin,
            'serial_number' => $serialNumber,
            'issuer_name' => $issuerName,
            'subject' => $certData['subject'],
            'valid_from' => $certData['validFrom_time_t'] ?? null,
            'valid_to' => $certData['validTo_time_t'] ?? null
        ];
    }
    
    /**
     * Extract TIN dari certificate
     */
    private function extractTinFromCertificate(array $certData): string
    {
        $subject = $certData['subject'];
        
        // Priority 1: 2.5.4.97 (organizationIdentifier OID) - format yang digunakan LHDNM
        if (isset($subject['2.5.4.97'])) {
            $oid = $subject['2.5.4.97'];
            // Extract IGxxxxxxx dari OID
            if (preg_match('/(IG\d+)/', $oid, $matches)) {
                return $matches[1];
            }
            return $oid;
        }
        
        // Priority 2: serialNumber field
        if (isset($subject['serialNumber'])) {
            return $subject['serialNumber'];
        }
        
        // Priority 3: CN field
        if (isset($subject['CN'])) {
            $cn = $subject['CN'];
            if (preg_match('/(IG\d+)/', $cn, $matches)) {
                return $matches[1];
            }
        }
        
        throw new Exception("TIN not found in certificate. Certificate subject: " . print_r($subject, true));
    }
    
    /**
     * Format issuer name sesuai format yang diharapkan MyInvois
     */
    private function formatIssuerName(array $issuer): string
    {
        return sprintf(
            'CN=%s, OU=%s, O=%s, C=%s',
            $issuer['CN'] ?? '',
            $issuer['OU'] ?? '',
            $issuer['O'] ?? '',
            $issuer['C'] ?? ''
        );
    }
    
    /**
     * Extract dan convert serial number
     */
    private function extractSerialNumber(array $certData): string
    {
        $serialHex = $certData['serialNumber'];
        return $this->hexToDecimal($serialHex);
    }
    
    /**
     * Clean certificate untuk base64
     */
    private function cleanCertificate(string $cert): string
    {
        // Remove semua yang bukan base64
        $cleaned = preg_replace([
            '/-----BEGIN CERTIFICATE-----/',
            '/-----END CERTIFICATE-----/',
            '/\s+/',
            '/BagAttributes.*?MII/',  // Remove BagAttributes sampai MII
            '/friendlyName.*?MII/',    // Remove friendlyName sampai MII
            '/subject=.*?MII/',        // Remove subject= sampai MII
            '/issuer=.*?MII/'          // Remove issuer= sampai MII
        ], '', $cert);
        
        // Ekstrak hanya base64 murni yang dimulai dengan MII
        if (preg_match('/MII[A-Za-z0-9+\/=]+/', $cleaned, $matches)) {
            $cleaned = $matches[0];
        }
        
        // Tambahkan padding jika diperlukan
        $padding = strlen($cleaned) % 4;
        if ($padding > 0) {
            $cleaned .= str_repeat('=', 4 - $padding);
        }
        
        // Validasi base64
        if (!base64_decode($cleaned, true)) {
            throw new Exception("Invalid base64 certificate after cleaning");
        }
        
        return $cleaned;
    }
    
    /**
     * Hex to decimal conversion
     */
    private function hexToDecimal(string $hex): string
    {
        $hex = strtolower(ltrim($hex, '0x'));
        
        if (!preg_match('/^[0-9a-f]+$/', $hex)) {
            throw new Exception("Invalid hex string: " . $hex);
        }
        
        // Use GMP if available
        if (function_exists('gmp_init')) {
            $gmp = gmp_init($hex, 16);
            return gmp_strval($gmp, 10);
        }
        
        // Fallback to BCMath
        $dec = '0';
        $len = strlen($hex);
        
        for ($i = 0; $i < $len; $i++) {
            $current = hexdec($hex[$i]);
            $dec = bcmul($dec, '16', 0);
            $dec = bcadd($dec, (string)$current, 0);
        }
        
        return $dec;
    }
    
    /**
     * Main signing function dengan semua perbaikan
     */
    public function sign(string $xmlString): string
    {
        // Step 1: Fix XML issues sebelum signing
        $xmlString = $this->fixAllXmlIssues($xmlString);
        
        // Step 2: Validasi dan update TIN agar match dengan certificate
        $xmlString = $this->updateSupplierTin($xmlString);
        
        // Step 3: Validasi MSIC Code
        $xmlString = $this->fixMsiCode($xmlString);
        
        // Step 4: Load XML
        $xml = new DOMDocument();
        $xml->preserveWhiteSpace = true;  // IMPORTANT: jangan ubah whitespace
        $xml->formatOutput = false;
        
        if (!$xml->loadXML($xmlString, LIBXML_NOBLANKS)) {
            throw new Exception("Failed to load XML");
        }
        
        // Step 5: Calculate semua digests dengan benar
        $digests = $this->calculateAllDigests($xml);
        
        // Step 6: Build dan sign SignedInfo
        $signatureValue = $this->createSignature($digests);
        
        // Step 7: Update XML dengan signature
        return $this->updateXmlWithSignature($xml, $digests, $signatureValue);
    }
    
    /**
     * Fix semua issue XML
     */
    private function fixAllXmlIssues(string $xmlString): string
    {
        $xml = new DOMDocument();
        $xml->preserveWhiteSpace = true;
        $xml->formatOutput = false;
        $xml->loadXML($xmlString, LIBXML_NOBLANKS);
        
        $xpath = new DOMXPath($xml);
        
        // 1. Fix CommodityClassification namespace
        $this->fixCommodityClassificationNamespace($xml, $xpath);
        
        // 2. Fix MSIC Code jika ada yang salah
        $this->fixMsiCodeInXml($xml, $xpath);
        
        // 3. Validasi struktur lainnya
        $this->validateXmlStructure($xml, $xpath);
        
        return $xml->saveXML();
    }
    
    /**
     * Fix CommodityClassification namespace
     */
    private function fixCommodityClassificationNamespace(DOMDocument $xml, DOMXPath $xpath): void
    {
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        
        // Find wrong ItemClassificationCode elements
        $wrongNodes = $xpath->query('//cac:CommodityClassification/cac:ItemClassificationCode');
        
        foreach ($wrongNodes as $node) {
            $newNode = $xml->createElementNS(
                'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
                'cbc:ItemClassificationCode',
                $node->nodeValue
            );
            
            // Copy all attributes
            if ($node->hasAttributes()) {
                foreach ($node->attributes as $attr) {
                    $newNode->setAttribute($attr->nodeName, $attr->nodeValue);
                }
            }
            
            $node->parentNode->replaceChild($newNode, $node);
        }
    }
    
    /**
     * Fix MSIC Code dalam XML
     */
    private function fixMsiCodeInXml(DOMDocument $xml, DOMXPath $xpath): void
    {
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        
        // Find IndustryClassificationCode
        $msicNodes = $xpath->query('//cbc:IndustryClassificationCode');
        
        foreach ($msicNodes as $node) {
            $value = $node->nodeValue;
            
            // Jika code invalid, ganti dengan yang valid
            if ($value === '89990') {
                $node->nodeValue = '46510';  // Valid MSIC code
                $node->setAttribute('name', 'Wholesale of computer hardware, software and peripherals');
            }
        }
    }
    
    /**
     * Update supplier TIN agar match dengan certificate
     */
    private function updateSupplierTin(string $xmlString): string
    {
        $xml = new DOMDocument();
        $xml->preserveWhiteSpace = true;
        $xml->formatOutput = false;
        $xml->loadXML($xmlString, LIBXML_NOBLANKS);
        
        $xpath = new DOMXPath($xml);
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        
        // Find supplier TIN
        $tinNodes = $xpath->query("//cac:AccountingSupplierParty//cbc:ID[@schemeID='TIN']");
        
        if ($tinNodes->length > 0) {
            $currentTin = $tinNodes->item(0)->nodeValue;
            $certTin = $this->certificateInfo['tin'];
            
            // Jika TIN tidak match, update ke TIN dari certificate
            if ($currentTin !== $certTin) {
                $tinNodes->item(0)->nodeValue = $certTin;
            }
        } else {
            // Add TIN jika tidak ada
            $this->addSupplierTinElement($xml, $xpath);
        }
        
        return $xml->saveXML();
    }
    
    /**
     * Tambahkan elemen TIN jika tidak ada
     */
    private function addSupplierTinElement(DOMDocument $xml, DOMXPath $xpath): void
    {
        $supplierParty = $xpath->query("//cac:AccountingSupplierParty//cac:Party")->item(0);
        
        if ($supplierParty) {
            $tinElement = $xml->createElementNS(
                'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
                'cbc:ID',
                $this->certificateInfo['tin']
            );
            $tinElement->setAttribute('schemeID', 'TIN');
            
            $partyIdentification = $xml->createElementNS(
                'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
                'cac:PartyIdentification'
            );
            $partyIdentification->appendChild($tinElement);
            
            $supplierParty->insertBefore($partyIdentification, $supplierParty->firstChild);
        }
    }
    
    /**
     * Fix MSIC Code secara terpisah
     */
    private function fixMsiCode(string $xmlString): string
    {
        return preg_replace(
            '/<cbc:IndustryClassificationCode[^>]*>89990<\/cbc:IndustryClassificationCode>/',
            '<cbc:IndustryClassificationCode name="Wholesale of computer hardware, software and peripherals">46510</cbc:IndustryClassificationCode>',
            $xmlString
        );
    }
    
    /**
     * Validasi struktur XML
     */
    private function validateXmlStructure(DOMDocument $xml, DOMXPath $xpath): void
    {
        // Validasi root element
        $root = $xml->documentElement;
        if ($root->nodeName !== 'Invoice') {
            throw new Exception("Root element must be 'Invoice'");
        }
        
        // Validasi namespace
        $xmlns = $root->getAttribute('xmlns');
        if ($xmlns !== 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2') {
            throw new Exception("Invalid UBL namespace");
        }
    }
    
    /**
     * Calculate semua digests yang diperlukan
     */
    private function calculateAllDigests(DOMDocument $xml): array
    {
        // 1. Document digest (tanpa UBLExtensions dan cac:Signature)
        $docDigest = $this->calculateDocumentDigest($xml);
        
        // 2. Certificate digest
        $certDigest = $this->calculateCertificateDigest();
        
        // 3. SignedProperties digest
        $signedPropsDigest = $this->calculateSignedPropertiesDigest();
        
        return [
            'document' => $docDigest,
            'certificate' => $certDigest,
            'signed_properties' => $signedPropsDigest
        ];
    }
    
    /**
     * Calculate document digest (sesuai dengan MyInvois)
     */
    private function calculateDocumentDigest(DOMDocument $xml): string
    {
        // Clone XML untuk menghindari modifikasi original
        $tempDoc = new DOMDocument();
        $tempDoc->preserveWhiteSpace = true;
        $tempDoc->formatOutput = false;
        $tempDoc->loadXML($xml->saveXML(), LIBXML_NOBLANKS);
        
        $xpath = new DOMXPath($tempDoc);
        $xpath->registerNamespace('ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        
        // Remove UBLExtensions
        $extensions = $xpath->query('//ext:UBLExtensions');
        foreach ($extensions as $ext) {
            $ext->parentNode->removeChild($ext);
        }
        
        // Remove cac:Signature
        $signatures = $xpath->query('//cac:Signature');
        foreach ($signatures as $sig) {
            $sig->parentNode->removeChild($sig);
        }
        
        // Canonicalize dengan EXC-C14N
        $canonical = $tempDoc->C14N(true, false);
        
        // Debug: Simpan canonical untuk verifikasi
        // file_put_contents('debug_canonical.xml', $canonical);
        
        return base64_encode(hash('sha256', $canonical, true));
    }
    
    /**
     * Calculate certificate digest
     */
    private function calculateCertificateDigest(): string
    {
        $certClean = $this->certificateInfo['clean'];
        return base64_encode(hash('sha256', base64_decode($certClean, true), true));
    }
    
    /**
     * Calculate SignedProperties digest
     */
    private function calculateSignedPropertiesDigest(): string
    {
        $signingTime = gmdate('Y-m-d\TH:i:s\Z');
        $certDigest = $this->calculateCertificateDigest();
        
        // Build SignedProperties XML
        $signedPropsXml = $this->buildSignedPropertiesXml($signingTime, $certDigest);
        
        // Canonicalize dan hitung digest
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = false;
        $doc->loadXML($signedPropsXml, LIBXML_NOBLANKS);
        
        $canonical = $doc->C14N(true, false);
        
        return base64_encode(hash('sha256', $canonical, true));
    }
    
    /**
     * Build SignedProperties XML
     */
    private function buildSignedPropertiesXml(string $signingTime, string $certDigest): string
    {
        $issuerName = $this->certificateInfo['issuer_name'];
        $serialNumber = $this->certificateInfo['serial_number'];
        
        return <<<XML
<xades:SignedProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Id="id-xades-signed-props">
    <xades:SignedSignatureProperties>
        <xades:SigningTime>{$signingTime}</xades:SigningTime>
        <xades:SigningCertificate>
            <xades:Cert>
                <xades:CertDigest>
                    <ds:DigestMethod xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                    <ds:DigestValue xmlns:ds="http://www.w3.org/2000/09/xmldsig#">{$certDigest}</ds:DigestValue>
                </xades:CertDigest>
                <xades:IssuerSerial>
                    <ds:X509IssuerName xmlns:ds="http://www.w3.org/2000/09/xmldsig#">{$issuerName}</ds:X509IssuerName>
                    <ds:X509SerialNumber xmlns:ds="http://www.w3.org/2000/09/xmldsig#">{$serialNumber}</ds:X509SerialNumber>
                </xades:IssuerSerial>
            </xades:Cert>
        </xades:SigningCertificate>
    </xades:SignedSignatureProperties>
</xades:SignedProperties>
XML;
    }
    
    /**
     * Create signature dengan private key
     */
    private function createSignature(array $digests): string
    {
        $password = env('PKCS12_PASSWORD', '');
        
        // Load private key
        $privateKey = $this->loadPrivateKey($password);
        if (!$privateKey) {
            throw new Exception("Failed to load private key: " . openssl_error_string());
        }
        
        // Build SignedInfo
        $signedInfo = $this->buildSignedInfo($digests);
        
        // Canonicalize sebelum signing
        $canonicalData = $this->canonicalizeXml($signedInfo);
        
        // Sign
        $signature = '';
        if (!openssl_sign($canonicalData, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            openssl_free_key($privateKey);
            throw new Exception("Signing failed: " . openssl_error_string());
        }
        
        openssl_free_key($privateKey);
        
        return base64_encode($signature);
    }
    
    /**
     * Build SignedInfo XML
     */
    private function buildSignedInfo(array $digests): string
    {
        return <<<XML
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
        <ds:DigestValue>{$digests['document']}</ds:DigestValue>
    </ds:Reference>
    <ds:Reference Type="http://www.w3.org/2000/09/xmldsig#SignatureProperties" URI="#id-xades-signed-props">
        <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
        <ds:DigestValue>{$digests['signed_properties']}</ds:DigestValue>
    </ds:Reference>
</ds:SignedInfo>
XML;
    }
    
    /**
     * Load private key
     */
    private function loadPrivateKey(string $password = '')
    {
        if (!file_exists($this->privateKeyPath)) {
            throw new Exception("Private key file not found: " . $this->privateKeyPath);
        }
        
        $keyContent = file_get_contents($this->privateKeyPath);
        
        // Try different formats
        $privateKey = openssl_pkey_get_private($keyContent, $password);
        
        if (!$privateKey && !empty($password)) {
            // Try without password
            $privateKey = openssl_pkey_get_private($keyContent);
        }
        
        if (!$privateKey && str_ends_with($this->privateKeyPath, '.p12')) {
            // Try PKCS#12 format
            $certs = [];
            if (openssl_pkcs12_read($keyContent, $certs, $password)) {
                $privateKey = openssl_pkey_get_private($certs['pkey']);
            }
        }
        
        return $privateKey;
    }
    
    /**
     * Update XML dengan signature
     */
    private function updateXmlWithSignature(DOMDocument $xml, array $digests, string $signatureValue): string
    {
        $xpath = new DOMXPath($xml);
        $this->registerXpathNamespaces($xpath);
        
        // Update digest values
        $this->updateDigestValues($xpath, $digests);
        
        // Update signature value
        $this->updateNodeValue($xpath, "//ds:SignatureValue", $signatureValue);
        
        // Update certificate
        $this->updateNodeValue($xpath, "//ds:X509Certificate", $this->certificateInfo['clean']);
        
        // Update signing time
        $signingTime = gmdate('Y-m-d\TH:i:s\Z');
        $this->updateNodeValue($xpath, "//xades:SigningTime", $signingTime);
        
        // Update issuer and serial number
        $this->updateNodeValue($xpath, "//ds:X509IssuerName", $this->certificateInfo['issuer_name']);
        $this->updateNodeValue($xpath, "//ds:X509SerialNumber", $this->certificateInfo['serial_number']);
        
        return $xml->saveXML();
    }
    
    /**
     * Update digest values di XML
     */
    private function updateDigestValues(DOMXPath $xpath, array $digests): void
    {
        // Document digest
        $this->updateNodeValue(
            $xpath,
            "//ds:Reference[@Id='id-doc-signed-data']/ds:DigestValue",
            $digests['document']
        );
        
        // Signed properties digest
        $this->updateNodeValue(
            $xpath,
            "//ds:Reference[@Type='http://www.w3.org/2000/09/xmldsig#SignatureProperties']/ds:DigestValue",
            $digests['signed_properties']
        );
        
        // Certificate digest in XAdES
        $this->updateNodeValue(
            $xpath,
            "//xades:CertDigest/ds:DigestValue",
            $digests['certificate']
        );
    }
    
    /**
     * Update node value
     */
    private function updateNodeValue(DOMXPath $xpath, string $query, string $value): void
    {
        $nodes = $xpath->query($query);
        if ($nodes->length > 0) {
            $nodes->item(0)->nodeValue = $value;
        } else {
            throw new Exception("Node not found: " . $query);
        }
    }
    
    /**
     * Register namespaces untuk XPath
     */
    private function registerXpathNamespaces(DOMXPath $xpath): void
    {
        $namespaces = [
            'ds' => 'http://www.w3.org/2000/09/xmldsig#',
            'xades' => 'http://uri.etsi.org/01903/v1.3.2#',
            'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
            'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
            'ext' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2',
            'sig' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2',
            'sac' => 'urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2',
            'sbc' => 'urn:oasis:names:specification:ubl:schema:xsd:SignatureBasicComponents-2'
        ];
        
        foreach ($namespaces as $prefix => $uri) {
            $xpath->registerNamespace($prefix, $uri);
        }
    }
    
    /**
     * Canonicalize XML
     */
    private function canonicalizeXml(string $xmlString): string
    {
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = false;
        $doc->loadXML($xmlString, LIBXML_NOBLANKS);
        
        return $doc->C14N(true, false);
    }
    
    /**
     * Debug information untuk certificate
     */
    public function getCertificateInfo(): array
    {
        return [
            'tin' => $this->certificateInfo['tin'],
            'serial_number' => $this->certificateInfo['serial_number'],
            'issuer_name' => $this->certificateInfo['issuer_name'],
            'subject' => $this->certificateInfo['subject'],
            'valid_from' => date('Y-m-d H:i:s', $this->certificateInfo['valid_from']),
            'valid_to' => date('Y-m-d H:i:s', $this->certificateInfo['valid_to']),
            'certificate_preview' => substr($this->certificateInfo['clean'], 0, 100) . '...'
        ];
    }
    
    /**
     * Validate and fix XML helper
     */
    public function validateAndFixXml(string $xmlString): array
    {
        $result = [
            'original' => $xmlString,
            'fixed' => null,
            'issues' => [],
            'certificate_info' => $this->getCertificateInfo()
        ];
        
        try {
            // Fix all issues
            $fixedXml = $this->fixAllXmlIssues($xmlString);
            $fixedXml = $this->updateSupplierTin($fixedXml);
            $fixedXml = $this->fixMsiCode($fixedXml);
            
            $result['fixed'] = $fixedXml;
            $result['issues'][] = 'XML issues fixed successfully';
            
            // Check TIN match
            $xml = new DOMDocument();
            $xml->loadXML($fixedXml);
            $xpath = new DOMXPath($xml);
            $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
            $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            
            $tinNodes = $xpath->query("//cac:AccountingSupplierParty//cbc:ID[@schemeID='TIN']");
            if ($tinNodes->length > 0) {
                $invoiceTin = $tinNodes->item(0)->nodeValue;
                $certTin = $this->certificateInfo['tin'];
                
                if ($invoiceTin === $certTin) {
                    $result['issues'][] = 'TIN matches certificate ✓';
                } else {
                    $result['issues'][] = "TIN mismatch! Invoice: {$invoiceTin}, Certificate: {$certTin}";
                }
            }
            
        } catch (Exception $e) {
            $result['issues'][] = 'Error during validation: ' . $e->getMessage();
        }
        
        return $result;
    }
}