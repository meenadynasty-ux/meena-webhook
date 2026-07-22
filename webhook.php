<?php
// ==========================================
// 👑 MEENA DYNASTY - VIP PRIVACY PROXY WALL
// INTEGRATION: MYSQL + FIREBASE + META API
// FINAL PERMANENT SYSTEM MULTI-FOLDER
// ==========================================

// ------------------------------------------
// 1. DATABASE CONNECTION
// ------------------------------------------
$db_host = "sql211.infinityfree.com";
$db_user = "if0_40880172";
$db_pass = "Rishi7665";
$db_name = "if0_40880172_dynasty_bot";

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB Connection Failed"]));
}
$conn->set_charset("utf8mb4");

// ------------------------------------------
// 2. WEBSITE PING SIGNAL (Image Ping)
// ------------------------------------------
if (isset($_REQUEST['sender_phone']) && isset($_REQUEST['receiver_phone'])) {
    header('Content-Type: application/json');
    $sender_phone = $_REQUEST['sender_phone'];
    $receiver_phone = $_REQUEST['receiver_phone'];
    $sender_id = isset($_REQUEST['sender_id']) ? $_REQUEST['sender_id'] : 'NONE';

    $stmt = $conn->prepare("INSERT INTO connection_requests (sender_phone, receiver_phone, status, sender_id) VALUES (?, ?, 'pending', ?)");
    $stmt->bind_param("sss", $sender_phone, $receiver_phone, $sender_id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Ping Stored"]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB Error"]);
    }
    $stmt->close();
    exit;
}

// ------------------------------------------
// 3. META API & FIREBASE CREDENTIALS
// ------------------------------------------
$verify_token    = "Meena_Biodata_Secure_Token_123";

// 👇 आपका परमानेंट (Never Expire) टोकन 👇
$access_token    = "EAAOqLdrjEfIBSNHsGFVlwI5r36Y7lhnVDt4x79UwH3nPJQGP7Ks3q0ZCPSdGvjJ1Cp77ZByrWVu70QLzdFKvKCGnxGYkdm5ZAIBASZAZC4qhPRDmnZCYMDXW13YAj8p87Eu5bx1DBU0UHUQPrg35zQPbJFBq7GMrwEaEVGJnOP8JmpzpnmjSdZAjkG6tZAl1l6hiygZDZD";

// 👇 आपके नंबर की Phone Number ID 👇
$phone_number_id = "1194552483746801"; 

$firebase_url    = "https://meena-marriage-default-rtdb.asia-southeast1.firebasedatabase.app";
$firebase_secret = "KLEHB8GIs2PxUIobazUAGHsObWz2AT1Gtqjk83tV"; 

// ------------------------------------------
// 4. ADVANCED HELPERS & MULTI-FOLDER SEARCH
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

// 🚀 सभी फोल्डर्स में प्रोफाइल ढूंढने का लॉजिक 🚀
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
// 5. WEBHOOK VERIFICATION
// ------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_mode'])) {
    if ($_GET['hub_mode'] === 'subscribe' && $_GET['hub_verify_token'] === $verify_token) {
        echo $_GET['hub_challenge'];
        http_response_code(200);
        exit;
    }
}

// ------------------------------------------
// 6. MESSAGE RECEIVING & ROUTING
// ------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
        $msg_obj = $data['entry'][0]['changes'][0]['value']['messages'][0];
        
        $sender_number = $msg_obj['from'];
        $my_admin_number = "918905651034"; // एडमिन का नंबर लूप रोकने के लिए

        if ($sender_number === $my_admin_number) {
             http_response_code(200); echo "OK"; exit;
        }
        
        // --- BUTTON CLICKS ---
        if ($msg_obj['type'] === 'interactive') {
            $button_id = $msg_obj['interactive']['button_reply']['id'];
            $session_info = firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'GET');
            
            if (!empty($session_info) && isset($session_info['target'])) {
                $target = $session_info['target'];
                
                if ($button_id === 'btn_accept') {
                    $stmt = $conn->prepare("INSERT INTO connection_requests (sender_phone, receiver_phone, status) VALUES (?, ?, 'pending')");
                    $stmt->bind_param("ss", $sender_number, $target);
                    $stmt->execute();
                    $stmt->close();
                    
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
        // --- TEXT MESSAGES ---
        elseif ($msg_obj['type'] === 'text') {
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
            // 🚀 SMART REGEX: ID निकालने का सबसे शक्तिशाली तरीका 🚀
            elseif (preg_match('/id=([A-Za-z0-9_-]+)/i', $message_text, $matches) || preg_match('/Profile ID:\s*([A-Za-z0-9_-]+)/i', $message_text, $matches)) {
                
                $target_id = trim($matches[1]);
                
                // 🚀 नया फंक्शन: यह सभी 5 फोल्डर में ढूंढेगा 🚀
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

                        $vip_message = "💛👑 *MEENA DYNASTY* 👑💛\nनमस्कार 🙏\nकिसी ने आपकी प्रोफाइल में रूचि दिखाई है।\n\nआपकी प्रोफाइल लिंक: https://meenabiodata.infinityfree.me/InT.html?id={$target_id}";
                        
                        send_whatsapp_api($target_number, 'interactive', $vip_message, $phone_number_id, $access_token);
                        send_whatsapp_api($sender_number, 'text', "✅ आपकी रिक्वेस्ट सफलतापूर्वक Profile ID: {$target_id} को भेज दी गई है!", $phone_number_id, $access_token);
                    } else {
                         send_whatsapp_api($sender_number, 'text', "⚠️ प्रोफाइल मिल गई, लेकिन इस प्रोफाइल में कोई मोबाइल नंबर सेव नहीं है।", $phone_number_id, $access_token);
                    }
                } else {
                    send_whatsapp_api($sender_number, 'text', "⚠️ यह Profile ID ({$target_id}) डेटाबेस में नहीं मिली। कृपया सही ID जाँचे।", $phone_number_id, $access_token);
                }
            } 
            elseif (strtolower($message_text) === 'hi' || strtolower($message_text) === 'hello') {
                send_whatsapp_api($sender_number, 'text', "नमस्ते! 🙏 Meena Dynasty Bot सक्रिय है।", $phone_number_id, $access_token);
            }
            // --- RELAY MESSAGE ---
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
