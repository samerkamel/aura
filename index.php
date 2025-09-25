<?php

/**
 * Laravel Application Entry Point
 *
 * This file redirects to the public directory where Laravel's
 * actual index.php file is located.
 */

// Redirect to public directory
$publicPath = __DIR__ . '/public/index.php';

if (file_exists($publicPath)) {
    // Change working directory to public
    chdir(__DIR__ . '/public');

    // Include the actual Laravel bootstrap
    require $publicPath;
} else {
    // Fallback error message
    http_response_code(500);
    echo '<h1>Application Error</h1>';
    echo '<p>Laravel public/index.php not found.</p>';
    echo '<p>Please ensure the application is properly installed.</p>';
}