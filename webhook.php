<?php
// ==========================================
// 👑 MEENA DYNASTY - VIP PRIVACY PROXY WALL
// INTEGRATION: MYSQL + FIREBASE + META API
// ==========================================

// ------------------------------------------
// 1. CONFIGURATION CREDENTIALS
// ------------------------------------------
$verify_token    = "Meena_Biodata_Secure_Token_123";
$access_token    = "EAAO9cTe8B0ABR0viE2AOTVqZAwXjNHwTqZCXlOJBQirEIULVaZAYHkg7NZAd9XRsuMfzFI2cJljriGEtR51fjtaIzkbpuoVTdUmZCcb4p2d8Ltu7h9ViNYTIg65l221TWZA5nlGkP6v6H1AngXwyyJzUF5V4Jt3m2ZBkiuZCrYoZAifVzBGGKRgpDeZAjHZAjINoIZAP9QZDZD";
$phone_number_id = "1181713018363171"; 

$firebase_url    = "https://meena-marriage-default-rtdb.asia-southeast1.firebasedatabase.app";
$firebase_secret = "KLEHB8GIs2PxUIobazUAGHsObWz2AT1Gtqjk83tV"; 

// ------------------------------------------
// 2. META WEBHOOK VERIFICATION (GET REQUEST)
// ------------------------------------------
// यह हिस्सा डेटाबेस से पूरी तरह अलग है ताकि Meta Verification 100% पास हो सके
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['hub_mode']) && isset($_GET['hub_verify_token'])) {
        if ($_GET['hub_mode'] === 'subscribe' && $_GET['hub_verify_token'] === $verify_token) {
            echo $_GET['hub_challenge'];
            http_response_code(200);
            exit;
        } else {
            http_response_code(403);
            echo "Token Mismatch";
            exit;
        }
    }
}

// ------------------------------------------
// 3. DATABASE HELPER FUNCTION (SAFE CONNECTION)
// ------------------------------------------
function get_db_connection() {
    $db_host = "sql211.infinityfree.com";
    $db_user = "if0_40880172";
    $db_pass = "Rishi7665";
    $db_name = "if0_40880172_dynasty_bot";

    // Silence errors to prevent crashes if remote MySQL fails
    $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        log_debug("DB Connection Failed: " . $conn->connect_error);
        return false;
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// ------------------------------------------
// 4. WEBSITE SIGNAL (API ENDPOINT)
// ------------------------------------------
if (isset($_REQUEST['sender_phone']) && isset($_REQUEST['receiver_phone'])) {
    header('Content-Type: application/json');
    $conn = get_db_connection();
    
    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "DB Connection Failed"]);
        exit;
    }

    $sender_phone = $_REQUEST['sender_phone'];
    $receiver_phone = $_REQUEST['receiver_phone'];
    $sender_id = isset($_REQUEST['sender_id']) ? $_REQUEST['sender_id'] : 'NONE';

    $stmt = $conn->prepare("INSERT INTO connection_requests (sender_phone, receiver_phone, status, sender_id) VALUES (?, ?, 'pending', ?)");
    $stmt->bind_param("sss", $sender_phone, $receiver_phone, $sender_id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Request stored securely"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database Error"]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// ------------------------------------------
// 5. ADVANCED HELPER FUNCTIONS
// ------------------------------------------
function log_debug($msg) {
    file_put_contents('whatsapp_log.txt', date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

function get_secure_url($url) {
    global $firebase_secret;
    return $url . (strpos($url, '?') !== false ? '&' : '?') . 'auth=' . $firebase_secret;
}

function firebase_request($url, $method = 'GET', $data = null) {
    $ch = curl_init(get_secure_url($url));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

function send_whatsapp_api($to, $type, $content, $phone_id, $token) {
    $url = 'https://graph.facebook.com/v19.0/' . $phone_id . '/messages';
    
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
    curl_exec($ch);
    curl_close($ch);
}

// ------------------------------------------
// 6. MESSAGE RECEIVING & ROUTING (POST REQUEST)
// ------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
        $msg_obj = $data['entry'][0]['changes'][0]['value']['messages'][0];
        
        // ==========================================
        // BUTTON CLICKS (Interactive Reply)
        // ==========================================
        if ($msg_obj['type'] === 'interactive') {
            $sender_number = $msg_obj['from'];
            $button_id = $msg_obj['interactive']['button_reply']['id'];
            
            $session_info = firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'GET');
            
            if (!empty($session_info) && isset($session_info['target'])) {
                $target = $session_info['target'];
                
                if ($button_id === 'btn_accept') {
                    $conn = get_db_connection();
                    if ($conn) {
                        $stmt = $conn->prepare("INSERT INTO connection_requests (sender_phone, receiver_phone, status) VALUES (?, ?, 'pending')");
                        $stmt->bind_param("ss", $sender_number, $target);
                        $stmt->execute();
                        $stmt->close();
                        $conn->close();
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
        
        // ==========================================
        // NORMAL TEXT MESSAGES
        // ==========================================
        elseif ($msg_obj['type'] === 'text') {
            $sender_number = $msg_obj['from'];
            $message_text = trim($msg_obj['text']['body']); 

            if (strtoupper($message_text) === 'STOP' || $message_text === 'स्टॉप') {
                $session_info = firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'GET');
                if (!empty($session_info) && isset($session_info['target'])) {
                    $target = $session_info['target'];
                    firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'DELETE');
                    firebase_request($firebase_url . '/whatsapp_sessions/' . $target . '.json', 'DELETE');
                    
                    send_whatsapp_api($sender_number, 'text', "🚫 आपकी चैट सुरक्षित रूप से समाप्त कर दी गई है। अब आपके मैसेज आगे नहीं भेजे जाएंगे।", $phone_number_id, $access_token);
                    send_whatsapp_api($target, 'text', "🚫 सामने वाले यूज़र ने चैट समाप्त कर दी है। यह कनेक्शन अब बंद हो चुका है।", $phone_number_id, $access_token);
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

                        $t_name = $t_profile['name'] ?? '-';
                        $t_dob = $t_profile['dob'] ?? '-';
                        $t_age = $t_profile['ageCalc'] ?? $t_profile['age'] ?? '-';
                        $t_height = $t_profile['height'] ?? '-';
                        $t_job = $t_profile['jobType'] ?? '-';
                        if (!empty($t_profile['desig'])) $t_job .= ' (' . $t_profile['desig'] . ')';
                        
                        $t_gotras = array_filter([$t_profile['g1']??'', $t_profile['g2']??'', $t_profile['g3']??'', $t_profile['g4']??'']);
                        $t_gotra_str = !empty($t_gotras) ? implode(" / ", $t_gotras) : '-';
                        $t_city = $t_profile['city'] ?? '-';
                        $t_native = $t_profile['native'] ?? '-';

                        $vip_message = "༺👑═༻༺═👑༻\n💛👑 *MEENA DYNASTY* 👑💛\n✨ Meena Rishton Ka Sangam ✨🤝\n༺══════👑══════༻\n\n";
                        $vip_message .= "नमस्कार 🙏\n हमने आपकी प्रोफाइल:\n\n";
                        $vip_message .= "👤 Name: {$t_name}\n🎂 DOB/Age: {$t_dob} ({$t_age})\n📏 Height: {$t_height}\n";
                        $vip_message .= "💼 Job: {$t_job}\n🕉 Gotras: {$t_gotra_str}\n🏙 City: {$t_city}\n🏡 Native: {$t_native}\n\n";
                        $vip_message .= "*MEENA DYNASTY* पर देखी है।\n\n";
                        $vip_message .= "मेरा बायोडाटा ये है:\n\n";

                        if ($sender_id !== 'NONE') {
                            $sender_data = firebase_request($firebase_url . '/profiles_v200.json?orderBy="id"&equalTo="' . $sender_id . '"', 'GET');
                            if (!empty($sender_data) && !isset($sender_data['error'])) {
                                $s_profile = $sender_data[array_key_first($sender_data)];
                                $s_name = $s_profile['name'] ?? '-';
                                $s_job = $s_profile['jobType'] ?? '-';
                                $s_city = $s_profile['city'] ?? '-';
                                
                                $vip_message .= "👤 Name: {$s_name}\n💼 Job: {$s_job}\n🏙 City: {$s_city}\n\n";
                                $vip_message .= "मेरी प्रोफाइल लिंक: https://meenabiodata.infinityfree.me/?id={$sender_id}\n\n";
                            }
                        } else {
                            $vip_message .= "मैंने अभी ऐप पर अपना बायोडाटा पूरा नहीं किया है।\n\n";
                        }

                        $vip_message .= "मुझे आपका बायो डाटा पसन्द आया है, इस संदर्भ में मैं आपसे बात करना चाहता हूँ।\n\n";
                        $vip_message .= "आपकी प्रोफाइल लिंक: https://meenabiodata.infinityfree.me/?id={$target_id}\n\n";
                        $vip_message .= "WhatsApp Group Link:\nhttps://chat.whatsapp.com/KoaOf7sb9yKIqS5acKAww2";

                        send_whatsapp_api($target_number, 'interactive', $vip_message, $phone_number_id, $access_token);
                    }
                }
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
