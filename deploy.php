<?php
/**
 * GitHub Webhook Auto-Deploy Script
 *
 * Setup:
 * 1. Place this file in your public directory or a secure location
 * 2. Set up a webhook in GitHub: Settings > Webhooks > Add webhook
 *    - Payload URL: https://your-domain.com/deploy.php
 *    - Content type: application/json
 *    - Secret: (set a secret key and update GITHUB_SECRET below)
 *    - Events: Just the push event
 * 3. Make sure the web server user has permission to run git commands
 */

// Configuration
define('GITHUB_SECRET', 'your-secret-key-here'); // Change this!
define('PROJECT_DIR', dirname(__DIR__)); // Adjust path if needed
define('LOG_FILE', PROJECT_DIR . '/storage/logs/deploy.log');
define('BRANCH', 'main');

// Logging function
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(LOG_FILE, "[$timestamp] $message\n", FILE_APPEND);
}

// Verify GitHub signature
function verifySignature($payload, $signature) {
    if (empty($signature)) {
        return false;
    }
    $hash = 'sha256=' . hash_hmac('sha256', $payload, GITHUB_SECRET);
    return hash_equals($hash, $signature);
}

// Start deployment
logMessage("=== Deployment triggered ===");

// Get the payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Verify the request is from GitHub
if (!verifySignature($payload, $signature)) {
    logMessage("ERROR: Invalid signature");
    http_response_code(403);
    die('Invalid signature');
}

// Decode payload
$data = json_decode($payload, true);

// Check if it's a push to the main branch
$ref = $data['ref'] ?? '';
if ($ref !== 'refs/heads/' . BRANCH) {
    logMessage("Ignoring push to branch: $ref");
    die('Not the main branch');
}

// Change to project directory
chdir(PROJECT_DIR);
logMessage("Working directory: " . getcwd());

// Commands to run
$commands = [
    'git fetch origin ' . BRANCH,
    'git reset --hard origin/' . BRANCH,
    'composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev',
    'php artisan migrate --force',
    'php artisan config:cache',
    'php artisan route:cache',
    'php artisan view:cache',
    'php artisan queue:restart',
];

// Execute commands
$output = [];
foreach ($commands as $command) {
    logMessage("Running: $command");
    exec($command . ' 2>&1', $cmdOutput, $returnCode);
    $output[] = "$ $command";
    $output = array_merge($output, $cmdOutput);

    if ($returnCode !== 0) {
        logMessage("ERROR: Command failed with code $returnCode");
        $output[] = "ERROR: Command failed with code $returnCode";
    }
    logMessage("Output: " . implode("\n", $cmdOutput));
}

logMessage("=== Deployment completed ===\n");

// Return response
header('Content-Type: text/plain');
echo implode("\n", $output);
