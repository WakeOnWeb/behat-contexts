<?php

namespace Tests\functional\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelDictionary;
use InterNations\Component\HttpMock\PHPUnit\HttpMockTrait;

/**
 * Class HttpMockContext
 *
 * @author Alexandre Tomatis <a.tomatis@wakeonweb.com>
 */
class HttpMockContext implements Context
{
    use KernelDictionary;
    use HttpMockTrait;

    /** @var array */
    protected $mockServers;

    /** @var string */
    protected $bodyResponsePath;

    /**
     * HttpMockContext constructor.

     * @param array  $mockServers
     * @param string $bodyResponsePath
     */
    public function __construct(?array $mockServers = [], ?string $bodyResponsePath = null)
    {
        $this->mockServers = $mockServers;
        $this->bodyResponsePath = $bodyResponsePath;
    }

    /**
     * @param TableNode $mockServers
     *
     * @BeforeScenario @mockable
     *
     * @Given I set up mock servers:
     */
    public function setUpMockServer(TableNode $mockServers = null)
    {
        $mockServers = $mockServers ?? $this->mockServers;

        foreach ($mockServers as $mockServer) {
            static::setUpHttpMockBeforeClass($mockServer['port'] ?? null, $mockServer['host'] ?? null, $mockServer['basePath'] ?? null, $mockServer['serverName']);
        }

        $this->setUpHttpMock();
    }

    /**
     * @AfterScenario @mockable
     *
     * @Then I tear down mock servers
     */
    public function tearDownMockServers()
    {
        static::tearDownHttpMockAfterClass();
    }

    /**
     * @param string      $serverName
     * @param string      $path
     * @param string      $method
     * @param int         $responseCode
     * @param string      $body
     * @param string|null $bodyFileName
     *
     * @Given A :method request on :path to the mock server :serverName must be return a :responseCode response
     * @Given A :method request on :path to the mock server :serverName must be return a :responseCode response with content :body
     * @Given A :method request on :path to the mock server :serverName must be return a :responseCode response with file :bodyFileName as content
     */
    public function genericMock(string $serverName, string $path, string $method, int $responseCode, ?string $body = null, string $bodyFileName = null)
    {
        $body = null === $bodyFileName ? $body : $this->getBodyResponseFromFile($bodyFileName);
        $this->http[$serverName]->mock
            ->when()
            ->methodIs($method)
            ->pathIs($path)
            ->then()
            ->statusCode($responseCode)
            ->body($body)
            ->end();
        $this->http[$serverName]->setUp();
    }

    /**
     * @param string      $serverName
     * @param string      $regex
     * @param string      $method
     * @param int         $responseCode
     * @param string      $body
     * @param string|null $bodyFileName
     *
     * @Given A :method request on path matching :regex to the mock server :serverName must be return a :responseCode response
     * @Given A :method request on path matching :regex to the mock server :serverName must be return a :responseCode response with content :body
     * @Given A :method request on path matching :regex to the mock server :serverName must be return a :responseCode response with file :bodyFileName as content
     */
    public function regexMock(string $serverName, string $regex, string $method, int $responseCode, ?string $body = null, string $bodyFileName = null)
    {
        $body = null === $bodyFileName ? $body : $this->getBodyResponseFromFile($bodyFileName);
        $this->http[$serverName]->mock
            ->when()
            ->methodIs($method)
            ->pathIs($this->http[$serverName]->matches->regex($regex))
            ->then()
            ->statusCode($responseCode)
            ->body($body)
            ->end();
        $this->http[$serverName]->setUp();
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
}
