<?php

namespace Mindwalk;

use Mindwalk\DocumentRepositories\FetchDocumentException;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;

abstract class DocumentRepository
{
    protected $client;
    protected $guzzleClient;

    public function __construct()
    {
        $this->guzzleClient = new GuzzleClient([
            'timeout' => 60,
        ]);
        $this->client = new Client();
        $this->client->setClient($this->guzzleClient);
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

    protected function getFilePrefix()
    {
        return 'fetchr';
    }

    /**
     * @param string $filePath
     * @param string|null $extension
     * @return string
     * @throws FetchDocumentException
     */
    protected function downloadFile($filePath, $extension = null)
    {
        $temporaryFilename = tempnam('/tmp', $this->getFilePrefix() . '-');

        /**
         * Copy all cookies to Guzzle
         */
        $cookieJar = $this->client->getCookieJar();
        $guzzleCompatibleCookies = [];

        foreach ($cookieJar->all() as $cookie) {
            $guzzleCompatibleCookies[] = [
                'Name'     => $cookie->getName(),
                'Value'    => $cookie->getValue(),
                'Domain'   => $cookie->getDomain(),
                'Path'     => $cookie->getPath(),
                'Expires'  => $cookie->getExpiresTime(),
                'Secure'   => $cookie->isSecure(),
            ];
        }

        $guzzleCookieJar = new \GuzzleHttp\Cookie\CookieJar(
            false,
            $guzzleCompatibleCookies
        );

        $guzzleClient = new GuzzleClient([
            'timeout' => 60,
            'cookies' => $guzzleCookieJar,
        ]);

        $response = $guzzleClient->request('GET', $filePath);

        if (empty($extension)) {
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);

            if (preg_match('/.*filename=([^ ]+)/', $response->getHeaderLine('Content-Disposition'), $matches)) {
                $temporaryFilename .= '-' . $matches[1];
            }

            if (!empty($extension)) {
                $temporaryFilename .= '.' . $extension;
            }
        }
        else {
            $temporaryFilename .= '.' . $extension;
        }

        if (!file_put_contents($temporaryFilename, $response->getBody())) {
            throw new FetchDocumentException('Failed to download file ' . $filePath . ' to ' . $temporaryFilename);
        }

        return $temporaryFilename;
    }
}