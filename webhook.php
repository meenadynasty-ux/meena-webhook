<?php
// ==========================================
// 👑 MEENA DYNASTY - VIP PRIVACY PROXY WALL
// WITH ERROR LOGGING (CCTV CAMERA)
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

// आपका लेटेस्ट टोकन
$access_token    = "EAAOqLdrjEfIBSGAnIzvpPD9P3dJAy5ER8lnkldVIeeAveYQxZAIUflNYWHtAVFwa4tgQ8HJkVOWWdmZB4YGZA35QSvNY6KD7Xx5RPQm00tLEBVU96kN7ezFPFBPQLWGamkO2QWXAm191ACoVSXyBW40HGlHRzvWTEMRupafhBTIbPqLVgOhmrGvnZC4F08zTU6DE1Q7rLvyvUnSCpOUPIs6bEwq8FxLlLRrJrpxfEFx6DeB2XE3Ybh6MNuS7nrtMhGmDZCEX5bXvZA7KoKI0giEvvT6P7OdpqGfQIkwgZDZD"; 

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

    // 🔴 यह लाइनें Facebook का एरर सीधे Render में दिखाएंगी 🔴
    error_log("=== WHATSAPP SEND ATTEMPT ===");
    error_log("TO: " . $to . " | PHONE_ID: " . $phone_id);
    error_log("META RESPONSE CODE: " . $httpcode);
    error_log("META RESPONSE BODY: " . $response);
    error_log("=============================");
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
        
        $current_phone_id = "1168343493037440"; 
        if (isset($data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'])) {
            $current_phone_id = $data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'];
        }

        $msg_obj = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $sender_number = $msg_obj['from'];
        $my_admin_number = "918905651034"; 

        if ($sender_number === $my_admin_number) {
             http_response_code(200); echo "OK"; exit;
        }
        
        if ($msg_obj['type'] === 'text') {
            $message_text = trim($msg_obj['text']['body']); 

            if (strtolower($message_text) === 'hi' || strtolower($message_text) === 'hello' || strtolower($message_text) === 'hiii' || strtolower($message_text) === 'hii') {
                error_log("=> HI COMMAND TRIGGERED BY: " . $sender_number);
                send_whatsapp_api($sender_number, 'text', "नमस्ते! 🙏 Meena Dynasty Bot सक्रिय है।", $current_phone_id, $access_token);
            }
            elseif (preg_match('/id=([A-Za-z0-9_-]+)/i', $message_text, $matches) || preg_match('/Profile ID:\s*([A-Za-z0-9_-]+)/i', $message_text, $matches)) {
                $target_id = trim($matches[1]);
                $sender_id = 'NONE';
                if (preg_match('/My ID:\s*([A-Za-z0-9_-]+)/i', $message_text, $myIdMatches)) {
                    $sender_id = trim($myIdMatches[1]);
                }
                
                $t_profile = findProfileInAllFolders($target_id);
                
                if ($t_profile !== null) {
                    $raw_phone = $t_profile['phone'] ?? $t_profile['mobile'] ?? '';
                    if (!empty($raw_phone)) {
                        $clean_phone = preg_replace('/\D/', '', $raw_phone);
                        $target_number = "91" . substr($clean_phone, -10);

                        $session_data = [
                            $sender_number => ['target' => $target_number, 'time' => time()],
                            $target_number => ['target' => $sender_number, 'time' => time()]
                        ];
                        firebase_request($firebase_url . '/whatsapp_sessions.json', 'PATCH', $session_data);

                        $t_name = $t_profile['name'] ?? '-';
                        $vip_message = "💛👑 *MEENA DYNASTY* 👑💛\nनमस्कार 🙏\nकिसी ने आपकी प्रोफाइल में रूचि दिखाई है।\n\nआपकी प्रोफाइल लिंक: https://meenabiodata.infinityfree.me/InT.html?id={$target_id}";
                        
                        send_whatsapp_api($target_number, 'interactive', $vip_message, $current_phone_id, $access_token);
                        send_whatsapp_api($sender_number, 'text', "✅ आपकी रिक्वेस्ट सफलतापूर्वक Profile ID: {$target_id} को भेज दी गई है!", $current_phone_id, $access_token);
                    } else {
                        send_whatsapp_api($sender_number, 'text', "⚠️ प्रोफाइल में नंबर नहीं है।", $current_phone_id, $access_token);
                    }
                } else {
                    send_whatsapp_api($sender_number, 'text', "⚠️ Profile ID डेटाबेस में नहीं मिली।", $current_phone_id, $access_token);
                }
            } 
            else {
                $session_info = firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'GET');
                if (!empty($session_info) && isset($session_info['target'])) {
                    send_whatsapp_api($session_info['target'], 'text', $message_text, $current_phone_id, $access_token);
                }
            }
        }
    }
    http_response_code(200);
    echo "OK";
    exit;
}
?>
