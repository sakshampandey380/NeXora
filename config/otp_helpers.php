<?php

function otp_delivery_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $configPath = __DIR__ . '/otp_delivery_config.php';
    $fileConfig = file_exists($configPath) ? require $configPath : [];
    $fileConfig = is_array($fileConfig) ? $fileConfig : [];

    $config = [
        'email_from' => trim((string) ($fileConfig['email_from'] ?? '')),
        'email_from_name' => trim((string) ($fileConfig['email_from_name'] ?? '')),
        'sms_gateway_url' => trim((string) ($fileConfig['sms_gateway_url'] ?? '')),
        'sms_gateway_method' => trim((string) ($fileConfig['sms_gateway_method'] ?? '')),
        'sms_content_type' => trim((string) ($fileConfig['sms_content_type'] ?? '')),
        'sms_to_field' => trim((string) ($fileConfig['sms_to_field'] ?? '')),
        'sms_message_field' => trim((string) ($fileConfig['sms_message_field'] ?? '')),
        'sms_token_field' => trim((string) ($fileConfig['sms_token_field'] ?? '')),
        'sms_gateway_token' => trim((string) ($fileConfig['sms_gateway_token'] ?? '')),
        'sms_auth_header' => trim((string) ($fileConfig['sms_auth_header'] ?? '')),
        'sms_auth_value' => trim((string) ($fileConfig['sms_auth_value'] ?? '')),
        'sms_static_fields' => $fileConfig['sms_static_fields'] ?? [],
        'allow_local_otp_fallback' => (bool) ($fileConfig['allow_local_otp_fallback'] ?? false),
    ];

    return $config;
}

function ensureOtpVerificationSchema(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS otp_verification (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phone VARCHAR(20) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'user',
            otp VARCHAR(6) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $roleColumn = $conn->query("SHOW COLUMNS FROM otp_verification LIKE 'role'");
    if ($roleColumn && $roleColumn->num_rows === 0) {
        $conn->query("ALTER TABLE otp_verification ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user' AFTER phone");
    }

    $createdAtColumn = $conn->query("SHOW COLUMNS FROM otp_verification LIKE 'created_at'");
    if ($createdAtColumn && $createdAtColumn->num_rows === 0) {
        $conn->query("ALTER TABLE otp_verification ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER expires_at");
    }
}

function maskPhoneNumber(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);

    if ($digits === '') {
        return 'Not available';
    }

    $visibleDigits = substr($digits, -4);
    $maskedLength = max(strlen($digits) - 4, 0);

    return str_repeat('*', $maskedLength) . $visibleDigits;
}

function maskEmailAddress(string $email): string
{
    $email = trim($email);

    if ($email === '' || strpos($email, '@') === false) {
        return 'Not available';
    }

    [$localPart, $domainPart] = explode('@', $email, 2);
    $localVisible = substr($localPart, 0, 1);
    $domainPieces = explode('.', $domainPart);
    $domainName = $domainPieces[0] ?? '';
    $domainSuffix = isset($domainPieces[1]) ? '.' . implode('.', array_slice($domainPieces, 1)) : '';
    $domainVisible = substr($domainName, 0, 1);

    return $localVisible . str_repeat('*', max(strlen($localPart) - 1, 1))
        . '@'
        . $domainVisible . str_repeat('*', max(strlen($domainName) - 1, 1))
        . $domainSuffix;
}

function clearOtpForPhoneRole(mysqli $conn, string $phone, string $role): void
{
    $stmt = $conn->prepare('DELETE FROM otp_verification WHERE phone = ? AND role = ?');
    $stmt->bind_param('ss', $phone, $role);
    $stmt->execute();
    $stmt->close();
}

function createVisibleOtp(mysqli $conn, string $phone, string $role, int $validMinutes = 10): array
{
    clearOtpForPhoneRole($conn, $phone, $role);

    $otp = (string) random_int(1000, 9999);
    $expiresAt = date('Y-m-d H:i:s', time() + ($validMinutes * 60));

    $stmt = $conn->prepare('INSERT INTO otp_verification (phone, role, otp, expires_at) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $phone, $role, $otp, $expiresAt);
    $stmt->execute();
    $stmt->close();

    return [
        'otp' => $otp,
        'expires_at' => $expiresAt,
    ];
}

function getOtpEmailSenderAddress(): string
{
    $config = otp_delivery_config();
    $configuredAddress = $config['email_from'] !== '' ? $config['email_from'] : trim((string) getenv('OTP_EMAIL_FROM'));

    if ($configuredAddress !== '' && filter_var($configuredAddress, FILTER_VALIDATE_EMAIL)) {
        return $configuredAddress;
    }

    return 'noreply@shopsphere.local';
}

function getOtpEmailSenderName(): string
{
    $config = otp_delivery_config();
    $configuredName = $config['email_from_name'] !== '' ? $config['email_from_name'] : trim((string) getenv('OTP_EMAIL_FROM_NAME'));
    return $configuredName !== '' ? $configuredName : 'ShopSphere';
}

function sendOtpEmail(string $email, string $name, string $otp, string $expiresAt, string $role): array
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'channel' => 'email',
            'error' => 'Registered email address is invalid.',
        ];
    }

    $subject = 'ShopSphere password reset OTP';
    $senderAddress = getOtpEmailSenderAddress();
    $senderName = getOtpEmailSenderName();
    $recipientName = trim($name) !== '' ? trim($name) : 'User';
    $expiresLabel = date('d M Y h:i A', strtotime($expiresAt));

    $htmlMessage = '
        <html>
        <body style="font-family:Segoe UI,Arial,sans-serif;background:#f8fafc;color:#0f172a;padding:24px;">
            <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:18px;padding:28px;border:1px solid #e2e8f0;">
                <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#2563eb;margin-bottom:12px;">ShopSphere Security</div>
                <h2 style="margin:0 0 12px;font-size:28px;">Password Reset OTP</h2>
                <p style="margin:0 0 16px;line-height:1.6;">Hello ' . htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') . ', use the OTP below to reset your ' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . ' password.</p>
                <div style="font-size:34px;font-weight:800;letter-spacing:.34em;background:#eff6ff;color:#1d4ed8;padding:18px 22px;border-radius:16px;text-align:center;">' . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . '</div>
                <p style="margin:16px 0 0;line-height:1.6;">This OTP expires on <strong>' . htmlspecialchars($expiresLabel, ENT_QUOTES, 'UTF-8') . '</strong>.</p>
                <p style="margin:12px 0 0;line-height:1.6;color:#475569;">If you did not request this password reset, please ignore this message.</p>
            </div>
        </body>
        </html>';

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $senderName . ' <' . $senderAddress . '>',
    ];

    $success = @mail($email, $subject, $htmlMessage, implode("\r\n", $headers));

    return [
        'success' => $success,
        'channel' => 'email',
        'error' => $success ? '' : 'Email delivery failed. Check your mail configuration.',
    ];
}

function sendOtpSms(string $phone, string $name, string $otp, string $expiresAt): array
{
    $config = otp_delivery_config();
    $gatewayUrl = $config['sms_gateway_url'] !== '' ? $config['sms_gateway_url'] : trim((string) getenv('OTP_SMS_GATEWAY_URL'));

    if ($gatewayUrl === '') {
        return [
            'success' => false,
            'channel' => 'sms',
            'error' => 'SMS gateway is not configured.',
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'channel' => 'sms',
            'error' => 'cURL is not available for SMS delivery.',
        ];
    }

    $method = strtoupper($config['sms_gateway_method'] !== '' ? $config['sms_gateway_method'] : trim((string) getenv('OTP_SMS_GATEWAY_METHOD')));
    $method = $method !== '' ? $method : 'POST';
    $contentType = strtolower($config['sms_content_type'] !== '' ? $config['sms_content_type'] : trim((string) getenv('OTP_SMS_CONTENT_TYPE')));
    $contentType = in_array($contentType, ['json', 'form'], true) ? $contentType : 'json';
    $toField = $config['sms_to_field'] !== '' ? $config['sms_to_field'] : trim((string) getenv('OTP_SMS_TO_FIELD'));
    $messageField = $config['sms_message_field'] !== '' ? $config['sms_message_field'] : trim((string) getenv('OTP_SMS_MESSAGE_FIELD'));
    $tokenField = $config['sms_token_field'] !== '' ? $config['sms_token_field'] : trim((string) getenv('OTP_SMS_TOKEN_FIELD'));
    $gatewayToken = $config['sms_gateway_token'] !== '' ? $config['sms_gateway_token'] : trim((string) getenv('OTP_SMS_GATEWAY_TOKEN'));
    $authHeader = $config['sms_auth_header'] !== '' ? $config['sms_auth_header'] : trim((string) getenv('OTP_SMS_AUTH_HEADER'));
    $authValue = $config['sms_auth_value'] !== '' ? $config['sms_auth_value'] : trim((string) getenv('OTP_SMS_AUTH_VALUE'));
    $staticFields = is_array($config['sms_static_fields']) ? $config['sms_static_fields'] : json_decode((string) getenv('OTP_SMS_STATIC_FIELDS'), true);
    $urlParts = parse_url($gatewayUrl);
    $gatewayHost = strtolower((string) ($urlParts['host'] ?? ''));
    $isFast2Sms = $gatewayHost !== '' && str_contains($gatewayHost, 'fast2sms.com');
    $queryParams = [];

    if (!empty($urlParts['query'])) {
        parse_str($urlParts['query'], $queryParams);
    }

    if ($gatewayToken === '' && isset($queryParams['authorization'])) {
        $gatewayToken = trim((string) $queryParams['authorization']);
    }

    if ($isFast2Sms && isset($queryParams['authorization'])) {
        unset($queryParams['authorization']);
        $gatewayUrl =
            ($urlParts['scheme'] ?? 'https') . '://' . ($urlParts['host'] ?? '') . ($urlParts['path'] ?? '') .
            ($queryParams !== [] ? '?' . http_build_query($queryParams) : '');
    }

    if ($isFast2Sms) {
        $contentType = 'form';
        $toField = 'numbers';

        if ($authHeader === '' && $gatewayToken !== '') {
            $authHeader = 'authorization';
            $authValue = $gatewayToken;
        }
    }

    $payload = is_array($staticFields) ? $staticFields : [];
    $payload[$toField !== '' ? $toField : 'phone'] = preg_replace('/\D+/', '', $phone);
    $payload[$messageField !== '' ? $messageField : 'message'] = sprintf(
        'ShopSphere OTP for %s password reset is %s. Valid until %s.',
        trim($name) !== '' ? trim($name) : 'your',
        $otp,
        date('d M Y h:i A', strtotime($expiresAt))
    );

    if ($isFast2Sms) {
        if (!isset($payload['route']) || trim((string) $payload['route']) === '') {
            $payload['route'] = 'q';
        }

        if (!isset($payload['language']) || trim((string) $payload['language']) === '') {
            $payload['language'] = 'english';
        }

        if (!isset($payload['flash']) || trim((string) $payload['flash']) === '') {
            $payload['flash'] = '0';
        }
    }

    if ($tokenField !== '' && $gatewayToken !== '' && !($isFast2Sms && strcasecmp($tokenField, 'authorization') === 0)) {
        $payload[$tokenField] = $gatewayToken;
    }

    $headers = [];
    $body = '';

    if ($contentType === 'form') {
        $body = http_build_query($payload);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    } else {
        $body = json_encode($payload);
        $headers[] = 'Content-Type: application/json';
    }

    if ($authHeader !== '' && $authValue !== '') {
        $headers[] = $authHeader . ': ' . $authValue;
    }

    $ch = curl_init($gatewayUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $success = $curlError === '' && $statusCode >= 200 && $statusCode < 300;
    $decodedResponse = json_decode((string) $response, true);
    $providerMessage = '';

    if (is_array($decodedResponse)) {
        foreach (['message', 'error_message', 'errors', 'msg'] as $key) {
            if (!isset($decodedResponse[$key])) {
                continue;
            }

            if (is_array($decodedResponse[$key])) {
                $providerMessage = trim((string) implode(', ', array_map('strval', $decodedResponse[$key])));
            } else {
                $providerMessage = trim((string) $decodedResponse[$key]);
            }

            if ($providerMessage !== '') {
                break;
            }
        }
    }

    $errorMessage = '';

    if (!$success) {
        if ($curlError !== '') {
            $errorMessage = $curlError;
        } else {
            $errorMessage = 'SMS gateway returned HTTP ' . $statusCode . '.';

            if ($providerMessage !== '') {
                $errorMessage .= ' Provider message: ' . $providerMessage;
            }
        }
    }

    return [
        'success' => $success,
        'channel' => 'sms',
        'error' => $errorMessage,
        'status_code' => $statusCode,
        'response' => $response,
    ];
}

function isSmsGatewayConfigured(): bool
{
    $config = otp_delivery_config();
    $gatewayUrl = $config['sms_gateway_url'] !== '' ? $config['sms_gateway_url'] : trim((string) getenv('OTP_SMS_GATEWAY_URL'));

    return $gatewayUrl !== '';
}

function isLocalOtpFallbackEnabled(): bool
{
    $config = otp_delivery_config();

    if (!empty($config['allow_local_otp_fallback'])) {
        return true;
    }

    $serverValues = [
        strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? ''))),
        strtolower(trim((string) ($_SERVER['SERVER_NAME'] ?? ''))),
        trim((string) ($_SERVER['REMOTE_ADDR'] ?? '')),
        trim((string) ($_SERVER['SERVER_ADDR'] ?? '')),
    ];

    foreach ($serverValues as $value) {
        if ($value === '') {
            continue;
        }

        if ($value === '::1' || $value === '127.0.0.1' || str_starts_with($value, 'localhost')) {
            return true;
        }

        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/', $value)) {
            return true;
        }
    }

    return false;
}

function deliverOtpToPhoneOnly(array $account, array $otpRecord): array
{
    $phone = trim((string) ($account['phone'] ?? ''));
    $name = trim((string) ($account['name'] ?? ''));
    $email = trim((string) ($account['email'] ?? ''));

    if ($phone === '') {
        return [
            'success' => false,
            'message' => 'Registered mobile number is required for OTP delivery.',
            'channels' => [],
        ];
    }

    $smsResult = sendOtpSms($phone, $name, $otpRecord['otp'], $otpRecord['expires_at']);

    if ($smsResult['success']) {
        return [
            'success' => true,
            'fallback' => false,
            'message' => 'OTP sent to your registered mobile number.',
            'delivery_channel' => 'sms',
            'delivery_label' => maskPhoneNumber($phone),
            'channels' => [
                'sms' => $smsResult,
            ],
        ];
    }

    if ($email !== '') {
        $emailResult = sendOtpEmail($email, $name, $otpRecord['otp'], $otpRecord['expires_at'], 'account');

        if ($emailResult['success']) {
            return [
                'success' => true,
                'fallback' => false,
                'message' => 'SMS delivery failed, so the OTP was sent to your registered email address.',
                'delivery_channel' => 'email',
                'delivery_label' => maskEmailAddress($email),
                'channels' => [
                    'sms' => $smsResult,
                    'email' => $emailResult,
                ],
            ];
        }
    }

    if (isLocalOtpFallbackEnabled()) {
        return [
            'success' => true,
            'fallback' => true,
            'message' => 'SMS delivery failed (' . $smsResult['error'] . '), but local OTP fallback is enabled. Use the OTP shown below to continue resetting the password.',
            'delivery_channel' => 'local',
            'delivery_label' => 'Visible only on this local reset page',
            'channels' => [
                'sms' => $smsResult,
            ],
        ];
    }

    return [
        'success' => false,
        'fallback' => false,
        'message' => 'OTP could not be delivered to the registered mobile number. SMS: ' . $smsResult['error'],
        'delivery_channel' => '',
        'delivery_label' => '',
        'channels' => [
            'sms' => $smsResult,
        ],
    ];
}

function getActiveOtp(mysqli $conn, string $phone, string $role): ?array
{
    $stmt = $conn->prepare(
        'SELECT otp, expires_at
         FROM otp_verification
         WHERE phone = ? AND role = ? AND expires_at >= NOW()
         ORDER BY id DESC
         LIMIT 1'
    );
    $stmt->bind_param('ss', $phone, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc() ?: null;
    $stmt->close();

    return $row;
}

function verifyVisibleOtp(mysqli $conn, string $phone, string $role, string $otp): bool
{
    $stmt = $conn->prepare(
        "SELECT otp, expires_at 
         FROM otp_verification 
         WHERE phone=? AND role=? 
         ORDER BY id DESC 
         LIMIT 1"
    );

    $stmt->bind_param("ss", $phone, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return false;
    }

    if ($row['otp'] !== $otp) {
        return false;
    }

    if (strtotime($row['expires_at']) < time()) {
        return false;
    }

    return true;
}
