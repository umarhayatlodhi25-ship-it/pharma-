<?php
$apiKey = "AQ.Ab8RN6Ieaih9Tam97CdZGQJpDCGeNEP06YbW0s9fI9bB71RQqA";
$query = "panadol";
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=" . $apiKey;
$prompt = "You are an expert pharmacy data normalization assistant. The user is searching for an item in a pharmacy POS system: \"{$query}\". This item is categorized as a: MEDICINE. Your job is to normalize this search into structured JSON format. If you do not know a value, leave it as null or empty string. DO NOT invent facts. Return strictly valid JSON with this exact schema based on the category: {\"normalized_name\": \"string (e.g. Panadol)\", \"generic_name\": \"string (e.g. Paracetamol)\", \"strength\": \"string (e.g. 500mg)\", \"dosage_form\": \"string (e.g. Tablet, Syrup)\", \"brand\": \"string\", \"manufacturer\": \"string\", \"confidence\": 100}";
$body = json_encode([
    "contents" => [
        ["parts" => [["text" => $prompt]]]
    ],
    "generationConfig" => [
        "response_mime_type" => "application/json",
    ]
]);
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
$resp = curl_exec($ch);
if($resp === false) echo curl_error($ch);
else echo $resp;
