<?php
// app/Core/helpers.php

// No 'use HTMLPurifier...' needed here if not using sanitize_html

function view(string $view, array $data = []): string
{
    // No require_once needed here
    $viewPath = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $view) . '.php';
    if (!file_exists($viewPath)) {
        error_log("View file not found: {$viewPath}");
        return "Error: View file '{$view}' not found.";
    }
    extract($data);
    ob_start();
    include $viewPath;
    $content = ob_get_clean();
    $layoutPath = dirname(__DIR__) . "/Views/layouts/app.php";
    if (file_exists($layoutPath)) {
        ob_start();
        include $layoutPath;
        return ob_get_clean();
    } else {
        return $content;
    }
}

function vite_assets(string|array $entrypoints): string
{
    $viteDevServerBase = "http://localhost:5173";
    $manifestPath = dirname(__DIR__, 2) . "/public/build/.vite/manifest.json";
    $isDev = defined("APP_ENV") && APP_ENV === "development";
    if (!is_array($entrypoints)) {
        $entrypoints = [$entrypoints];
    }
    $html = "";
    $devServerIsRunning = false;
    static $renderedCss = [];

    if ($isDev) {
        $ch = curl_init($viteDevServerBase . "/@vite/client");
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        @curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        @curl_setopt($ch, CURLOPT_NOBODY, true);
        @curl_exec($ch);
        $error = curl_errno($ch);
        curl_close($ch);
        $devServerIsRunning = $error === 0;
        if (!$devServerIsRunning) {
            static $l = false;
            if (!$l) {
                error_log("Vite dev server not reachable: $viteDevServerBase");
                $l = true;
            }
        }
    }

    if ($isDev && $devServerIsRunning) {
        // In Dev, Vite handles CSS injection mostly via the JS module.
        // We MUST include the Vite client and the main JS entry point.
        $html .=
            '<script type="module" src="' .
            $viteDevServerBase .
            '/@vite/client"></script>';
        foreach ($entrypoints as $entry) {
            $path = ltrim($entry, "/");
            if (str_ends_with($path, ".js")) {
                // Ensure we link the JS entry point
                $html .=
                    '<script type="module" src="' .
                    $viteDevServerBase .
                    "/" .
                    $path .
                    '"></script>';
            }
            // Link CSS explicitly too, Vite handles HMR for it.
            elseif (str_ends_with($path, ".css")) {
                $html .=
                    '<link rel="stylesheet" href="' .
                    $viteDevServerBase .
                    "/" .
                    $path .
                    '">';
            }
        }
    } else {
        // Production mode
        if (!file_exists($manifestPath)) {
            $m = "manifest.json not found: {$manifestPath}";
            error_log($m);
            return "<!-- {$m} -->";
        }
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if ($manifest === null) {
            $m = "Failed decode manifest.json: {$manifestPath}";
            error_log($m);
            return "<!-- {$m} -->";
        }
        $baseUrl = rtrim(config("BASE_URL", ""), "/");
        $buildPath = "/build/";

        foreach ($entrypoints as $entry) {
            $entryKey = ltrim($entry, "/");
            if (!isset($manifest[$entryKey])) {
                error_log("Entry '{$entryKey}' not found in manifest");
                continue;
            }
            $entryData = $manifest[$entryKey];

            // Add CSS file(s) for the entry point AND its imports
            if (isset($entryData["css"]) && is_array($entryData["css"])) {
                foreach ($entryData["css"] as $cssFile) {
                    if (!in_array($cssFile, $renderedCss)) {
                        $html .=
                            '<link rel="stylesheet" href="' .
                            $baseUrl .
                            $buildPath .
                            $cssFile .
                            '">';
                        $renderedCss[] = $cssFile;
                    }
                }
            }
            if (
                isset($entryData["imports"]) &&
                is_array($entryData["imports"])
            ) {
                foreach ($entryData["imports"] as $importKey) {
                    if (!isset($manifest[$importKey])) {
                        continue;
                    }
                    $importData = $manifest[$importKey];
                    if (
                        isset($importData["css"]) &&
                        is_array($importData["css"])
                    ) {
                        foreach ($importData["css"] as $cssFile) {
                            if (!in_array($cssFile, $renderedCss)) {
                                $html .=
                                    '<link rel="stylesheet" href="' .
                                    $baseUrl .
                                    $buildPath .
                                    $cssFile .
                                    '">';
                                $renderedCss[] = $cssFile;
                            }
                        }
                    }
                }
            }
            // Handle case where the entry point *is* the CSS file
            if (
                isset($entryData["file"]) &&
                str_ends_with($entryData["file"], ".css")
            ) {
                if (!in_array($entryData["file"], $renderedCss)) {
                    $html .=
                        '<link rel="stylesheet" href="' .
                        $baseUrl .
                        $buildPath .
                        $entryData["file"] .
                        '">';
                    $renderedCss[] = $entryData["file"];
                }
            }

            // Add the main JS file for the entry point
            if (
                isset($entryData["file"]) &&
                str_ends_with($entryData["file"], ".js")
            ) {
                $html .=
                    '<script type="module" src="' .
                    $baseUrl .
                    $buildPath .
                    $entryData["file"] .
                    '"></script>';
            }

            // Preload imported JS modules
            if (
                isset($entryData["imports"]) &&
                is_array($entryData["imports"])
            ) {
                foreach ($entryData["imports"] as $importKey) {
                    if (!isset($manifest[$importKey])) {
                        continue;
                    }
                    $importData = $manifest[$importKey];
                    if (
                        isset($importData["file"]) &&
                        str_ends_with($importData["file"], ".js")
                    ) {
                        $html .=
                            '<link rel="modulepreload" href="' .
                            $baseUrl .
                            $buildPath .
                            $importData["file"] .
                            '">';
                    }
                }
            }
        }
    }
    return $html;
}

function link_hashtags(?string $text): string
{
    if ($text === null || $text === '') {
        return '';
    }

    // Regex to find hashtags: # followed by letters, numbers, or underscore
    // \p{L} matches any Unicode letter, \p{N} any number
    // Added negative lookbehind (?<!\S) to avoid matching things like url#anchor
    // Added negative lookahead (?!\S) to ensure tag ends correctly
    $pattern = '/(?<!\S)#([a-zA-Z0-9_\p{L}\p{N}]+)/u';

    // Replace found hashtags with links
    $linkedText = preg_replace_callback(
        $pattern,
        function ($matches) {
            $tag = $matches[1]; // The tag text without the #
            // URL encode the tag for the query parameter
            $encodedTag = urlencode($tag);
            // Link points to the main feed with a search query
            // We'll use JS later to make this trigger the AJAX search directly
            $url = BASE_URL . '/?search=' . $encodedTag; // Simple GET search link for now
            // Or potentially a dedicated route: $url = BASE_URL . '/tags/' . $encodedTag;

            // Add appropriate classes for styling
            $linkClass = "text-primary hover:underline font-medium"; // Example classes

            return "<a href=\"{$url}\" class=\"{$linkClass} hashtag-link\" data-hashtag=\"{$encodedTag}\">#{$tag}</a>";
        },
        $text // Apply to the input text
    );

    // Handle potential errors from preg_replace_callback
    if ($linkedText === null) {
        error_log("preg_replace_callback failed for hashtags in text: " . $text);
        return $text; // Return original text on error
    }

    return $linkedText;
}

// --- CSRF Functions ---
function csrf_token(): string
{
    if (empty($_SESSION["csrf_token"])) {
        try {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            $_SESSION["csrf_token"] = sha1(uniqid((string) mt_rand(), true));
            error_log("CSRF fallback: " . $e->getMessage());
        }
    }
    return $_SESSION["csrf_token"];
}
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' .
        csrf_token() .
        '">';
}
function verify_csrf_token(): void
{
    $token = null;
    if (!empty($_POST["_csrf_token"])) {
        $token = $_POST["_csrf_token"];
    } elseif (!empty($_SERVER["HTTP_X_CSRF_TOKEN"])) {
        $token = $_SERVER["HTTP_X_CSRF_TOKEN"];
    }
    if (
        empty($_SESSION["csrf_token"]) ||
        empty($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        error_log(
            "CSRF validation failed. Session: " .
                ($_SESSION["csrf_token"] ?? "None") .
                " | Submitted: " .
                ($token ?? "None")
        );
        http_response_code(403);
        $isAjax =
            (!empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
                strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) ==
                    "xmlhttprequest") ||
            strpos($_SERVER["HTTP_ACCEPT"] ?? "", "application/json") !== false;
        if ($isAjax) {
            header("Content-Type: application/json");
            echo json_encode([
                "success" => false,
                "message" => "Invalid security token. Refresh page.",
            ]);
        } else {
            exit(
                "<h1>403 Forbidden</h1><p>Invalid security token. Go back, refresh, try again.</p>"
            );
        }
        exit();
    }
}

// --- Sanitize Text Function ---
function sanitize_text(?string $text): string
{
    return htmlspecialchars($text ?? "", ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

// --- HTMLPurifier Function (Keep if re-adding WYSIWYG later) ---
function sanitize_html(?string $dirtyHtml): string
{
    if ($dirtyHtml === null || $dirtyHtml === "") {
        return "";
    }
    static $purifier = null;
    if ($purifier === null) {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set(
            "HTML.Allowed",
            "p,b,strong,i,em,u,ul,ol,li,br,a[href|title|target]"
        );
        $config->set("HTML.TargetBlank", true);
        $config->set("HTML.Nofollow", true);
        $config->set("AutoFormat.AutoParagraph", true);
        $config->set("AutoFormat.Linkify", true);
        $cachePath = dirname(__DIR__, 2) . "/storage/cache/htmlpurifier";
        if (!is_dir($cachePath)) {
            @mkdir($cachePath, 0775, true);
        }
        if (is_writable($cachePath)) {
            $config->set("Cache.SerializerPath", $cachePath);
            $config->set("Cache.SerializerPermissions", 0775);
        } else {
            error_log(
                "HTMLPurifier cache directory not writable: " . $cachePath
            );
            $config->set("Cache.DefinitionImpl", null);
        }
        $purifier = new \HTMLPurifier($config);
    }
    $cleanHtml = $purifier->purify($dirtyHtml);
    return $cleanHtml;
}
