<?php
// ==========================================
// 👑 MEENA DYNASTY - ADVANCED VIP PROXY WALL
// BASED ON STABLE SPY CODE + ALL VIP FEATURES
// ==========================================

// 1. PHP के सारे छुपे हुए एरर स्क्रीन पर दिखाने के लिए (Spy Camera ON)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

error_log("==================================================");
error_log("🕵️ SPY ALERT: SERVER WOKE UP AT " . date('Y-m-d H:i:s'));

// 2. CRASH-PROOF DATABASE CONFIG & TOKENS
$db_host = "sql211.infinityfree.com";
$db_user = "if0_40880172";
$db_pass = "Rishi7665";
$db_name = "if0_40880172_dynasty_bot";

// 🔥 CRASH PROTECTION: MySQL एरर से कोड क्रैश होने से बचाएगा
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if (!$conn->connect_error) { 
    $conn->set_charset("utf8mb4"); 
} else {
    error_log("⚠️ SPY DB WARNING: InfinityFree Blocked DB. Bot will continue safely using Firebase...");
}

$verify_token    = "Meena_Biodata_Secure_Token_123";
$access_token    = "EAAOqLdrjEfIBSLwVD2qIJ1POvP6GzkfZBh9KZAazbM2k1AK78K8qSqPdZAUV2qDWc19VApBTXbIePAx0Nhx9nWMQTZAAdUpZAvKT3MmpLfl2Jdw6aoZCWKcmfX36Ok4WkJX3T13f2vSOiVWvGK45rVuNStFWZB1Q3CHBrHmLalgAPWAZCmYaOqvsB6zoY618QHRkfwZDZD"; 
$firebase_url    = "https://meena-marriage-default-rtdb.asia-southeast1.firebasedatabase.app";
$firebase_secret = "KLEHB8GIs2PxUIobazUAGHsObWz2AT1Gtqjk83tV"; 

// 3. CORE FIREBASE & META API FUNCTIONS
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

// Spy Code के ही मज़बूत CURL पर आधारित सेंडिंग फंक्शन
function send_whatsapp_api($to, $type, $content, $phone_id, $token) {
    $url = 'https://graph.facebook.com/v25.0/' . $phone_id . '/messages';
    
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

    error_log("🚀 SPY: Outgoing API Request to -> " . $to);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log("❌ SPY CURL ERROR: " . $curl_error);
    }
    error_log("🎯 SPY META RESPONSE [$httpcode]: " . $response);
}

// 5 Folders Deep Search Logic
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

// Profile Data Formatter
function get_profile_data($profile) {
    if (!$profile) return ['name'=>'-', 'dob'=>'-', 'height'=>'-', 'job'=>'-', 'gotra'=>'-', 'city'=>'-', 'native'=>'-'];
    
    $job = $profile['jobType'] ?? '-';
    if (!empty($profile['desig'])) $job .= ' (' . $profile['desig'] . ')';
    
    $gotras = array_filter([$profile['g1']??'', $profile['g2']??'', $profile['g3']??'', $profile['g4']??'']);
    return [
        'name'   => $profile['name'] ?? '-',
        'dob'    => ($profile['dob'] ?? '-') . (!empty($profile['ageCalc']) || !empty($profile['age']) ? ' (' . ($profile['ageCalc'] ?? $profile['age']) . ')' : ''),
        'height' => $profile['height'] ?? '-',
        'job'    => $job,
        'gotra'  => !empty($gotras) ? implode(" / ", $gotras) : '-',
        'city'   => $profile['city'] ?? '-',
        'native' => $profile['native'] ?? '-'
    ];
}

// 4. WEBHOOK VERIFICATION (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_mode'])) {
    error_log("🕵️ SPY: META IS VERIFYING WEBHOOK...");
    echo $_GET['hub_challenge'];
    http_response_code(200);
    exit;
}

// 5. MESSAGE RECEIVING (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Facebook ने क्या भेजा, उसका कच्चा (Raw) डेटा कैप्चर करें
    $input = file_get_contents('php://input');
    error_log("📥 SPY [RAW META PAYLOAD]: " . $input); 

    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ SPY ERROR: JSON Decode Failed - " . json_last_error_msg());
    }

    if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
        error_log("✅ SPY: Message object successfully found inside payload!");
        
        $msg_obj = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $sender_number = isset($msg_obj['from']) ? $msg_obj['from'] : 'UNKNOWN';
        $msg_type = isset($msg_obj['type']) ? $msg_obj['type'] : 'UNKNOWN';
        
        error_log("👤 SPY: Sender Number -> " . $sender_number);
        error_log("📝 SPY: Message Type -> " . $msg_type);

        $current_phone_id = "1168343493037440"; // Default
        if (isset($data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'])) {
            $current_phone_id = $data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'];
            error_log("📱 SPY: Phone ID Detect -> " . $current_phone_id);
        }

        // ==========================================
        // BUTTON CLICKS (ACCEPT / REJECT)
        // ==========================================
        if ($msg_type === 'interactive') {
            $button_id = $msg_obj['interactive']['button_reply']['id'];
            error_log("🖱️ SPY: Button Clicked -> " . $button_id);
            
            $session_info = firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'GET');
            
            if (!empty($session_info) && isset($session_info['target'])) {
                $target = $session_info['target'];
                
                if ($button_id === 'btn_accept') {
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
        // TEXT MESSAGE LOGIC (ADVANCED REGEX & PROXY)
        // ==========================================
        elseif ($msg_type === 'text') {
            $message_text = trim($msg_obj['text']['body']); 
            error_log("💬 SPY: Text Content -> " . $message_text);

            // 1. STOP COMMAND
            if (strtoupper($message_text) === 'STOP' || $message_text === 'स्टॉप') {
                error_log("🎯 SPY: 'STOP' Command Matched!");
                $session_info = firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'GET');
                if (!empty($session_info) && isset($session_info['target'])) {
                    $target = $session_info['target'];
                    firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'DELETE');
                    firebase_request($firebase_url . '/whatsapp_sessions/' . $target . '.json', 'DELETE');
                    send_whatsapp_api($sender_number, 'text', "🚫 आपकी चैट सुरक्षित रूप से समाप्त कर दी गई है।", $current_phone_id, $access_token);
                    send_whatsapp_api($target, 'text', "🚫 सामने वाले यूज़र ने चैट समाप्त कर दी है। कनेक्शन बंद हो चुका है।", $current_phone_id, $access_token);
                } else {
                    send_whatsapp_api($sender_number, 'text', "आप अभी किसी सक्रिय चैट में नहीं हैं।", $current_phone_id, $access_token);
                }
            }
            // 2. ID & URL EXTRACTION (लिंक या टेक्स्ट दोनों से काम करेगा)
            elseif (preg_match('/(?:[?&]id=|(?:Profile ID|id)[\s:=]*)([A-Za-z0-9_-]+)/i', $message_text, $m1)) {
                $target_id = trim($m1[1]); 
                error_log("🎯 SPY: Target ID Found -> " . $target_id);
                
                $sender_id = null;
                if (preg_match('/(?:My ID[\s:=]*|मेरी प्रोफाइल लिंक.*[?&]id=)([A-Za-z0-9_-]+)/i', $message_text, $s1)) {
                    $sender_id = trim($s1[1]); 
                    error_log("🎯 SPY: My ID Found -> " . $sender_id);
                }
                
                $t_profile_raw = findProfileInAllFolders($target_id);
                
                if ($t_profile_raw !== null) {
                    $raw_phone = $t_profile_raw['phone'] ?? $t_profile_raw['mobile'] ?? '';
                    if (!empty($raw_phone)) {
                        $clean_phone = preg_replace('/\D/', '', $raw_phone);
                        $target_number = "91" . substr($clean_phone, -10);

                        // CREATE PENDING SESSION
                        $session_data = [
                            $sender_number => ['target' => $target_number, 'time' => time()],
                            $target_number => ['target' => $sender_number, 'time' => time()]
                        ];
                        firebase_request($firebase_url . '/whatsapp_sessions.json', 'PATCH', $session_data);

                        // FORMAT DATA
                        $t = get_profile_data($t_profile_raw);
                        $s_profile_raw = $sender_id ? findProfileInAllFolders($sender_id) : null;
                        $s = get_profile_data($s_profile_raw);

                        // 👑 ROYAL TEMPLATE (बिल्कुल आपका डिज़ाइन) 👑
                        $vip_message = "༺👑═༻༺═👑༻\n";
                        $vip_message .= "💛👑 *𝑴𝑬𝑬𝑵𝑨 𝑫𝒀𝑵𝑨𝑺𝑻𝒀* 👑💛\n";
                        $vip_message .= "✨ *𝑴𝒆𝒆𝒏𝒂 𝑹𝒊𝒔𝒉𝒕𝒐𝒏 𝑲𝒂 𝑺𝒂𝒏𝒈𝒂𝒎* ✨🤝\n";
                        $vip_message .= "༺══════👑══════༻\n\n";
                        
                        $vip_message .= "नमस्कार 🙏\nहमने आपकी प्रोफाइल:\n\n";
                        $vip_message .= "Name: {$t['name']}\n";
                        $vip_message .= "DOB/Age: {$t['dob']}\n";
                        $vip_message .= "Height: {$t['height']}\n";
                        $vip_message .= "Job: {$t['job']}\n";
                        $vip_message .= "Gotras: {$t['gotra']}\n";
                        $vip_message .= "City: {$t['city']}\n";
                        $vip_message .= "Native: {$t['native']}\n\n";
                        
                        $vip_message .= "MEENA DYNASTY\nपर देखी है।\n\n";
                        
                        $vip_message .= "मेरा बायोडाटा ये है:\n\n";
                        $vip_message .= "Name: {$s['name']}\n";
                        $vip_message .= "DOB/Age: {$s['dob']}\n";
                        $vip_message .= "Height: {$s['height']}\n";
                        $vip_message .= "Job: {$s['job']}\n";
                        $vip_message .= "Gotras: {$s['gotra']}\n";
                        $vip_message .= "City: {$s['city']}\n";
                        $vip_message .= "Native: {$s['native']}\n\n";
                        
                        $vip_message .= "मुझे आपका बायो डाटा पसन्द आया है इस संदर्भ में में आप से बात करना चाहता हूं।\n\n";
                        
                        $vip_message .= "आपकी प्रोफाइल लिंक: https://meenabiodata.infinityfree.me/InT.html?id={$target_id}\n";
                        if ($sender_id) {
                            $vip_message .= "मेरी प्रोफाइल लिंक: https://meenabiodata.infinityfree.me/InT.html?id={$sender_id}\n\n";
                        } else {
                            $vip_message .= "\n";
                        }
                        
                        $vip_message .= "WhatsApp Group Link:\nhttps://chat.whatsapp.com/KoaOf7sb9yKIqS5acKAww2?s=cl&p=a&mlu=4\n\n";
                        
                        // FOOTER
                        $vip_message .= "------------------------\n";
                        $vip_message .= "*System Connection Data*\n";
                        $vip_message .= "Profile ID: {$target_id}\n";
                        $vip_message .= "My ID: " . ($sender_id ? $sender_id : "-");

                        send_whatsapp_api($target_number, 'interactive', $vip_message, $current_phone_id, $access_token);
                        send_whatsapp_api($sender_number, 'text', "✅ आपकी रिक्वेस्ट सफलतापूर्वक Profile ID: {$target_id} को भेज दी गई है! उनके जवाब का इंतज़ार करें।", $current_phone_id, $access_token);
                    } else {
                        send_whatsapp_api($sender_number, 'text', "⚠️ प्रोफाइल मिल गई, लेकिन उसमें मोबाइल नंबर सेव नहीं है।", $current_phone_id, $access_token);
                    }
                } else {
                    send_whatsapp_api($sender_number, 'text', "⚠️ यह Profile ID ({$target_id}) डेटाबेस में नहीं मिली। कृपया सही ID जांचें।", $current_phone_id, $access_token);
                }
            } 
            // 3. ANONYMOUS CHAT OR DEFAULT VIP GREETING
            else {
                $session_info = firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'GET');
                if (!empty($session_info) && isset($session_info['target'])) {
                    // Chat is active, proxy the message
                    error_log("🎯 SPY: Proxying message to -> " . $session_info['target']);
                    send_whatsapp_api($session_info['target'], 'text', $message_text, $current_phone_id, $access_token);
                } else {
                    // Default Royal Welcome Message
                    error_log("🎯 SPY: Sending Default Greeting.");
                    $greeting = "༺👑═༻༺═👑༻\n";
                    $greeting .= "💛👑 *𝑴𝑬𝑬𝑵𝑨 𝑫𝒀𝑵𝑨𝑺𝑻𝒀* 👑💛\n";
                    $greeting .= "✨ *𝑴𝒆𝒆𝒏𝒂 𝑹𝒊𝒔𝒉𝒕𝒐𝒏 𝑲𝒂 𝑺𝒂𝒏𝒈𝒂𝒎* ✨🤝\n";
                    $greeting .= "༺══════👑══════༻\n\n";
                    $greeting .= "नमस्कार 🙏\n*Meena Dynasty VIP Matrimony* में आपका स्वागत है।\n\n";
                    $greeting .= "यहाँ आपकी प्राइवेसी 100% सुरक्षित है। किसी भी प्रोफाइल से जुड़ने के लिए कृपया उनकी पूरी प्रोफाइल लिंक या *Profile ID* यहाँ भेजें।";
                    
                    send_whatsapp_api($sender_number, 'text', $greeting, $current_phone_id, $access_token);
                }
            }
        } else {
            error_log("⚠️ SPY: Ignored because message type is not text/interactive.");
        }
    } else {
        error_log("ℹ️ SPY: No 'messages' found in payload. Probably a status update (Delivery/Read receipt).");
    }

    http_response_code(200);
    error_log("✅ SPY: Sent 200 OK back to Meta successfully. Script Finished.");
    exit;
}
?>
