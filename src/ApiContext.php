<?php

namespace WakeOnWeb\BehatContexts;

use Behat\Behat\Context\Context;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Swaggest\JsonDiff\JsonDiff;
use Ubirak\RestApiBehatExtension\Rest\RestApiBrowser;

/**
 * Class ApiContext
 *
 * @author Alexandre Tomatis <a.tomatis@wakeonweb.com>
 */
class ApiContext implements Context
{
    use KernelDictionary;

    /** @var RestApiBrowser */
    protected $restApiBrowser;

    /** @var string|null */
    protected $bodyRequestPath;

    /** @var string|null */
    protected $bodyResponsePath;

    /**
     * ApiContext constructor.
     *
     * @param RestApiBrowser $restApiBrowser
     * @param string         $bodyRequestPath
     * @param string         $bodyResponsePath
     */
    public function __construct(RestApiBrowser $restApiBrowser, ?string $bodyRequestPath = null, ?string $bodyResponsePath = null)
    {
        $this->restApiBrowser = $restApiBrowser;
        $this->bodyRequestPath = $bodyRequestPath;
        $this->bodyResponsePath = $bodyResponsePath;
    }

    /**
     * @param string      $method
     * @param string      $url
     * @param string $bodyFileName
     *
     * @When I send a :method request to :url with content from file :bodyFileName as body
     */
    public function sendRequestToUrl(string $method, string $url, string $bodyFileName): void
    {
        $this->restApiBrowser->sendRequest($method, $url, $this->getBodyRequestFromFile($bodyFileName));
    }

    /**
     * @param string $header
     * @param string $value
     *
     * @throws \Exception
     *
     * @Then The response header :header should be equal to :value
     */
    public function theResponseHeaderShouldBeEqualTo(string $header, string $value)
    {
        $response = $this->restApiBrowser->getResponse();
        $headerInResponse = implode(',', $response->getHeader($header));

        if (empty($headerInResponse)) {
            throw new \Exception(sprintf('Header "%s" not found.', $header));
        }

        if ($headerInResponse !== $value) {
            throw new \Exception(sprintf('Response header value "%s" is not equals to "%s".', $value, $headerInResponse));
        }
    }

    /**
     * Use full match pattern '/^myPattern$/'if possible.
     *
     * @param string $header
     * @param string $regex
     *
     * @throws \Exception
     *
     * @Then the response header :header should be match with pattern :regex
     */
    public function headerShouldBeEqualToRegExp(string $header, string $regex = null): void
    {
        $response = $this->restApiBrowser->getResponse();
        $headerInResponse = implode(',', $response->getHeader($header));

        if (empty($headerInResponse)) {
            throw new \Exception(sprintf('Header "%s" not found.', $header));
        }

        if (0 === preg_match($regex, $headerInResponse)) {
            throw new \Exception(sprintf('Pattern "%s" not match with "%s" for header "%s".', $regex, $headerInResponse, $header));
        }
    }

    /**
     * @param string $responseFileName
     *
     * @throws \Exception
     *
     * @Then The JSON response body should be equal to content from :responseFileName
     */
    public function responseShouldBeEqual(string $responseFileName = null): void
    {
        $responseExpected = $this->getBodyResponseFromFile($responseFileName);
        $responseBody = $this->restApiBrowser->getResponse()->getBody();
        $expected = json_decode($responseExpected);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('The response expected given is not a valid Json string.');
        }

        $body = json_decode($responseBody);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('The response body given is not a valid Json string.');
        }

        $jsonDiff = new JsonDiff($body, $expected, JsonDiff::REARRANGE_ARRAYS);

        if (0 < $jsonDiff->getDiffCnt()) {
            $diff = '';

            if (0 < $jsonDiff->getRemovedCnt()) {
                $diff .= sprintf('%sMissing field(s):%s  - %s', PHP_EOL, PHP_EOL, implode(PHP_EOL.'  - ', $this->formatPath($jsonDiff->getRemovedPaths())));
            }

            if (0 < $jsonDiff->getAddedCnt()) {
                $diff .= sprintf('%sNot expected extra field(s):%s  - %s', PHP_EOL, PHP_EOL, implode(PHP_EOL.'  - ', $this->formatPath($jsonDiff->getAddedPaths())));
            }

            if (0 < $jsonDiff->getModifiedCnt()) {
                $modified = [];

                foreach ($jsonDiff->getModifiedFull() as $modifiedFull) {
                    $path = '['.str_replace('/', '][', substr($modifiedFull['path'], 1)).']';
                    $new = null === $modifiedFull['new'] ? 'null' : sprintf('"%s"', $modifiedFull['new']);
                    $original = null === $modifiedFull['original'] ? 'null' : sprintf('"%s"', $modifiedFull['original']);
                    $modified[] = sprintf('%s value is %s instead of %s', $path, $new, $original);
                }

                $diff .= sprintf('%sNot expected value(s):%s  - %s', PHP_EOL, PHP_EOL, implode(PHP_EOL.'  - ', $modified));
            }

            throw new \Exception(sprintf('Json don\'t match.%s%s', PHP_EOL, $diff));
        }
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    protected function getBodyRequestFromFile(string $fileName): string
    {
        $path = $this->bodyRequestPath ?? 'tests/functional/fixtures/request';

        return file_get_contents(sprintf('%s/%s/%s', $this->getContainer()->getParameter('kernel.project_dir'), $path, $fileName));
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    protected function getBodyResponseFromFile(string $fileName): string
    {
        $path = $this->bodyResponsePath ?? 'tests/functional/fixtures/response';

        return file_get_contents(sprintf('%s/%s/%s', $this->getContainer()->getParameter('kernel.project_dir'), $path, $fileName));
    }

    /**
     * @param array $paths
     *
     * @return array
     */
    protected function formatPath(array $paths): array
    {
        $results = [];

        foreach ($paths as $path) {
            $results[] = '['.str_replace('/', '][', substr($path, 1)).']';
        }

        return $results;
    }
}
