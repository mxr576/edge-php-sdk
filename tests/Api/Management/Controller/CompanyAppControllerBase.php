<?php

/*
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Apigee\Edge\Tests\Api\Management\Controller;

use Apigee\Edge\Api\Management\Controller\CompanyAppController;
use Apigee\Edge\Api\Management\Controller\CompanyController;
use Apigee\Edge\Api\Management\Entity\CompanyApp;
use Apigee\Edge\Controller\EntityControllerInterface;
use Apigee\Edge\Entity\EntityInterface;
use Apigee\Edge\Structure\AttributesProperty;
use Apigee\Edge\Tests\Test\Controller\AttributesAwareEntityControllerTestTrait;
use Apigee\Edge\Tests\Test\Controller\OrganizationAwareEntityControllerValidatorTrait;
use Apigee\Edge\Tests\Test\TestClientFactory;

/**
 * Class CompanyAppControllerTest.
 *
 * @group controller
 */
class CompanyAppControllerBase extends AppByOwnerControllerBase
{
    use AttributesAwareEntityControllerTestTrait;
    use CompanyAwareControllerTestTrait;
    use OrganizationAwareEntityControllerValidatorTrait;

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::setupCompany();
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        static::tearDownCompany();
    }

    /**
     * @inheritdoc
     */
    public static function sampleDataForEntityCreate(): EntityInterface
    {
        static $entity;
        if (null === $entity) {
            $isMock = TestClientFactory::isMockClient(static::$client);
            $entity = new CompanyApp(
                [
                    'name' => $isMock ? 'phpunit_test_app' : static::$random->unique()->userName,
                    'apiProducts' => [static::$apiProductName],
                    'attributes' => new AttributesProperty(['foo' => 'bar']),
                    'callbackUrl' => 'http://example.com',
                ]
            );
            $entity->setDisplayName(
                $isMock ? 'PHP Unit: Test app' : static::$random->unique()->words(
                    static::$random->numberBetween(1, 8),
                    true
                )
            );
            $entity->setDescription($isMock ? 'This is a test app created by PHP Unit.' : static::$random->text());
        }

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public static function sampleDataForEntityUpdate(): EntityInterface
    {
        static $entity;
        if (null === $entity) {
            $isMock = TestClientFactory::isMockClient(static::$client);
            $entity = new CompanyApp(
                [
                    'attributes' => new AttributesProperty(['foo' => 'foo', 'bar' => 'baz']),
                    'callbackUrl' => $isMock ? 'http://foo.example.com' : static::$random->url,
                ]
            );
            $entity->setDisplayName(
                $isMock ? '(Edited) PHP Unit: Test app' : static::$random->unique()->words(
                    static::$random->numberBetween(1, 8),
                    true
                )
            );
            $entity->setDescription(
                $isMock ? '(Edited) This is a test app created by PHP Unit.' : static::$random->unique()->text()
            );
        }

        return $entity;
    }

    /**
     * We have to override this otherwise dependents of this function are being skipped.
     * Also, "@inheritdoc" is not going to work in case of "@depends" annotations so those must be repeated.
     *
     * @inheritdoc
     */
    public function testCreate()
    {
        return parent::testCreate();
    }

    /**
     * It is easier to test it here instead in the CompanyControllerTest.
     */
    public function testCompanyHasApp(): void
    {
        if (TestClientFactory::isMockClient(static::$client)) {
            $this->markTestSkipped(static::$onlyOnlineClientSkipMessage);
        }
        $controller = new CompanyController(
            static::getOrganization(static::$client),
            static::$client
        );
        $entity = clone static::sampleDataForEntityCreate();
        $entity->{'set' . ucfirst($entity->idProperty())}($entity->id() . '_has');
        $this->getEntityController()->create($entity);
        static::$createdEntities[$entity->id()] = $entity;
        /** @var \Apigee\Edge\Api\Management\Entity\CompanyAppInterface $company */
        $company = $controller->load(static::$companyName);
        $this->assertTrue($company->hasApp($entity->id()));
        $this->getEntityController()->delete($entity->id());
        $company = $controller->load(static::$companyName);
        $this->assertFalse($company->hasApp($entity->id()));
        unset(static::$createdEntities[$entity->id()]);
    }

    /**
     * @inheritdoc
     */
    public function cpsLimitTestIdFieldProvider(): array
    {
        return [['name']];
    }

    /**
     * @inheritdoc
     */
    protected static function getEntityController(): EntityControllerInterface
    {
        static $controller;
        if (!$controller) {
            $controller = new CompanyAppController(
                static::getOrganization(static::$client),
                static::$companyName,
                static::$client
            );
        }

        return $controller;
    }

    /**
     * @inheritdoc
     */
    protected static function expectedAfterEntityCreate(): EntityInterface
    {
        /** @var \Apigee\Edge\Api\Management\Entity\CompanyApp $entity */
        $entity = parent::expectedAfterEntityCreate();
        $entity->setStatus('approved');
        // The testCreate test would fail without this because ObjectNormalizer creates displayName and description
        // properties on entities (because of the existence of getters) because these are not in the
        // Edge response, at least not as entity properties.
        $entity->deleteAttribute('DisplayName');
        $entity->deleteAttribute('Notes');

        return $entity;
    }

    protected static function expectedAfterEntityUpdate(): EntityInterface
    {
        /** @var \Apigee\Edge\Api\Management\Entity\CompanyApp $entity */
        $entity = parent::expectedAfterEntityUpdate();
        // The testUpdate test would fail without this because ObjectNormalizer creates displayName and description
        // properties on entities (because of the existence of getters) but these are not in the
        // Edge response, at least not as entity properties.
        $entity->deleteAttribute('DisplayName');
        $entity->deleteAttribute('Notes');

        return $entity;
    }
}
