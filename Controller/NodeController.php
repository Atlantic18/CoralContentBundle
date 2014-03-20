<?php

namespace Coral\ContentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\Query;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Coral\CoreBundle\Controller\JsonController;
use Coral\CoreBundle\Utility\JsonParser;
use Coral\CoreBundle\Exception\JsonException;
use Coral\CoreBundle\Exception\AuthenticationException;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

/**
 * @Route("/v1/node")
 */
class NodeController extends JsonController
{
    private function throwIfNotUniqueSlug(\Coral\ContentBundle\Entity\Node $node)
    {
        if($node->getId()) {
            $nodeCount = $this->getDoctrine()->getManager()->createQuery(
                    'SELECT COUNT(n)
                    FROM CoralContentBundle:Node n
                    INNER JOIN n.account a WITH (a.id = :account_id)
                    WHERE n.slug = :slug
                    AND n.id != :node_id'
                )
                ->setParameter('account_id', $this->getAccount()->getId())
                ->setParameter('slug', $node->getSlug())
                ->setParameter('node_id', $node->getId())
                ->getSingleScalarResult();
        }
        else {
            $nodeCount = $this->getDoctrine()->getManager()->createQuery(
                    'SELECT COUNT(n)
                    FROM CoralContentBundle:Node n
                    INNER JOIN n.account a WITH (a.id = :account_id)
                    WHERE n.slug = :slug'
                )
                ->setParameter('account_id', $this->getAccount()->getId())
                ->setParameter('slug', $node->getSlug())
                ->getSingleScalarResult();
        }

        if($nodeCount > 0)
        {
            throw new InvalidTypeException('Unable to create/update node, duplicit slug[' . $nodeCount . ']: ' . $node->getSlug());
        }
    }

    private function getRootNode()
    {
        return $this->getDoctrine()->getManager()->createQuery(
                    'SELECT n, a
                    FROM CoralContentBundle:Node n
                    INNER JOIN n.account a WITH (a.id = :account_id)
                    WHERE n.parent IS NULL'
                )
                ->setParameter('account_id', $this->getAccount()->getId())
                ->getSingleResult();
    }

    private function createNode(JsonParser $request)
    {
        $node = new \Coral\ContentBundle\Entity\Node;
        $node->setName($request->getMandatoryParam('name'));
        $node->setSlug($request->getMandatoryParam('slug'));
        $node->setAccount($this->getAccount());

        return $node;
    }

    private function createNodeAttribute(\Coral\ContentBundle\Entity\Node $node, $key, $value)
    {
        $nodeAttribute = new \Coral\ContentBundle\Entity\NodeAttribute;
        $nodeAttribute->setNode($node);
        $nodeAttribute->setName($key);
        $nodeAttribute->setValue($value);

        return $nodeAttribute;
    }

    /**
     * Returns doctrine query nested set as an array for json
     *
     * @param  DoctrineORMQuery $query Nested set tree query
     * @return array                   Result as array for json
     */
    private function getTreeQueryAsArray(\Doctrine\ORM\Query $query)
    {
        $items = array();
        $index = array();

        $nodes = $query->getResult();
        $count = count($nodes);

        for($i = 0; $i < $count; $i++)
        {
            $node = $nodes[$i];

            $newItem = array(
                'id'     => $node->getId(),
                'name'   => $node->getName(),
                'slug'   => $node->getSlug()
            );
            if($i == 0)
            {
                //for first item - parent of the subtree - add tree_ params
                $newItem = array_merge($this->getParentTreeParams($node), $newItem);
            }
            foreach ($node->getNodeAttributes() as $attribute) {
                $newItem[$attribute->getName()] = $attribute->getValue();
            }

            if(($node->getParent()) && isset($index[$node->getParent()->getId()]))
            {
                $_path = $index[$node->getParent()->getId()];
                eval('$parent = &$items' . $_path . ';');
                if(!isset($parent['items']))
                {
                    $parent['items'] = array();
                }
                //copy tree_ params
                foreach ($parent as $paramKey => $paramValue)
                {
                    if(substr($paramKey, 0, 5) == 'tree_')
                    {
                        $newItem[$paramKey] = $paramValue;
                    }
                }
                $index[$node->getId()] = $_path . '["items"][' . count($parent['items']) . ']';
                $parent['items'][] = $newItem;
            }
            else
            {
                $index[$node->getId()] = '[' . count($items) . ']';
                $items[] = $newItem;
            }
        }

        return $items;
    }

    /**
     * Fetches all parent params and returns as array
     *
     * @param  CoralContentBundleEntityNode $node Child node
     * @return array                              param => value
     */
    private function getParentTreeParams(\Coral\ContentBundle\Entity\Node $node)
    {
        $params = $this->getDoctrine()->getManager()->createQuery(
                'SELECT n.level, na.name, na.value
                FROM CoralContentBundle:NodeAttribute na
                INNER JOIN na.node n WITH (n.lft < :lft AND n.rgt > :rgt)
                INNER JOIN n.account a WITH (a.id = :account_id)
                WHERE
                    na.name LIKE \'tree_%\'
                ORDER BY n.level ASC'
            )
            ->setParameter('account_id', $this->getAccount()->getId())
            ->setParameter('lft', $node->getLft())
            ->setParameter('rgt', $node->getRgt())
            ->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $treeParams = array();

        foreach ($params as $param)
        {
            $treeParams[$param['name']] = $param['value'];
        }

        return $treeParams;
    }

    private function getNodeDetailAsArray(\Coral\ContentBundle\Entity\Node $node)
    {
        $response = array(
            'id'         => $node->getId(),
            'name'       => $node->getName(),
            'slug'       => $node->getSlug(),
            'created_at' => $node->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $node->getUpdatedAt()->format('Y-m-d H:i:s'),
            'sections'   => array()
        );

        $response = array_merge($this->getParentTreeParams($node), $response);

        foreach($node->getNodeAttributes() as $nodeAttribute)
        {
            $response[$nodeAttribute->getName()] = $nodeAttribute->getValue();
        }

        foreach ($node->getSections() as $section) {
            $sectionArray = array(
                'name'  => $section->getName(),
                'items' => array()
            );
            foreach($section->getContents() as $content)
            {
                $sectionArray['items'][] = array(
                    'permid'   => $content->getPermid(),
                    'content'  => $content->getContent(),
                    'renderer' => $content->getRenderer()
                );
            }
            $response['sections'][] = $sectionArray;
        }

        return $response;
    }

    private function getLatestSectionIds(\Coral\ContentBundle\Entity\Node $node)
    {
        return $this->getDoctrine()->getManager()->createQuery(
                'SELECT MAX(s.id)
                FROM CoralContentBundle:Section s
                WHERE s.node = :node_id
                GROUP BY s.name'
            )
            ->setParameter('node_id', $node->getId())
            ->getArrayResult();
    }

    /**
     * @Route("/add")
     * @Method("POST")
     */
    public function addAction()
    {
        $request = new JsonParser($this->get("request")->getContent(), true);

        $node = $this->createNode($request);

        $this->throwIfNotUniqueSlug($node);

        try {
            $node->setParent($this->getRootNode());
        }
        catch (\Doctrine\Orm\NoResultException $e) {
            //it's ok, there is no parent yet
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($node);

        $params = $request->getParams();
        unset($params['name'], $params['slug']);

        foreach ($params as $key => $value) {
            if($value) {
                $em->persist($this->createNodeAttribute($node, $key, $value));
            }
        }

        $em->flush();

        return $this->createCreatedJsonResponse($node->getId());
    }

    /**
     * @Route("/update/{id}")
     * @Method("POST")
     */
    public function updateAction($id)
    {
        $request = new JsonParser($this->get("request")->getContent(), true);

        $node    = $this->getDoctrine()
            ->getRepository('CoralContentBundle:Node')
            ->find($id);

        $this->throwNotFoundExceptionIf(!$node, 'No node found for id ' . $id);
        $this->throwExceptionUnlessEntityForAccount($node);

        $node->setName($request->getMandatoryParam('name'));
        $node->setSlug($request->getMandatoryParam('slug'));

        $this->throwIfNotUniqueSlug($node);

        $em = $this->getDoctrine()->getManager();
        foreach($node->getNodeAttributes() as $nodeAttribute)
        {
            $em->remove($nodeAttribute);
        }

        $params = $request->getParams();
        unset($params['name'], $params['slug']);

        foreach ($params as $key => $value) {
            if($value) {
                $em->persist($this->createNodeAttribute($node, $key, $value));
            }
        }

        $em->flush();

        return $this->createSuccessJsonResponse();
    }

    /**
     * @Route("/move/{nodeIdWhat}/{position}/{nodeIdWhere}")
     * @Method("POST")
     */
    public function moveAction($nodeIdWhat, $position, $nodeIdWhere)
    {
        $repository = $this->getDoctrine()->getRepository('CoralContentBundle:Node');

        $nodeWhat = $repository->find($nodeIdWhat);
        $this->throwNotFoundExceptionIf(!$nodeWhat, 'No node found for id ' . $nodeIdWhat);
        $this->throwExceptionUnlessEntityForAccount($nodeWhat);

        $nodeWhere = $repository->find($nodeIdWhere);
        $this->throwNotFoundExceptionIf(!$nodeWhere, 'No node found for id ' . $nodeIdWhere);
        $this->throwExceptionUnlessEntityForAccount($nodeWhere);

        if($position == 'first-child-of')
        {
            $repository->persistAsFirstChildOf($nodeWhat, $nodeWhere);
        }
        elseif($position == 'before')
        {
            $repository->persistAsPrevSiblingOf($nodeWhat, $nodeWhere);
        }
        elseif($position == 'after')
        {
            $repository->persistAsNextSiblingOf($nodeWhat, $nodeWhere);
        }
        else
        {
            $repository->persistAsLastChildOf($nodeWhat, $nodeWhere);
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->createSuccessJsonResponse();
    }

    /**
     * @Route("/add/{position}/{id}")
     * @Method("POST")
     */
    public function addPositionAction($position, $id)
    {
        $request = new JsonParser($this->get("request")->getContent(), true);

        $repository    = $this->getDoctrine()->getRepository('CoralContentBundle:Node');
        $referenceNode = $repository->find($id);

        $this->throwNotFoundExceptionIf(!$referenceNode, 'No node found for id ' . $id);
        $this->throwExceptionUnlessEntityForAccount($referenceNode);

        $node = $this->createNode($request);

        $this->throwIfNotUniqueSlug($node);

        if($position == 'first-child-of')
        {
            $repository->persistAsFirstChildOf($node, $referenceNode);
        }
        elseif($position == 'before')
        {
            $repository->persistAsPrevSiblingOf($node, $referenceNode);
        }
        elseif($position == 'after')
        {
            $repository->persistAsNextSiblingOf($node, $referenceNode);
        }
        else
        {
            $repository->persistAsLastChildOf($node, $referenceNode);
        }

        $em = $this->getDoctrine()->getManager();

        $params = $request->getParams();
        unset($params['name'], $params['slug']);

        foreach ($params as $key => $value) {
          $em->persist($this->createNodeAttribute($node, $key, $value));
        }

        $em->flush();

        return $this->createCreatedJsonResponse($node->getId());
    }

    /**
     * @Route("/delete/{id}")
     * @Method("DELETE")
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository('CoralContentBundle:Node');
        $node       = $repository->find($id);

        $this->throwNotFoundExceptionIf(!$node, 'No node found for id ' . $id);
        $this->throwExceptionUnlessEntityForAccount($node);

        $em->remove($node);
        $this->getDoctrine()->getManager()->flush();

        return $this->createSuccessJsonResponse();
    }

    /**
     * @Route("/list")
     * @Method("GET")
     */
    public function listAction()
    {
        $query = $this->getDoctrine()->getManager()->createQuery(
                'SELECT n, p, a, na
                FROM CoralContentBundle:Node n
                LEFT JOIN n.nodeAttributes na
                LEFT JOIN n.parent p
                INNER JOIN n.account a WITH (a.id = :account_id)
                ORDER BY n.root ASC, n.lft ASC'
            )
            ->setParameter('account_id', $this->getAccount()->getId());

        return $this->createListJsonResponse($this->getTreeQueryAsArray($query));
    }

    /**
     * @Route("/list/{id}")
     * @Method("GET")
     */
    public function listSubtreeAction($id)
    {
        $repository = $this->getDoctrine()->getRepository('CoralContentBundle:Node');
        $node       = $repository->find($id);

        $this->throwNotFoundExceptionIf(!$node, 'No node found for id ' . $id);
        $this->throwExceptionUnlessEntityForAccount($node);

        $query = $this->getDoctrine()->getManager()->createQuery(
                'SELECT n, na, p, a
                FROM CoralContentBundle:Node n
                LEFT JOIN n.nodeAttributes na
                LEFT JOIN n.parent p
                INNER JOIN n.account a WITH (a.id = :account_id)
                WHERE
                    n.lft >= :lft
                AND
                    n.rgt <= :rgt
                ORDER BY n.root ASC, n.lft ASC'
            )
            ->setParameter('account_id', $this->getAccount()->getId())
            ->setParameter('lft', $node->getLft())
            ->setParameter('rgt', $node->getRgt());

        return $this->createListJsonResponse($this->getTreeQueryAsArray($query));
    }

    /**
     * @Route("/info/{id}")
     * @Method("GET")
     */
    public function infoAction($id)
    {
        $repository = $this->getDoctrine()->getRepository('CoralContentBundle:Node');
        $node       = $repository->find($id);

        $this->throwNotFoundExceptionIf(!$node, 'No node found for id ' . $id);
        $this->throwExceptionUnlessEntityForAccount($node);

        $response = array(
            'id'   => $node->getId(),
            'name' => $node->getName(),
            'slug' => $node->getSlug()
        );
        foreach($node->getNodeAttributes() as $nodeAttribute)
        {
            $response[$nodeAttribute->getName()] = $nodeAttribute->getValue();
        }

        return $this->createSuccessJsonResponse($response);
    }

    /**
     * @Route("/detail/published/{slug}")
     * @Method("GET")
     */
    public function detailPublishedAction($slug)
    {
        $repository = $this->getDoctrine()->getRepository('CoralContentBundle:Node');

        try {
            $node = $this->getDoctrine()->getManager()->createQuery(
                    'SELECT n, s, c, na, a
                    FROM CoralContentBundle:Node n
                    LEFT JOIN n.nodeAttributes na
                    LEFT JOIN n.sections s WITH (s.published = 1)
                    LEFT JOIN s.contents c
                    INNER JOIN n.account a
                    WHERE n.slug = :slug
                    ORDER BY s.name, c.sortorder'
                )
                ->setParameter('slug', $slug)
                ->getSingleResult();
        }
        catch (\Doctrine\Orm\NoResultException $e) {
            throw new NotFoundHttpException('No node found for slug ' . $slug);
        }
        $this->throwExceptionUnlessEntityForAccount($node);

        return $this->createSuccessJsonResponse($this->getNodeDetailAsArray($node));
    }

    /**
     * @Route("/detail/latest/{slug}")
     * @Method("GET")
     */
    public function detailLatestAction($slug)
    {
        $repository = $this->getDoctrine()->getRepository('CoralContentBundle:Node');
        $node       = $repository->findOneBySlug($slug);

        $this->throwNotFoundExceptionIf(!$node, 'No node found for slug ' . $slug);
        $this->throwExceptionUnlessEntityForAccount($node);

        $nodeAll = $this->getDoctrine()->getManager()->createQuery(
                'SELECT n, s, c, na, a
                FROM CoralContentBundle:Node n
                LEFT JOIN n.nodeAttributes na
                LEFT JOIN n.sections s WITH (s.id IN (:section_ids))
                LEFT JOIN s.contents c
                INNER JOIN n.account a
                WHERE n.slug = :slug
                ORDER BY s.id DESC, s.name, c.sortorder'
            )
            ->setParameter('slug', $slug)
            ->setParameter('section_ids', $this->getLatestSectionIds($node))
            ->getSingleResult();

        return $this->createSuccessJsonResponse($this->getNodeDetailAsArray($nodeAll));
    }
}
