<?php

include 'PhpClass/Crawler.php';
use PhpClass\Crawler;

$agencyAnalyticsCrawler = new Crawler('https://agencyanalytics.com/', 6);
$agencyAnalyticsCrawler->crawl($agencyAnalyticsCrawler->getUrl(), 0);

echo $agencyAnalyticsCrawler->print();

?>
