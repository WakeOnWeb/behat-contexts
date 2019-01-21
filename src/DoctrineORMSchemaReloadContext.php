<?php

namespace WakeOnWeb\BehatContexts;

use Behat\Behat\Context\Context;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * DoctrineORMSchemaReloadContext
 *
 * @uses Context
 * @author Stephane PY <s.py@wakeonweb.com>
 */
class DoctrineORMSchemaReloadContext implements Context
{
    use KernelDictionary;

    /**
     * @BeforeScenario @database
     *
     * @return void
     */
    public function beforeScenario()
    {
        $this->buildSchema();
    }

    /**
     * @AfterScenario @database
     *
     * @return void
     */
    public function afterScenario()
    {
        foreach ($this->getManagers() as $manager) {
            $manager->clear();
        }
    }

    /**
     * @return void
     */
    protected function buildSchema()
    {
        foreach ($this->getManagers() as $manager) {
            $metadata = $this->getMetadata($manager);
            if (!empty($metadata)) {
                $tool = new SchemaTool($manager);
                $tool->dropSchema($metadata);
                $tool->createSchema($metadata);
            }
        }
    }

    /**
     * @param ObjectManager $manager manager
     *
     * @return array
     */
    protected function getMetadata(ObjectManager $manager)
    {
        return $manager->getMetadataFactory()->getAllMetadata();
    }

    /**
     * @return ObjectManager[]
     */
    private function getManagers()
    {
        return $this->getContainer()->get('doctrine')->getManagers();
    }
}
