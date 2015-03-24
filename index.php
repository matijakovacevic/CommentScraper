<?php

require './vendor/autoload.php';
require 'CommentScraper.php';

$sitesConfig = array(
    'booking' => array(
        'languageURI' => array(
            'en' => 'http://www.booking.com/reviews/hr/hotel/waldinger.html?r_lang=en&order=score_desc',
            'hr' => 'http://www.booking.com/reviews/hr/hotel/waldinger.html?r_lang=hr&order=score_desc'
        ),
    ),
    'tripadvisor' => array(
        'languageURI' => array(
            'en' => 'http://www.tripadvisor.com/Hotel_Review-g303823-d645060-Reviews-Hotel_Waldinger-Osijek_Osijek_Baranja_County_Slavonia.html'
        ),
    )
);

$client = new \Goutte\Client;
$scraper = new \CompressD\CommentScraper($client, $sitesConfig);

$comments = $scraper->getComments();

var_dump($comments);
exit;
