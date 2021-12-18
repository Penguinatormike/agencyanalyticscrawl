<?php
include 'class\Crawler.php';
use class\Crawler;

$agencyAnalyticsCrawler = new Crawler('https://agencyanalytics.com/', 6);
$agencyAnalyticsCrawler->crawl($agencyAnalyticsCrawler->getUrl(), 0);

echo $agencyAnalyticsCrawler->print();

