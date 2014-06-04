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

class NodeManipulationControllerTest extends JsonTestCase
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

    public function testAdd()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Homepage", "slug": "homepage", "param1": "value1", "param2": "value2", "param3": "" }'
        );

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertGreaterThan(0, $jsonRequest->getMandatoryParam('id'));

        $client = $this->doGetRequest('/v1/node/list');
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $items = $jsonRequest->getMandatoryParam('items');
        $this->assertCount(1, $items);
        $this->assertEquals('value1', $items[0]['param1']);
        $this->assertEquals('value2', $items[0]['param2']);
        $this->assertFalse(isset($items[0]['param3']));
    }

    public function testAddDuplicit()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Homepage", "slug": "homepage", "param1": "value1", "param2": "value2", "param3": "" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);

        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Homepage 2", "slug": "homepage" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 500);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('failed', $jsonRequest->getMandatoryParam('status'));
        $this->assertRegExp('/duplicit/', $jsonRequest->getMandatoryParam('message'));

        $client = $this->doAlternativeAccountPostRequest(
            '/v1/node/add',
            '{ "name": "Homepage", "slug": "homepage", "param1": "value1", "param2": "value2", "param3": "" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);
    }

    public function testUpdateDuplicit()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Homepage", "slug": "homepage", "param1": "value1", "param2": "value2", "param3": "" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);

        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Homepage 2", "slug": "homepage-2" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $client = $this->doPostRequest(
            '/v1/node/update/' . $jsonRequest->getMandatoryParam('id'),
            '{ "name": "Homepage 2", "slug": "homepage" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 500);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('failed', $jsonRequest->getMandatoryParam('status'));
        $this->assertRegExp('/duplicit/', $jsonRequest->getMandatoryParam('message'));
    }

    public function testUpdate()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        //Add new node
        $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Homepage", "slug": "homepage", "param1": "value1", "param2": "value2" }'
        );

        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "First Child", "slug": "first_child", "param_delete": "value_delete", "param_delete2": "value_delete" }'
        );

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertGreaterThan(0, $jsonRequest->getMandatoryParam('id'));
        $nodeId = $jsonRequest->getMandatoryParam('id');

        //Check what's there
        $client = $this->doGetRequest('/v1/node/list');

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $items = $jsonRequest->getMandatoryParam('items');
        $this->assertCount(1, $items);
        $this->assertEquals('value1', $items[0]['param1']);
        $this->assertCount(1, $items[0]['items']);
        $this->assertEquals('first_child', $items[0]['items'][0]['slug']);
        $this->assertFalse(isset($items[0]['items'][0]['param3']));
        $this->assertEquals('value_delete', $items[0]['items'][0]['param_delete']);
        $this->assertEquals('value_delete', $items[0]['items'][0]['param_delete2']);

        //Update
        $client = $this->doPostRequest(
            '/v1/node/update/' . $nodeId,
            '{ "name": "First Child", "slug": "first_child_changed", "param3": "value3", "param_delete2": "" }'
        );

        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));

        //Check what's there
        $client = $this->doGetRequest('/v1/node/list');

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $items = $jsonRequest->getMandatoryParam('items');
        $this->assertCount(1, $items);
        $this->assertEquals('value1', $items[0]['param1']);
        $this->assertCount(1, $items[0]['items']);
        $this->assertEquals('first_child_changed', $items[0]['items'][0]['slug']);
        $this->assertEquals('value3', $items[0]['items'][0]['param3']);
        $this->assertFalse(isset($items[0]['items'][0]['param_delete']));
        $this->assertFalse(isset($items[0]['items'][0]['param_delete2']));
    }

    public function testAddLastChild()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Homepage", "slug": "homepage", "param1": "value1", "param2": "value2" }'
        );
        $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "First Child", "slug": "first_child" }'
        );
        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Last Child", "slug": "last_child" }'
        );

        $client = $this->doGetRequest('/v1/node/list');

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $items = $jsonRequest->getMandatoryParam('items');
        $this->assertCount(1, $items);
        $this->assertCount(2, $items[0]['items']);
        $this->assertEquals('value1', $items[0]['param1']);
        $this->assertEquals('first_child', $items[0]['items'][0]['slug']);
        $this->assertEquals('last_child', $items[0]['items'][1]['slug']);
    }

    public function testAddPositionAndMove()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        //root
        $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Homepage", "slug": "homepage" }'
        );

        //first child
        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "First Child", "slug": "first_child" }'
        );
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $firstChildId = $jsonRequest->getMandatoryParam('id');

        //before first child
        $client = $this->doPostRequest(
            '/v1/node/add/before/' . $firstChildId,
            '{ "name": "Last Child", "slug": "last_child" }'
        );
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 201);

        $jsonRequest   = new JsonParser($client->getResponse()->getContent());
        $secondChildId = $jsonRequest->getMandatoryParam('id');

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $this->assertGreaterThan(0, $secondChildId);

        //List
        $client = $this->doGetRequest('/v1/node/list');

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $items = $jsonRequest->getMandatoryParam('items');
        $this->assertCount(1, $items);
        $this->assertCount(2, $items[0]['items']);
        $this->assertEquals('last_child', $items[0]['items'][0]['slug']);
        $this->assertEquals('first_child', $items[0]['items'][1]['slug']);

        //move to last position
        $client = $this->doPostRequest('/v1/node/move/' . $secondChildId . '/after/' . $firstChildId);
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));

        //List
        $client = $this->doGetRequest('/v1/node/list');

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $items = $jsonRequest->getMandatoryParam('items');
        $this->assertCount(1, $items);
        $this->assertCount(2, $items[0]['items']);
        $this->assertEquals('first_child', $items[0]['items'][0]['slug']);
        $this->assertEquals('last_child', $items[0]['items'][1]['slug']);

        //move to last position
        $client = $this->doPostRequest('/v1/node/move/' . $firstChildId . '/last-child-of/' . $secondChildId);
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));

        //List
        $client = $this->doGetRequest('/v1/node/list');

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());

        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $items = $jsonRequest->getMandatoryParam('items');
        $this->assertCount(1, $items);
        $this->assertCount(1, $items[0]['items']);
        $this->assertCount(1, $items[0]['items'][0]['items']);
        $this->assertEquals('last_child', $items[0]['items'][0]['slug']);
        $this->assertEquals('first_child', $items[0]['items'][0]['items'][0]['slug']);
    }

    public function testDelete()
    {
        $this->loadFixtures(array(
            'Coral\CoreBundle\Tests\DataFixtures\ORM\MinimalSettingsData'
        ));

        //add nodes
        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Homepage", "slug": "homepage", "param1": "value1", "param2": "value2" }'
        );
        $jsonRequest = new JsonParser($client->getResponse()->getContent());
        $rootId      = $jsonRequest->getMandatoryParam('id');

        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "First Child", "slug": "first_child" }'
        );
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $firstChildId = $jsonRequest->getMandatoryParam('id');

        $client = $this->doPostRequest(
            '/v1/node/add',
            '{ "name": "Last Child", "slug": "last_child" }'
        );
        $jsonRequest   = new JsonParser($client->getResponse()->getContent());
        $secondChildId = $jsonRequest->getMandatoryParam('id');

        //list all is ok
        $client = $this->doGetRequest('/v1/node/list');

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $items = $jsonRequest->getMandatoryParam('items');
        $this->assertCount(1, $items);
        $this->assertCount(2, $items[0]['items']);
        $this->assertEquals('value1', $items[0]['param1']);
        $this->assertEquals('first_child', $items[0]['items'][0]['slug']);
        $this->assertEquals('last_child', $items[0]['items'][1]['slug']);

        //delete first child invalid account
        $client = $this->doAlternativeAccountDeleteRequest('/v1/node/delete/' . $firstChildId);
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 401);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('failed', $jsonRequest->getMandatoryParam('status'));
        $this->assertRegExp('/invalid/', $jsonRequest->getMandatoryParam('message'));

        //delete first child
        $client = $this->doDeleteRequest('/v1/node/delete/' . $firstChildId);
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));

        //List result
        $client = $this->doGetRequest('/v1/node/list');

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $items = $jsonRequest->getMandatoryParam('items');
        $this->assertCount(1, $items);
        $this->assertCount(1, $items[0]['items']);
        $this->assertEquals('value1', $items[0]['param1']);
        $this->assertEquals('last_child', $items[0]['items'][0]['slug']);

        //delete root
        $client = $this->doDeleteRequest('/v1/node/delete/' . $rootId);
        $this->assertIsJsonResponse($client);
        $this->assertIsStatusCode($client, 200);
        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));

        //List result
        $client = $this->doGetRequest('/v1/node/list');

        $jsonRequest  = new JsonParser($client->getResponse()->getContent());
        $this->assertEquals('ok', $jsonRequest->getMandatoryParam('status'));
        $items = $jsonRequest->getMandatoryParam('items');
        $this->assertCount(0, $items);
    }
}
