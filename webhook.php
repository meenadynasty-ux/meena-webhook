<?php
// ==========================================
// 👑 MEENA DYNASTY - VIP PRIVACY PROXY WALL
// INTEGRATION: MYSQL (TERMUX) + FIREBASE + META API
// SYSTEM: ENTERPRISE LEVEL ARCHITECTURE + MULTI FOLDER + DYNAMIC ID
// ==========================================

// ------------------------------------------
// 1. DATABASE CONNECTION (HIGH SECURITY)
// ------------------------------------------
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

// ------------------------------------------
// 2. TERMUX / WEBSITE SIGNAL (Prepared Statements)
// ------------------------------------------
if (isset($_REQUEST['sender_phone']) && isset($_REQUEST['receiver_phone'])) {
    header('Content-Type: application/json');
    if ($conn) {
        $sender_phone = $_REQUEST['sender_phone'];
        $receiver_phone = $_REQUEST['receiver_phone'];
        $sender_id = isset($_REQUEST['sender_id']) ? $_REQUEST['sender_id'] : 'NONE';

        $stmt = $conn->prepare("INSERT INTO connection_requests (sender_phone, receiver_phone, status, sender_id) VALUES (?, ?, 'pending', ?)");
        if($stmt) {
            $stmt->bind_param("sss", $sender_phone, $receiver_phone, $sender_id);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Request stored securely for Termux Bot"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Database Error"]);
            }
            $stmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Query Prepare Error"]);
        }
    }
    exit;
}

// ------------------------------------------
// 3. META API & FIREBASE CONFIGURATION
// ------------------------------------------
$verify_token    = "Meena_Biodata_Secure_Token_123";

// 👇 आपका एकदम लेटेस्ट टोकन (cURL कमांड वाला) 👇
$access_token    = "EAAOqLdrjEfIBSGAnIzvpPD9P3dJAy5ER8lnkldVIeeAveYQxZAIUflNYWHtAVFwa4tgQ8HJkVOWWdmZB4YGZA35QSvNY6KD7Xx5RPQm00tLEBVU96kN7ezFPFBPQLWGamkO2QWXAm191ACoVSXyBW40HGlHRzvWTEMRupafhBTIbPqLVgOhmrGvnZC4F08zTU6DE1Q7rLvyvUnSCpOUPIs6bEwq8FxLlLRrJrpxfEFx6DeB2XE3Ybh6MNuS7nrtMhGmDZCEX5bXvZA7KoKI0giEvvT6P7OdpqGfQIkwgZDZD"; 

$firebase_url    = "https://meena-marriage-default-rtdb.asia-southeast1.firebasedatabase.app";
$firebase_secret = "KLEHB8GIs2PxUIobazUAGHsObWz2AT1Gtqjk83tV"; 

// ------------------------------------------
// 4. ADVANCED HELPER FUNCTIONS
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
    // API Version v20.0 कर दिया है ताकि एरर न आये
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
    curl_exec($ch);
    curl_close($ch);
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

// ------------------------------------------
// 5. WEBHOOK VERIFICATION (GET)
// ------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_mode'])) {
    if ($_GET['hub_mode'] === 'subscribe' && $_GET['hub_verify_token'] === $verify_token) {
        echo $_GET['hub_challenge'];
        http_response_code(200);
        exit;
    }
}

// ------------------------------------------
// 6. MESSAGE RECEIVING & ROUTING (POST)
// ------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
        
        // 🚀 DYNAMIC PHONE ID LOGIC (यह आपके दोनों नंबरों को सपोर्ट करेगा) 🚀
        // यहाँ से कोड खुद समझ जाएगा कि मैसेज टेस्ट नंबर पर आया है या असली पर!
        $current_phone_id = "1168343493037440"; // Default Fallback
        if (isset($data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'])) {
            $current_phone_id = $data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'];
        }

        $msg_obj = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $sender_number = $msg_obj['from'];
        $my_admin_number = "918905651034"; // एडमिन लूप से बचने के लिए

        if ($sender_number === $my_admin_number) {
             http_response_code(200); echo "OK"; exit;
        }
        
        // ==========================================
        // BUTTON CLICKS (Interactive Reply)
        // ==========================================
        if ($msg_obj['type'] === 'interactive') {
            $button_id = $msg_obj['interactive']['button_reply']['id'];
            
            $session_info = firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'GET');
            
            if (!empty($session_info) && isset($session_info['target'])) {
                $target = $session_info['target'];
                
                if ($button_id === 'btn_accept') {
                    global $conn;
                    if($conn) {
                        $stmt = $conn->prepare("INSERT INTO connection_requests (sender_phone, receiver_phone, status) VALUES (?, ?, 'pending')");
                        if($stmt){
                            $stmt->bind_param("ss", $sender_number, $target);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                    
                    send_whatsapp_api($sender_number, 'text', "✅ *कनेक्शन सफल!*\nअब आप यहाँ जो भी लिखेंगे, वह सुरक्षित रूप से उन्हें भेज दिया जाएगा।\n\n*(चैट बंद करने के लिए किसी भी समय 'STOP' टाइप करें)*", $current_phone_id, $access_token);
                    send_whatsapp_api($target, 'text', "🎉 सामने वाले ने आपसे बात करना स्वीकार कर लिया है! अब आप अपना मैसेज भेज सकते हैं।\n\n*(चैट बंद करने के लिए किसी भी समय 'STOP' टाइप करें)*", $current_phone_id, $access_token);
                } else {
                    send_whatsapp_api($sender_number, 'text', "❌ आपने इस चैट को मना कर दिया है।", $current_phone_id, $access_token);
                    send_whatsapp_api($target, 'text', "माफ़ करें, सामने वाले यूज़र अभी बात करने के लिए उपलब्ध नहीं हैं।", $current_phone_id, $access_token);
                    
                    firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'DELETE');
                    firebase_request($firebase_url . '/whatsapp_sessions/' . $target . '.json', 'DELETE');
                }
            }
        }
        
        // ==========================================
        // NORMAL TEXT MESSAGES
        // ==========================================
        elseif ($msg_obj['type'] === 'text') {
            $message_text = trim($msg_obj['text']['body']); 

            // --- PREMIUM FEATURE: STOP COMMAND ---
            if (strtoupper($message_text) === 'STOP' || $message_text === 'स्टॉप') {
                $session_info = firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'GET');
                if (!empty($session_info) && isset($session_info['target'])) {
                    $target = $session_info['target'];
                    firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'DELETE');
                    firebase_request($firebase_url . '/whatsapp_sessions/' . $target . '.json', 'DELETE');
                    
                    send_whatsapp_api($sender_number, 'text', "🚫 आपकी चैट सुरक्षित रूप से समाप्त कर दी गई है। अब आपके मैसेज आगे नहीं भेजे जाएंगे।", $current_phone_id, $access_token);
                    send_whatsapp_api($target, 'text', "🚫 सामने वाले यूज़र ने चैट समाप्त कर दी है। यह कनेक्शन अब बंद हो चुका है।", $current_phone_id, $access_token);
                }
            }
            // --- NEW CONNECTION REGEX MATCH (Works with links or plain ID text) ---
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
                        $vip_message .= "नमस्कार 🙏\nहमने आपकी प्रोफाइल:\n\n";
                        $vip_message .= "👤 Name: {$t_name}\n🎂 DOB/Age: {$t_dob} ({$t_age})\n📏 Height: {$t_height}\n";
                        $vip_message .= "💼 Job: {$t_job}\n🕉 Gotras: {$t_gotra_str}\n🏙 City: {$t_city}\n🏡 Native: {$t_native}\n\n";
                        $vip_message .= "*MEENA DYNASTY* पर देखी है।\n\n";
                        $vip_message .= "मेरा बायोडाटा ये है:\n\n";

                        if ($sender_id !== 'NONE') {
                            $s_profile = findProfileInAllFolders($sender_id);
                            if ($s_profile !== null) {
                                $s_name = $s_profile['name'] ?? '-';
                                $s_job = $s_profile['jobType'] ?? '-';
                                $s_city = $s_profile['city'] ?? '-';
                                
                                $vip_message .= "👤 Name: {$s_name}\n💼 Job: {$s_job}\n🏙 City: {$s_city}\n\n";
                                $vip_message .= "मेरी प्रोफाइल लिंक: https://meenabiodata.infinityfree.me/?id={$sender_id}\n\n";
                            } else {
                                $vip_message .= "मैंने अभी ऐप पर अपना बायोडाटा पूरा नहीं किया है।\n\n";
                            }
                        } else {
                            $vip_message .= "मैंने अभी ऐप पर अपना बायोडाटा पूरा नहीं किया है।\n\n";
                        }

                        $vip_message .= "मुझे आपका बायो डाटा पसन्द आया है, इस संदर्भ में मैं आपसे बात करना चाहता हूँ।\n\n";
                        $vip_message .= "आपकी प्रोफाइल लिंक: https://meenabiodata.infinityfree.me/?id={$target_id}\n\n";
                        $vip_message .= "WhatsApp Group Link:\nhttps://chat.whatsapp.com/KoaOf7sb9yKIqS5acKAww2";

                        send_whatsapp_api($target_number, 'interactive', $vip_message, $current_phone_id, $access_token);
                        
                        send_whatsapp_api($sender_number, 'text', "✅ आपकी रिक्वेस्ट सफलतापूर्वक Profile ID: {$target_id} को भेज दी गई है!", $current_phone_id, $access_token);
                    } else {
                        send_whatsapp_api($sender_number, 'text', "⚠️ प्रोफाइल मिल गई, लेकिन इस प्रोफाइल में कोई मोबाइल नंबर सेव नहीं है।", $current_phone_id, $access_token);
                    }
                } else {
                    send_whatsapp_api($sender_number, 'text', "⚠️ यह Profile ID ({$target_id}) डेटाबेस में नहीं मिली। कृपया सही ID जाँचे।", $current_phone_id, $access_token);
                }
            } 
            elseif (strtolower($message_text) === 'hi' || strtolower($message_text) === 'hello' || strtolower($message_text) === 'hiii' || strtolower($message_text) === 'hii') {
                send_whatsapp_api($sender_number, 'text', "नमस्ते! 🙏 Meena Dynasty Bot सक्रिय है।", $current_phone_id, $access_token);
            }
            // --- RELAY MESSAGE (Proxy Wall) ---
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
