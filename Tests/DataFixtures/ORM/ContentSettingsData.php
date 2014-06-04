<?php

/*
 * This file is part of the Coral package.
 *
 * (c) Frantisek Troster <frantisek.troster@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Coral\ContentBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Coral\CoreBundle\Entity\Event;
use Coral\CoreBundle\Entity\Account;
use Coral\CoreBundle\Entity\Client;
use Coral\ContentBundle\Entity\Node;
use Coral\ContentBundle\Entity\NodeAttribute;

class ContentSettingsData implements FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $event = new Event();
        $event->setName('add_content');

        $account = new Account();
        $account->setName('test_account');

        $client = new Client();
        $client->setToken('super_secure_shared_password');
        $client->setDescription('functional test accesss');
        $client->setAccount($account);

        $account2 = new Account();
        $account2->setName('test_account2');

        $client2 = new Client();
        $client2->setToken('super_secure_shared_password2');
        $client2->setDescription('functional test accesss (2)');
        $client2->setAccount($account2);

        $node1 = new Node();
        $node1->setName('Homepage');
        $node1->setSlug('homepage');
        $node1->setAccount($account);

        $subParentNode = new Node();
        $subParentNode->setName('SubParent');
        $subParentNode->setSlug('sub-parent');
        $subParentNode->setAccount($account);

        $nodeTreeParam = new NodeAttribute;
        $nodeTreeParam->setName('tree_param');
        $nodeTreeParam->setValue('oldvalue');
        $nodeTreeParam->setNode($node1);

        $nodeTreeParam2 = new NodeAttribute;
        $nodeTreeParam2->setName('tree_param');
        $nodeTreeParam2->setValue('treevalue');
        $nodeTreeParam2->setNode($subParentNode);

        $node2 = new Node();
        $node2->setName('First child');
        $node2->setSlug('first-child');
        $node2->setParent($subParentNode);
        $node2->setAccount($account);

        $repository = $manager->getRepository('CoralContentBundle:Node');

        $manager->persist($event);
        $manager->persist($account);
        $manager->persist($client);
        $manager->persist($account2);
        $manager->persist($client2);
        $manager->persist($node1);
        $manager->persist($nodeTreeParam);
        $manager->persist($nodeTreeParam2);

        $repository->persistAsLastChildOf($subParentNode, $node1);
        $repository->persistAsLastChildOf($node2, $subParentNode);
        $manager->flush();
    }
}