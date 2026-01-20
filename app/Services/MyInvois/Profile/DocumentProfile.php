<?php
namespace App\Libraries\MyInvois\Profile;

interface DocumentProfile
{
    public function code(): string;
    public function allowNegative(): bool;
    public function requireBillingReference(): bool;
}
