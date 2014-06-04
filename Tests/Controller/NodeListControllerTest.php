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

class NodeListControllerTest extends JsonTestCase
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

    public function testList()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Homepage", "slug": "homepage", "param1": "value1", "param2": "value2", "tree_param": "treevalue" }'
        );
        $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "First Child", "slug": "first_child" }'
        );

        $client = $this->doGetRequest('/v1/node/list');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $items = $jsonRequest->getMandatoryParam('items');
        $this->assertCount(1, $items);
        $this->assertEquals('homepage', $jsonRequest->getMandatoryParam('items[0].slug'));
        $this->assertEquals('value1', $jsonRequest->getMandatoryParam('items[0].param1'));
        $this->assertEquals('treevalue', $jsonRequest->getMandatoryParam('items[0].tree_param'));
        $this->assertEquals('treevalue', $jsonRequest->getMandatoryParam('items[0].items[0].tree_param'));
        $this->assertFalse($jsonRequest->hasParam('items[0].items[0].param1'));
        $this->assertFalse($jsonRequest->hasParam('items[0].created_at'));

        //another account
        $client = $this->doAlternativeAccountGetRequest('/v1/node/list');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertCount(0, $jsonRequest->getMandatoryParam('items'));
    }

    public function testListSubTree()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Homepage", "slug": "homepage", "param1": "value1", "param2": "value2", "tree_param": "treevalue" }'
        );

        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "First Child", "slug": "first_child" }'
        );
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $firstChildId = $jsonRequest->getMandatoryParam('id');

        $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Last Child", "slug": "last_child" }'
        );

        $this->doPostRequest(
            '/v1/node/add/last-child-of/' . $firstChildId,
            '{ "name": "Sub Child", "slug": "sub_child" }'
        );

        $client = $this->doGetRequest('/v1/node/list/' . $firstChildId);

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $items = $jsonRequest->getMandatoryParam('items');
        $this->assertCount(1, $items);
        $this->assertCount(1, $items[0]['items']);
        $this->assertEquals('first_child', $items[0]['slug']);
        $this->assertEquals('treevalue', $items[0]['tree_param']);
        $this->assertEquals('sub_child', $items[0]['items'][0]['slug']);
        $this->assertEquals('treevalue', $items[0]['items'][0]['tree_param']);
        $this->assertFalse($jsonRequest->hasParam('items[0].created_at'));

        //another account
        $client = $this->doAlternativeAccountGetRequest('/v1/node/list/' . $firstChildId);
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 401);
    }

    public function testInfo()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        //create nodes
        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Homepage", "slug": "homepage", "param1": "value1", "param2": "value2", "tree_param": "treevalue" }'
        );
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $rootId = $jsonRequest->getMandatoryParam('id');

        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "First Child", "slug": "first_child" }'
        );
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $childId = $jsonRequest->getMandatoryParam('id');

        //Get node info
        $client = $this->doGetRequest('/v1/node/info/' . $rootId);

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEmpty($jsonRequest->getOptionalParam('items'));
        $this->assertEquals($rootId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('homepage', $jsonRequest->getMandatoryParam('slug'));
        $this->assertEquals('value1', $jsonRequest->getMandatoryParam('param1'));
        $this->assertEquals('treevalue', $jsonRequest->getMandatoryParam('tree_param'));
        $this->assertFalse($jsonRequest->hasParam('created_at'));

        //Get node info
        $client = $this->doGetRequest('/v1/node/info/' . $childId);

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($childId, $jsonRequest->getMandatoryParam('id'));
        $this->assertFalse($jsonRequest->hasParam('tree_param'));

        //another account
        $client = $this->doAlternativeAccountGetRequest('/v1/node/info/' . $rootId);
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 401);
    }

    public function testDetailPublished()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 3;

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "some_text", "renderer": "markdown" }'
        );
        $this->assertIsStatusCode($client, 201);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $permid = $jsonRequest->getMandatoryParam('permid');
        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "other_text", "renderer": "markdown" }'
        );
        $this->assertIsStatusCode($client, 201);

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/xion2',
            '{ "content": "other_text_section2", "renderer": "markdown" }'
        );
        $this->assertIsStatusCode($client, 201);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $permid2 = $jsonRequest->getMandatoryParam('permid');

        //Get node detail
        $client = $this->doGetRequest('/v1/node/detail/published/first-child');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('first-child', $jsonRequest->getMandatoryParam('slug'));
        $this->assertEquals('treevalue', $jsonRequest->getMandatoryParam('tree_param'));
        //is valid timestamp
        $this->assertTrue(strtotime($jsonRequest->getMandatoryParam('created_at')) !== false);
        $this->assertEquals(strtotime($jsonRequest->getMandatoryParam('created_at')), strtotime($jsonRequest->getMandatoryParam('updated_at')));
        $sections = $jsonRequest->getMandatoryParam('sections');
        $this->assertCount(0, $sections);

        //test publish
        $client = $this->doPostRequest('/v1/content/publish/' . $nodeId);
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $client = $this->doPostRequest(
            '/v1/content/update/' . $permid,
            '{ "content": "text3", "renderer": "markdown" }'
        );
        $this->assertIsStatusCode($client, 200);

        $client = $this->doPostRequest(
            '/v1/content/update/' . $permid,
            '{ "content": "text3", "renderer": "markdown" }'
        );
        $this->assertIsStatusCode($client, 200);

        $client = $this->doPostRequest(
            '/v1/content/update/' . $permid2,
            '{ "content": "new_text_section2", "renderer": "markdown" }'
        );
        $this->assertIsStatusCode($client, 200);

        //Get node detail
        $client = $this->doGetRequest('/v1/node/detail/published/first-child');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('first-child', $jsonRequest->getMandatoryParam('slug'));
        $this->assertEquals('treevalue', $jsonRequest->getMandatoryParam('tree_param'));
        $this->assertGreaterThanOrEqual(strtotime($jsonRequest->getMandatoryParam('created_at')), strtotime($jsonRequest->getMandatoryParam('updated_at')));
        $sections = $jsonRequest->getMandatoryParam('sections');
        $this->assertCount(2, $sections);
        $this->assertCount(2, $sections[0]['items']);
        $this->assertEquals('text', $sections[0]['name']);
        $this->assertEquals('some_text', $jsonRequest->getMandatoryParam('sections[0].items[0].content'));
        $this->assertEquals('markdown', $sections[0]['items'][0]['renderer']);
        $this->assertCount(1, $sections[1]['items']);
        $this->assertEquals('xion2', $sections[1]['name']);
        $this->assertEquals('other_text_section2', $jsonRequest->getMandatoryParam('sections[1].items[0].content'));

        //publish text3 change
        $client = $this->doPostRequest('/v1/content/publish/' . $nodeId);
        $this->assertIsStatusCode($client, 200);
        $client = $this->doGetRequest('/v1/node/detail/published/first-child');
        $this->assertIsJsonResponse($client);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('text3', $jsonRequest->getMandatoryParam('sections[0].items[0].content'));
        $this->assertEquals('new_text_section2', $jsonRequest->getMandatoryParam('sections[1].items[0].content'));
    }

    public function testDetailPublishedInvalidAccount()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));

        //another account
        $client = $this->doAlternativeAccountGetRequest('/v1/node/detail/published/homepage');
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 401);
    }

    public function testDetailLatest()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));
        $nodeId = 3;

        $client = $this->doPostRequest(
            '/v1/content/add/' . $nodeId . '/text',
            '{ "content": "some_text", "renderer": "markdown" }'
        );

        //Get node detail
        $client = $this->doGetRequest('/v1/node/detail/latest/first-child');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('first-child', $jsonRequest->getMandatoryParam('slug'));
        $this->assertEquals('treevalue', $jsonRequest->getMandatoryParam('tree_param'));
        //is valid timestamp
        $this->assertTrue(strtotime($jsonRequest->getMandatoryParam('created_at')) !== false);
        $sections = $jsonRequest->getMandatoryParam('sections');
        $this->assertCount(1, $sections);
        $this->assertCount(1, $sections[0]['items']);
        $this->assertEquals('text', $sections[0]['name']);
        $this->assertEquals('some_text', $sections[0]['items'][0]['content']);
        $this->assertEquals('markdown', $sections[0]['items'][0]['renderer']);

        $client = $this->doPostRequest('/v1/content/publish/' . $nodeId);
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        //Get node detail
        $client = $this->doGetRequest('/v1/node/detail/latest/first-child');

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertEquals($nodeId, $jsonRequest->getMandatoryParam('id'));
        $this->assertEquals('first-child', $jsonRequest->getMandatoryParam('slug'));
        $this->assertEquals('treevalue', $jsonRequest->getMandatoryParam('tree_param'));
        $sections = $jsonRequest->getMandatoryParam('sections');
        $this->assertCount(1, $sections);
        $this->assertCount(1, $sections[0]['items']);
        $this->assertEquals('text', $sections[0]['name']);
        $this->assertEquals('some_text', $sections[0]['items'][0]['content']);
        $this->assertEquals('markdown', $sections[0]['items'][0]['renderer']);
    }

    public function testDetailLatestInvalidAccount()
    {
        $this->loadFixtures(array(
            'Coral\ContentBundle\Tests\DataFixtures\ORM\ContentSettingsData'
        ));

        //another account
        $client = $this->doAlternativeAccountGetRequest('/v1/node/detail/latest/homepage');
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 401);
    }
}
