<?php
// ==========================================
// 👑 MEENA DYNASTY - VIP PRIVACY PROXY WALL
// INTEGRATION: MYSQL + FIREBASE + META API
// ==========================================

$verify_token    = "Meena_Biodata_Secure_Token_123";

// 👇 आपका नया परमानेंट टोकन यहाँ सेट कर दिया गया है 👇
$access_token    = "EAAOqLdrjEfIBSDRmNyo7ujfpMb9ixZAILCdajTiBDtKAil2oghvG07SpQN6Hr2M8f6ZBFCRBBYtOZAL2j9TZAgk5hktZCZC6JlG5yf89ZACLpvYc12kFQt6rmTdfpcQcBQqqwSOhK8Dx7j6BMOcuSvqaBnQnYUiZC6mlXENk2UZApzrW6Gc7hqTdtPO1FhdWCcMxxDQZDZD";

// 👇 आपकी सही Phone Number ID 👇
$phone_number_id = "1168343493037440"; 

$firebase_url    = "https://meena-marriage-default-rtdb.asia-southeast1.firebasedatabase.app";
$firebase_secret = "KLEHB8GIs2PxUIobazUAGHsObWz2AT1Gtqjk83tV"; 

// ------------------------------------------
// 1. META WEBHOOK VERIFICATION (GET)
// ------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_mode'])) {
    if ($_GET['hub_mode'] === 'subscribe' && $_GET['hub_verify_token'] === $verify_token) {
        echo $_GET['hub_challenge'];
        http_response_code(200);
        exit;
    }
}

// ------------------------------------------
// 2. DATABASE HELPER
// ------------------------------------------
function get_db_connection() {
    $db_host = "sql211.infinityfree.com";
    $db_user = "if0_40880172";
    $db_pass = "Rishi7665";
    $db_name = "if0_40880172_dynasty_bot";

    $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) { return false; }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// ------------------------------------------
// 3. API HELPERS
// ------------------------------------------
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
    $url = 'https://graph.facebook.com/v19.0/' . $phone_id . '/messages';
    $data = ['messaging_product' => 'whatsapp', 'to' => $to, 'type' => $type];

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
    curl_exec($ch);
    curl_close($ch);
}

// ------------------------------------------
// 4. MESSAGE HANDLING (POST)
// ------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
        $msg_obj = $data['entry'][0]['changes'][0]['value']['messages'][0];
        
        if ($msg_obj['type'] === 'interactive') {
            $sender_number = $msg_obj['from'];
            $button_id = $msg_obj['interactive']['button_reply']['id'];
            
            $session_info = firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'GET');
            
            if (!empty($session_info) && isset($session_info['target'])) {
                $target = $session_info['target'];
                
                if ($button_id === 'btn_accept') {
                    $conn = get_db_connection();
                    if($conn) {
                        $stmt = $conn->prepare("INSERT INTO connection_requests (sender_phone, receiver_phone, status) VALUES (?, ?, 'pending')");
                        $stmt->bind_param("ss", $sender_number, $target);
                        $stmt->execute();
                        $stmt->close();
                    }
                    send_whatsapp_api($sender_number, 'text', "✅ *कनेक्शन सफल!*\nअब आप यहाँ जो भी लिखेंगे, वह सुरक्षित रूप से उन्हें भेज दिया जाएगा।\n\n*(चैट बंद करने के लिए किसी भी समय 'STOP' टाइप करें)*", $phone_number_id, $access_token);
                    send_whatsapp_api($target, 'text', "🎉 सामने वाले ने आपसे बात करना स्वीकार कर लिया है! अब आप अपना मैसेज भेज सकते हैं।\n\n*(चैट बंद करने के लिए किसी भी समय 'STOP' टाइप करें)*", $phone_number_id, $access_token);
                } else {
                    send_whatsapp_api($sender_number, 'text', "❌ आपने इस चैट को मना कर दिया है।", $phone_number_id, $access_token);
                    send_whatsapp_api($target, 'text', "माफ़ करें, सामने वाले यूज़र अभी बात करने के लिए उपलब्ध नहीं हैं।", $phone_number_id, $access_token);
                    firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'DELETE');
                    firebase_request($firebase_url . '/whatsapp_sessions/' . $target . '.json', 'DELETE');
                }
            }
        }
        elseif ($msg_obj['type'] === 'text') {
            $sender_number = $msg_obj['from'];
            $message_text = trim($msg_obj['text']['body']); 

            if (strtoupper($message_text) === 'STOP' || $message_text === 'स्टॉप') {
                $session_info = firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'GET');
                if (!empty($session_info) && isset($session_info['target'])) {
                    $target = $session_info['target'];
                    firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'DELETE');
                    firebase_request($firebase_url . '/whatsapp_sessions/' . $target . '.json', 'DELETE');
                    send_whatsapp_api($sender_number, 'text', "🚫 आपकी चैट सुरक्षित रूप से समाप्त कर दी गई है।", $phone_number_id, $access_token);
                    send_whatsapp_api($target, 'text', "🚫 सामने वाले यूज़र ने चैट समाप्त कर दी है।", $phone_number_id, $access_token);
                }
            }
            elseif (preg_match('/Profile ID:\s*([A-Za-z0-9_-]+).*?My ID:\s*([A-Za-z0-9_-]+)/is', $message_text, $matches)) {
                $target_id = trim($matches[1]);
                $sender_id = trim($matches[2]);
                
                $target_data = firebase_request($firebase_url . '/profiles_v200.json?orderBy="id"&equalTo="' . $target_id . '"', 'GET');
                
                if (!empty($target_data) && !isset($target_data['error'])) {
                    $t_profile = $target_data[array_key_first($target_data)];
                    $raw_phone = $t_profile['phone'] ?? $t_profile['mobile'] ?? '';
                    
                    if (!empty($raw_phone)) {
                        $clean_phone = preg_replace('/\D/', '', $raw_phone);
                        $target_number = "91" . substr($clean_phone, -10);

                        $session_data = [
                            $sender_number => ['target' => $target_number, 'time' => time()],
                            $target_number => ['target' => $sender_number, 'time' => time()]
                        ];
                        firebase_request($firebase_url . '/whatsapp_sessions.json', 'PATCH', $session_data);

                        $vip_message = "💛👑 *MEENA DYNASTY* 👑💛\nनमस्कार 🙏\nकिसी ने आपकी प्रोफाइल में रूचि दिखाई है।\n\nआपकी प्रोफाइल लिंक: https://meenabiodata.infinityfree.me/?id={$target_id}";
                        
                        send_whatsapp_api($target_number, 'interactive', $vip_message, $phone_number_id, $access_token);
                        send_whatsapp_api($sender_number, 'text', "✅ आपकी रिक्वेस्ट सफलतापूर्वक Profile ID: {$target_id} को भेज दी गई है!", $phone_number_id, $access_token);
                    }
                } else {
                    send_whatsapp_api($sender_number, 'text', "⚠️ यह Profile ID डेटाबेस में नहीं मिली। कृपया सही ID जाँचे।", $phone_number_id, $access_token);
                }
            } 
            elseif (strtolower($message_text) === 'hi' || strtolower($message_text) === 'hello') {
                send_whatsapp_api($sender_number, 'text', "नमस्ते! 🙏 Meena Dynasty Bot सक्रिय है। किसी से जुड़ने के लिए कृपया इस फॉर्मेट में मैसेज भेजें:\n\nProfile ID: [सामने वाले की ID]\nMy ID: [आपकी ID]", $phone_number_id, $access_token);
            }
            else {
                $session_info = firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'GET');
                if (!empty($session_info) && isset($session_info['target'])) {
                    send_whatsapp_api($session_info['target'], 'text', $message_text, $phone_number_id, $access_token);
                }
            }
        }
    }
    
    http_response_code(200);
    echo "OK";
    exit;
}
?>
