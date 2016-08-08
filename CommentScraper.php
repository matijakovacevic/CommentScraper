<?php
namespace CompressD;

use Exception;

/**
 * Comment scraper class with built-in configuration for Booking.com and Tripadvisor.com sites
 *
 * @category   Scraper
 * @package    CommentScraper
 * @author     Matija Kovačević <mata987@gmail.com>
 * @license    http://opensource.org/licenses/MIT  MIT license
 * @version    Release: 0.1
 * @link       https://github.com/matijakovacevic/CommentScraper
 */
class CommentScraper
{
    /**
     * Variable to hold Client object
     *
     * @var object Client class
     */
    private $client;

    /**
     * Name of the client class, so it can be re-instantiated
     *
     * @var string
     */
    private $clientClass;

    /**
     * Comments from sites
     *
     * @var array
     */
    private $comments = array();

    /**
     * Headers for scraper class
     *
     * @var array
     */
    private $headers = array(
        'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:38.0) Gecko/20100101 Firefox/38.0',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Encoding' => 'gzip, deflate',
        'Accept-Language' => 'en-US,en;q=0.5',
        'Connection' => 'keep-alive',
    );

    /**
     * Default sites configuration
     *
     * Contains built-in Booking and Tripadvisor configuration
     * (Only needs URIs to be passed into the configuration for those sites)
     *
     * @var array
     */
    private $sites = array(
        'booking' => array(
            'n_comments' => 5,
            'commentSelector' => '.review_item',
            'elemSelectors' => array(
                'author'  => '.review_item_reviewer h4',
                'country' => '.review_item_reviewer .reviewer_country',
                'score'   => '.review_item_header_score_container',
                'header'  => '.review_item_header_content',
                'content' => '.review_item_review_content',
                'date'    => '.review_item_date',
            )
        ),
        'tripadvisor' => array(
            'n_comments' => 10,
            'commentSelector' => '.review',
            'elemSelectors' => array(
                'author'  => '.member_info .username',
                'country' => '.member_info .location',
                'score'   => '.rate',
                'header'  => '.quote',
                'content' => '.partial_entry',
                'date'    => '.ratingDate',
            ),
        ),
    );

    /**
     * Instantiate scraper class and merge configs
     *
     * @param object $clientObject Object of the scraper class
     * @param array  $sitesConfig  Sites configuration
     */
    public function __construct($clientObject, array $sitesConfig = array())
    {
        $this->clientClass = '\\'.get_class($clientObject);

        if (!empty($sitesConfig)) {
            $this->sites = array_replace_recursive($this->sites, $sitesConfig);
        }
    }

    /**
     * Function for initializing everything
     *
     * @param  string  $site         Site key
     * @param  boolean $forceRefresh Force refresh (re-fetch)
     * @return array
     */
    public function getComments($site = '', $forceRefresh = false)
    {
        try {
            if ($site) {
                if (!array_key_exists($site, $this->sites)) {
                    throw new Exception('No site configuration found');
                }

                if (!isset($this->comments[$site])
                    || empty($this->comments[$site])
                    || $forceRefresh === true) {
                        $this->fetchComments($site);
                }

                return $this->comments[$site];
            }
        } catch (Exception $e) {
            $file = $e->getFile();
            $line = $e->getLine();

            die("Error crawling site '$site'! Message: ".$e->getMessage()." ($file:$line)");
        }

        if (empty($this->comments) || $forceRefresh === true) {
            foreach ($this->sites as $site => $config) {
                $this->fetchComments($site);
            }
        }

        return $this->comments;
    }

    /**
     * Fetches single or multiple (paginated) URIs
     *
     * @param  string $site Site key
     * @return void
     */
    private function fetchComments($site)
    {
        foreach ($this->sites[$site]['languageURI'] as $lang => $uri) {
            // it is possible that the comments are paged
            // if an array of links is provided per language, go through each
            // else fetch just one URI and parse it for comments
            if (is_array($uri)) {
                foreach ($uri as $lang_uri) {
                    $this->fetchURI($site, $lang, $lang_uri);
                }

                continue;
            }

            $this->fetchURI($site, $lang, $uri);
        }
    }

    /**
     * Fetch single URI and parse content
     *
     * @param  string $site Site key
     * @param  string $lang Lang key
     * @param  string $uri  URI to fetch
     * @return void
     */
    private function fetchURI($site, $lang, $uri)
    {
        try {
            $this->client = new $this->clientClass();
            $this->initHeaders();

            // set Host header (works better if set)
            $siteURI = parse_url($uri);
            $this->client->setHeader('Host', $siteURI['host']);

            // fetch html
            $crawler = $this->client->request('GET', $uri);

            $crawler->filter($this->sites[$site]['commentSelector'])->each(function ($node, $i) use ($site, $lang) {
                if ($i < $this->sites[$site]['n_comments']) {
                    // fetch no more than n comments
                    $this->parseContent($node, $site, $lang);
                }
            });

            unset($this->client);
        } catch (Exception $e) {
            $file = $e->getFile();
            $line = $e->getLine();

            die("Error crawling site '$site'! Message: ".$e->getMessage()." ($file:$line)");
        }
    }

    /**
     * Sets headers
     *
     * @return void
     */
    private function initHeaders()
    {
        foreach ($this->headers as $header => $value) {
            $this->client->setHeader($header, $value);
        }
    }

    /**
     * Sets header item
     *
     * @param  string       $key Name of the Header property
     * @param  string       $val Value for the Header property
     * @return void|boolean False if any of the attributes is empty
     */
    public function setHeader($key, $val)
    {
        if (!$key || !$val) {
            return false;
        }

        $this->headers[$key] = $val;
    }

    /**
     * Sets the whole Header array, if one wants to overwrite it
     *
     * @param  array        $headers Headers properties and values array
     * @return void|boolean False if $headers are empty or not an array
     */
    public function setHeaders($headers)
    {
        if (!is_array($headers) || empty($headers)) {
            return false;
        }

        $this->headers = $headers;
    }

    /**
     * Parsing comments and push to $comments array
     *
     * @param  object $node Element node object
     * @param  string $site Site key
     * @param  string $lang Lang key
     * @param  string $uri  URI to fetch
     * @return void
     */
    private function parseContent($node, $site, $lang)
    {
        $elemSelectors = $this->sites[$site]['elemSelectors'];

        if (!count($elemSelectors)) {
            return false;
        }

        $newReview = array(
            'id' => '', // md5 hash of parsed content for comparing comments
            'site'    => $site,
        );

        foreach ($elemSelectors as $key => $el) {
            $elNode = $node->filter($el);

            // if element not found, skip to another
            if ($elNode === null) {
                continue;
            }

            // predefined site content parsing fn
            $parseFnName = 'parse'.ucfirst($site).'Content';

            if (method_exists($this, $parseFnName)) {
                $val = call_user_func_array(
                    array($this, $parseFnName),
                    array($elNode, $key)
                );
            } elseif (isset($this->sites[$site]['parseFn']) && is_callable($this->sites[$site]['parseFn'])) {
                // call user provided parse function
                $fn = $this->sites[$site]['parseFn'];

                $val = $fn($elNode, $key);
            }

            $newReview[$key] = strip_tags($val); // to be on the safe side

            if ($key == 'content') {
                // add md5 hash
                $newReview['id'] = md5($newReview[$key]);
            }
        }

        if (!isset($this->comments[$site])) {
            $this->comments[$site] = array();
        }

        if (!isset($this->comments[$site][$lang])) {
            $this->comments[$site][$lang] = array();
        }

        array_push($this->comments[$site][$lang], $newReview);
    }

    /**
     * Predefined/built-in function for parsing Booking.com comments
     *
     * @param  string $elementNode jQuery-like element selectors
     * @param  string $key         Part of the comment block
     * @return string Comment part value parsed and filtered
     */
    private function parseBookingContent($elementNode, $key)
    {
        if (is_null($elementNode->getNode(0))) {
            return '';
        }

        if ($key == 'content') {
            $val =  trim($elementNode->html());
            // remove blank lines + newline at the end of comment
            // swap + and - html with signs
            $val = preg_replace(
                [
                    '/^\n+|^[\t\s]*\n+/',
                    '/<p class="review_neg">(\n)*/',
                    '/<p class="review_pos">(\n)*/',
                    '/<\/p>/',
                    '/\n{1}$/', ],
                [
                    '',
                    '--- ',
                    '+++ ',
                    '',
                     '',
                 ],
                $val
            );
        } elseif ($key == 'score') {
            $val = trim($elementNode->text()).'/10';
        } else {
            $val = trim($elementNode->text());
        }

        return $val;
    }

    /**
     * Predefined/built-in function for parsing Tripadvisor.com comments
     *
     * @param  string $elementNode jQuery-like element selectors
     * @param  string $key         Part of the comment block
     * @return string Comment part value parsed and filtered
     */
    private function parseTripAdvisorContent($elementNode, $key)
    {
        if ($key == 'content') {
            $val = trim($elementNode->text());
            $val = preg_replace(
                ['/(^\n+|^[\t\s]*\n+)(More\s*)/m'],
                [''],
                $val
            );
        } elseif ($key == 'score') {
            $score = array();
            $html = $elementNode->html();

            preg_match('/alt="([1-5]){1}/', $html, $score);

            if (!count($score)) {
                $val = '';
                break;
            }

            $val = $score[1].'/5';
        } elseif ($key == 'date') {
            $val = str_replace('Reviewed ', '', trim($elementNode->text()));
        } else {
            $val = trim($elementNode->text());
        }

        return $val;
    }
}
