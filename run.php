<?php
require __DIR__ . '/vendor/autoload.php';

$mailConfig = require __DIR__ . '/config/mail.php';
$repositories = require __DIR__ . '/config/repositories.php';

$database = new \Mindwalk\DocumentFetchDatabase(__DIR__ . '/db/fetched_documents.db');
$fetcher = new \Mindwalk\DocumentFetcher($repositories, $database, $mailConfig);

$fetcher->fetch();