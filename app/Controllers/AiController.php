<?php
// app/Controllers/AiController.php

namespace App\Controllers;

use Throwable; // Use Throwable to catch broad errors

class AiController
{
    private ?int $currentUserId = null;
    private string $apiKey;
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent'; // Use 1.5 Flash

    public function __construct()
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user']['id'])) {
            $this->currentUserId = (int) $_SESSION['user']['id'];
        }

        $this->apiKey = config('GEMINI_API_KEY', '');

        if (empty($this->apiKey) || $this->apiKey === 'YOUR_GEMINI_API_KEY_HERE') {
             error_log("Gemini API Key is not configured correctly in config.php");
        }
    }

    /**
     * Generate a post idea using Gemini via direct HTTP request.
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

        $systemPrompt = "You are generating short social media post ideas for a platform called Bailanysta. "
                      . "Write in a concise 'TLDR' style. Use 1-2 relevant emojis (like smiles 😊 or similar positive ones). "
                      . "Focus on being engaging and brief (1-3 sentences). Include 1-2 relevant hashtags if appropriate. "
                      . "Directly output only the post text idea itself, without any introductory phrases like 'Here's an idea:'.";

         $generationTopic = "The post should be about: ";
         if (!empty($userPromptContext)) {
             $generationTopic .= htmlspecialchars($userPromptContext);
         } else {
             $defaultThemes = [ "a recent interesting thought", "an engaging question for others", "a neutral comment on something happening", "a small personal win", "a funny daily observation" ];
             $generationTopic .= $defaultThemes[array_rand($defaultThemes)];
         }

         $fullPrompt = $systemPrompt . "\n\n" . $generationTopic;


        error_log("Gemini Prompt (Full): " . $fullPrompt);

        $urlWithKey = $this->apiUrl . '?key=' . $this->apiKey;
        $postData = json_encode([
             'contents' => [[
                 'parts' => [['text' => $fullPrompt]]
             ]],
        ]);

        if ($postData === false) {
            error_log("Failed to encode JSON for Gemini request.");
             http_response_code(500); echo json_encode(['success' => false, 'message' => 'Internal error preparing request.']); exit;
        }

        $ch = curl_init($urlWithKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $responseJson = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseJson === false || $curlError) {
            error_log("cURL error calling Gemini API: " . $curlError);
            http_response_code(502); echo json_encode(['success' => false, 'message' => 'Failed to communicate with AI service (cURL Error).']); exit;
        }
        if ($httpCode >= 400) {
            error_log("Gemini API returned HTTP error {$httpCode}. Response: " . $responseJson);
             $errorData = json_decode($responseJson, true);
             $apiErrorMessage = $errorData['error']['message'] ?? 'AI service returned an error.';
             http_response_code(502); echo json_encode(['success' => false, 'message' => "AI Error: " . htmlspecialchars($apiErrorMessage)]); exit;
        }

        $responseData = json_decode($responseJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Failed to decode JSON response from Gemini API: " . json_last_error_msg() . ". Response: " . $responseJson);
             http_response_code(500); echo json_encode(['success' => false, 'message' => 'Received invalid response from AI service.']); exit;
        }

        $generatedText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($generatedText === null || trim($generatedText) === '') {
             error_log("Gemini API response parsed, but no text content found. Full Response: " . $responseJson);
             http_response_code(500); echo json_encode(['success' => false, 'message' => 'AI service returned empty content.']); exit;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'idea' => trim($generatedText)
        ]);
        exit;
    }
}