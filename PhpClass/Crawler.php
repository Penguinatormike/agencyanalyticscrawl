<?php

namespace PhpClass;

/**
 * Class to crawl a page detailing:
 *
 * Number of pages crawled
 * Number of a unique images
 * Number of unique internal links
 * Number of unique external links
 * Average page load in seconds
 * Average word count
 * Average title length
 */
class Crawler
{
    const URL = 'url';
    const TIME = 'time';
    const IMAGE = 'image';
    const INTERNAL_PAGE = 'internalPage';
    const EXTERNAL_PAGE = 'externalPage';
    const WORD = 'word';
    const TITLE = 'title';
    const STATUS_CODE = 'statusCode';

    /** @var array $links */
    private array $links = [];

    /** @var string $url */
    private string $url = '';

    /** @var int $linksToTraverse */
    private int $linksToTraverse = 0;

    /** @var array $crawled */
    private array $crawled = [];

    public function __construct(string $url, int $linksToTraverse)
    {
        $this->url             = $url;
        $this->linksToTraverse = $linksToTraverse;
    }

    /**
     * Recursively crawl through $url pages until $linksToTraverse is exceeded
     * @param string $url
     * @param int $linksToTraverse
     * @return void
     */
    public function crawl(string $url, int $linksToTraverse) : void
    {
        if ($linksToTraverse <= $this->linksToTraverse) {
            $curlHandler = curl_init();
            curl_setopt($curlHandler, CURLOPT_URL, $url);
            curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
            $startTime = microtime(true);
            $content = curl_exec($curlHandler);
            $endTime = microtime(true);

            $this->crawled[$linksToTraverse][self::URL]           = $url;
            $this->crawled[$linksToTraverse][self::TIME]          = $this->getDuration($endTime, $startTime);
            $this->crawled[$linksToTraverse][self::STATUS_CODE]   = $this->getStatusCode($curlHandler);
            $this->crawled[$linksToTraverse][self::TITLE]         = $this->getTitle($content);
            $this->crawled[$linksToTraverse][self::WORD]          = $this->getWord($content);
            $this->crawled[$linksToTraverse][self::INTERNAL_PAGE] = $links = $this->getInternalPages($content);
            $this->crawled[$linksToTraverse][self::EXTERNAL_PAGE] = $this->getExternalPages($content);
            $this->crawled[$linksToTraverse][self::IMAGE]         = $this->getImages($content);

            curl_close($curlHandler);
            // set links to be traversed by first/home page
            if (count($this->links) == 0) {
                $this->links = $links;
            }

            $linksToTraverse++;
            $this->crawl($this->url . $this->links[$linksToTraverse], $linksToTraverse);
        }
    }

    /**
     * Base function for getting content by regex
     * @param $regex
     * @return array
     */
    private function getContentByRegex(string $regex, string $content) : array
    {
        preg_match_all($regex, $content, $parts);
        return $parts[1] ?? [];
    }

    /**
     * Get internal url array
     * @return array
     */
    private function getInternalPages(string $content) : array
    {
        return $this->getContentByRegex('|<a.*href="\/(.*?)"|', $content);
    }

    /**
     * Get external url array
     * @return array
     */
    private function getExternalPages(string $content) : array
    {
        return $this->getContentByRegex('|<a.*?href="http(.*?)"|', $content);
    }

    /**
     * Get Images array
     * @param string $content
     * @return array
     */
    private function getImages(string $content) : array
    {
        return $this->getContentByRegex('|<img.*?src="(.*?)"|', $content);
    }

    /**
     * Get Title string
     * @param string $content
     * @return string|null
     */
    private function getTitle(string $content) : ?string
    {
        $title = $this->getContentByRegex('/<title>(.*?)<\/title>/', $content);
        return !empty($title) ? $title[0] : '';
    }

    /**
     * Calculate time to fetch url rounded to 2 decimal places
     * @param float $endTime
     * @param float $startTime
     * @return float
     */
    private function getDuration(float $endTime, float $startTime) : float
    {
        return round($endTime - $startTime, 2);
    }

    /**
     * get http status code
     * @param $curlHandler
     * @return string
     */
    private function getStatusCode($curlHandler) : string
    {
        return curl_getinfo($curlHandler)['http_code'];
    }

    /**
     * get words of page, stripping tags that are non-words
     *
     * @link https://stackoverflow.com/a/3485707/2958996
     * @param string $content
     * @return string
     */
    private function getWord(string $content) : string
    {
        $search = [
            '@<script[^>]*?>.*?</script>@si',  // Strip out javascript
            '@<head>.*?</head>@siU',           // Strip the head section
            '@<style[^>]*?>.*?</style>@siU',   // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@'        // Strip multi-line comments including CDATA
        ];

        $contents = preg_replace($search, '', $content);

        return strip_tags($contents);
    }

    public function getUrl() : string
    {
        return $this->url;
    }

    /**
     * Prints table and aggregates crawled data
     * @return string
     */
    public function print() : string
    {
        $output  = '<table border="1px">';
        $output .= '<tr>
                        <th>Url</th>
                        <th>Http Status Code</th>
                        <th>Number Images</th>
                        <th>Number Internal Links</th>
                        <th>Number External Links</th>
                        <th>Page Load Time (s)</th>
                        <th>Word Count</th>
                        <th>Title Length</th>
                    </tr>';

        $stats = [];
        for ($linksToTraverse = 0; $linksToTraverse < $this->linksToTraverse; $linksToTraverse++) {
            $url                  = $this->crawled[$linksToTraverse][self::URL];
            $statusCode           = $this->crawled[$linksToTraverse][self::STATUS_CODE];
            $images               = $this->crawled[$linksToTraverse][self::IMAGE] ?? [];
            $internalPage         = $this->crawled[$linksToTraverse][self::INTERNAL_PAGE] ?? [];
            $externalPage         = $this->crawled[$linksToTraverse][self::EXTERNAL_PAGE] ?? [];
            $time                 = $this->crawled[$linksToTraverse][self::TIME];
            $stats[self::WORD][]  = $wordCount = str_word_count($this->crawled[$linksToTraverse][self::WORD] ?? 0);
            $stats[self::TITLE][] = $titleLength = strlen($this->crawled[$linksToTraverse][self::TITLE] ?: '');

            $stats[self::IMAGE]         = array_merge($stats[self::IMAGE] ?? [], $images);
            $stats[self::INTERNAL_PAGE] = array_merge($stats[self::INTERNAL_PAGE] ?? [], $internalPage);
            $stats[self::EXTERNAL_PAGE] = array_merge($stats[self::EXTERNAL_PAGE] ?? [], $externalPage);
            $stats[self::TIME]          = array_merge($stats[self::TIME] ?? [], [$time]);

            $output .= "<tr>
                            <td>{$url}</td>
                            <td>{$statusCode}</td>
                            <td>".count($images)."</td>
                            <td>".count($internalPage)."</td>
                            <td>".count($externalPage)."</td>
                            <td>{$time}</td>
                            <td>{$wordCount}</td>
                            <td>{$titleLength}</td>
                        </td>";
        }
        $output .= '</table><br/>';


        $uniqueImages        = count(array_unique($stats[self::IMAGE]));
        $uniqueInternalLinks = count(array_unique($stats[self::INTERNAL_PAGE]));
        $uniqueExternalLinks = count(array_unique($stats[self::EXTERNAL_PAGE]));
        $avgTime             = round(array_sum($stats[self::TIME]) / $this->linksToTraverse, 2);
        $avgWordCount        = (int)(array_sum($stats[self::WORD]) / $this->linksToTraverse);
        $avgTitleCount       = (int)(array_sum($stats[self::TITLE]) / $this->linksToTraverse);

        $output .= "<div>Web Crawl of {$this->url}</div>";
        $output .= "<div>Pages crawled: {$this->linksToTraverse}</div>";
        $output .= "<div>Number of a unique images: {$uniqueImages}</div>";
        $output .= "<div>Number of unique internal links: {$uniqueInternalLinks}</div>";
        $output .= "<div>Number of unique external links: {$uniqueExternalLinks}</div>";
        $output .= "<div>Average page loads in seconds: {$avgTime}</div>";
        $output .= "<div>Average word count: {$avgWordCount}</div>";
        $output .= "<div>Average title length: {$avgTitleCount}</div>";

        return $output;
    }
}