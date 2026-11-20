<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ExportApi extends Command
{
    protected $signature = 'wmo:export-api';

    protected $description = 'Export Scramble OpenAPI and derive Postman and Bruno clients';

    public function handle(): int
    {
        File::ensureDirectoryExists(base_path('docs/postman'));
        File::ensureDirectoryExists(base_path('docs/bruno/environments'));
        if ($this->call('scramble:export', ['--path' => 'docs/openapi.json']) !== 0) {
            return self::FAILURE;
        }
        $spec = json_decode(File::get(base_path('docs/openapi.json')), true, 512, JSON_THROW_ON_ERROR);
        File::delete(File::glob(base_path('docs/bruno/[0-9][0-9]-*.bru')));
        $basePath = parse_url($spec['servers'][0]['url'], PHP_URL_PATH) ?: '/api/v1';
        $items = [];
        $sequence = 0;
        foreach ($spec['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                    continue;
                }
                $sequence++;
                $name = $operation['operationId'] ?? $method.' '.$path;
                $url = '{{base_url}}'.$basePath.preg_replace('/\{(\w+)\}/', '{{$1}}', $path);
                $headers = [['key' => 'Accept', 'value' => 'application/json']];
                foreach ($operation['parameters'] ?? [] as $parameter) {
                    if ($parameter['in'] === 'header') {
                        $headers[] = ['key' => $parameter['name'], 'value' => strtolower($parameter['name']) === 'idempotency-key' ? '{{idempotency_key}}' : ($parameter['example'] ?? '')];
                    }
                }
                $schema = $operation['requestBody']['content']['application/json']['schema'] ?? null;
                $body = $schema ? $this->example($schema, $spec) : null;
                $request = ['method' => strtoupper($method), 'header' => $headers, 'url' => $url, 'description' => $operation['description'] ?? $operation['summary'] ?? ''];
                if ($body !== null) {
                    $request['header'][] = ['key' => 'Content-Type', 'value' => 'application/json'];
                    $request['body'] = ['mode' => 'raw', 'raw' => json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 'options' => ['raw' => ['language' => 'json']]];
                }
                $items[] = ['name' => $name, 'request' => $request];
                $bru = "meta {\n  name: $name\n  type: http\n  seq: $sequence\n}\n\n$method {\n  url: $url\n  body: ".($body === null ? 'none' : 'json')."\n  auth: bearer\n}\n\nauth:bearer {\n  token: {{token}}\n}\n\nheaders {\n";
                foreach ($request['header'] as $header) {
                    $bru .= '  '.$header['key'].': '.$header['value']."\n";
                }
                $bru .= "}\n";
                if ($body !== null) {
                    $bru .= "\nbody:json {\n".json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n}\n";
                }
                File::put(base_path('docs/bruno/'.sprintf('%02d', $sequence).'-'.str_replace('.', '-', $name).'.bru'), $bru);
            }
        }
        $this->json('docs/postman/wmo-api.postman_collection.json', ['info' => ['name' => 'Wmo Utrecht API', 'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json', 'description' => 'Set the token environment variable to a personal access token from the web app. A booking requires a resident ID, a future ISO 8601 timestamp with an offset, and an Idempotency-Key. The token endpoints require a web session with CSRF protection.'], 'auth' => ['type' => 'bearer', 'bearer' => [['key' => 'token', 'value' => '{{token}}', 'type' => 'string']]], 'item' => $items]);
        $variables = ['base_url' => 'http://localhost', 'token' => '', 'resident' => '1', 'token_id' => '1', 'idempotency_key' => '', 'scheduled_at' => '', 'current_password' => ''];
        $collectionPath = base_path('docs/postman/wmo-api.postman_collection.json');
        File::put($collectionPath, str_replace('/tokens/{{token}}', '/tokens/{{token_id}}', File::get($collectionPath)));
        $this->json('docs/postman/local.postman_environment.json', ['name' => 'Wmo local (template)', 'values' => collect($variables)->map(fn ($value, $key) => ['key' => $key, 'value' => $value, 'type' => in_array($key, ['token', 'current_password']) ? 'secret' : 'default', 'enabled' => true])->values()->all(), '_postman_variable_scope' => 'environment']);
        $this->json('docs/bruno/bruno.json', ['version' => '1', 'name' => 'Wmo Utrecht API', 'type' => 'collection', 'ignore' => ['node_modules', '.git']]);
        $environment = "vars {\n";
        foreach ($variables as $key => $value) {
            if (! in_array($key, ['token', 'current_password'])) {
                $environment .= "  $key: $value\n";
            }
        }
        $environment .= "}\nvars:secret [\n  token\n  current_password\n]\n";
        File::put(base_path('docs/bruno/environments/Local.bru'), $environment);
        foreach (File::files(base_path('docs/bruno')) as $file) {
            if ($file->getExtension() === 'bru') {
                File::put($file->getPathname(), str_replace('/tokens/{{token}}', '/tokens/{{token_id}}', File::get($file->getPathname())));
            }
        }
        File::put(base_path('docs/bruno/README.md'), <<<'MARKDOWN'
            # Wmo Utrecht API

            Open the collection in Bruno and select the `Local` environment. Set `token` to a personal access token from the web app.

            For a booking, set `resident`, `scheduled_at` (a future ISO 8601 timestamp with an offset), and `idempotency_key`. Use the same key and request body when retrying a booking.

            The token endpoints require a web session with CSRF protection.
            MARKDOWN
            ."\n");
        $zip = new ZipArchive;
        $zip->open(base_path('docs/bruno.zip'), ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach (File::allFiles(base_path('docs/bruno')) as $file) {
            $zip->addFile($file->getPathname(), 'bruno/'.$file->getRelativePathname());
        }
        $zip->close();
        $this->info('OpenAPI, Postman and Bruno artifacts generated from one specification.');

        return self::SUCCESS;
    }

    private function json(string $path, array $value): void
    {
        File::put(base_path($path), json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
    }

    private function example(array $schema, array $spec, string $name = ''): mixed
    {
        if (isset($schema['$ref'])) {
            $schema = $spec['components']['schemas'][basename($schema['$ref'])];
        }
        if (isset($schema['example'])) {
            return $schema['example'];
        }
        if (isset($schema['examples'][0])) {
            return $schema['examples'][0];
        }
        if (isset($schema['properties'])) {
            $object = [];
            foreach ($schema['properties'] as $key => $property) {
                if (in_array($key, $schema['required'] ?? [])) {
                    $object[$key] = $this->example($property, $spec, $key);
                }
            }

            return $object;
        }
        if ($name === 'resident_id') {
            return '{{resident}}';
        }
        if ($name === 'scheduled_at') {
            return '{{scheduled_at}}';
        }
        if ($name === 'password') {
            return '{{current_password}}';
        }

        return $schema['enum'][0] ?? match ($schema['type'] ?? 'string') {
            'integer', 'number' => $schema['minimum'] ?? 1,
            'boolean' => false,
            'array' => [],
            default => 'Example '.$name,
        };
    }
}
