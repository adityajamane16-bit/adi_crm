<?php
// ============================================
// ai_summary.php - Claude AI Integration
// Call this file via POST to get AI summary
// ============================================

header('Content-Type: application/json');

// Your Anthropic API Key
define('ANTHROPIC_API_KEY', 'YOUR_ANTHROPIC_API_KEY_HERE'); // 🔑 Replace this!

function getAISummary($problem_text) {
    $prompt = "You are an assistant for an AC (Air Conditioner) service centre CRM system. 
A customer has reported the following issue with their AC:

\"$problem_text\"

Please do the following:
1. Write a short 2-line professional summary of the complaint (what the problem is, clearly stated).
2. Suggest a priority level: low, normal, or urgent — based on the severity.

Respond ONLY in this exact JSON format with no extra text:
{
  \"summary\": \"Your 2-line summary here.\",
  \"priority\": \"normal\"
}";

    $data = json_encode([
        "model"      => "claude-sonnet-4-20250514",
        "max_tokens" => 300,
        "messages"   => [
            ["role" => "user", "content" => $prompt]
        ]
    ]);

    $ch = curl_init("https://api.anthropic.com/v1/messages");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "x-api-key: " . ANTHROPIC_API_KEY,
        "anthropic-version: 2023-06-01"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response    = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error  = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return ["summary" => "AI summary unavailable (network error).", "priority" => "normal"];
    }

    if ($http_status !== 200) {
        return ["summary" => "AI summary unavailable (API error $http_status).", "priority" => "normal"];
    }

    $result = json_decode($response, true);

    if (!isset($result['content'][0]['text'])) {
        return ["summary" => "AI summary unavailable (empty response).", "priority" => "normal"];
    }

    $text = $result['content'][0]['text'];

    // Strip markdown fences if any
    $text = preg_replace('/```json|```/', '', $text);
    $text = trim($text);

    $parsed = json_decode($text, true);

    if (!$parsed || !isset($parsed['summary'])) {
        return ["summary" => $text, "priority" => "normal"];
    }

    // Validate priority value
    $valid_priorities = ['low', 'normal', 'urgent'];
    if (!in_array($parsed['priority'], $valid_priorities)) {
        $parsed['priority'] = 'normal';
    }

    return $parsed;
}

// If called via POST (AJAX from register_complaint.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $problem = isset($_POST['problem']) ? trim($_POST['problem']) : '';

    if (empty($problem)) {
        echo json_encode(["error" => "No problem text provided."]);
        exit();
    }

    $result = getAISummary($problem);
    echo json_encode($result);
    exit();
}
?>
