<?php

declare(strict_types=1);

namespace Petfinder\Http;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpAsyncClient;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\RequestFactory;

class Builder
{
    private ?HttpAsyncClient $http;

    private ?RequestFactory $requestFactory;

    private ?PluginClient $client;

    /**
     * @var Plugin[]
     */
    private array $plugins = [];

    private array $headers = [];

    public function __construct(
        ?HttpAsyncClient $httpClient = null,
        ?RequestFactory $requestFactory = null
    ) {
        $this->http = $httpClient ?? HttpAsyncClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? MessageFactoryDiscovery::class::find();
        $this->headers['X-Api-Sdk'] = 'petfinder-php-sdk/v1.0 (https://github.com/petfinder-com/petfinder-php-sdk)';
    }

    public function getHttpClient(): HttpAsyncClient
    {
        if ($this->client) {
            return $this->client;
        }

        return $this->client = new PluginClient($this->http, $this->plugins);
    }

    public function getRequestFactory(): RequestFactory
    {
        return $this->requestFactory;
    }

    public function addPlugin(Plugin $plugin): void
    {
        $this->client = null;
        $this->plugins[] = $plugin;
    }

    public function removePlugin(string $fqcn): void
    {
        foreach ($this->plugins as $index => $plugin) {
            if ($plugin instanceof $fqcn) {
                unset($this->plugins[$index]);
                $this->client = null;
            }
        }
    }

    public function clearHeaders(): void
    {
        $this->client = null;
        $this->headers = [];

        $this->removePlugin(Plugin\HeaderAppendPlugin::class);
    }

    public function addHeaders(array $headers): void
    {
        $this->client = null;
        $this->headers = array_merge($this->headers, $headers);

        $this->removePlugin(Plugin\HeaderAppendPlugin::class);
        $this->addPlugin(new Plugin\HeaderAppendPlugin($this->headers));
    }

    public function authenticate(string $token): void
    {
        $this->addHeaders(['Authorization' => sprintf('Bearer %s', $token)]);
    }

    public function isAuthenticated(): bool
    {
        return array_key_exists('Authorization', $this->headers);
    }
}
