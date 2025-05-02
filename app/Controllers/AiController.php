<?php
// app/Controllers/AiController.php

namespace App\Controllers;

use Throwable; // Use Throwable to catch broad errors

class AiController
{
    private ?int $currentUserId = null;
    private string $apiKey;
    // Updated API URL based on the provided curl command
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct()
    {
        // Start session if not already started (important for web context)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user']['id'])) {
            $this->currentUserId = (int) $_SESSION['user']['id'];
        }

        // Assuming you have a helper function config() to get environment variables or config values
        // Make sure config() function is defined and loads your configuration correctly
        // Example: function config($key, $default = null) { return getenv($key) ?: $default; }
        $this->apiKey = config('GEMINI_API_KEY', ''); // Ensure config() helper exists or replace with your method

        if (empty($this->apiKey) || $this->apiKey === 'YOUR_GEMINI_API_KEY_HERE') {
             error_log("Gemini API Key is not configured correctly."); // Log more specific error
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
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            exit;
        }
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_GEMINI_API_KEY_HERE') { // Added check again in case config failed silently
             http_response_code(503);
             echo json_encode(['success' => false, 'message' => 'AI content generation is not configured. API Key missing or invalid.']);
             exit;
        }

        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody, true);

        // Check if JSON decoding failed
        if (json_last_error() !== JSON_ERROR_NONE && !empty($requestBody)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON payload received.']);
            exit;
        }

        $userPromptContext = trim($data['prompt'] ?? '');

        $systemPrompt = "You are generating short social media post ideas for a platform called Bailanysta. "
                      . "Write in a concise 'TLDR' style. Use 1-2 relevant emojis (like smiles 😊 or similar positive ones). "
                      . "Focus on being engaging and brief (1-3 sentences). Include 1-2 relevant hashtags if appropriate. "
                      . "Directly output only the post text idea itself, without any introductory phrases like 'Here's an idea:'.";

         $generationTopic = "The post should be about: ";
         if (!empty($userPromptContext)) {
             $generationTopic .= htmlspecialchars($userPromptContext); // Sanitize user input before adding to prompt
         } else {
             $defaultThemes = [ "a recent interesting thought", "an engaging question for others", "a neutral comment on something happening", "a small personal win", "a funny daily observation" ];
             $generationTopic .= $defaultThemes[array_rand($defaultThemes)];
         }

         $fullPrompt = $systemPrompt . "\n\n" . $generationTopic;


        error_log("Gemini Prompt (Full): " . $fullPrompt); // Good for debugging

        // Construct the URL with the API key in the query string
        $urlWithKey = $this->apiUrl . '?key=' . $this->apiKey;

        // Prepare the POST data structure required by the API
        $postData = json_encode([
             'contents' => [[
                 'parts' => [['text' => $fullPrompt]]
             ]],
             // Optional: Add generation config if needed, e.g., temperature, max tokens
             // 'generationConfig' => [
             //     'temperature' => 0.7,
             //     'maxOutputTokens' => 200,
             // ]
        ]);

        if ($postData === false) {
            error_log("Failed to encode JSON for Gemini request.");
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => 'Internal error preparing request.']);
             exit;
        }

        $ch = curl_init($urlWithKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json' // Often good practice to include Accept header
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);        // Total cURL execution timeout in seconds
        // Optional: Disable SSL verification for local testing if needed (NOT recommended for production)
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $responseJson = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        // Check for cURL errors first
        if ($curlErrno !== 0) {
            error_log("cURL error calling Gemini API (errno: {$curlErrno}): " . $curlError);
            http_response_code(502); // Bad Gateway might be appropriate
            echo json_encode(['success' => false, 'message' => 'Failed to communicate with AI service (cURL Error: ' . $curlError . ').']);
            exit;
        }

        // Check for non-2xx HTTP status codes from the API
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Gemini API returned HTTP error {$httpCode}. Response: " . $responseJson);
             $errorData = json_decode($responseJson, true);
             // Try to extract a specific error message from the API response
             $apiErrorMessage = 'AI service returned an error.'; // Default message
             if (isset($errorData['error']['message'])) {
                 $apiErrorMessage = $errorData['error']['message'];
             } elseif (!empty($responseJson)) {
                 $apiErrorMessage = "Status code {$httpCode}."; // Use status code if no message
             }
             http_response_code(502); // Treat API errors as Bad Gateway
             echo json_encode(['success' => false, 'message' => "AI Error: " . htmlspecialchars($apiErrorMessage)]);
             exit;
        }

        // Decode the successful JSON response
        $responseData = json_decode($responseJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Failed to decode JSON response from Gemini API: " . json_last_error_msg() . ". Response: " . $responseJson);
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => 'Received invalid response format from AI service.']);
             exit;
        }

        // Extract the generated text, checking structure carefully
        // Standard Gemini API response structure
        $generatedText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

        // Handle cases where the expected text part is missing or empty
        if ($generatedText === null || trim($generatedText) === '') {
             error_log("Gemini API response parsed, but no text content found in expected location. Full Response: " . $responseJson);
             // Check for safety ratings / blocked content which might explain missing text
             $finishReason = $responseData['candidates'][0]['finishReason'] ?? 'UNKNOWN';
             if ($finishReason !== 'STOP') {
                 error_log("Generation finish reason: " . $finishReason);
                 $message = 'AI service stopped generation prematurely (' . $finishReason . '). This might be due to safety filters.';
                 // You might want to check $responseData['promptFeedback']['blockReason'] too if available
             } else {
                 $message = 'AI service returned empty content.';
             }
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => $message]);
             exit;
        }

        // Success! Return the generated idea
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'idea' => trim($generatedText) // Trim whitespace from the result
        ]);
        exit; // Ensure script termination after sending response
    }

    // Placeholder for config() function if not defined globally
    // You should replace this with your actual config loading mechanism
    private function config($key, $default = null) {
        // Example using getenv(), adjust as needed (e.g., reading from a file)
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}