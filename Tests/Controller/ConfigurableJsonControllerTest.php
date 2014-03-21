<?php

namespace Coral\ContentBundle\Tests\Controller;

use Doctrine\Common\DataFixtures\Loader;
use Coral\SiteBundle\Service\CoralConnectService;

use Coral\ContentBundle\Controller\ConfigurableJsonController;
use Coral\CoreBundle\Utility\JsonParser;
use Coral\CoreBundle\Test\JsonTestCase;

class SampleConfiguraleJsonController extends ConfigurableJsonController
{
    private $testCaseContext;

    //Force inject JsonTestCase object instead of coral_connect service
    public function get($id)
    {
        if($id == 'coral_connect')
        {
            return $this->testCaseContext;
        }
        return parent::get($id);
    }

    public function publicGetConfiguration($slug, $isMandatory, $testCaseContext)
    {
        $this->testCaseContext = $testCaseContext;
        return $this->getConfiguration($slug, $isMandatory);
    }
}

class ConfigurableJsonControllerTest extends JsonTestCase
{
    public function __construct()
    {
        /**
         * Initially a database needs to be created or the very first run
         * of phpunit fails. setupBeforeClass couldn't be used as it is static.
         */
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));
    }

    public function doGetRequest($uri, $bodyContent = null)
    {
        $client = parent::doGetRequest($uri, $bodyContent);
        $this->assertIsJsonResponse($client);
        if($client->getResponse()->getStatusCode() > 299)
        {
            throw new \Coral\CoreBundle\Exception\CoralConnectException("Unable to find content");
        }
        return new JsonParser($client->getResponse()->getContent());
    }

    public function testReadMandatory()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $client = $this->doPostRequest('/v1/node/add', '{ "name": "Config", "slug": "config" }' );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);
        $jsonRequest = new JsonParser($client->getResponse()->getContent());
        $nodeId = $jsonRequest->getMandatoryParam('id');

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "some_text", "renderer": "markdown" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "{ \'key\': 10 }", "renderer": "json" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);

        $client = $this->doPostRequest('/v1/content/publish/' . $nodeId);
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $controller = new SampleConfiguraleJsonController;

        $configuration = $controller->publicGetConfiguration('config', true, $this);

        $this->assertEquals(10, $configuration->getMandatoryParam('key'));
    }

    /**
     * @expectedException Coral\CoreBundle\Exception\CoralConnectException
     */
    public function testReadMandatoryException()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $controller = new SampleConfiguraleJsonController;

        $controller->publicGetConfiguration('unknown', true, $this);
    }

    public function testReadOptional()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $client = $this->doPostRequest('/v1/node/add', '{ "name": "Config", "slug": "config" }' );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);
        $jsonRequest = new JsonParser($client->getResponse()->getContent());
        $nodeId = $jsonRequest->getMandatoryParam('id');

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "some_text", "renderer": "markdown" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "{ \'key\': 10 }", "renderer": "json" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);

        $client = $this->doPostRequest('/v1/content/publish/' . $nodeId);
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $controller = new SampleConfiguraleJsonController;

        $configuration = $controller->publicGetConfiguration('config', false, $this);

        $this->assertEquals(10, $configuration->getOptionalParam('key'));
        $this->assertEquals(20, $configuration->getOptionalParam('unknown', 20));

        $configuration = $controller->publicGetConfiguration('unknown', false, $this);

        $this->assertFalse($configuration->getOptionalParam('invalid'));
    }
}
