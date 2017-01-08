<?php
namespace Mindwalk\DocumentRepositories;

use Mindwalk\DocumentRepository;

class Bredband2 extends DocumentRepository
{
    public function get(array $config)
    {
        $this->login($config['username'], $config['password']);
        return $this->downloadLastDocument();
    }

    private function login($username, $password)
    {
        $crawler = $this->client->request(
            'GET',
            'https://portal.bredband2.com/'
        );

        $form = $crawler->selectButton('Logga in')->form();

        $crawler = $this->client->submit(
            $form,
            [
                'cUsername' => $username,
                'cPassword' => $password
            ]
        );

        $errorCrawler = $crawler->filter('.error-message');

        if ($errorCrawler->count()) {
            throw new LoginException(trim($errorCrawler->text()));
        }
    }

    private function downloadLastDocument()
    {
        $crawler = $this->client->request(
            'GET',
            'https://portal.bredband2.com/start/'
        );

        preg_match_all(
            '/"(\/invoice\/invoice\/cInvoiceNumber\/(.+?)\/)"/',
            $crawler->html(),
            $matches
        );

        if (empty($matches) || !isset($matches[1][0])) {
            throw new FetchDocumentException('No document found');
        }

        $filename = 'https://portal.bredband2.com' . $matches[1][0];

        return $this->downloadFile($filename);
    }
}