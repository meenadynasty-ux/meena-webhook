<?php
// ==========================================
// 👑 MEENA DYNASTY - VIP PROXY WALL (WEBHOOK.PHP)
// SPY CAMERA + ADVANCED MATCHMAKING + DUAL ID SEARCH
// ==========================================

// 1. PHP के सारे छुपे हुए एरर स्क्रीन पर दिखाने के लिए (Spy Camera ON)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

error_log("==================================================");
error_log("🕵️ SPY ALERT: SERVER WOKE UP AT " . date('Y-m-d H:i:s'));

// 2. TOKENS & DB CONFIG
$db_host = "sql211.infinityfree.com";
$db_user = "if0_40880172";
$db_pass = "Rishi7665";
$db_name = "if0_40880172_dynasty_bot";

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn) { $conn->set_charset("utf8mb4"); }

$verify_token    = "Meena_Biodata_Secure_Token_123";
$access_token    = "EAAOqLdrjEfIBSLwVD2qIJ1POvP6GzkfZBh9KZAazbM2k1AK78K8qSqPdZAUV2qDWc19VApBTXbIePAx0Nhx9nWMQTZAAdUpZAvKT3MmpLfl2Jdw6aoZCWKcmfX36Ok4WkJX3T13f2vSOiVWvGK45rVuNStFWZB1Q3CHBrHmLalgAPWAZCmYaOqvsB6zoY618QHRkfwZDZD"; 
$firebase_url    = "https://meena-marriage-default-rtdb.asia-southeast1.firebasedatabase.app";
$firebase_secret = "KLEHB8GIs2PxUIobazUAGHsObWz2AT1Gtqjk83tV"; 

// 3. CORE FUNCTIONS (Firebase & WhatsApp API)
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
    
    error_log("🚀 META SENT [$httpcode]: $response");
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

function get_profile_data($profile) {
    if (!$profile) {
        return ['name'=>'-', 'dob'=>'-', 'height'=>'-', 'job'=>'-', 'gotra'=>'-', 'city'=>'-', 'native'=>'-'];
    }
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

    if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
        
        $msg_obj = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $sender_number = isset($msg_obj['from']) ? $msg_obj['from'] : 'UNKNOWN';
        $msg_type = isset($msg_obj['type']) ? $msg_obj['type'] : 'UNKNOWN';
        
        $current_phone_id = "1168343493037440"; // Default Phone ID
        if (isset($data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'])) {
            $current_phone_id = $data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'];
        }

        error_log("👤 SPY: Sender -> $sender_number | Type -> $msg_type");

        // ==========================================
        // BUTTON CLICKS (ACCEPT / REJECT)
        // ==========================================
        if ($msg_type === 'interactive') {
            $button_id = $msg_obj['interactive']['button_reply']['id'];
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
        // TEXT MESSAGE LOGIC (ID SEARCH & ROYAL FORMAT)
        // ==========================================
        elseif ($msg_type === 'text') {
            $message_text = trim($msg_obj['text']['body']); 
            error_log("💬 SPY: Message Text -> " . $message_text);

            // STOP COMMAND
            if (strtoupper($message_text) === 'STOP' || $message_text === 'स्टॉप') {
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
            // ID EXTRACTION LOGIC (SAFE & ACCURATE REGEX FOR LINKS AND TEXT)
            else {
                $target_id = null;
                $sender_id = null;

                // 1. Extract Target ID (पूरी लिंक या सिर्फ ID से)
                if (preg_match('/[?&]id=([A-Za-z0-9_-]+)/i', $message_text, $m1)) {
                    $target_id = trim($m1[1]); 
                } elseif (preg_match('/(?:Profile ID|id)[\s:=]+([A-Za-z0-9_-]+)/i', $message_text, $m2)) {
                    $target_id = trim($m2[1]); 
                }

                // 2. Extract My ID (Sender ID) (पूरी लिंक या सिर्फ ID से)
                if (preg_match('/मेरी प्रोफाइल लिंक.*[?&]id=([A-Za-z0-9_-]+)/i', $message_text, $s1)) {
                    $sender_id = trim($s1[1]); 
                } elseif (preg_match('/My ID[\s:=]+([A-Za-z0-9_-]+)/i', $message_text, $s2)) {
                    $sender_id = trim($s2[1]); 
                }

                // PROCESS MATCHMAKING
                if ($target_id !== null) {
                    error_log("🎯 SPY: Target ID Detected -> $target_id | My ID -> $sender_id");
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

                            // FORMAT BOTH PROFILES
                            $t = get_profile_data($t_profile_raw);
                            $s_profile_raw = $sender_id ? findProfileInAllFolders($sender_id) : null;
                            $s = get_profile_data($s_profile_raw);

                            // 👑 EXACT ROYAL TEMPLATE MATCHING 👑
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
                            
                            // 🔥 SYSTEM CONNECTION DATA (FOOTER) 🔥
                            $vip_message .= "------------------------\n";
                            $vip_message .= "*System Connection Data*\n";
                            $vip_message .= "Profile ID: {$target_id}\n";
                            $vip_message .= "My ID: " . ($sender_id ? $sender_id : "-");

                            // SEND INTERACTIVE BUTTON TO TARGET
                            send_whatsapp_api($target_number, 'interactive', $vip_message, $current_phone_id, $access_token);
                            
                            // NOTIFY SENDER
                            send_whatsapp_api($sender_number, 'text', "✅ आपकी रिक्वेस्ट सफलतापूर्वक Profile ID: {$target_id} को भेज दी गई है! उनके जवाब का इंतज़ार करें।", $current_phone_id, $access_token);
                        } else {
                            send_whatsapp_api($sender_number, 'text', "⚠️ प्रोफाइल मिल गई, लेकिन उसमें मोबाइल नंबर सेव नहीं है।", $current_phone_id, $access_token);
                        }
                    } else {
                        send_whatsapp_api($sender_number, 'text', "⚠️ यह Profile ID ({$target_id}) डेटाबेस में नहीं मिली। कृपया सही ID जांचें।", $current_phone_id, $access_token);
                    }
                } 
                // IF NO ID FOUND -> CHECK ACTIVE CHAT OR SEND GREETING
                else {
                    $session_info = firebase_request($firebase_url . '/whatsapp_sessions/' . $sender_number . '.json', 'GET');
                    if (!empty($session_info) && isset($session_info['target'])) {
                        // Forward text anonymously
                        send_whatsapp_api($session_info['target'], 'text', $message_text, $current_phone_id, $access_token);
                    } else {
                        // Default Royal Welcome Message
                        $greeting = "༺👑═༻༺═👑༻\n";
                        $greeting .= "💛👑 *𝑴𝑬𝑬𝑵𝑨 𝑫𝒀𝑵𝑨𝑺𝑻𝒀* 👑💛\n";
                        $greeting .= "✨ *𝑴𝒆𝒆𝒏𝒂 𝑹𝒊𝒔𝒉𝒕𝒐𝒏 𝑲𝒂 𝑺𝒂𝒏𝒈𝒂𝒎* ✨🤝\n";
                        $greeting .= "༺══════👑══════༻\n\n";
                        $greeting .= "नमस्कार 🙏\n*Meena Dynasty VIP Matrimony* में आपका स्वागत है।\n\n";
                        $greeting .= "यहाँ आपकी प्राइवेसी 100% सुरक्षित है। किसी भी प्रोफाइल से जुड़ने के लिए कृपया उनकी पूरी प्रोफाइल लिंक या *Profile ID* यहाँ भेजें।";
                        
                        send_whatsapp_api($sender_number, 'text', $greeting, $current_phone_id, $access_token);
                    }
                }
            }
        }
    }
    http_response_code(200);
    error_log("✅ SPY: Sent 200 OK back to Meta successfully. Script Finished.");
    exit;
}
?>
