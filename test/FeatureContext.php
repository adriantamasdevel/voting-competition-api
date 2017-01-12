<?php

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Mink\Driver\Selenium2Driver;

/**
 * Features context.
 */
class FeatureContext extends MinkContext
{

    public $urls;

    private $placeHolders = array();
    static $page;

    /**
     * {@inheritdoc}
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Adds Basic Authentication header to next request.
     *
     * @param string $username
     * @param string $password
     *
     * @Given /^I am authenticating as "([^"]*)" with "([^"]*)" password$/
     */
    public function iAmAuthenticatingAs($username, $password)
    {
        $this->removeHeader('Authorization');
        $this->authorization = base64_encode($username . ':' . $password);
        $this->addHeader('Authorization', 'Basic ' . $this->authorization);
    }

    /**
     * Sets a HTTP Header.
     *
     * @param string $name header name
     * @param string $value header value
     *
     * @Given /^I set header "([^"]*)" with value "([^"]*)"$/
     */
    public function iSetHeaderWithValue($name, $value)
    {
        $this->addHeader($name, $value);
    }

    /**
     * Sends HTTP request to specific relative URL.
     *
     * @param string $method request method
     * @param string $url relative url
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)"$/
     */
    public function iSendARequest($method, $url)
    {
        $url = $this->prepareUrl($url);

        if (version_compare(ClientInterface::VERSION, '6.0', '>=')) {
            $this->request = new Request($method, $url, $this->headers);
        } else {
            $this->request = $this->getClient()->createRequest($method, $url);
            if (!empty($this->headers)) {
                $this->request->addHeaders($this->headers);
            }
        }

        $this->sendRequest();
    }

    /**
     * Sends HTTP request to specific URL with field values from Table.
     *
     * @param string $method request method
     * @param string $url relative url
     * @param TableNode $post table of post values
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with values:$/
     */
    public function iSendARequestWithValues($method, $url, TableNode $post)
    {
        $url = $this->prepareUrl($url);
        $fields = array();

        foreach ($post->getRowsHash() as $key => $val) {
            $fields[$key] = $this->replacePlaceHolder($val);
        }

        $bodyOption = array(
            'body' => json_encode($fields),
        );

        if (version_compare(ClientInterface::VERSION, '6.0', '>=')) {
            $this->request = new Request($method, $url, $this->headers, $bodyOption['body']);
        } else {
            $this->request = $this->getClient()->createRequest($method, $url, $bodyOption);
            if (!empty($this->headers)) {
                $this->request->addHeaders($this->headers);
            }
        }

        $this->sendRequest();
    }

    /**
     * Sends HTTP request to specific URL with raw body from PyString.
     *
     * @param string $method request method
     * @param string $url relative url
     * @param PyStringNode $string request body
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with body:$/
     */
    public function iSendARequestWithBody($method, $url, PyStringNode $string)
    {
        $url = $this->prepareUrl($url);
        $string = $this->replacePlaceHolder(trim($string));

        if (version_compare(ClientInterface::VERSION, '6.0', '>=')) {
            $this->request = new Request($method, $url, $this->headers, $string);
        } else {
            $this->request = $this->getClient()->createRequest(
                $method,
                $url,
                array(
                    'headers' => $this->getHeaders(),
                    'body' => $string,
                )
            );
        }

        $this->sendRequest();
    }

    /**
     * Sends HTTP request to specific URL with form data from PyString.
     *
     * @param string $method request method
     * @param string $url relative url
     * @param PyStringNode $body request body
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with form data:$/
     */
    public function iSendARequestWithFormData($method, $url, PyStringNode $body)
    {
        $url = $this->prepareUrl($url);
        $body = $this->replacePlaceHolder(trim($body));

        $fields = array();
        parse_str(implode('&', explode("\n", $body)), $fields);

        if (version_compare(ClientInterface::VERSION, '6.0', '>=')) {
            $this->request = new Request($method, $url, ['Content-Type' => 'application/x-www-form-urlencoded'], http_build_query($fields, null, '&'));
        } else {
            $this->request = $this->getClient()->createRequest($method, $url);
            ///** @var \GuzzleHttp\Post\PostBodyInterface $requestBody */
            $requestBody = $this->request->getBody();
            foreach ($fields as $key => $value) {
                $requestBody->setField($key, $value);
            }
        }

        $this->sendRequest();
    }

    /**
     * Checks that response has specific status code.
     *
     * @param string $code status code
     *
     * @Then /^(?:the )?response code should be (\d+)$/
     */
    public function theResponseCodeShouldBe($code)
    {
        $expected = intval($code);
        $actual = intval($this->response->getStatusCode());
        Assertions::assertSame($expected, $actual);
    }

    /**
     * Checks that response body contains specific text.
     *
     * @param string $text
     *
     * @Then /^(?:the )?response should contain "([^"]*)"$/
     */
    public function theResponseShouldContain($text)
    {
        $expectedRegexp = '/' . preg_quote($text) . '/i';
        $actual = (string)$this->response->getBody();
        Assertions::assertRegExp($expectedRegexp, $actual);
    }

    /**
     * Checks that response body doesn't contains specific text.
     *
     * @param string $text
     *
     * @Then /^(?:the )?response should not contain "([^"]*)"$/
     */
    public function theResponseShouldNotContain($text)
    {
        $expectedRegexp = '/' . preg_quote($text) . '/';
        $actual = (string)$this->response->getBody();
        Assertions::assertNotRegExp($expectedRegexp, $actual);
    }

    /**
     * Checks that response body contains JSON from PyString.
     *
     * Do not check that the response body /only/ contains the JSON from PyString,
     *
     * @param PyStringNode $jsonString
     *
     * @throws \RuntimeException
     *
     * @Then /^(?:the )?response should contain json:$/
     */
    public function theResponseShouldContainJson(PyStringNode $jsonString)
    {
        $etalon = json_decode($this->replacePlaceHolder($jsonString->getRaw()), true);
        $actual = json_decode($this->response->getBody(), true);

        if (null === $etalon) {
            throw new \RuntimeException(
                "Can not convert etalon to json:\n" . $this->replacePlaceHolder($jsonString->getRaw())
            );
        }

        if (null === $actual) {
            throw new \RuntimeException(
                "Can not convert actual to json:\n" . $this->replacePlaceHolder((string)$this->response->getBody())
            );
        }

        Assertions::assertGreaterThanOrEqual(count($etalon), count($actual));
        foreach ($etalon as $key => $needle) {
            Assertions::assertArrayHasKey($key, $actual);
            Assertions::assertEquals($etalon[$key], $actual[$key]);
        }
    }

    /**
     * Prints last response body.
     *
     * @Then print response
     */
    public function printResponse()
    {
        $request = $this->request;
        $response = $this->response;

        echo sprintf(
            "%s %s => %d:\n%s",
            $request->getMethod(),
            (string)($request instanceof RequestInterface ? $request->getUri() : $request->getUrl()),
            $response->getStatusCode(),
            (string)$response->getBody()
        );
    }

    /**
     * Prepare URL by replacing placeholders and trimming slashes.
     *
     * @param string $url
     *
     * @return string
     */
    private function prepareUrl($url)
    {
        return ltrim($this->replacePlaceHolder($url), '/');
    }

    /**
     * Sets place holder for replacement.
     *
     * you can specify placeholders, which will
     * be replaced in URL, request or response body.
     *
     * @param string $key token name
     * @param string $value replace value
     */
    public function setPlaceHolder($key, $value)
    {
        $this->placeHolders[$key] = $value;
    }

    /**
     * Replaces placeholders in provided text.
     *
     * @param string $string
     *
     * @return string
     */
    protected function replacePlaceHolder($string)
    {
        foreach ($this->placeHolders as $key => $val) {
            $string = str_replace($key, $val, $string);
        }

        return $string;
    }

    /**
     * Returns headers, that will be used to send requests.
     *
     * @return array
     */
    protected function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Adds header
     *
     * @param string $name
     * @param string $value
     */
    protected function addHeader($name, $value)
    {
        if (isset($this->headers[$name])) {
            if (!is_array($this->headers[$name])) {
                $this->headers[$name] = array($this->headers[$name]);
            }

            $this->headers[$name][] = $value;
        } else {
            $this->headers[$name] = $value;
        }
    }

    /**
     * Removes a header identified by $headerName
     *
     * @param string $headerName
     */
    protected function removeHeader($headerName)
    {
        if (array_key_exists($headerName, $this->headers)) {
            unset($this->headers[$headerName]);
        }
    }

    private function sendRequest()
    {
        try {
            $this->response = $this->getClient()->send($this->request);
        } catch (RequestException $e) {
            $this->response = $e->getResponse();

            if (null === $this->response) {
                throw $e;
            }
        }
    }

    private function getClient()
    {
        if (null === $this->client) {
            throw new \RuntimeException('Client has not been set in WebApiContext');
        }

        return $this->client;
    }

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct()
    {

        $environment = 'stage';

        $urlSettings = [
            'live' => array(
                'home.localhost' => 'http://www.localhost.com',
                'forum.localhost' => 'http://forum.localhost.com',
                'home.localhost' => 'http://www.localhost.com',
                'home.localhost' => 'http://www.localhost.com',
                'forum.localhost' => 'http://www.localhost.com/forums'
            ),
            'dev' => array(
                'home.localhost' => 'http://dev.localhost.fte.localhost.com',
                'forum.localhost' => 'http://dev.forum.localhost.localhost.com',
                'forum.localhost' => 'http://dev.localhost.fte.localhost.com/forums/',
                'home.localhost' => 'http://dev.localhost.fte.localhost.com'
            ),
            'stage' => array(
                'home.localhost' => 'http://stage.fte.localhost.com',
                'forum.localhost' => 'http://forum.localhost.com',
                'home.localhost' => 'http://stage.fte.localhost.com',
                'home.localhost' => 'http://stage.fte.localhost.com',
                'forum.localhost' => 'http://stage.fte.localhost.com/forums'
            )
        ];

        $environmentSetting = getenv('TEST_ENV');
        if ($environmentSetting !== false && array_key_exists($environmentSetting, $urlSettings) == true) {
            $environment = $environmentSetting;
        }
        static $firstLoop = true;
        if ($firstLoop == true) {
            echo "Read value [$environmentSetting] from TEST_ENV, using environment [$environment]\n";
            $firstLoop = false;
        }
        $this->urls = $urlSettings[$environment];
    }

    /**
     * @Given /^I use URL "([^"]*)" and path "([^"]*)"$/
     */
    public function iUseUrlAndPath($arg1, $arg2)
    {
        $page = $this->urls[$arg1];

        if (!empty($arg2) || $arg2 !== '/') {
            $page = $page . '/' . trim($arg2, '/');
        }

        // $found=$this->getSession()->visit($this->locatePath($page));
        //   if(null === $found) {
        //      throw new \Exception(sprintf('The url and path  "%s%s" not found', $arg1 ,$arg2));
        // }

        $element = $this->getSession()->visit($this->locatePath($page));
        //sleep (1);
        //$this->removeTheCookieNotification();


    }

    /**
     * @Given /^I click link with class "([^"]*)"$/
     */
    public function iClickLinkWithClass($arg1)
    {
        $session = $this->getSession();

        $element = $session->getPage()->find('css', $arg1);

        //print_r($arg1);
        //throw new PendingException();

        // Clicks on the element
        $element->click();

    }

    /**
     * @Given /^I take a screenshot$/
     */
    public function iTakeAScreenshot()
    {
        $screenshot = $this->getSession()->getDriver()->getScreenshot();
        file_put_contents('./test_' . time('r') . '.png', $screenshot);
    }

    /**
     * @Given /^my screesize is "([^"]*)"$/
     */
    public function myScreesizeIs($arg1)
    {

        switch ($arg1) {
            case 'desktop':
                $w = 1400;
                $h = 900;
                break;

            case 'chris':
                $w = 1;
                $h = 9;
                break;

            default:
                $w = 1400;
                $h = 900;
                break;
        }

        $this->getSession()->resizeWindow($w, $h, 'current');

    }

    /**
     * @Given /^I wait for "([^"]*)"$/
     */
    public function iWaitFor($arg1)
    {
        $this->getSession()->wait($arg1);
    }

    /**
     * @Given /^I will wait for "([^"]*)"$/
     */
    public function iWillWaitFor($arg1)
    {
        $this->getSession()->wait($arg1);
    }


    /**
     * @Given /^I fill in box "([^"]*)" with "([^"]*)"$/
     */
    public function iFillInBoxWith($field, $value)
    {
        $session = $this->getSession();

        //$field = $this->fixStepArgument($field);
        $value = $this->fixStepArgument($value);

        $element = $session->getPage()->findAll('css', '.input-search.input-full');

        foreach ($element as $row) {
            echo 'NUMBERS ';
            echo $row->getAttribute('placeholder');
            $row->setValue('blahs');

        }

        //print_r($element);

        $screenshot = $this->getSession()->getDriver()->getScreenshot();
        file_put_contents('/tmp/test.png', $screenshot);

    }

    /**
     * Click on the element with the provided xpath query
     *
     * @When /^I click on the element with xpath "([^"]*)"$/
     */
    public function iClickOnTheElementWithXpath($xpath)
    {
        // Gets the mink session
        $session = $this->getSession();
        // Runs the query and returns the specified element
        $element = $session->getPage()->find('xpath', $session->getSelectorsHandler()->selectorToXpath('xpath', $xpath));

        // Ensures errors do not pass silently
        if (null === $element) {
            throw new \InvalidArgumentException(sprintf('Could not evaluate XPath: "%s"', $xpath));
        }

        // Clicks on the element
        $element->click();

    }

    /**
     * Click on the element with the provided CSS Selector
     *
     * @When /^I click on the element "([^"]*)"$/
     */
    public function iClickOnTheElement($cssSelector)
    {
        switch ($cssSelector) {
            case "deal finder widget button":
                $cssSelector = ".br-btn.br-btn__reg.br-btn__beta";
                break;
            case "deal finder navigation button":
                $cssSelector = ".br-orange-button";
                break;
            case "deal finder in navigation bar":
                $cssSelector = ".sub-nav-item-link";
                break;
            case "merchant":
                $cssSelector = ".deals__merchant";
                break;
            case "deal description":
                $cssSelector = ".deals__desc";
                break;
            case "gallery forward arrow":
                $cssSelector = ".js-gallery-image-next";
                break;
            case "gallery thumbnail forward arrow":
                $cssSelector = ".icon-arrow-right.icon-w";
                break;
            case "second thumbnail":
                $cssSelector = ".gallery-thumbnail.gallery-thumbnail-1.thumbnail-loaded";
                break;
            case "teeshop link":
                $cssSelector = "#menu > nav > ul > li.sub-nav-item.nav-deals.is-active > div > div > div.tee-shop-link > a";
                break;
        }
        $element = $this->getSession()->getPage()->find('css', $cssSelector);
        if (null === $element || empty($element)) {
            throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
        }
        $element->mouseOver();
        sleep(1);
        $element->click();
    }

    /**
     * Click on the element with the provided CSS Selector
     *
     * @When /^I double click on the element "([^"]*)"$/
     */
    public function iDoubleClickOnTheElement($cssSelector)
    {

        $element = $this->getSession()->getPage()->find('css', $cssSelector);
        if (null === $element || empty($element)) {
            throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
        }
        $element->mouseOver();
        sleep(1);
        $element->doubleClick();
    }

    /**
     * Checks, that element with specified CSS doesn't exist on page.
     *
     * @Then /^(?:|I )should not see? "(?P<element>[^"]*)" element$/
     */
    public function assertElementNotOnPage($element)
    {
        switch ($element) {
            case "23rd thumbnail":
                $element = ".gallery-thumbnail.gallery-thumbnail-22.thumbnail-loaded";
                break;
        }
        $this->assertSession()->elementNotExists('css', $element);
    }

    /**
     * @Given /^I click on "([^"]*)"$/
     */
    public function iClickOn($arg1)
    {
        switch ($arg1) {
            case "the review search button":
                $arg1 = "#menu > nav > ul > li.sub-nav-item.is-active > div > div.nav-block-content.nav-block-content-left > form > div > button";
                break;
            case "the first review" :
            case "the first review in gear" :
            case "the first review in folding" :
            case "the first review in electric bikes" :
            case "the first review in hybird" :
            case "cycling plus subscription in article" :
                $arg1 = ".post-title-link";
                break;
            case "deal finder widget button":
                $arg1 = ".br-btn.br-btn__reg.br-btn__beta";
                break;
            case "merchant":
                $arg1 = ".deals__merchant";
                break;
            case "deal description":
                $arg1 = ".deals__desc";
                break;
            case "gallery forward arrow":
                $arg1 = ".js-gallery-image-next";
                break;
            case "gallery thumbnail forward arrow":
                $arg1 = ".icon-arrow-right.icon-w";
                break;
            case "second thumbnail":
                $arg1 = ".gallery-thumbnail.gallery-thumbnail-1.thumbnail-loaded";
                break;
            case "teeshop link":
                $arg1 = "#menu > nav > ul > li.sub-nav-item.nav-deals.is-active > div > div > div.tee-shop-link > a";
                break;
        }
        $session = $this->getSession();
        $element = $session->getPage()->findAll('css', $arg1);
        foreach ($element as $el) {
            if (!empty ($el)) {
                break;
            }
        }
        if (null === $el || empty($el)) {
            throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: .%s', $arg1));
        }
        //sleep(1);
        $el->click();
        sleep(1);
    }

    /**
     * Click on the input with the provided CSS Selector
     *
     * @When /^I click on the input with CSS selector "([^"]*)"$/
     */
    public function iClickOnTheInputWithCSSSelector($cssSelector)
    {
        switch ($cssSelector) {
            case "deal finder widget button":
                $cssSelector = ".br-button";
                break;
        }
        $session = $this->getSession();
        $element = $session->getPage()->find('css', 'input' . $cssSelector);

        if (null === $element) {
            throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
        }

        $element->click();

    }

    /**
     * @When /^I type "([^"]*)" in  "([^"]*)"$/
     */
    public function iTypeIn2($arg1, $arg2)
    {
        $this->find('named', array(
            'field', $this->getSession()->getSelectorsHandler()->xpathLiteral($arg2)
        ))->setValue($arg1);

        throw new PendingException();
    }


    /**
     * @Then /^I should see canonical "([^"]*)"$/
     */
    public function iShouldSeeCanonical($arg1)
    {

        $canonical = $this->getSession()->getDriver()->getAttribute('//link[@rel="alternate"][@hreflang="' . $arg1 . '"]', 'href');
        if (trim(($this->getSession()->getCurrentUrl()), '/') !== trim($canonical, '/')) {
            throw new Exception;
        }
    }

    /**
     * @Given /^I should see href-lang tag "([^"]*)" contain "([^"]*)"$/
     */
    public function iShouldSeeHrefLangTagContain($arg1, $arg2)
    {

        $urlDomain = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_HOST);

        $arg2 = 'http://' . $urlDomain . '/' . trim($arg2, '/');

        $alternative = $this->getSession()->getDriver()->getAttribute('//link[@rel="alternate"][@hreflang="' . $arg1 . '"]', 'href');

        #echo "Behat .. ". $arg2 . PHP_EOL;
        #echo "Site  .. " . $alternative . PHP_EOL;

        if (trim($alternative, '/') !== trim($arg2, '/')) {
            throw new Exception;
        }

    }

    /**
     * @Given /^I should see related link tag "([^"]*)" contain "([^"]*)"$/
     */
    public function iShouldSeeRelatedLinkTagContain($arg1, $arg2)
    {
        $urlDomain = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_HOST);
        $arg2 = 'http://' . $urlDomain . '/' . trim($arg2, '/');
        $alternative = $this->getSession()->getDriver()->getAttribute('//link[@rel="' . $arg1 . '"]', 'href');
        if (trim($alternative, '/') !== trim($arg2, '/')) {
            throw new Exception;
        }
    }

    /**
     * @Given /^I should see youtube video "([^"]*)"$/
     */
    public function iShouldSeeYoutubeEmbeddedVideo($arg1)
    {

        $youtubeVideo = $this->getSession()->getDriver()->getAttribute('//iframe[@class="youtubeEmbed"]', 'src');

        if (!strstr($youtubeVideo, $arg1)) {
            throw new \Exception;
        }

    }

    /**
     * Checks, that element with specified CSS is on the nav.
     *
     * @Then /^I should see "([^"]*)" in nav$/
     */
    public function assertElementOnPage($itemText)
    {
        $element = $this->getSession()->getPage();
        $nodes = $element->findAll('css', '.sub-nav-group');

        foreach ($nodes as $node) {
            $nodesArray = explode(' ', $node->getText());
        }
        if (!in_array($itemText, $nodesArray)) {
            throw new \Exception();
        }
    }

    /**
     * @Then /^I should not be on "([^"]*)"$/
     */
    public function iShouldNotBeOn($arg1)
    {
        $haystack = $this->getSession()->getPage()->getText();
        $needle = "/No route found for/";
        if (!preg_match("$needle", $haystack)) {
            $page = $this->urls[$arg1];
            sleep(2);
            if ($this->assertSession()->addressNotEquals($page)) {
                throw new \Exception (sprintf("still on page %s ", $arg1));
            }
        } else {
            throw new \Exception(sprintf('the page 404s %s', $this->getSession()->getDriver()->getCurrentUrl()));
        }
    }

    /**
     * @Then /^the current website is not "([^"]*)"$/
     */
    public function theCurrentWebsiteIsNot($arg1)
    {
        //$this->iSwitchToPopup();
        $curr = $this->getSession()->getCurrentUrl();
        $page = $this->urls[$arg1];
        echo $curr . "\n" . $page;
        if (preg_match("#$page#", $curr)) {
            throw new \Exception (sprintf("$curr is the same as the %s ", $page));
        }
    }

    /**
     * @Then /^I switch to popup$/
     */
    public function iSwitchToPopup()
    {
        $originalWindowName = $this->getSession()->getWindowName(); //Get the original name
        if (empty($this->originalWindowName)) {
            $this->originalWindowName = $originalWindowName;
        }
        $popupName = $this->getNewPopup($originalWindowName);
        //Switch to the popup Window
        $this->getSession()->switchToWindow($popupName);
    }

    /**
     * This gets the window name of the new popup.
     */
    private function getNewPopup($originalWindowName = NULL)
    {
        //Get all of the window names first
        $names = $this->getSession()->getWindowNames();
        //Now it should be the last window name
        $last = array_pop($names);
        if (!empty($originalWindowName)) {
            while ($last == $originalWindowName && !empty($names)) {
                $last = array_pop($names);
            }
        }
        return $last;
    }

    /**
     * Checks, that element with specified CSS is between 2 given nav items.
     *
     * @Given /^I should see "([^"]*)" between "([^"]*)" - "([^"]*)"$/
     */
    public function assertFormElementBetween($itemText, $prevItemText, $nextItemText)
    {

        $element = $this->getSession()->getPage();
        $nodes = $element->findAll('css', '.sub-nav-group');

        foreach ($nodes as $node) {
            $nodesArray = explode(' ', $node->getText());
        }
        $key = array_search($itemText, $nodesArray);
        if ($prevItemText != $nodesArray[$key - 1] || $nextItemText != $nodesArray[$key + 1]) {
            throw new \Exception();
        }
    }

    /**
     * @When /^I click "([^"]*)" and containing text "([^"]*)"$/
     */
    public function iClickAndContainingText($className, $href)
    {
        switch ($className) {
            case "Reviews mountain bike" :
                $className = ".nav-block-list-item-link delink";
                break;
            case "navigation block":
                $className = ".nav-block-group-item-link";
                break;
            case "navigation deal finder":
                $className = ".sub-nav-item-link";
                break;
            case "navigation deal finder button":
                $className = ".br-orange-button";
                break;
        }
        //$urlDomain = parse_url($this->getSession()->getCurrentUrl(),PHP_URL_HOST);
        //$domain = 'http://' . $urlDomain;
        // find the element with a give class name and href
        $element = $this->getSession()->getPage()->findAll('css', $className);
        $foundElement = null;
        if (empty($element)) {
            throw new \Exception(sprintf('No element found containing the class: "%s"', $className));
        }
//        $link =$element->getAttribute('href');
//        if($link !== $href ) {
//            throw new \Exception(sprintf(' the link: "%s" is not present', $href));
//        }

        foreach ($element as $el) {
            //echo "elllll $el->getAttribute('href')";
            if ($el->getAttribute('href') === $href . "/") {
                $foundElement = $el;
                break;
            }
        }
        if ($foundElement === null) {
            throw new \Exception(sprintf('No element found having the href attribute: "%s"', $href));
        }
        $foundElement->click();
    }

    /**
     * @Then /^I click on the element with class "([^"]*)" and containing text "([^"]*)"$/
     */
    public function iClickOnTheElementWithClassStringAndContainingString($className, $href)
    {
        switch ($className) {
            case "navigation block":
                $className = ".nav-block-group-item-link";
                break;
            case "navigation deal finder":
                $className = ".sub-nav-item-link";
                break;
            case "navigation deal finder button":
                $className = ".br-orange-button";
                break;
            case "tech in navigation bar":
                $className = ".nav-tech.sub-nav-item-link";
                break;
            case "women tribe link" :
                $className = ".tribe-link.is-active";
                break;
        }
        $urlDomain = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_HOST);
        $domain = 'http://' . $urlDomain;
        // find the element with a give class name and href
        $elements = $this->getSession()->getPage()->findAll('css', $className);
        if (empty($elements)) {
            throw new \Exception(sprintf('No element found containing the class attribute: "%s"', $className));
        }

        foreach ($elements as $el) {
            if ($el->getAttribute('href') == $href //|| // absolute url
                //$el->getAttribute('href') == $href) { //relative url
            ) {
                $foundElement = $el;
                break;
            }
        }
        if (!isset($foundElement)) {
            throw new \Exception(sprintf('No element found having the href attribute: "%s"', $href));
        }
        $foundElement->click();
    }

    /**
     * @Then /^I click on the element  "([^"]*)" and containing text "([^"]*)"$/
     */
    public function iClickOnTheElemenAndContainingText($className, $href)
    {
        switch ($className) {
            case "deal finder navigation button":
                $className = ".br-orange-button";
                break;
            case "navigation deal finder button":
                $className = ".br-orange-button";
                break;
            case "Reviews mountain bike":
                $className = "nav-block-list-item-link";
                break;
            case "navigation block":
                $className = ".nav-block-group-item-link";
                break;
            case "navigation deal finder":
                $className = ".sub-nav-item-link";
                break;
//          case "navigation deal finder button":
//              $className = ".br-orange-button";
//                break;
        }
        $urlDomain = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_HOST);
        $domain = 'http://' . $urlDomain;
        // find the element with a give class name and href
        $elements = $this->getSession()->getPage()->findAll('css', $className);
        if (empty($elements)) {
            throw new \Exception(sprintf('No element found containing the class attribute: "%s"', $className));
        }

        foreach ($elements as $el) {
            if ($el->getAttribute('href') === $href) {
                $foundElement = $el;
                break;
            }
        }
        if (!isset($foundElement)) {
            throw new \Exception(sprintf('No element found having the href attribute: "%s"', $href));
        }
        $foundElement->click();
    }

    /**
     **
     * @Then /^Expandable menu "([^"]*)" contains "([^"]*)" linking to "([^"]*)"$/
     */
    public function expandableMenuContainsLinkingTo($arg1, $arg2, $arg3)
    {
        // fix url for non enternal links
        $urlDomain = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_HOST);
        $domain = 'http://' . $urlDomain;
        $parsed = parse_url($arg3);

        if (empty($parsed['scheme'])) {
            $arg3 = $domain . ltrim($arg3);
        }
        // find the element with a give class name and href
        $elements = $this->getSession()->getPage()->findAll('css', '.sub-nav-item-link.toggle');

        if (empty($elements)) {
            throw new \Exception('No expandable menu found');
        }

        foreach ($elements as $el) {
            $linkTextMatch = $linkHrefMatch = false;
            $text = $el->getText();

            // if menu found, check for expandable section
            if ($text == $arg1) {
                $expandedSection = $el->getParent()->find('css', '.nav-block');

                $visible = $expandedSection->isVisible();

                if (1 != $visible)
                    throw new \Exception('No expandable section found');

                //check links from expandable section

                $linksInsideExpandableSection = $expandedSection->findAll('xpath', '//a[(@href)]');

                foreach ($linksInsideExpandableSection as $link) {
                    if ($link->getText() == $arg2) $linkTextMatch = true;
                    if ($link->getAttribute('href') == $arg3) $linkHrefMatch = true;
                }

                if ($linkTextMatch == false || $linkHrefMatch == false)
                    throw new \Exception(sprintf('Text or href not matching for "%s"', $arg2));

            }

        }
    }

    /**
     * @Then /^I should see shop links "([^"]*)" in element class "([^"]*)" pointing to "([^"]*)" "([^"]*)"$/
     */
    public function iShouldSeeShopLinksInElementClassPointingTo2($arg1, $arg2, $arg3, $arg4)
    {

        $urlDomain = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_HOST);

        $domain = 'http://' . $urlDomain;

        // find the element with a given class name and href
        $elements = $this->getSession()->getPage()->find('css', $arg2)->findAll('xpath', '//a[(@href)]');

        $found = false;
        foreach ($elements as $el) {
            // echo $el->getText();
            $linkHref = ($arg4 == 'no') ? $domain . $arg3 : $arg3;

            if ($el->getText() == $arg1 && $el->getAttribute('href') == $linkHref) {
                $found = true;

            }
        }

        if (false == $found)
            throw new \Exception(sprintf('"%s" link not found', $arg1));

    }

    /**
     * Presses button with specified id|name|title|alt|value.
     *
     * @When /^(?:|I )click button "(?P<button>(?:[^"]|\\")*)"$/
     */
    public function pressButton($button)
    {
        $button = $this->fixStepArgument($button);
        $this->getSession()->getPage()->pressButton($button);
    }

    /**
     * Clicks link with specified id|title|alt|text.
     *
     * @When /^(?:|I )click on link "(?P<link>(?:[^"]|\\")*)"$/
     */
    public function clickLink($link)
    {

        $link = $this->fixStepArgument($link);
        $this->getSession()->getPage()->clickLink($link);
    }

    /**
     * @Given /^I click but do not follow selector "([^"]*)"$/
     */
    public function iClickButDoNotFollowSelector($target)
    {
        switch ($target) {
            case "Today On CYN main article" :
                $target = ".today-cn-page-1";
                break;
            case "Today On CYN first small article" :
                $target = ".today-cn-article.today-cn-2.today-cn-small";
                break;
            case "Today On CYN second small article" :
                $target = ".today-cn-article.today-cn-3.today-cn-small";
                break;
        }
        $foundIt = $this->getSession()->getPage()->find('css', $target);
        if ($foundIt === null) {
            throw new \Exception('Could not find the target');
        } else {
            $this->getSession()->executeScript("document.querySelectorAll('" . $target . "')[0].addEventListener('click', function(event) { event.preventDefault(); });");
            $foundIt->click();
        }
    }

    /**
     * @Given /^I click but do not follow selector "([^"]*)" the "([^"]*)" item$/
     */
    public function iClickButDoNotFollowSelectorOnlyTheItem($target, $arg)
    {

        $count = 0;
        $foundIt = $this->getSession()->getPage()->findAll('css', $target);

        if ($foundIt === null) {
            throw new \Exception('Could not find the target');
        }
        echo "text = $foundIt";
        foreach ($foundIt as $el) {
            echo $el->getText();
            $count++;
            echo "count = $count";
            if ($count === 35) {
                //$this->iClickButDoNotFollowSelector("$el->getText()");
                //$this->getSession()->executeScript("document.querySelectorAll('".$target."')[0].addEventListener('click', function(event) { event.preventDefault(); });");
                $this->clickLink($el->getText());
                break;
            }

        }
        //$inside = $foundIt->find('css', $arg);

    }

    /**
     * @Given /^I should see a Google Analytics confirmation attribute inside "([^"]*)"$/
     */
    public function iShouldSeeAGoogleAnalyticsConfirmationAttributeInside($target)
    {
        switch ($target) {
            case "Today On CYN main article" :
                $target = ".today-cn-page-1";
                break;
            case "Today On CYN first small article" :
                $target = ".today-cn-article.today-cn-2.today-cn-small";
                break;
            case "Today On CYN second small article" :
                $target = ".today-cn-article.today-cn-3.today-cn-small";
                break;
        }
        //      $findelement=$this->getSession()->getPage()->find('css', $target);
        $foundIt = $this->getSession()->getPage()->find('css', $target)->find('css', '[data-analytics-event]');

        if ($foundIt === null) {
            throw new \Exception('Could not find data-analytics-events in ' . $target);
        }
    }

    /**
     * @Given /^I should see an element with CSS selector "([^"]*)"$/
     */
    public function iShouldSeeAnElementWithCssSelector($arg1)
    {
        $count = $this->getSession()->getPage()->find('css', $arg1);
        if (empty($count) || null === $count) {
            throw new \Exception('CSS Selector ' . $arg1 . ' not found');
        }
    }

    /**
     * @Given /^I should see "([^"]*)" element$/
     */
    public function iShouldSeeElememt($arg1)
    {
        switch ($arg1) {
            case "deal finder search widget":
                $arg1 = "#dealFinderSearch";
                break;
            case "deal description" :
                $arg1 = ".deals__desc";
                break;
            case "merchant info":
                $arg1 = ".deals__merchant";
                break;
            case "deal sale price":
                $arg1 = ".deals__salePrice";
                break;
            case "deal finder widget search button":
                $arg1 = ".br-btn";
                break;
            case "first thumbnail":
                $arg1 = "#gallery-wrapper > div.gallery-inner-wrap > div.gallery-thumbnails-wrapper.js-thumbnail-touch-area.thumbnails-ready > div > figure.gallery-thumbnail.gallery-thumbnail-0.is-selected.thumbnail-loaded";
                break;
            case "second thumbnail":
                $arg1 = "#gallery-wrapper > div.gallery-inner-wrap > div.gallery-thumbnails-wrapper.js-thumbnail-touch-area.thumbnails-ready > div > figure.gallery-thumbnail.gallery-thumbnail-1.thumbnail-loaded";
                break;
            case "first image":
                $arg1 = ".gallery-item.gallery-item-0.current";
                break;
            case "second image":
                $arg1 = ".gallery-item.gallery-item-1.current";
                break;
            case "the selected first thumbnail":
                $arg1 = ".gallery-thumbnail.gallery-thumbnail-0.is-selected";
                break;
            case "23rd thumbnail":
                $arg1 = ".gallery-thumbnail.gallery-thumbnail-22.thumbnail-loaded";
                break;
            case"teeshop link":
                $arg1 = ".tee-shop-link";
                break;
        }
        $count = $this->getSession()->getPage()->find('css', $arg1);;
        if (empty($count) || null === $count) {
            throw new \Exception('CSS Selector ' . $arg1 . ' not found');
        }
    }

    /**
     * @Then /^I should see "([^"]*)" in the page$/
     */
    public function iShouldSeeInThePage($arg1)
    {
        switch ($arg1) {
            case "the search result container" :
                $arg1 = "#js-ui-deals-finder";
                break;
            case "the first search result item":
                $arg1 = "#js-ui-deals-finder > ul";
                break;
        }
        $element = $this->getSession()->getPage()->find('css', $arg1);
        if (empty($element) || null === $element) {
            throw new \Exception('CSS Selector ' . $arg1 . ' not found');
        }
    }

    /**
     * @Then /^I should be on "([^"]*)" with path "([^"]*)"$/
     */
    public function assertPageAddress2($page, $path)
    {
        $page = $this->urls[$page];
        $this->assertSession()->addressEquals($this->locatePath($page . $path));
    }

    /**
     * @Given /^I fill in "([^"]*)" with "([^"]*)" for successful registration$/
     */
    public function iFillInWithForSuccessfulRegistration($arg1, $arg2)
    {

        $field = $this->fixStepArgument($arg1);
        $value = $this->fixStepArgument($arg2);
        if ($field === "email") {
            $value = trim($value, "@example.com");
            $value = $value . rand(0, 1000000);
            $value = $value . "@example.com";
            $this->getSession()->getPage()->fillField($field, $value);
        } else {
            $this->getSession()->getPage()->fillField($field, $value . rand(0, 1000000));
        }
        //throw new PendingException();
    }

    /**
     * @Given /^I restart session$/
     */
    public function iRestartSession()
    {
        $this->getSession()->stop();
// or if you want to start again at the same time
        $this->getSession()->restart();
    }

    /**
     * @Given /^the page "([^"]*)" source should not contain "([^"]*)"$/
     */
    public function thePageSourceShouldNotContain($arg1, $arg2)
    {
        $html = $this->getSession()->getPage()->getHtml();
        if ($html === null) {
            throw new \Exception(sprintf('The page "%s does not exist', $arg1));
        } else {
            if ($this->contains($arg2, $html) === true) {
                throw new \Exception(sprintf('The page "%s contians %s', $arg1, $arg2));
            }
        }
    }


    /* a function to check if a string contains another string */


    function contains($needle, $haystack)
    {
        return strpos($haystack, $needle);
    }

    /**
     * @Given /^the page "([^"]*)" source should contain "([^"]*)"$/
     */
    public function thePageSourceShouldContain($arg1, $arg2)
    {
        $html = $this->getSession()->getPage()->getHtml();
        if ($html === null) {
            throw new \Exception(sprintf('The page "%s does not exist', $arg1));
        } else {
            if ($this->contains($arg2, $html) === false) {
                throw new \Exception(sprintf('The page "%s does not contian %s', $arg1, $arg2));
            }
        }
    }

    /**
     * @Then /^the page source should contain "([^"]*)"$/
     */
    public function thePageSourceShouldContain2($arg1)
    {
        $html = $this->getSession()->getPage()->getHtml();
        #echo $html;
        if ($html === null) {
            throw new \Exception(sprintf('The page "%s does not exist', $arg1));
        } else {
            if ($this->contains($arg1, $html) === false) {
                throw new \Exception(sprintf('The page does not contian %s', $arg1));
            }
        }
    }

    /**
     * Checks, that element with specified CSS doesn't contain specified text.
     *
     * @Then /^(?:|I )should not see "(?P<text>(?:[^"]|\\")*)" in the "(?P<element>[^"]*)" all elements$/
     */
    public function assertElementNotContainsText2($text, $selector)
    {
        $count = 0;
        $Elements = $this->getSession()->getPage()->findAll('css', $selector);
        if (null === $Elements) {
            throw new \Exception(sprintf('element not found'));
        }
        foreach ($Elements as $E) {
            $count++;
            if ($this->contains($text, $E->getHtml())) {
                echo "$count\n";
                throw new \Exception(sprintf('element does contain %s ', $text));
            }
        }
    }

    /**
     * @Then /^I should see "([^"]*)" elements with CSS selector "([^"]*)"$/
     */
    public function iShouldSeeElementsWithCssSelector($arg1, $arg2)
    {
        $count = 0;
        $Elements = $this->getSession()->getPage()->findAll('css', $arg2);
        if (empty($Elements)) {
            throw new \Exception(sprintf('element  %s not found ', $arg2));
        }
//        foreach ($Elements as $E) {
//            $count++;
//        }
//        if ((string)$count !== $arg1) {
//            throw new \Exception(sprintf('there is no %d deals in the homepage ',$arg1));
//        }
    }

    /**
     * @Then /^I should see "([^"]*)" products in "([^"]*)"$/
     */
    public function iShouldSeeProductsIn($arg1, $arg2)
    {
        switch ($arg2) {
            case "daily deals widget":
                $arg2 = ".media__product";
                break;
        }
        $count = 0;
        $Elements = $this->getSession()->getPage()->findAll('css', $arg2);
        if (empty($Elements)) {
            throw new \Exception(sprintf('element  %s not found ', $arg2));
        }
        foreach ($Elements as $E) {
            $count++;
        }
        if ((string)$count !== $arg1) {
            throw new \Exception(sprintf('there is no %d deals in the homepage ', $arg1));
        }
    }

    /**
     * @Then /^check the element "([^"]*)" is not the same as in homepage "([^"]*)"$/
     */
    public function checkTheElementIsNotTheSameAsInHomepage2($arg1, $arg2)
    {
        switch ($arg1) {
            case "blog widget":
                $arg1 = ".widget.widget-blogs";
                break;

        }
        $element = $this->getSession()->getPage()->find('css', $arg1);
        if (empty($element))
            throw new \Exception (sprintf('the element %s is not found', $arg1));
        $this->getSession()->visit($this->urls[$arg2]);
        $element2 = $this->getSession()->getPage()->find('css', $arg1);
        if ($element === $element2) {
            throw new \Exception (sprintf('the element is the same is in homepage'));
        }
    }

    /**
     * Checks, that element with specified CSS contains specified HTML
     * Example: Then the "body" element should contain "style=\"color:black;\""
     * Example: And the "body" element should contain "style=\"color:black;\""
     *
     * @Then /^the "(?P<element>[^"]*)" element should contain "(?P<value>(?:[^"]|\\")*)" text$/
     */
    public function assertElementContainsText($element, $value)
    {
        switch ($element) {
            case "listing title":
                $element = ".post-title-link.delink";
                break;
            case "article title":
                $element = ".article__title";
                break;
        }
        $this->assertSession()->elementContains('css', $element, $value);
    }

    /**
     * @Then /^I shoud see rubicon call within ads$/
     */
    public function iShoudSeeWithinAds()
    {
        $count = 0;
        $arg1 = "data-rpfl";
        $arg2 = ".dfp-plugin-advert";
        $elements = $this->getSession()->getPage()->findAll('css', $arg2);
        if (null === $elements) {
            throw new \Exception(sprintf('the element %s is not found', $arg2));
        }
        foreach ($elements as $E) {
            if (null === $E) {
                throw new \Exception(sprintf("the element not found %s", $E));
            }
            $attr = $E->getAttribute($arg1);
            echo "attribute == " . $attr . "\n";
            if (null === $attr || $attr === "") {
                $count++;
                if ($count > 1) { //this should fail once because one of the ad slots is not used for ads
                    throw new \Exception(sprintf('the attribute %s is not found', $arg1));
                }
            }

        }
    }

    /**
     * @Then /^I should not see "([^"]*)" in title$/
     */
    public function iShouldNotSeeInTitle($arg1)
    {
        $text = $this->getSession()->getPage()->getHtml();
        if ($this->contains($arg1, $text)) {
            throw new \Exception (sprintf('the page title contains $arg1 '));
        }

    }

    /**
     * @Then /^I switch back to original window$/
     */
    public function iSwitchBackToOriginalWindow()
    {
        //Switch to the original window
        $this->getSession()->switchToWindow($this->originalWindowName);
    }

    /**
     * @Given /^I click on share link element with css selector "([^"]*)"$/
     */
    public function iClickOnShareLinkElementWithCssSelector($arg1)
    {
        $session = $this->getSession();
        $element = $session->getPage()->find('css', $arg1);
        if (null === $element || empty($element)) {
            throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $arg1));
        }
        $element->click();
        $element->click();
    }

    /**
     * @Given /^I click on share link "([^"]*)"$/
     */
    public function iClickOnShareLink($arg1)
    {
        switch ($arg1) {
            case "twitter" :
                $arg1 = ".share-button.share-button-twitter";
                break;
        }
        $session = $this->getSession();
        $element = $session->getPage()->find('css', $arg1);
        if (null === $element || empty($element)) {
            throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $arg1));
        }
        $element->click();
        $element->click();
    }

    /**
     * @Then /^the "([^"]*)" font should be using  "([^"]*)" in size "([^"]*)"$/
     */
    public function theFontShouldBeUsingInSize($arg1, $arg2, $arg3)
    {
        if (!$this->assertCssValue($arg1, $arg2, $arg3)) {
            throw new \Exception (sprintf('failed'));
        }
    }

    public function assertCssValue($arg1, $arg2, $arg3)
    {

        // JS script that makes the CSS assertion in the browser.

        $script = <<<JS
            (function(){
                return $($arg1).css($arg2) === $arg3;
            })();
JS;

        if (!$this->getSession()->evaluateScript($script)) {
            return false;
        }

    }


    private function removeTheCookieNotification()
    {
        $cssSelector = ".notify-close-button";
        $session = $this->getSession();

        $element = null;

        try {
            $element = $session->getPage()->find('css', $cssSelector);
            if ($element != null && $element->isVisible()) {
                $element->click(); // Clicks on the element
            }
        } catch (\WebDriver\Exception\NoSuchElement $nse) {
            // this is fine and normal.
        } catch (\WebDriver\Exception\StaleElementReference $nse) {
            // this is also fine and normal.
        } catch (WebDriver\Exception\MoveTargetOutOfBounds $nse) {
            // this is also fine and normal.
        }
    }

    /** @BeforeStep */
    public function beforeStep(BeforeStepScope $scope)
    {
    }

    /** @AfterStep */
    public function afterStep(AfterStepScope $scope)
    {
        $this->getSession()->wait(10000, "document.readyState === 'complete'");
        $this->removeTheCookieNotification();
        usleep(1000 * 100);
    }

    /**
     * @When I scroll :elementId into view
     */
    public function scrollIntoView($elementName)
    {


        $function = <<<JS
$(document).ready(function() {

$('html, body').animate({ scrollTop: $($elementName).offset().top},2000);
});
JS;
        try {
            $this->getSession()->executeScript($function);
        } catch (Exception $e) {
            throw new \Exception("ScrollIntoView failed");
        }
    }

    /**
     * @Then /^the page title should be  "([^"]*)"$/
     */
    public function thePageTitleShouldBe($expectedTitle)
    {
        $title = $this->getSession()->getPage()->find('css', 'title')->getHtml();

        $encodedExpectedTitle = htmlspecialchars($expectedTitle);

        if (strcmp($title, $encodedExpectedTitle) !== 0) {
            throw new \Exception("Expected page title [$encodedExpectedTitle] but it is actually [$title]");
        }

    }

    /**
     * @Then /^I should see the share bar item "([^"]*)" in every article in the homepage "([^"]*)"$/
     */
    public function iShouldSeeTheShareBarItemInEveryArticleInTheHomepage($social, $home)
    {

        $count = 1;
        # News
        $cssSelector = '.post-title';
        #find the News articles or caresoul articles
        $elements = $this->getSession()->getPage()->findAll('css', $cssSelector);
        if ( empty($elements)  || null === $elements)
            throw new Exception (sprintf ("the element is not found %s", $cssSelector));
        $page = $this->urls[$home];
        foreach ($elements as $el) {
            # pick only the first three news  articles and three Today on Cycling news
            if ($count === 3) {
                break;
            }
            if (null !== $el) {
                $el->mouseOver();
                $el->click();
                $sharebar = $this->getSession()->getPage()->find('css', $social);
                if (!empty($sharebar)) {
                    $sharebar->click();
                    $this->iSwitchToPopup();
                    $current = $this->getSession()->getDriver()->getCurrentUrl();
                    if ($current === $page) {
                        throw new \Exception (sprintf("can't find %s popup in %s", $social, $el->getText()));
                    }
                    $this->iSwitchBackToOriginalWindow();
                    $checkpos = $this->getSession()->getPage()->find('css', '.post-category');
                }
                $this->back();
            } else {
                throw new \Exception (sprintf("no elements found with %s",$cssSelector));
            }
            $count++;
        }

    }

    /**
     * @Then /^I should see "([^"]*)" between "([^"]*)" and "([^"]*)"$/
     */

    public function iShouldSeeBetweenAnd($txt, $parent, $child)
    {

        $element = $this->getSession()->getPage()->find('css',$parent);
        if (null === $element || empty($element)) {
            throw new \Exception (sprintf(" element not found %s",$parent));
        }
        //echo $element->getOuterHtml(); //getOuterHtml();
        $txtpos=strpos($element->getHtml(),$txt);
        $childpos= strpos($element->getHtml(),$child);
       // echo "\n" . $txtpos . "\n" . $childpos . "\n";
        #check that the text is found in the element
        if (strpos($element->getHtml() , $txt) === false)
            throw new \Exception (sprintf("the string %s doesn't exist", $txt));
        #check that the text is before the child element if not throw exception
        if (intval($txtpos) > intval($childpos))
            throw new \Exception (sprintf("the share bar is not in correct location"));
    }



    /**
     * @Then /^I should see "([^"]*)" between "([^"]*)" and "([^"]*)" and the count should be equal or more than "([^"]*)"$/
     */

    public function iShouldSeeBetweenAnAdriandTheCountShouldBeEqualOrMoreThan($txt, $parent, $child,$count)
    {

        $element = $this->getSession()->getPage()->find('css',$parent);
        if (null === $element || empty($element)) {
            throw new \Exception (sprintf(" element not found %s",$parent));
        }
        //echo $element->getText(); //getOuterHtml();
        preg_match('/[0-9]{1,7} shares/', $element->getText(),$matches);
        if ( empty($matches))
                throw new Exception (sprintf ("%s are not found in %s" , $txt,$element->getText()));
       // echo $matches[0] . " " .  $count;
        $matches[0] = str_replace (" shares","",$matches[0]);
        //echo $matches[0] . " " .  $count;
        if ( intval($matches[0],10) < intval($count,10))
              throw new Exception (sprintf(" %s count is broken", $txt));
    }
/**
 * @Then /^page title "([^"]*)" should be the same as articel title in every article in "([^"]*)" using path "([^"]*)"$/
 */
    public function ThePageTitleShouldBeTheSameAsArticleTitleInEveryArticleInUsingPath($selector,$uri,$path)
    {
            $NOTFOUND = 0;
            $page = $this->urls[$uri];
            $page = $page . $path;
            $this->visit($page);
            $article_title = $this->getSession()->getPage()->find('css', $selector);
            if ($article_title === null ||  empty($article_title ))
                throw new Exception (sprintf ( " element " . $selector . " doesn't exist"));
            $html = $this->getSession()->getPage()->find('css', 'title')->getHtml();
            $html = str_replace("amp;","",$html );
            $html = str_replace( " - localhost  ","",$html);
            $html = str_replace (" | localhost.com","",$html);
            $html = str_replace (" 2016 Pro Cycling Team","",$html);
            $article_txt = $article_title->getText();
            if ( preg_match("/" .$html."/i" , "/$article_txt/") === $NOTFOUND || $article_title === null ) {
                throw new \Exception (sprintf("The page tilte " . $html . " is not as expected " . $article_txt));
            }
    }
}
//       public function iClickOnTheElementWithXpath(2) ($arg1) {
//          $alternative = $this->getSession()->getDriver()->getAttribute('//link[@rel="alternate"][@hreflang="'.$arg1.'"]','href');
//
//          #echo "Behat .. ". $arg2 . PHP_EOL;
//         #echo "Site  .. " . $alternative . PHP_EOL;
//
//          if ($alternative === null) {
//             throw new \Exception('Could not find the target '.  $arg1);
//         }
//       }

