<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ProjectType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubmitMultipleFilesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_submit_multiple_files(): void
    {
        $user = User::factory()->create([
            'role' => 'member',
        ]);

        $projectType = ProjectType::firstOrCreate(['name' => 'Web App']);

        Sanctum::actingAs($user);

        $response = $this->post('/api/submissions', [
            'title' => 'Test Project',
            'tags' => 'tag1, tag2',
            'project_type_id' => $projectType->id,
            'owner_type' => 'individual',
            'description' => 'Test description',
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
            'document' => [
                UploadedFile::fake()->create('doc1.pdf', 100),
                UploadedFile::fake()->create('doc2.pdf', 100),
            ],
            'source_code' => [
                UploadedFile::fake()->create('source1.zip', 100),
                UploadedFile::fake()->create('source2.zip', 100),
            ],
            'dataset' => [
                UploadedFile::fake()->create('dataset1.csv', 100),
                UploadedFile::fake()->create('dataset2.csv', 100),
            ],
        ]);

        if ($response->status() !== 201) {
            echo "\nSTATUS: " . $response->status() . "\n";
            echo "CONTENT: " . $response->getContent() . "\n";
        }

        $response->assertStatus(201);
    }

    public function test_index_as_director(): void
    {
        $user = User::factory()->create([
            'role' => 'director',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/submissions');

        if ($response->status() !== 200) {
            echo "\nDIRECTOR STATUS: " . $response->status() . "\n";
            echo "DIRECTOR CONTENT: " . $response->getContent() . "\n";
        }

        $response->assertStatus(200);
    }

    public function test_index_as_assistant(): void
    {
        $user = User::factory()->create([
            'role' => 'assistant',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/submissions');

        if ($response->status() !== 200) {
            echo "\nASSISTANT STATUS: " . $response->status() . "\n";
            echo "ASSISTANT CONTENT: " . $response->getContent() . "\n";
        }

        $response->assertStatus(200);
    }

    public function test_authenticate_via_cookie_only(): void
    {
        $user = User::factory()->create([
            'role' => 'member',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->call('GET', '/api/user', [], [
            'radice_auth_token' => $token,
        ]);

        if ($response->status() !== 200) {
            echo "\nCOOKIE AUTH STATUS: " . $response->status() . "\n";
            echo "COOKIE AUTH CONTENT: " . $response->getContent() . "\n";
        }

        $response->assertStatus(200);
        $response->assertJsonPath('email', $user->email);
    }

    public function test_delete_submission_cascades_and_deletes_contribution_files(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'member',
        ]);

        $projectType = ProjectType::firstOrCreate(['name' => 'Web App']);

        Sanctum::actingAs($user);

        // 1. Create a submission
        $subResponse = $this->post('/api/submissions', [
            'title' => 'Test Project for Cascade Delete',
            'tags' => 'tag1, tag2',
            'project_type_id' => $projectType->id,
            'owner_type' => 'individual',
            'description' => 'Test description',
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
            'document' => [
                UploadedFile::fake()->create('doc1.pdf', 100),
            ],
            'source_code' => [
                UploadedFile::fake()->create('source1.zip', 100),
            ],
            'dataset' => [
                UploadedFile::fake()->create('dataset1.csv', 100),
            ],
        ]);

        $subResponse->assertStatus(201);
        $submissionId = $subResponse->json('data.id');

        // 2. Add a contribution with file
        $conResponse = $this->post("/api/submissions/{$submissionId}/contributions", [
            'category' => 'manuscript',
            'file' => UploadedFile::fake()->create('contribution_manuscript.pdf', 100),
        ]);

        $conResponse->assertStatus(201);
        $contributionId = $conResponse->json('data.id');
        $contribution = \App\Models\GroupContribution::find($contributionId);
        $contributionFilePath = $contribution->file_path;

        // Verify contribution file exists in storage
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($contributionFilePath);

        // 3. Delete the project submission
        $delResponse = $this->delete("/api/submissions/{$submissionId}");
        $delResponse->assertStatus(200);

        // Verify project submission is deleted
        $this->assertNull(\App\Models\ProjectSubmission::find($submissionId));

        // Verify related group contribution is deleted
        $this->assertNull(\App\Models\GroupContribution::find($contributionId));

        // Verify contribution file is deleted from disk
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($contributionFilePath);
    }

    public function test_api_always_returns_json_even_without_accept_header(): void
    {
        // Make request to protected API endpoint without any Accept headers or credentials
        $response = $this->call('GET', '/api/submissions');

        // It should return 401 Unauthenticated instead of trying to redirect to /login route
        $response->assertStatus(401);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJson([
            'message' => 'Unauthenticated.'
        ]);
    }
}
