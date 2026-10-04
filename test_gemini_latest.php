<?php
$apiKey = "AQ.Ab8RN6Ieaih9Tam97CdZGQJpDCGeNEP06YbW0s9fI9bB71RQqA";
$query = "Novidat 250";
$endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . $apiKey;

$prompt = "You are an expert pharmacy data normalization assistant.
The user is searching for a medicine: \"{$query}\"
Your job is to normalize this search into structured JSON format.
If you do not know a value, leave it as null or empty string. DO NOT invent facts.

Return strictly valid JSON with this exact schema:
{
  \"normalized_name\": \"string (the correct medical name without form/strength, e.g. Panadol)\",
  \"strength\": \"string (e.g. 500mg, 250 mg)\",
  \"dosage_form\": \"string (e.g. Tablet, Syrup, Injection)\",
  \"brand\": \"string\",
  \"manufacturer\": \"string\",
  \"barcode\": \"string (if present in the query)\",
  \"confidence\": integer (0 to 100 based on how sure you are about the medicine)
}";

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'contents' => [
        ['parts' => [['text' => $prompt]]]
    ],
    'generationConfig' => [
        'response_mime_type' => 'application/json',
    ]
]));
$response = curl_exec($ch);
curl_close($ch);

echo $response;
