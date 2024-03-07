<?php
namespace Facebook\WebDriver;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Chrome\ChromeOptions;

require_once('vendor/autoload.php');

$host = 'http://localhost:4444/';

$capabilities = DesiredCapabilities::chrome();

$chromeOptions = new ChromeOptions();
// $chromeOptions->addArguments(['--headless']); // script wil alleen werken als de browser zichtbaar is

$capabilities->setCapability(ChromeOptions::CAPABILITY_W3C, $chromeOptions);
$driver = RemoteWebDriver::create($host, $capabilities);
$driver->manage()->window()->maximize();
$driver->get('https://www.plus.nl/producten/snoep-koek-chocolade-chips-noten/');

$cookieButton = $driver->wait()->until(
    WebDriverExpectedCondition::elementToBeClickable(
        WebDriverBy::cssSelector('.gtm-cookies-popup-accept-all-btn')
    )
);
$cookieButton->click();

// Page down werkt alleen nadat er geklikt is op de pagina
$focus = $driver->wait()->until(
    WebDriverExpectedCondition::elementToBeClickable(
        WebDriverBy::cssSelector('.plp-results-list')
    )
);
$focus->click();

$products = [];
sleep(5);

$elements = $driver->findElements(WebDriverBy::cssSelector('.plp-results-list > a'));

while (count($elements) < 1500) {
    $driver->getKeyboard()->pressKey(WebDriverKeys::SPACE);
    $driver->getKeyboard()->pressKey(WebDriverKeys::SPACE);
    sleep(1);
    $elements = $driver->findElements(WebDriverBy::cssSelector('.plp-results-list > a'));
    echo count($elements) . ' ';

}

$products = [];

foreach ($elements as $element) {
    try {
        $promo = $element->findElement(WebDriverBy::cssSelector('.promo-offer-label span'))->getText();
    }
    catch (Exception\NoSuchElementException $e) {
        $promo = '';
    }
    $products[] = [
        'Afbeelding' => '<img src="' . $element->findElement(WebDriverBy::cssSelector('.plp-item-image-container img'))->getAttribute('src') . '" width="120">',
        'Naam' => $element->findElement(WebDriverBy::cssSelector('.list-item-content-title h3 span'))->getText(),
        'Prijs' => $element->findElement(WebDriverBy::cssSelector('.plp-item-price .product-header-price-integer span'))->getText() . $element->findElement(WebDriverBy::cssSelector('.plp-item-price .product-header-price-decimals span'))->getText(),
        'Gewicht/aantal' => $element->findElement(WebDriverBy::cssSelector('.plp-item-complementary span'))->getText(),
        'Aanbieding' => $promo,
    ];


}

$table = '<table><tr><th>' . implode('</th><th>', array_keys($products[0])) . '</th></tr>';
foreach ($products as $row) {
    $table .= '<tr><td>' . implode('</td><td>', array_values($row)) . '</td></tr>';
}

$file = fopen('output.html', 'w');
fwrite($file, $table . '</table>');
fclose($file);
echo "\n" . 'Output written to output.html' . "\n";
$driver->close();

