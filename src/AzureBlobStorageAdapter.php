<?php

namespace Matthewbdaly\LaravelAzureStorage;

use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter as BaseAzureBlobStorageAdapter;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\BlobSharedAccessSignatureHelper;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use Matthewbdaly\LaravelAzureStorage\Exceptions\InvalidCustomUrl;
use Carbon\Carbon;

/**
 * Blob storage adapter
 */
final class AzureBlobStorageAdapter extends BaseAzureBlobStorageAdapter
{
    /**
     * The Azure Blob Client
     *
     * @var BlobRestProxy
     */
    private $client;

    /**
     * The container name
     *
     * @var string
     */
    private $container;

    /**
     * Custom url for getUrl()
     *
     * @var string
     */
    private $url;

    /**
     * Account Key
     *
     * @var string
     */
    private $accountKey;

    /**
     * Create a new AzureBlobStorageAdapter instance.
     *
     * @param  \MicrosoftAzure\Storage\Blob\BlobRestProxy $client    Client.
     * @param  string                                     $container Container.
     * @param  string|null                                $url       URL.
     * @param  string|null                                $prefix    Prefix.
     * @throws InvalidCustomUrl                                      URL is not valid.
     */
    public function __construct(BlobRestProxy $client, string $container, string $url = null, $prefix = null, $accountKey = null)
    {
        parent::__construct($client, $container, $prefix);
        $this->client = $client;
        $this->container = $container;
        if ($url && !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidCustomUrl();
        }
        $this->url = $url;
        $this->setPathPrefix($prefix);
        $this->accountKey = $accountKey;
    }

    /**
     * Get the file URL by given path.
     *
     * @param  string $path Path.
     * @return string
     */
    public function getUrl(string $path)
    {
        if ($this->url) {
            return rtrim($this->url, '/') . '/' . ($this->container === '$root' ? '' : $this->container . '/') . ltrim($path, '/');
        }
        return $this->client->getBlobUrl($this->container, $path);
    }
    /**
     * Generate Temporary Url with SAS query
     *
     * @param $path
     * @param $ttl
     * @param $options
     * @return string
     */
    public function getTemporaryUrl($path, $ttl, $options)
    {
        $sas = new BlobSharedAccessSignatureHelper($this->client->getAccountName(), $this->accountKey);
        $sasString = $sas->generateBlobServiceSharedAccessSignatureToken(Resources::RESOURCE_TYPE_BLOB, $this->container . '/' . $path, 'r', $ttl, '', '', 'https');
        return $this->getUrl($path) . '?' . $sasString;
    }
}
