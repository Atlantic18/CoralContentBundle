<?php

/*
 * This file is part of the Coral package.
 *
 * (c) Frantisek Troster <frantisek.troster@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Coral\ContentBundle\Tests\Controller;

use Doctrine\Common\DataFixtures\Loader;

use Coral\CoreBundle\Utility\JsonParser;
use Coral\CoreBundle\Test\JsonTestCase;

class NodeContentControllerTest extends JsonTestCase
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

    public function testAddAndDetail()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "Some text", "renderer": "markdown" }'
        );

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertFalse($jsonRequest->getMandatoryParam('permid') == '');

        $permid = $jsonRequest->getMandatoryParam('permid');

        //Get node detail
        $client = $this->doGetRequest('/v1/node/detail/latest/homepage');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('homepage', $jsonRequest->getMandatoryParam('slug'));
        $sections = $jsonRequest->getMandatoryParam('sections');
        $this->assertCount(1, $sections);
        $this->assertCount(1, $sections[0]['items']);
        $this->assertEquals('text', $sections[0]['name']);
        $this->assertEquals('Some text', $sections[0]['items'][0]['content']);
        $this->assertEquals('markdown', $sections[0]['items'][0]['renderer']);
    }

    public function testAddMoreContent()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text1", "renderer": "markdown" }'
        );
        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/column',
            '{ "content": "text3", "renderer": "markdown" }'
        );
        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text2", "renderer": "markdown" }'
        );

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertFalse($jsonRequest->getMandatoryParam('permid') == '');

        $permid = $jsonRequest->getMandatoryParam('permid');

        //Get node detail - latest
        $client = $this->doGetRequest('/v1/node/detail/latest/homepage');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('homepage', $jsonRequest->getMandatoryParam('slug'));
        $sections = $jsonRequest->getMandatoryParam('sections');
        $this->assertCount(2, $sections);
        $this->assertCount(2, $sections[0]['items']);
        $this->assertCount(1, $sections[1]['items']);
        $this->assertEquals('text', $sections[0]['name']);
        $this->assertEquals('text1', $sections[0]['items'][0]['content']);
        $this->assertEquals('text2', $sections[0]['items'][1]['content']);
        $this->assertEquals($permid, $sections[0]['items'][1]['permid']);
        $this->assertEquals('text3', $sections[1]['items'][0]['content']);
    }

    public function testAddInvalidAccount()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $client = $this->doAlternativeAccountPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "Some text", "renderer": "markdown" }'
        );

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 401);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('failed', $jsonRequest->getMandatoryParam('status'));
        $this->assertRegExp('/invalid/', $jsonRequest->getMandatoryParam('message'));
    }

    public function testAddPositionContent()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text1", "renderer": "markdown" }'
        );
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $permid = $jsonRequest->getMandatoryParam('permid');

        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/column',
            '{ "content": "text3", "renderer": "markdown" }'
        );
        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text2", "renderer": "markdown" }'
        );

        //Get node detail - latest
        $client = $this->doGetRequest('/v1/node/detail/latest/homepage');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('homepage', $jsonRequest->getMandatoryParam('slug'));
        $sections = $jsonRequest->getMandatoryParam('sections');
        $this->assertCount(2, $sections);
        $this->assertCount(2, $sections[0]['items']);
        $this->assertCount(1, $sections[1]['items']);
        $this->assertEquals('text', $sections[0]['name']);
        $this->assertEquals('text1', $sections[0]['items'][0]['content']);
        $this->assertEquals('text2', $sections[0]['items'][1]['content']);
        $this->assertEquals('text3', $sections[1]['items'][0]['content']);


        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/after/' . $permid,
            '{ "content": "text4", "renderer": "markdown" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertFalse($jsonRequest->getMandatoryParam('permid') == '');
        $newPermid = $jsonRequest->getMandatoryParam('permid');

        //Get node detail - latest
        $client = $this->doGetRequest('/v1/node/detail/latest/homepage');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('homepage', $jsonRequest->getMandatoryParam('slug'));
        $sections = $jsonRequest->getMandatoryParam('sections');
        $this->assertCount(2, $sections);
        $this->assertCount(3, $sections[0]['items']);
        $this->assertCount(1, $sections[1]['items']);
        $this->assertEquals('text', $sections[0]['name']);
        $this->assertEquals('text1', $sections[0]['items'][0]['content']);
        $this->assertEquals($permid, $sections[0]['items'][0]['permid']);
        $this->assertEquals('text4', $sections[0]['items'][1]['content']);
        $this->assertEquals($newPermid, $sections[0]['items'][1]['permid']);
        $this->assertEquals('text2', $sections[0]['items'][2]['content']);
        $this->assertEquals('text3', $sections[1]['items'][0]['content']);
    }

    public function testAddPositionInvalidAccount()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "Some text", "renderer": "markdown" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $permid = $jsonRequest->getMandatoryParam('permid');

        $client = $this->doAlternativeAccountPostRequest(
            '/v1/content/add/' . $nodeId . '/after/' . $permid,
            '{ "content": "Some text", "renderer": "markdown" }'
        );

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 401);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('failed', $jsonRequest->getMandatoryParam('status'));
        $this->assertRegExp('/invalid/', $jsonRequest->getMandatoryParam('message'));
    }

    public function testUpdateContent()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text1", "renderer": "markdown" }'
        );
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $permid = $jsonRequest->getMandatoryParam('permid');

        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/column',
            '{ "content": "text3", "renderer": "markdown" }'
        );
        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text2", "renderer": "markdown" }'
        );

        //Get node detail - latest
        $client = $this->doGetRequest('/v1/node/detail/latest/homepage');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('homepage', $jsonRequest->getMandatoryParam('slug'));
        $sections = $jsonRequest->getMandatoryParam('sections');
        $this->assertCount(2, $sections);
        $this->assertCount(2, $sections[0]['items']);
        $this->assertCount(1, $sections[1]['items']);
        $this->assertEquals('text', $sections[0]['name']);
        $this->assertEquals('text1', $sections[0]['items'][0]['content']);
        $this->assertEquals('text2', $sections[0]['items'][1]['content']);
        $this->assertEquals('text3', $sections[1]['items'][0]['content']);


        $client = $this->doPostRequest(
            '/v1/content/update/' . $permid,
            '{ "content": "text4", "renderer": "markdown" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));

        //Get node detail - latest
        $client = $this->doGetRequest('/v1/node/detail/latest/homepage');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('homepage', $jsonRequest->getMandatoryParam('slug'));
        $sections = $jsonRequest->getMandatoryParam('sections');
        $this->assertCount(2, $sections);
        $this->assertCount(2, $sections[0]['items']);
        $this->assertCount(1, $sections[1]['items']);
        $this->assertEquals('text', $sections[0]['name']);
        $this->assertEquals('text4', $sections[0]['items'][0]['content']);
        $this->assertEquals($permid, $sections[0]['items'][0]['permid']);
        $this->assertEquals('text2', $sections[0]['items'][1]['content']);
        $this->assertEquals('text3', $sections[1]['items'][0]['content']);
    }

    public function testUpdateInvalidAccount()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "Some text", "renderer": "markdown" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $permid = $jsonRequest->getMandatoryParam('permid');

        $client = $this->doAlternativeAccountPostRequest(
            '/v1/content/update/' . $permid,
            '{ "content": "Some text", "renderer": "markdown" }'
        );

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 401);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('failed', $jsonRequest->getMandatoryParam('status'));
        $this->assertRegExp('/invalid/', $jsonRequest->getMandatoryParam('message'));
    }

    public function testMoveUpContent()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text1", "renderer": "markdown" }'
        );
        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/column',
            '{ "content": "text3", "renderer": "markdown" }'
        );
        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text2", "renderer": "markdown" }'
        );
        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text4", "renderer": "markdown" }'
        );
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $permid = $jsonRequest->getMandatoryParam('permid');

        $client = $this->doPostRequest('/v1/content/move-up/' . $permid);

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));

        //Get node detail - latest
        $client = $this->doGetRequest('/v1/node/detail/latest/homepage');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('homepage', $jsonRequest->getMandatoryParam('slug'));
        $sections = $jsonRequest->getMandatoryParam('sections');

        $this->assertCount(2, $sections);
        $this->assertCount(3, $sections[0]['items']);
        $this->assertCount(1, $sections[1]['items']);
        $this->assertEquals('text', $sections[0]['name']);
        $this->assertEquals('text1', $sections[0]['items'][0]['content']);
        $this->assertEquals('text4', $sections[0]['items'][1]['content']);
        $this->assertEquals('text2', $sections[0]['items'][2]['content']);
        $this->assertEquals('text3', $sections[1]['items'][0]['content']);
    }

    public function testMoveUpFirst()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text1", "renderer": "markdown" }'
        );
        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/column',
            '{ "content": "text3", "renderer": "markdown" }'
        );
        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text2", "renderer": "markdown" }'
        );
        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text4", "renderer": "markdown" }'
        );
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $permid = $jsonRequest->getMandatoryParam('permid');

        $client = $this->doPostRequest('/v1/content/move-up/' . $permid);

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));

        //Get node detail - latest
        $client = $this->doGetRequest('/v1/node/detail/latest/homepage');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('homepage', $jsonRequest->getMandatoryParam('slug'));
        $sections = $jsonRequest->getMandatoryParam('sections');
        $this->assertCount(2, $sections);
        $this->assertCount(3, $sections[0]['items']);
        $this->assertCount(1, $sections[1]['items']);
        $this->assertEquals('text', $sections[0]['name']);
        $this->assertEquals('text1', $sections[0]['items'][0]['content']);
        $this->assertEquals('text2', $sections[0]['items'][1]['content']);
        $this->assertEquals('text4', $sections[0]['items'][2]['content']);
        $this->assertEquals('text3', $sections[1]['items'][0]['content']);
    }

    public function testMoveUpInvalidAccount()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "Some text", "renderer": "markdown" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $permid = $jsonRequest->getMandatoryParam('permid');

        $client = $this->doAlternativeAccountPostRequest(
            '/v1/content/move-up/' . $permid
        );

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 401);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('failed', $jsonRequest->getMandatoryParam('status'));
        $this->assertRegExp('/invalid/', $jsonRequest->getMandatoryParam('message'));
    }

    public function testMoveDownContent()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text1", "renderer": "markdown" }'
        );
        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/column',
            '{ "content": "text3", "renderer": "markdown" }'
        );
        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text2", "renderer": "markdown" }'
        );
        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text4", "renderer": "markdown" }'
        );
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $permid = $jsonRequest->getMandatoryParam('permid');

        $client = $this->doPostRequest('/v1/content/move-down/' . $permid);

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));

        //Get node detail - latest
        $client = $this->doGetRequest('/v1/node/detail/latest/homepage');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('homepage', $jsonRequest->getMandatoryParam('slug'));
        $sections = $jsonRequest->getMandatoryParam('sections');

        $this->assertCount(2, $sections);
        $this->assertCount(3, $sections[0]['items']);
        $this->assertCount(1, $sections[1]['items']);
        $this->assertEquals('text', $sections[0]['name']);
        $this->assertEquals('text1', $sections[0]['items'][0]['content']);
        $this->assertEquals('text4', $sections[0]['items'][1]['content']);
        $this->assertEquals('text2', $sections[0]['items'][2]['content']);
        $this->assertEquals('text3', $sections[1]['items'][0]['content']);
    }

    public function testMoveDownLast()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text1", "renderer": "markdown" }'
        );
        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/column',
            '{ "content": "text3", "renderer": "markdown" }'
        );
        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text2", "renderer": "markdown" }'
        );
        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text4", "renderer": "markdown" }'
        );
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $permid = $jsonRequest->getMandatoryParam('permid');

        $client = $this->doPostRequest('/v1/content/move-down/' . $permid);

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));

        //Get node detail - latest
        $client = $this->doGetRequest('/v1/node/detail/latest/homepage');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('homepage', $jsonRequest->getMandatoryParam('slug'));
        $sections = $jsonRequest->getMandatoryParam('sections');
        $this->assertCount(2, $sections);
        $this->assertCount(3, $sections[0]['items']);
        $this->assertCount(1, $sections[1]['items']);
        $this->assertEquals('text', $sections[0]['name']);
        $this->assertEquals('text1', $sections[0]['items'][0]['content']);
        $this->assertEquals('text2', $sections[0]['items'][1]['content']);
        $this->assertEquals('text4', $sections[0]['items'][2]['content']);
        $this->assertEquals('text3', $sections[1]['items'][0]['content']);
    }

    public function testMoveDownInvalidAccount()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "Some text", "renderer": "markdown" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $permid = $jsonRequest->getMandatoryParam('permid');

        $client = $this->doAlternativeAccountPostRequest(
            '/v1/content/move-down/' . $permid
        );

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 401);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('failed', $jsonRequest->getMandatoryParam('status'));
        $this->assertRegExp('/invalid/', $jsonRequest->getMandatoryParam('message'));
    }

    public function testDelete()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text1", "renderer": "markdown" }'
        );
        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/column',
            '{ "content": "text3", "renderer": "markdown" }'
        );
        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text2", "renderer": "markdown" }'
        );
        $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "text4", "renderer": "markdown" }'
        );
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $permid = $jsonRequest->getMandatoryParam('permid');

        $client = $this->doDeleteRequest('/v1/content/delete/' . $permid);

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));

        //Get node detail - latest
        $client = $this->doGetRequest('/v1/node/detail/latest/homepage');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('homepage', $jsonRequest->getMandatoryParam('slug'));
        $sections = $jsonRequest->getMandatoryParam('sections');
        $this->assertCount(2, $sections);
        $this->assertCount(2, $sections[0]['items']);
        $this->assertCount(1, $sections[1]['items']);
        $this->assertEquals('text', $sections[0]['name']);
        $this->assertEquals('text1', $sections[0]['items'][0]['content']);
        $this->assertEquals('text4', $sections[0]['items'][1]['content']);
        $this->assertEquals('text3', $sections[1]['items'][0]['content']);
    }

    public function testDeleteInvalidAccount()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 1;

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "Some text", "renderer": "markdown" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $permid = $jsonRequest->getMandatoryParam('permid');

        $client = $this->doAlternativeAccountDeleteRequest(
            '/v1/content/delete/' . $permid
        );

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 401);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('failed', $jsonRequest->getMandatoryParam('status'));
        $this->assertRegExp('/invalid/', $jsonRequest->getMandatoryParam('message'));
    }
}
