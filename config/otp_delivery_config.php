<?php

return [
    'email_from' => 'noreply@shopsphere.local',
    'email_from_name' => 'ShopSphere',
    'sms_gateway_url' => 'https://www.fast2sms.com/dev/bulkV2',
    'sms_gateway_method' => 'POST',
    'sms_content_type' => 'form',
    'sms_to_field' => 'numbers',
    'sms_message_field' => 'message',
    'sms_token_field' => '',
    'sms_gateway_token' => 'nQGMepCdcRfWkstaFNxq42bglyO86X9JIj7vDBuwYihEzHrKmA7woT9DbPJ0YLQ8syAhxlzMCVKu1HqG',
    'sms_auth_header' => 'authorization',
    'sms_auth_value' => 'nQGMepCdcRfWkstaFNxq42bglyO86X9JIj7vDBuwYihEzHrKmA7woT9DbPJ0YLQ8syAhxlzMCVKu1HqG',
    'sms_static_fields' => [
        'route' => 'q',
        'language' => 'english',
        'flash' => '0',
    ],
    'allow_local_otp_fallback' => true,
];
