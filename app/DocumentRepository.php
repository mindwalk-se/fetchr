<?php

namespace Mindwalk;

use Mindwalk\DocumentRepositories\FetchDocumentException;
use Goutte\Client;

abstract class DocumentRepository
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @param array $config
     * @return mixed
     */
    abstract public function get(array $config);

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param string $filePath
     * @return string
     * @throws FetchDocumentException
     */
    protected function downloadFile($filePath)
    {
        $temporaryFilename = tempnam('/tmp', 'fetchr');

        $guzzleClient = $this->client->getClient();
        $response = $guzzleClient->get(
            $filePath
        );

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if (preg_match('/.*filename=([^ ]+)/', $response->getHeaderLine('Content-Disposition'), $matches)) {
            $temporaryFilename .= '-' . $matches[1];
        }
        elseif (!empty($extension)) {
            $temporaryFilename .= '.' . $extension;
        }

        if (!file_put_contents($temporaryFilename, $response->getBody())) {
            throw new FetchDocumentException('Failed to download file ' . $filePath);
        }

        return $temporaryFilename;
    }
}