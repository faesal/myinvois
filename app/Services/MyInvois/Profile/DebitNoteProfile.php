<?php
namespace App\Libraries\MyInvois\Profile;

class DebitNoteProfile implements DocumentProfile
{
    public function code(): string { return '03'; }
    public function allowNegative(): bool { return false; }
    public function requireBillingReference(): bool { return true; }
}
