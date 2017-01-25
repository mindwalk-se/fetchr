<?php
namespace Mindwalk\DocumentRepositories;

use Mindwalk\DocumentRepository;

class KlarnaMerchantStatements extends DocumentRepository
{
    protected $baseUrl = 'https://online.klarna.com/';

    protected function getFilePrefix()
    {
        return 'klarna';
    }

    public function get(array $config)
    {
        $this->login($config['username'], $config['password']);

        $files = [];

        foreach ($this->getStoreIds() as $storeId) {
            $files = array_merge($files, $this->downloadLastDocuments($storeId));
        }

        return $files;
    }

    private function login($username, $password)
    {
        $crawler = $this->client->request(
            'GET',
            $this->baseUrl . 'login.yaws'
        );

        $form = $crawler->selectButton('Logga in')->form();

        $crawler = $this->client->submit(
            $form,
            [
                'uid' => $username,
                'password' => $password
            ]
        );

        $errorCrawler = $crawler->filter('.wtinfo');

        if ($errorCrawler->count()) {
            throw new LoginException(trim($errorCrawler->text()));
        }
    }

    private function getStoreIds()
    {
        $crawler = $this->client->request(
            'GET',
            $this->baseUrl . 'start_page.yaws'
        );

        preg_match_all(
            '/value="(.+?)"/',
            $crawler->filter('select[name=eid]')->html(),
            $matches
        );

        if (empty($matches) || empty($matches[1])) {
            return [];
        }

        return $matches[1];
    }

    private function selectStore($storeId)
    {
        $this->client->request(
            'GET',
            $this->baseUrl . 'choose_store.yaws?eid=' . $storeId
        );
    }

    private function downloadLastDocuments($storeId)
    {
        $this->selectStore($storeId);

        $crawler = $this->client->request(
            'GET',
            $this->baseUrl . 'costs.yaws'
        );

        $form = $crawler->selectButton('Visa')->form();

        preg_match_all(
            '/value="(.+?)">/',
            $crawler->filter('select[name=country_currency]')->html(),
            $matches
        );

        if (empty($matches) || empty($matches[1])) {
            return false;
        }

        $files = [];

        foreach ($matches[1] as $countryCurrency) {
            $crawler = $this->client->submit(
                $form,
                [
                    'fromdate' => date('Y-m-d'),
                    'country_currency' => $countryCurrency,
                ]
            );

            $filter = $crawler->filter('#costs_table_id');

            if (!$filter->count()) {
                continue;
            }

            preg_match_all(
                '/<td id="summary_only_pdf_div_(?:.+?)_id"><a href="store_costs\.yaws\?date=(?:.+?)&amp;eid=(?:.+?)&amp;country_currency=(?:.+?)&amp;ext_payout=include_all&amp;pdf=true&amp;summary_only=true">Summary Only PDF<\/a><\/td>\n<td id="amount_div_(?:.+?)_id">(?:.+?)<\/td>/s',
                $filter->html(),
                $matchingRows
            );

            if (empty($matchingRows)) {
                continue;
            }

            foreach ($matchingRows[0] as $matchingRow) {
                if (strpos($matchingRow, '0,00') !== false) {
                    continue;
                }

                $dom = new \DOMDocument();
                $dom->loadHTML($matchingRow);
                foreach ($dom->getElementsByTagName('a') as $node) {
                    $files[] = $this->downloadFile(
                        $this->baseUrl . $node->getAttribute('href'),
                        'pdf'
                    );
                }
            }
        }

        return $files;
    }
}
