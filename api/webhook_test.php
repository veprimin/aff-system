<?php
// Utility script to replay webhook payloads for local/production-safe verification.

$defaultTarget = $_GET['target'] ?? ($argv[2] ?? 'http://localhost/clinicsecret/api/webhook.php');
$mode = $_GET['mode'] ?? ($argv[1] ?? 'sample');

$sampleJsonPayload = [
    'event' => 'order.completed',
    'data' => [
        'order' => [
            'id' => 'sample-order-123',
            'product_id' => 'demo-product-1',
            'total' => 49.99,
            'referral_code' => 'TESTREF',
            'tracking_id' => 'track-demo-1',
        ],
        'customer' => [
            'email' => 'tester@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
        ],
        'subscription' => [
            'id' => 'sample-sub-001',
        ],
    ],
];

$sampleFormPayload = http_build_query([
    'event' => 'order.completed',
    'order_id' => 'form-order-456',
    'product_id' => 'form-product-9',
    'total' => '19.00',
    'referral_code' => 'FORMREF',
    'tracking_id' => 'form-track-2',
    'email' => 'form@example.com',
    'first_name' => 'Form',
    'last_name' => 'Poster',
]);

function latest_raw_payload(): ?string {
    $logFile = __DIR__ . '/webhook_raw.log';
    if (!file_exists($logFile)) {
        return null;
    }

    $raw = file_get_contents($logFile);
    $chunks = array_values(array_filter(explode("-----\n", $raw)));
    if (empty($chunks)) {
        return null;
    }

    $last = end($chunks);
    // drop metadata line if present
    $parts = explode("\n", $last, 2);
    if (count($parts) === 2) {
        return $parts[1];
    }

    return $last;
}

function send_payload(string $target, string $body, string $contentType): array {
    if (!function_exists('curl_init')) {
        return [
            'sent' => false,
            'status' => null,
            'response' => 'cURL extension not available; payload printed only',
        ];
    }

    $ch = curl_init($target);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: ' . $contentType],
        CURLOPT_POSTFIELDS => $body,
    ]);

    $resp = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return [
        'sent' => true,
        'status' => $status,
        'response' => $resp,
    ];
}

if ($mode === 'replay') {
    $raw = latest_raw_payload();
    if ($raw === null) {
        $output = ['error' => 'No raw payloads logged yet.'];
    } else {
        $output = send_payload($defaultTarget, $raw, 'application/json');
        $output['target'] = $defaultTarget;
        $output['body'] = $raw;
    }
} else {
    $jsonBody = json_encode($sampleJsonPayload, JSON_PRETTY_PRINT);
$output = send_payload($defaultTarget, $jsonBody, 'application/json');
$output['target'] = $defaultTarget;
$output['body'] = $jsonBody;
    $output['form_example'] = [
        'content_type' => 'application/x-www-form-urlencoded',
        'body' => $sampleFormPayload,
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'mode' => $mode,
    'target' => $defaultTarget,
    'payload_preview' => $output['body'] ?? null,
    'result' => $output,
    'curl_commands' => [
        'json' => "curl -i -X POST '" . $defaultTarget . "' -H 'Content-Type: application/json' --data '" . str_replace("'", "'\\''", $output['body'] ?? json_encode($sampleJsonPayload)) . "'",
        'form' => "curl -i -X POST '" . $defaultTarget . "' -H 'Content-Type: application/x-www-form-urlencoded' --data '" . $sampleFormPayload . "'",
    ],
]);
