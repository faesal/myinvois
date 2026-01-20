<?php
namespace App\Libraries\MyInvois\Profile;

class CreditNoteProfile implements DocumentProfile
{
    public function code(): string { return '02'; }
    public function allowNegative(): bool { return true; }
    public function requireBillingReference(): bool { return true; }
}
