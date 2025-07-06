<?php
$openaiKey = "sk-XXXXXXXXXXXXXXXXXXXXXXXX";  // ← REEMPLAZA con tu API key real
$voiceKey = "e4afb341ccee4c96a7d9d7a97fcb4ddd";  // ← VoiceRSS API key

if (!isset($_FILES['audio'])) {
    http_response_code(400);
    echo json_encode(["error" => "No se recibió archivo"]);
    exit;
}

// Transcripción con Whisper
$whisper = curl_init();
curl_setopt_array($whisper, [
    CURLOPT_URL => "https://api.openai.com/v1/audio/transcriptions",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        "file" => new CURLFile($_FILES['audio']['tmp_name'], "audio/wav", "input.wav"),
        "model" => "whisper-1"
    ],
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $openaiKey"
    ]
]);
$transcription = json_decode(curl_exec($whisper), true);
curl_close($whisper);

$texto = $transcription["text"] ?? "No entendí nada";

// Consultar a ChatGPT
$chat = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt_array($chat, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $openaiKey",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode([
        "model" => "gpt-3.5-turbo",
        "messages" => [["role" => "user", "content" => $texto]]
    ])
]);
$response = json_decode(curl_exec($chat), true);
curl_close($chat);

$reply = $response["choices"][0]["message"]["content"] ?? "No tengo respuesta.";

// Texto a voz con VoiceRSS
$ttsURL = "http://api.voicerss.org/?key=$voiceKey&hl=es-mx&src=" . urlencode($reply);
$audioReply = file_get_contents($ttsURL);
file_put_contents("respuesta.mp3", $audioReply);

header("Content-Type: audio/mpeg");
readfile("respuesta.mp3");
unlink("respuesta.mp3");
?>
