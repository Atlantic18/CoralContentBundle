<?php

/*
 * This file is part of the Coral package.
 *
 * (c) Frantisek Troster <frantisek.troster@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Coral\ContentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Coral\CoreBundle\Controller\JsonController;
use Coral\CoreBundle\Utility\JsonParser;
use Coral\CoreBundle\Exception\JsonException;
use Coral\CoreBundle\Exception\AuthenticationException;

use Coral\ContentBundle\Entity\Section;
use Coral\ContentBundle\Entity\Content;
use Coral\ContentBundle\Entity\Node;

/**
 * @Route("/v1/content")
 */
class ContentController extends JsonController
{
    private function getNodeByPermidAndValidate($permid)
    {
        $node = $this->getDoctrine()->getManager()->createQuery(
                'SELECT n, s
                FROM CoralContentBundle:Node n
                INNER JOIN n.sections s
                INNER JOIN s.contents c WITH (c.permid = :permid)'
            )
            ->setParameter('permid', $permid)
            ->getSingleResult();
        $this->throwNotFoundExceptionIf(!$node, 'No node found for permid ' . $permid);
        $this->throwExceptionUnlessEntityForAccount($node);

        return $node;
    }

    private function getLatestSectionByName(Node $node, $name)
    {
        $section = $this->getDoctrine()
            ->getRepository('CoralContentBundle:Section')
            ->findOneBy(
                array('node' => $node->getId(), 'name' => $name),
                array('id' => 'DESC')
            );

        $this->throwNotFoundExceptionIf(!$section, 'No section found by name ' . $name);

        return $section;
    }

    private function createNewSectionVersion(Node $node, $name)
    {
        $newSection = new Section;
        $newSection->setName($name);
        $newSection->setNode($node);
        $newSection->setPublished(false);
        $newSection->setAutosave(true);

        return $newSection;
    }

    /**
     * @Route("/add/{id}/{sectionName}")
     * @Method("POST")
     */
    public function addAction($id, $sectionName)
    {
        $request = new JsonParser($this->get("request")->getContent(), true);

        $node = $this->getDoctrine()
            ->getRepository('CoralContentBundle:Node')
            ->find($id);
        $this->throwNotFoundExceptionIf(!$node, 'No node found for id ' . $id);
        $this->throwExceptionUnlessEntityForAccount($node);

        $newSection  = $this->createNewSectionVersion($node, $sectionName);
        try {
            $newSection->appendContentFromSection(
                $this->getLatestSectionByName($node, $sectionName)
            );
        }
        catch(NotFoundHttpException $e)
        {
            //previous section not exist
        }

        $newContent = $newSection
            ->addNewContent($request->getMandatoryParam('content'), $request->getMandatoryParam('renderer'));

        $em = $this->getDoctrine()->getManager();
        $em->persist($newSection);
        foreach ($newSection->getContents() as $content) {
            $em->persist($content);
        }
        $em->flush();

        return new JsonResponse(array(
            'status'  => 'ok',
            'permid'  => $newContent->getPermid()
        ), 201);
    }

    /**
     * @Route("/add/{id}/after/{permid}")
     * @Method("POST")
     */
    public function addPositionAction($id, $permid)
    {
        $request = new JsonParser($this->get("request")->getContent(), true);

        $node = $this->getNodeByPermidAndValidate($permid);
        $this->throwNotFoundExceptionIf($node->getId() != $id, 'No node found for id/permid ' . $id . '/' . $permid);

        $sectionName = $node->getSections()->first()->getName();

        $newSection  = $this->createNewSectionVersion($node, $sectionName);
        try {
            $newSection->appendContentFromSection(
                $this->getLatestSectionByName($node, $sectionName)
            );
        }
        catch(NotFoundHttpException $e)
        {
            //previous section not exist
        }

        $newContent = $newSection
            ->addNewContent($request->getMandatoryParam('content'), $request->getMandatoryParam('renderer'));

        $em = $this->getDoctrine()->getManager();
        $em->persist($newSection);

        $sortorder = 1;
        foreach ($newSection->getContents() as $content) {
            if($content !== $newContent)
            {
                $content->setSortorder($sortorder++);
                if($content->getPermid() == $permid)
                {
                    $newContent->setSortorder($sortorder++);
                }
            }

            $em->persist($content);
        }

        $em->flush();

        return new JsonResponse(array(
            'status'  => 'ok',
            'permid'  => $newContent->getPermid()
        ), 201);
    }

    /**
     * @Route("/update/{permid}")
     * @Method("POST")
     */
    public function updateAction($permid)
    {
        $request     = new JsonParser($this->get("request")->getContent(), true);
        $node        = $this->getNodeByPermidAndValidate($permid);
        $sectionName = $node->getSections()->first()->getName();

        $newSection  = $this->createNewSectionVersion($node, $node->getSections()->first()->getName());
        $newSection->appendContentFromSection(
            $this->getLatestSectionByName($node, $sectionName)
        );

        $em = $this->getDoctrine()->getManager();
        $em->persist($newSection);

        $sortorder = 1;
        foreach ($newSection->getContents() as $content) {
            if($content->getPermid() == $permid)
            {
                $content->setContent($request->getMandatoryParam('content'));
                $content->setRenderer($request->getMandatoryParam('renderer'));
            }

            $em->persist($content);
        }

        $em->flush();

        return $this->createSuccessJsonResponse();
    }

    /**
     * @Route("/move-up/{permid}")
     * @Method("POST")
     */
    public function moveUpAction($permid)
    {
        $node = $this->getNodeByPermidAndValidate($permid);
        $sectionName = $node->getSections()->first()->getName();

        $newSection = $this->createNewSectionVersion($node, $node->getSections()->first()->getName());
        $newSection->appendContentFromSection(
            $this->getLatestSectionByName($node, $sectionName)
        );

        $em = $this->getDoctrine()->getManager();
        $em->persist($newSection);

        $prevContent = null;
        foreach ($newSection->getContents() as $content) {
            if(($content->getPermid() == $permid) && (null !== $prevContent))
            {
                $sortorder = $content->getSortorder();
                $content->setSortorder($sortorder - 1);
                $prevContent->setSortorder($sortorder);
            }
            $prevContent = $content;

            $em->persist($content);
        }

        $em->flush();

        return $this->createSuccessJsonResponse();
    }

    /**
     * @Route("/move-down/{permid}")
     * @Method("POST")
     */
    public function moveDownAction($permid)
    {
        $node = $this->getNodeByPermidAndValidate($permid);
        $sectionName = $node->getSections()->first()->getName();

        $newSection = $this->createNewSectionVersion($node, $node->getSections()->first()->getName());
        $newSection->appendContentFromSection(
            $this->getLatestSectionByName($node, $sectionName)
        );

        $em = $this->getDoctrine()->getManager();
        $em->persist($newSection);

        $prevContent = null;
        foreach ($newSection->getContents() as $content) {
            if((null !== $prevContent) && ($prevContent->getPermid() == $permid))
            {
                $sortorder = $prevContent->getSortorder();
                $content->setSortorder($sortorder);
                $prevContent->setSortorder($sortorder + 1);
            }
            $prevContent = $content;

            $em->persist($content);
        }

        $em->flush();

        return $this->createSuccessJsonResponse();
    }

    /**
     * @Route("/delete/{permid}")
     * @Method("DELETE")
     */
    public function deleteAction($permid)
    {
        $node = $this->getNodeByPermidAndValidate($permid);
        $sectionName = $node->getSections()->first()->getName();

        $newSection = $this->createNewSectionVersion($node, $node->getSections()->first()->getName());
        $newSection->appendContentFromSection(
            $this->getLatestSectionByName($node, $sectionName)
        );

        $em = $this->getDoctrine()->getManager();
        $em->persist($newSection);

        $sortorder = 1;
        foreach ($newSection->getContents() as $content) {
            if($content->getPermid() != $permid)
            {
                $content->setSortorder($sortorder++);
                $em->persist($content);
            }
            else
            {
                $newSection->removeContent($content);
            }
        }

        $em->flush();

        return $this->createSuccessJsonResponse();
    }

    /**
     * @Route("/publish/{id}")
     * @Method("POST")
     */
    public function publishAction($id)
    {
        $node = $this->getDoctrine()
            ->getRepository('CoralContentBundle:Node')
            ->find($id);
        $this->throwNotFoundExceptionIf(!$node, 'No node found for id ' . $id);
        $this->throwExceptionUnlessEntityForAccount($node);

        $this->getDoctrine()->getManager()->getConnection()->beginTransaction();

        try {
            //Set previously published nodes to unpublished
            $this->getDoctrine()->getManager()->createQuery(
                    'UPDATE
                        CoralContentBundle:Section s
                    SET
                        s.published = 0
                    WHERE
                        s.node = :node_id
                ')
                ->setParameter('node_id', $node->getId())
                ->execute();

            $latestVersionIds = array();
            $rawLatestVersionIds = $this->getDoctrine()->getManager()->createQuery(
                    'SELECT MAX(s2.id) AS id
                    FROM CoralContentBundle:Section s2
                    WHERE s2.node = :node_id
                    GROUP BY s2.name'
                )
                ->setParameter('node_id', $node->getId())
                ->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
            foreach ($rawLatestVersionIds as $rawLatestVersionId) {
                $latestVersionIds[] = $rawLatestVersionId['id'];
            }

            //publish latest version
            $this->getDoctrine()->getManager()->createQuery(
                    'UPDATE
                        CoralContentBundle:Section s
                    SET
                        s.published = 1, s.autosave = 0
                    WHERE
                        s.node = :node_id
                    AND
                        s.id IN (:latest_ids)
                ')
                ->setParameter('node_id', $node->getId())
                ->setParameter('latest_ids', $latestVersionIds)
                ->execute();

            //delete autosaves
            $this->getDoctrine()->getManager()
                ->createQuery('DELETE FROM  CoralContentBundle:Section s WHERE s.node = :node_id AND s.autosave = 1')
                ->setParameter('node_id', $node->getId())
                ->execute();

            $this->getDoctrine()->getManager()->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getDoctrine()->getManager()->getConnection()->rollback();
            $this->getDoctrine()->getManager()->close();
            throw $e;
        }



        return $this->createSuccessJsonResponse();
    }
}
