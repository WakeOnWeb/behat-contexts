<?php

namespace WakeOnWeb\BehatContexts;

use Behat\Behat\Context\Context;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Ubirak\RestApiBehatExtension\Rest\RestApiBrowser;
use WakeOnWeb\Component\Swagger\Loader\YamlLoader;
use WakeOnWeb\Component\Swagger\SwaggerFactory;
use WakeOnWeb\Component\Swagger\Test\ContentValidator;
use WakeOnWeb\Component\Swagger\Test\JustinRainbowJsonSchemaValidator;
use WakeOnWeb\Component\Swagger\Test\SwaggerValidator;

/**
 * Class SwaggerContext
 *
 * @author Alexandre Tomatis <a.tomatis@wakeonweb.com>
 *
 */
class SwaggerContext implements Context
{
    use KernelDictionary;

    /** @var RestApiBrowser */
    private $restApiBrowser;

    /** @var string */
    private $swaggerFile;

    /** @var SwaggerValidator */
    private $swaggerValidator;

    /**
     * SwaggerContext constructor.
     *
     * @param RestApiBrowser $restApiBrowser
     * @param string         $swaggerFile
     */
    public function __construct(RestApiBrowser $restApiBrowser, string $swaggerFile)
    {
        $this->restApiBrowser = $restApiBrowser;
        $this->swaggerFile = $swaggerFile;
    }

    /**
     * @param string $method
     * @param string $path
     * @param int $statusCode
     *
     * @Then I validate Swagger response on :path with :method method and statusCode :statusCode
     */
    public function validateOpenApiSpecificationResponse(string $method, string $path, int $statusCode)
    {
        $this->initSwagger();
        $response = $this->restApiBrowser->getResponse();
        $this->swaggerValidator->validateResponseFor($response, $method, $path, $statusCode);
    }

    private function initSwagger(): void
    {
        if (null !== $this->swaggerValidator) {
            return;
        }

        $factory = new SwaggerFactory();
        $contentValidator = new ContentValidator();
        $factory->addLoader(new YamlLoader());
        $swagger = $factory->buildFrom(sprintf('%s/%s', $this->getContainer()->getParameter('kernel.project_dir'), $this->swaggerFile));
        $this->swaggerValidator = new SwaggerValidator($swagger);
        $contentValidator->registerContentValidator(new JustinRainbowJsonSchemaValidator());
        $this->swaggerValidator->registerResponseValidator($contentValidator);
    }
}
