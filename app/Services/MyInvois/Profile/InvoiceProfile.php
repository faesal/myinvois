<?php
namespace App\Libraries\MyInvois\Profile;

class InvoiceProfile implements DocumentProfile
{
    public function code(): string { return '01'; }
    public function allowNegative(): bool { return false; }
    public function requireBillingReference(): bool { return false; }
}
