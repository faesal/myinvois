<?php
namespace App\Libraries\MyInvois;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InvoiceItem;
use App\Libraries\MyInvois\Builder\{HeaderBuilder, PartyBuilder, BillingReferenceBuilder, LineBuilder, TaxBuilder, MonetaryBuilder};
use App\Libraries\MyInvois\Converter\UblJsonToXml;
use App\Libraries\MyInvois\Signature\XadesSigner;
use App\Libraries\MyInvois\Profile\{InvoiceProfile, CreditNoteProfile, DebitNoteProfile, RefundNoteProfile};

class MyInvoisService
{
    protected function resolveProfile(Invoice $invoice)
    {
        return match ($invoice->invoice_type_code) {
            '02' => new CreditNoteProfile(),
            '03' => new DebitNoteProfile(),
            '14' => new RefundNoteProfile(),
            default => new InvoiceProfile(),
        };
    }

    public function generate(int $invoiceId): string
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $supplier = Customer::findOrFail($invoice->id_supplier);
        $buyer = Customer::findOrFail($invoice->id_customer);
        $items = InvoiceItem::where('id_invoice', $invoiceId)->get();

        $profile = $this->resolveProfile($invoice);

        $ubl = [
            '_D' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            '_A' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
            '_B' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
            'Invoice' => [[ ]]
        ];

        $node = &$ubl['Invoice'][0];

        $node += app(HeaderBuilder::class)->build($invoice, $profile);
        $node['AccountingSupplierParty'][] = app(PartyBuilder::class)->build($supplier);
        $node['AccountingCustomerParty'][] = app(PartyBuilder::class)->build($buyer);

        foreach ($items as $item) {
            $node['InvoiceLine'][] = app(LineBuilder::class)->build($item);
        }

        if ($ref = app(BillingReferenceBuilder::class)->build($invoice, $profile)) {
            $node += $ref;
        }

        $node['TaxTotal'][] = app(TaxBuilder::class)->build($invoice);
        $node['LegalMonetaryTotal'][] = app(MonetaryBuilder::class)->build($invoice);

        $xml = app(UblJsonToXml::class)->convert($ubl);

        if ($invoice->include_signature) {
            $xml = app(XadesSigner::class)->sign($xml);
        }

        return $xml;
    }
}
