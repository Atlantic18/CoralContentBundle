<?php

/*
 * This file is part of the Coral package.
 *
 * (c) Frantisek Troster <frantisek.troster@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Coral\ContentBundle\Entity;

use Doctrine\ORM\Mapping AS ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="Coral\ContentBundle\Entity\Repository\NodeRepository")
 * @ORM\Table(
 *     name="coral_node", 
 *     indexes={@ORM\Index(name="SlugIndex", columns={"slug"}),@ORM\Index(name="NameIndex", columns={"name"})}
 * )
 */
class Node
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer", nullable=false)
     *
     */
    private $lft;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer", nullable=false)
     *
     */
    private $rgt;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(type="integer", nullable=false)
     *
     */
    private $level;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(type="integer", nullable=true)
     */
    private $root;

    /**
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     *
     * @ORM\OneToMany(targetEntity="Coral\ContentBundle\Entity\Node", mappedBy="parent")
     * @ORM\OrderBy({"lft"="ASC"})
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="Coral\ContentBundle\Entity\NodeAttribute", mappedBy="node")
     */
    private $nodeAttributes;

    /**
     * @ORM\ManyToOne(targetEntity="Coral\CoreBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=false)
     */
    private $account;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Coral\ContentBundle\Entity\Node", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Coral\ContentBundle\Entity\Section", mappedBy="node")
     */
    private $sections;

    /**
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created_at;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->nodeAttributes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->sections = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Node
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return Node
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set lft
     *
     * @param integer $lft
     * @return Node
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft
     *
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return Node
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set level
     *
     * @param integer $level
     * @return Node
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set account
     *
     * @param \Coral\CoreBundle\Entity\Account $account
     * @return Node
     */
    public function setAccount(\Coral\CoreBundle\Entity\Account $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account
     *
     * @return \Coral\CoreBundle\Entity\Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Add sections
     *
     * @param \Coral\ContentBundle\Entity\Section $sections
     * @return Node
     */
    public function addSection(\Coral\ContentBundle\Entity\Section $sections)
    {
        $this->sections[] = $sections;

        return $this;
    }

    /**
     * Remove sections
     *
     * @param \Coral\ContentBundle\Entity\Section $sections
     */
    public function removeSection(\Coral\ContentBundle\Entity\Section $sections)
    {
        $this->sections->removeElement($sections);
    }

    /**
     * Get sections
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * Set root
     *
     * @param integer $root
     * @return Node
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root
     *
     * @return integer
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     * @return Node
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Node
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Add children
     *
     * @param \Coral\ContentBundle\Entity\Node $children
     * @return Node
     */
    public function addChildren(\Coral\ContentBundle\Entity\Node $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Coral\ContentBundle\Entity\Node $children
     */
    public function removeChildren(\Coral\ContentBundle\Entity\Node $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param \Coral\ContentBundle\Entity\Node $parent
     * @return Node
     */
    public function setParent(\Coral\ContentBundle\Entity\Node $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Coral\ContentBundle\Entity\Node
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add nodeAttributes
     *
     * @param \Coral\ContentBundle\Entity\NodeAttribute $nodeAttributes
     * @return Node
     */
    public function addNodeAttribute(\Coral\ContentBundle\Entity\NodeAttribute $nodeAttributes)
    {
        $this->nodeAttributes[] = $nodeAttributes;

        return $this;
    }

    /**
     * Remove nodeAttributes
     *
     * @param \Coral\ContentBundle\Entity\NodeAttribute $nodeAttributes
     */
    public function removeNodeAttribute(\Coral\ContentBundle\Entity\NodeAttribute $nodeAttributes)
    {
        $this->nodeAttributes->removeElement($nodeAttributes);
    }

    /**
     * Get nodeAttributes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNodeAttributes()
    {
        return $this->nodeAttributes;
    }
}