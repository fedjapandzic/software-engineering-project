<?php

require __DIR__ . '/vendor/autoload.php';

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

$chromeOptions = new \Facebook\WebDriver\Chrome\ChromeOptions();
// $chromeOptions->addArguments(['--headless']); // Remove this line if you want to see the browser

$capabilities = DesiredCapabilities::chrome();
$capabilities->setCapability(\Facebook\WebDriver\Chrome\ChromeOptions::CAPABILITY, $chromeOptions);

// Specify the path to your ChromeDriver executable
$chromeDriverPath = 'C:/Users/Pedja/Downloads/chromedriver-win64/chromedriver';

$webDriver = RemoteWebDriver::create("http://localhost:4444/wd/hub", $capabilities);

// Open the registration page
$webDriver->get('https://se-project-vcc8.onrender.com');

// Fill out the registration form
$webDriver->findElement(WebDriverBy::id('full_name'))->sendKeys('John Doe');
$webDriver->findElement(WebDriverBy::id('username'))->sendKeys('johndoe123');
$webDriver->findElement(WebDriverBy::id('email'))->sendKeys('johndoe@gmail.com');
$webDriver->findElement(WebDriverBy::id('phone_number'))->sendKeys('+38761222333');
$webDriver->findElement(WebDriverBy::id('password'))->sendKeys('ydgnhrfrw3213321');
$webDriver->findElement(WebDriverBy::id('repeat_password'))->sendKeys('ydgnhrfrw3213321');

// Submit the form
$webDriver->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

// Wait for a few seconds to see the result (you can adjust this)
sleep(3);

// Close the browser
$webDriver->quit();
