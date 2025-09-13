<?php
/**
 * Zalo OA Auto Test (manual input first token, then auto refresh)
 * Author: ChatGPT (2025)
 */

$app_id       = "2069822998817314449";
$app_secret   = "3L469R3XZlNqEiT5M024";
$oa_id        = "530119453891460352";
$user_id      = "5170627724267093288"; 
$token_file   = __DIR__ . "/zalo_token.json";

// =========================
// Nếu chưa có token.json → nhập tay
// =========================
if (!file_exists($token_file)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $access_token  = trim($_POST['access_token']);
        $refresh_token = trim($_POST['refresh_token']);
        if ($access_token && $refresh_token) {
            $data = [
                "access_token"  => $access_token,
                "refresh_token" => $refresh_token,
                "expires_in"    => 86400,
                "created_at"    => time()
            ];
            file_put_contents($token_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "<h3>✅ Token đã được lưu! Refresh từ lần sau.</h3>";
        } else {
            echo "<h3>❌ Vui lòng nhập đủ Access Token và Refresh Token</h3>";
        }
    }

    if (!file_exists($token_file)) {
        echo '<form method="post">
            <label>Access Token lần đầu:</label><br>
            <textarea name="access_token" rows="3" cols="60"></textarea><br><br>
            <label>Refresh Token:</label><br>
            <textarea name="refresh_token" rows="3" cols="60"></textarea><br><br>
            <button type="submit">Lưu Token</button>
        </form>';
        exit;
    }
}

// =========================
// Load token từ file
// =========================
$token = json_decode(file_get_contents($token_file), true);

// =========================
// Nếu token hết hạn → refresh
// =========================
$now = time();
if ($now - $token['created_at'] >= ($token['expires_in'] - 60)) {
    $url = "https://oauth.zaloapp.com/v4/oa/access_token";
    $data = [
        "app_id"        => $app_id,
        "app_secret"    => $app_secret,
        "refresh_token" => $token['refresh_token'],
        "grant_type"    => "refresh_token"
    ];

    $resp = call_api("POST", $url, $data, [], true);

    if (!empty($resp['access_token'])) {
        $resp['created_at'] = time();
        file_put_contents($token_file, json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $token = $resp;
        echo "<h3>🔄 Token đã refresh!</h3>";
    } else {
        die("<h3>❌ Refresh thất bại, vui lòng nhập lại token thủ công.</h3>");
    }
}

// =========================
// Gửi tin nhắn CS
// =========================
$access_token = $token['access_token'];
$url = "https://openapi.zalo.me/v3.0/oa/message/cs";

// Link mở lại Mini App
$miniAppLink = "https://zalo.me/s/{$oa_id}?openMiniApp={$app_id}";

$payload = [
    "recipient" => [
        "user_id" => $user_id
    ],
    "message" => [
        "attachment" => [
            "type" => "template",
            "payload" => [
                "template_type" => "button",
                "text" => "📢 Bạn có thông báo điểm danh từ câu lạc bộ ABC",
                "buttons" => [
                    [
                        "title"   => "Vào điểm danh",
                        "type"    => "oa.open.url",
                        "payload" => [
                            "url" => $miniAppLink
                        ]
                    ]
                ]
            ]
        ]
    ]
];

$resp = call_api("POST", $url, json_encode($payload), [
    "Authorization: Bearer " . $access_token,
    "Content-Type: application/json"
]);

echo "<h3>📩 Send Message Response:</h3>";
echo "<pre>";
print_r($resp);
echo "</pre>";

// =========================
// Helper
// =========================
function call_api($method, $url, $data = [], $headers = [], $isForm = false)
{
    $ch = curl_init();
    if ($method == "POST") {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($isForm) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            $headers[] = "Content-Type: application/x-www-form-urlencoded";
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}
