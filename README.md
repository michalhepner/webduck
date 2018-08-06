# Webduck

An application to test frontend site problems.

## Getting Started

### Installing

1. Clone the project repository.
2. Run:

```
composer install
npm install
```

## Usage

### page:audit

Performs checks for specific URL(s) provided as arguments
```
bin/console page:audit https://example.com https://example.com/page1.html --save-html=report.html --audit-violations --audit-resource-load
```

Available options:

* --save-html=filename.html : Allows to save the result in a HTML file.
* --audit-violations : Tests the results against best coding practice violations.
* --audit-resource-load : Tests if resources like CSS, image, JS files are not being loaded too slow.


### sitemap:audit

Performs checks against URLs found in provided sitemap URL.

```
bin/console sitemap:audit https://example.com/sitemap.xml --pool-size=5 --save-html=report.html --audit-violations --audit-resource-load
```

Available options:

* --pool-size : Defines how many parallel URLs should be called at the same time. Significantly affects performance.
*IMPORTANT*!! Setting value higher than 5 is strongly discouraged!
* --save-html=filename.html : Allows to save the result in a HTML file.
* --audit-violations : Tests the results against best coding practice violations.
* --audit-resource-load : Tests if resources like CSS, image, JS files are not being loaded too slow.


### site:audit

Goes to provided URLs, scrapes found links and crawls them recursively to perform checks against found pages.

```
bin/console site:audit https://example.com/page1 \
    --pool-size=5 \
    --save-html=report.html \
    --allowed-host=www.example.com \
    --allowed-host=example2.com \
    --audit-violations
    --url-filter='.*\/admin\/.*'
    --url-filter='.*\.(jpg|jpeg|pdf|png)'
```

Available options:

* --pool-size=5 : Defines how many parallel URLs should be called at the same time. Significantly affects performance.
*IMPORTANT*!! Setting value higher than 5 is strongly discouraged!
* --save-html=filename.html : Allows to save the result in a HTML file.
* --audit-violations : Tests the results against best coding practice violations.
* --audit-resource-load : Tests if resources like CSS, image, JS files are not being loaded too slow.
* --allowed-host : (MULTIPLE) allows to crawl URLs pointing to different domains. Useful when site uses more than one domain,
or when site has inconsistent www and non-www prefixed URLs.
* --url-filter : (MULTIPLE) if found url matches provided REGEX pattern it will not be crawled.

## Authors

* **Michał Hepner** <michal.hepner@gmail.com> - *design and implementation*

## License

 * Written by Michał Hepner <michal.hepner@gmail.com> 2018
 * For more info please check LICENSE.txt
