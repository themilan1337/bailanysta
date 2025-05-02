<?php
// app/Controllers/AiController.php

namespace App\Controllers;

use Throwable; // Use Throwable to catch broad errors

class AiController
{
    private ?int $currentUserId = null;
    private string $apiKey;
    private string $apiUrl = 'https://api.deepinfra.com/v1/openai/chat/completions';
    private string $modelName = 'deepseek-ai/DeepSeek-V3-0324'; // Define the model

    public function __construct()
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user']['id'])) {
            $this->currentUserId = (int) $_SESSION['user']['id'];
        }

        // Get DeepInfra API Key
        $this->apiKey = config('DEEPINFRA_API_KEY', '');

        if (empty($this->apiKey) || $this->apiKey === 'BT6umGVaGXQW3xQmB3k2A2BJZc9kcDww') {
             error_log("DeepInfra API Key is not configured correctly in config.php");
             // We'll handle this failure within the method call
        }
    }

    /**
     * Generate a post idea using DeepInfra API.
     * POST /api/ai/generate-post-idea
     * Expects JSON body: {"prompt": "custom prompt details"} (optional)
     */
    public function generatePostIdea(): void
    {
        header('Content-Type: application/json');

        if ($this->currentUserId === null) {
            http_response_code(401); echo json_encode(['success' => false, 'message' => 'Authentication required.']); exit;
        }
        if (empty($this->apiKey)) {
             http_response_code(503); echo json_encode(['success' => false, 'message' => 'AI content generation is not configured.']); exit;
        }

        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody, true);
        $userPromptContext = trim($data['prompt'] ?? '');

        // --- Construct the Prompt Messages (OpenAI format) ---
        $systemPrompt = "You are generating **one single, concise social media post idea** for a platform called Bailanysta. "
                      . "Write **one single, engaging sentence** using a 'TLDR' style. "
                      . "Include 1-2 relevant emojis (like 😊 or 🎉). "
                      . "Include 1-2 relevant hashtags if appropriate. "
                      . "**Strictly output only the post text itself**, with no introduction, explanation, or quotation marks.";

        $userMessageContent = "Generate a post idea."; // Base user message
        if (!empty($userPromptContext)) {
            $userMessageContent .= " The topic is: " . htmlspecialchars($userPromptContext); // Add context safely
        } else {
             $defaultThemes = [ "a recent interesting thought", "an engaging question for others", "a neutral comment on something happening", "a small personal win", "a funny daily observation" ];
             $userMessageContent .= " The topic can be about " . $defaultThemes[array_rand($defaultThemes)] . ".";
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessageContent]
        ];

        error_log("DeepInfra Prompt (Messages): " . json_encode($messages));

        // --- Prepare cURL Request ---
        $postData = json_encode([
             'model' => $this->modelName,
             'messages' => $messages,
             // Optional generation parameters:
             // 'max_tokens' => 100,
             // 'temperature' => 0.7,
        ]);

        if ($postData === false) {
            error_log("Failed to encode JSON for DeepInfra request.");
            http_response_code(500); echo json_encode(['success' => false, 'message' => 'Internal error preparing request.']); exit;
        }

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey, // Use Bearer token
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $responseJson = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // --- Handle cURL/HTTP Errors ---
        if ($responseJson === false || $curlError) {
            error_log("cURL error calling DeepInfra API: " . $curlError);
            http_response_code(502); echo json_encode(['success' => false, 'message' => 'Failed to communicate with AI service (cURL Error).']); exit;
        }
        if ($httpCode >= 400) {
            error_log("DeepInfra API returned HTTP error {$httpCode}. Response: " . $responseJson);
            $errorData = json_decode($responseJson, true);
            // DeepInfra OpenAI compatible errors might be in 'error' or 'detail'
            $apiErrorMessage = $errorData['error']['message'] ?? ($errorData['detail'] ?? 'AI service returned an error.');
            http_response_code($httpCode); // Return the actual error code from DeepInfra
            echo json_encode(['success' => false, 'message' => "AI Error: " . htmlspecialchars($apiErrorMessage)]); exit;
        }

        // --- Parse Successful Response ---
        $responseData = json_decode($responseJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Failed to decode JSON response from DeepInfra API: " . json_last_error_msg() . ". Response: " . $responseJson);
            http_response_code(500); echo json_encode(['success' => false, 'message' => 'Received invalid response from AI service.']); exit;
        }

        // Extract text from the OpenAI-compatible structure
        $generatedText = $responseData['choices'][0]['message']['content'] ?? null;

        if ($generatedText === null || trim($generatedText) === '') {
             error_log("DeepInfra API response parsed, but no text content found. Full Response: " . $responseJson);
             http_response_code(500); echo json_encode(['success' => false, 'message' => 'AI service returned empty content.']); exit;
        }

        // --- Success ---
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'idea' => trim($generatedText)
        ]);
        exit;
    }
}