<?php
namespace Mindwalk\DocumentRepositories;

use Mindwalk\DocumentRepository;

class Fyndiq extends DocumentRepository
{
    protected function getFilePrefix()
    {
        return 'fyndiq';
    }

    public function get(array $config)
    {
        $this->login($config['username'], $config['password']);
        return $this->downloadLastDocument();
    }

    private function login($username, $password)
    {
        $crawler = $this->client->request(
            'GET',
            'https://fyndiq.se/merchant/login/?next=/merchant/'
        );

        $form = $crawler->selectButton('Logga in')->form();

        $crawler = $this->client->submit(
            $form,
            [
                'username' => $username,
                'password' => $password
            ]
        );

        $errorCrawler = $crawler->filter('.errornote');

        if ($errorCrawler->count()) {
            throw new LoginException(trim($errorCrawler->text()));
        }
    }

    private function downloadLastDocument()
    {
        $crawler = $this->client->request(
            'GET',
            'https://fyndiq.se/merchant/merchantpayment/merchantpayment/'
        );

        preg_match_all(
            '/(field-download_pdf\"\>\<a href=\")(.+?)(\"\>)/',
            $crawler->html(),
            $matches
        );

        if (empty($matches) || !isset($matches[2][0])) {
            throw new FetchDocumentException('No document found');
        }

        $filename = 'https://fyndiq.se' . $matches[2][0];

        return $this->downloadFile($filename);
    }
}