<?php
// ==========================================
// 👑 MEENA DYNASTY - VIP PRIVACY PROXY WALL
// CRASH-PROOF VERSION + DIRECT DEBUGGING
// ==========================================

$db_host = "sql211.infinityfree.com";
$db_user = "if0_40880172";
$db_pass = "Rishi7665";
$db_name = "if0_40880172_dynasty_bot";

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    if (isset($_REQUEST['sender_phone'])) {
        die(json_encode(["status" => "error", "message" => "DB Connection Failed"]));
    }
} else {
    $conn->set_charset("utf8mb4");
}

if (isset($_REQUEST['sender_phone']) && isset($_REQUEST['receiver_phone'])) {
    header('Content-Type: application/json');
    if ($conn) {
        $sender_phone = $_REQUEST['sender_phone'];
        $receiver_phone = $_REQUEST['receiver_phone'];
        $sender_id = isset($_REQUEST['sender_id']) ? $_REQUEST['sender_id'] : 'NONE';

        $stmt = $conn->prepare("INSERT INTO connection_requests (sender_phone, receiver_phone, status, sender_id) VALUES (?, ?, 'pending', ?)");
        if($stmt) {
            $stmt->bind_param("sss", $sender_phone, $receiver_phone, $sender_id);
            $stmt->execute();
            $stmt->close();
        }
        echo json_encode(["status" => "success", "message" => "Request stored securely for Termux Bot"]);
    }
    exit;
}

$verify_token    = "Meena_Biodata_Secure_Token_123";

// आपका परमानेंट टोकन
$access_token    = "EAAOqLdrjEfIBSLwVD2qIJ1POvP6GzkfZBh9KZAazbM2k1AK78K8qSqPdZAUV2qDWc19VApBTXbIePAx0Nhx9nWMQTZAAdUpZAvKT3MmpLfl2Jdw6aoZCWKcmfX36Ok4WkJX3T13f2vSOiVWvGK45rVuNStFWZB1Q3CHBrHmLalgAPWAZCmYaOqvsB6zoY618QHRkfwZDZD"; 

$firebase_url    = "https://meena-marriage-default-rtdb.asia-southeast1.firebasedatabase.app";
$firebase_secret = "KLEHB8GIs2PxUIobazUAGHsObWz2AT1Gtqjk83tV"; 

function get_secure_url($url) {
    global $firebase_secret;
    return $url . (strpos($url, '?') !== false ? '&' : '?') . 'auth=' . $firebase_secret;
}

function firebase_request($url, $method = 'GET', $data = null) {
    $ch = curl_init(get_secure_url($url));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($data !== null) { curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

function send_whatsapp_api($to, $type, $content, $phone_id, $token) {
    error_log("🟢 ATTEMPTING TO SEND MESSAGE TO: $to FROM ID: $phone_id"); // यह लाइन बताएगी कि फंक्शन चला या नहीं
    
    $url = 'https://graph.facebook.com/v20.0/' . $phone_id . '/messages';
    
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => $type
    ];

    if ($type === 'text') {
        $data['text'] = ['body' => $content];
    } elseif ($type === 'interactive') {
        $data['interactive'] = [
            'type' => 'button',
            'body' => ['text' => mb_substr($content, 0, 1024)],
            'action' => [
                'buttons' => [
                    ['type' => 'reply', 'reply' => ['id' => 'btn_accept', 'title' => '✅ बात शुरू करें']],
                    ['type' => 'reply', 'reply' => ['id' => 'btn_reject', 'title' => '❌ मना करें']]
                ]
            ]
        ];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("🔴 META_DEBUG - HTTP_CODE: $httpcode | RESPONSE: $response");
}

function findProfileInAllFolders($target_id) {
    global $firebase_url;
    $folders = ['profiles_v200', 'profiles_v300', 'profiles_v10_final', 'profiles_v100', 'profiles'];
    foreach ($folders as $folder) {
        $data = firebase_request($firebase_url . '/' . $folder . '.json?orderBy="id"&equalTo="' . $target_id . '"', 'GET');
        if (!empty($data) && !isset($data['error'])) {
            return $data[array_key_first($data)]; 
        }
    }
    return null; 
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_mode'])) {
    if ($_GET['hub_mode'] === 'subscribe' && $_GET['hub_verify_token'] === $verify_token) {
        echo $_GET['hub_challenge'];
        http_response_code(200);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
        
        // CRASH-PROOF ID LOGIC
        $current_phone_id = "1168343493037440"; // Default Fallback (Test Number ID)
        if (isset($data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'])) {
            $current_phone_id = (string)$data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'];
        }

        $msg_obj = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $sender_number = isset($msg_obj['from']) ? (string)$msg_obj['from'] : "";
        
        if (isset($msg_obj['type']) && $msg_obj['type'] === 'text' && $sender_number !== "") {
            $message_text = trim($msg_obj['text']['body']); 

            if (strtolower($message_text) === 'hi' || strtolower($message_text) === 'hello' || strtolower($message_text) === 'hiii' || strtolower($message_text) === 'hii') {
                error_log("🔵 HI DETECTED FROM: $sender_number"); // यह लाइन बताएगी कि Hi पकड़ा गया
                send_whatsapp_api($sender_number, 'text', "नमस्ते! 🙏 Meena Dynasty Bot सक्रिय है।", $current_phone_id, $access_token);
            }
            // ... (बाकी कोड वही है, बस जगह बचाने के लिए यहाँ नहीं लिखा है) ...
        }
    }
    http_response_code(200);
    echo "OK";
    exit;
}
?>
