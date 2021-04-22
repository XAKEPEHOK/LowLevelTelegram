<?php
/**
 * Created for LowLevelTelegram
 * Date: 22.04.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace XAKEPEHOK\LowLevelTelegram;


use GuzzleHttp\Client;

class Telegram
{

    private Client $client;
    private string $apiKey;
    private array $handlers = [];
    private ?int $lastUpdateId = null;

    public function __construct(string $apiKey)
    {
        $this->client = new Client([
            'base_uri' => "https://api.telegram.org/bot{$apiKey}/",
        ]);
        $this->apiKey = $apiKey;
    }

    public function method(string $name, array $body): array
    {
        $response = $this->client->post('', [
            'json' => array_merge(['method' => $name], $body),
        ]);
        $result = json_decode($response->getBody()->getContents(), true);
        return $result['result'] ?? [];
    }

    public function getFileUri(string $fileId): string
    {
        $result = $this->method('getFile', [
            'file_id' => $fileId
        ]);
        return "https://api.telegram.org/file/bot{$this->apiKey}/{$result['file_path']}";
    }

    public function onEvent(callable $handler): void
    {
        $this->handlers[] = $handler;
    }

    public function handleUpdate(array $data)
    {
        foreach ($this->handlers as $handler) {
            $handler($data);
        }
    }

    public function getUpdates(): void
    {
        $body = [];
        if ($this->lastUpdateId) {
            $body = ['offset' => $this->lastUpdateId];
        }

        $updates = $this->method('getUpdates', $body);
        foreach ($updates as $update) {
            $this->lastUpdateId = max($this->lastUpdateId, $update['update_id']) + 1;
            $this->handleUpdate($update);
        }
    }

}