<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Get first submission
$submission = App\Models\ProjectSubmission::first();
if (!$submission) {
    echo "No submissions found\n";
    exit;
}
echo "Found submission ID: " . $submission->id . "\n";

// Get a director user and make a token
$director = App\Models\User::where('role', 'director')->first();
if (!$director) {
    echo "No director found\n";
    exit;
}
echo "Director: " . $director->email . "\n";
$token = $director->createToken('test-delete')->plainTextToken;
echo "Token created\n";

// Make the DELETE request
$request = Illuminate\Http\Request::create(
    '/api/submissions/' . $submission->id,
    'DELETE',
    [],
    [],
    [],
    ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token]
);

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Body: " . $response->getContent() . "\n";
