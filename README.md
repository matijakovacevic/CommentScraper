# Site Comment Scraper

----

Simple site comments scraping class, built on top of [Goutte web scraper](https://github.com/FriendsOfPHP/Goutte).

---
## install
clone it

    git clone https://github.com/matijakovacevic/CommentScraper CommentScraper

update dependencies

    cd CommentScraper
    composer update


----
## usage

There is an example usage of the class in index.php file

It features built-in scraping functionality of Tripadvisor/Booking.com sites. All that is needed in those cases is the config array with languages and their appropriate URIs to scrape (properties can be overriden).


For other sites, full config array has to be provided, for example (booking com)

```php
$sitesConfig = array(
    'booking' => array(
        'n_comments' => 5, // number of comments
        'commentSelector' => '.review_item',
        'elemSelectors' => array( // elements ids and selectors
            'author'  => '.review_item_reviewer h4',
            'country' => '.review_item_reviewer .reviewer_country',
            'score'   => '.review_item_header_score_container',
            'header'  => '.review_item_header_content',
            'content' => '.review_item_review_content',
            'date'    => '.review_item_header_date'
        ),
        'languageURI' => array(
            'en' => 'http://www.....' // URI
        ),
        'parseFn' => function ($elementNode, $key){
        // - function iterates through every elemSelector element
        //
        // args
        // - $elementNode - element object
        // - $key - key from 'elemSelectors' array, for parsing specific
        //   elements
        }
    )
);
```

## example (index.php)

1. Instantiate Goutte Client.
2. Pass the instance to the class with config array
3. Use getComments function on class (accepts string parameter to only fetch/parse certain site from config array) to get array of comments for each site

```php
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

```

## changelog
* 24/03/2015 - initial version
