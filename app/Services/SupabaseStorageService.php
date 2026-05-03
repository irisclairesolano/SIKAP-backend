<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;

class SupabaseStorageService
{
    protected $client;
    protected $url;
    protected $storageUrl;
    protected $serviceRoleKey;
    protected $bucket;

    public function __construct()
    {
        $this->client = new Client();
        $this->url = config('services.supabase.url');
        $this->serviceRoleKey = config('services.supabase.service_role_key');
        $this->bucket = config('services.supabase.bucket', 'government-ids');
        
        // Extract Supabase project URL from REST URL
        $this->storageUrl = str_replace('/rest/v1', '', $this->url);
    }

    public function upload(UploadedFile $file, string $path): string
    {
        $storageUrl = "{$this->storageUrl}/storage/v1/object/{$this->bucket}/{$path}";
        
        $response = $this->client->put($storageUrl, [
            'headers' => [
                'Authorization' => "Bearer {$this->serviceRoleKey}",
                'Content-Type' => $file->getMimeType(),
                'x-upsert' => 'true',
                'apikey' => $this->serviceRoleKey,
            ],
            'body' => fopen($file->getRealPath(), 'r')
        ]);

        return "{$this->storageUrl}/storage/v1/object/public/{$this->bucket}/{$path}";
    }
}
