<?php
namespace App\Libraries\MyInvois\Builder;

use App\Models\Customer;

class PartyBuilder
{
    public function build(Customer $c): array
    {
        return [
            'Party' => [[
                'PartyIdentification' => [
                    ['ID' => [[ '_' => $c->tin_no, 'schemeID' => 'TIN' ]]],
                ],
                'PostalAddress' => [[
                    'CityName' => [[ '_' => $c->city_name ]],
                    'PostalZone' => [[ '_' => $c->postal_zone ]],
                    'CountrySubentityCode' => [[ '_' => $c->country_subentity_code ]],
                    'AddressLine' => [
                        ['Line' => [[ '_' => $c->address_line_1 ]]],
                        ['Line' => [[ '_' => $c->address_line_2 ]]],
                        ['Line' => [[ '_' => $c->address_line_3 ]]]
                    ],
                    'Country' => [[
                        'IdentificationCode' => [[ '_' => 'MYS', 'listID' => 'ISO3166-1', 'listAgencyID' => '6' ]]
                    ]]
                ]],
                'PartyLegalEntity' => [[
                    'RegistrationName' => [[ '_' => $c->registration_name ]]
                ]]
            ]]
        ];
    }
}
