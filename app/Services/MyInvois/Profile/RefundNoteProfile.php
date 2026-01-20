<?php
namespace App\Libraries\MyInvois\Profile;

class RefundNoteProfile implements DocumentProfile
{
    public function code(): string { return '14'; }
    public function allowNegative(): bool { return true; }
    public function requireBillingReference(): bool { return true; }
}
