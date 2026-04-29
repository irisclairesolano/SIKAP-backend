<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;

class SupabaseStorageService
{
    protected $client;
    protected $url;
    protected $serviceRoleKey;
    protected $bucket;

    public function __construct()
    {
        $this->client = new Client();
        $this->url = config('services.supabase.url');
        $this->serviceRoleKey = config('services.supabase.service_role_key');
        $this->bucket = config('services.supabase.bucket', 'government-ids');
    }

    public function upload(UploadedFile $file, string $path): string
    {
        $response = $this->client->put("{$this->url}/storage/v1/object/{$this->bucket}/{$path}", [
            'headers' => [
                'Authorization' => "Bearer {$this->serviceRoleKey}",
                'Content-Type' => $file->getMimeType(),
                'x-upsert' => 'true',
            ],
            'body' => fopen($file->getRealPath(), 'r')
        ]);

        return "{$this->url}/storage/v1/object/public/{$this->bucket}/{$path}";
    }
}
