<?php
// ==========================================
// 🕵️ MEENA DYNASTY - ADVANCED SPY / DEBUG MODE
// ==========================================

// 1. PHP के सारे छुपे हुए एरर स्क्रीन पर दिखाने के लिए (Spy Camera ON)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

error_log("==================================================");
error_log("🕵️ SPY ALERT: SERVER WOKE UP AT " . date('Y-m-d H:i:s'));

// 2. TOKEN & CONFIG
$verify_token = "Meena_Biodata_Secure_Token_123";
$access_token = "EAAOqLdrjEfIBSLwVD2qIJ1POvP6GzkfZBh9KZAazbM2k1AK78K8qSqPdZAUV2qDWc19VApBTXbIePAx0Nhx9nWMQTZAAdUpZAvKT3MmpLfl2Jdw6aoZCWKcmfX36Ok4WkJX3T13f2vSOiVWvGK45rVuNStFWZB1Q3CHBrHmLalgAPWAZCmYaOqvsB6zoY618QHRkfwZDZD"; 

// 3. WEBHOOK VERIFICATION
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_mode'])) {
    error_log("🕵️ SPY: META IS VERIFYING WEBHOOK...");
    echo $_GET['hub_challenge'];
    http_response_code(200);
    exit;
}

// 4. MESSAGE RECEIVING (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Facebook ने क्या भेजा, उसका कच्चा (Raw) डेटा कैप्चर करें
    $input = file_get_contents('php://input');
    error_log("📥 SPY [RAW META PAYLOAD]: " . $input); // यह लाइन पूरा डेटा छापेगी

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

        if ($msg_type === 'text') {
            $message_text = trim($msg_obj['text']['body']); 
            error_log("💬 SPY: Text Message Content -> " . $message_text);

            if (strtolower($message_text) === 'hi' || strtolower($message_text) === 'hello') {
                error_log("🎯 SPY: 'Hi' Command Matched! Calling Facebook API now...");
                
                // CURL FUNCTION DIRECTLY CALLED
                $url = 'https://graph.facebook.com/v25.0/' . $current_phone_id . '/messages';
                $reply_data = [
                    'messaging_product' => 'whatsapp',
                    'to' => $sender_number,
                    'type' => 'text',
                    'text' => ['body' => "नमस्ते! 🙏 Meena Dynasty Bot जासूसी मोड में सक्रिय है।"]
                ];

                error_log("🚀 SPY: Outgoing CURL URL -> " . $url);

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reply_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                
                $response = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if ($curl_error) {
                    error_log("❌ SPY CURL NETWORK ERROR: " . $curl_error);
                }
                
                error_log("🎯 SPY META FINAL RESPONSE CODE: " . $httpcode);
                error_log("🎯 SPY META FINAL RESPONSE BODY: " . $response);

            } else {
                error_log("⚠️ SPY: Message was text, but not 'Hi' or 'Hello'. Ignoring.");
            }
        } else {
            error_log("⚠️ SPY: Ignored because message type is not text.");
        }
    } else {
        error_log("ℹ️ SPY: No 'messages' found in payload. Probably a status update (Delivery/Read receipt).");
    }

    http_response_code(200);
    error_log("✅ SPY: Sent 200 OK back to Meta successfully. Script Finished.");
    exit;
}
?>
