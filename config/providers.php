<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Registry only: adapters deliberately do not make network calls until
 * credentials, contracts and a provider-specific implementation are supplied.
 */
function provider_registry(): array
{
    return [
        'mpesa' => ['type' => 'mobile_money', 'name' => 'Vodacom M-Pesa', 'enabled' => env_value('MAMBO_PROVIDER_MPESA_ENABLED') === '1'],
        'halopesa' => ['type' => 'mobile_money', 'name' => 'HaloPesa', 'enabled' => env_value('MAMBO_PROVIDER_HALOPESA_ENABLED') === '1'],
        'mixx_by_yas' => ['type' => 'mobile_money', 'name' => 'Mixx by Yas', 'enabled' => env_value('MAMBO_PROVIDER_MIXX_BY_YAS_ENABLED') === '1'],
        'airtelmoney' => ['type' => 'mobile_money', 'name' => 'Airtel Money', 'enabled' => env_value('MAMBO_PROVIDER_AIRTELMONEY_ENABLED') === '1'],
        'crdb' => ['type' => 'bank', 'name' => 'CRDB Bank', 'enabled' => env_value('MAMBO_PROVIDER_CRDB_ENABLED') === '1'],
        'nmb' => ['type' => 'bank', 'name' => 'NMB Bank', 'enabled' => env_value('MAMBO_PROVIDER_NMB_ENABLED') === '1'],
        'sms' => ['type' => 'sms', 'name' => 'SMS provider', 'enabled' => env_value('MAMBO_SMS_ENABLED') === '1'],
    ];
}

function payment_instruction(string $provider): string
{
    $number = env_value('MAMBO_MANUAL_PAYMENT_NUMBER', 'Configure merchant number');
    return sprintf('Manual payment is not processed online. Pay TZS to the approved %s merchant account (%s), then retain the transaction reference for staff confirmation.', $provider, $number);
}
