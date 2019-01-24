<?php

namespace WakeOnWeb\BehatContexts;

use Behat\Behat\Context\Context;
use Behat\Symfony2Extension\Context\KernelDictionary;

/**
 * FidryAliceFixturesContext
 *
 * @uses Context
 *
 * @author Stephane PY <s.py@wakeonweb.com>
 */
class FidryAliceFixturesContext implements Context
{
    use KernelDictionary;

    /**
     * @var string
     */
    private $basepath;

    /**
     * @param string $basepath basepath
     */
    public function __construct(string $basepath = null)
    {
        $this->basepath = $basepath;
    }

    /**
     * @Given I use fixture file :filename
     */
    public function iUserFixtureFile(string $filename)
    {
        $filename = $this->getFilepath($filename);

        $em     = $this->getDoctrine();
        $loader = $this->getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        $loader->load([$filename]);

        $em->flush();
        $em->clear();
    }

    /**
     * @param string $filename filename
     *
     * @return string
     */
    private function getFilepath(string $filename): string
    {
        if (null === $this->basepath) {
            $this->basepath = 'tests/fixtures';
        }

        return $this->getContainer()->getParameter('kernel.project_dir').'/'.$this->basepath.'/'.$filename;
    }

    /**
     * @return EntityManagerInterface
     */
    private function getDoctrine()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }
}
