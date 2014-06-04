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
 * @ORM\Entity
 * @ORM\Table(name="coral_section")
 */
class Section
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32, nullable=false)
     */
    private $name;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $published;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $autosave;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created_at;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\ManyToOne(targetEntity="Coral\ContentBundle\Entity\Node", inversedBy="sections")
     * @ORM\JoinColumn(name="node_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $node;

    /**
     * @ORM\OneToMany(targetEntity="Coral\ContentBundle\Entity\Content", mappedBy="section")
     * @ORM\OrderBy({"sortorder"="ASC"})
     */
    private $contents;

    /**
     *
     *
     */
    private $Node;

    /**
     *
     *
     */
    private $Contents;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->contents = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Section
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
     * Set published
     *
     * @param boolean $published
     * @return Section
     */
    public function setPublished($published)
    {
        $this->published = $published;

        return $this;
    }

    /**
     * Get published
     *
     * @return boolean
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * Set autosave
     *
     * @param boolean $autosave
     * @return Section
     */
    public function setAutosave($autosave)
    {
        $this->autosave = $autosave;

        return $this;
    }

    /**
     * Get autosave
     *
     * @return boolean
     */
    public function getAutosave()
    {
        return $this->autosave;
    }

    /**
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     * @return Section
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
     * Set node
     *
     * @param \Coral\ContentBundle\Entity\Node $node
     * @return Section
     */
    public function setNode(\Coral\ContentBundle\Entity\Node $node)
    {
        $this->node = $node;

        return $this;
    }

    /**
     * Get node
     *
     * @return \Coral\ContentBundle\Entity\Node
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * Add contents
     *
     * @param \Coral\ContentBundle\Entity\Content $contents
     * @return Section
     */
    public function addContent(\Coral\ContentBundle\Entity\Content $contents)
    {
        $this->contents[] = $contents;

        return $this;
    }

    /**
     * Remove contents
     *
     * @param \Coral\ContentBundle\Entity\Content $contents
     */
    public function removeContent(\Coral\ContentBundle\Entity\Content $contents)
    {
        $this->contents->removeElement($contents);
    }

    /**
     * Get contents
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Section
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
     * Copies all content from other section to this one (appends it)
     *
     * @param  Section $section other section to copy from
     * @return void
     */
    public function appendContentFromSection(Section $section)
    {
        $sortorder = $this->getContents()->count();

        foreach ($section->getContents() as $content)
        {
            $newContent = new \Coral\ContentBundle\Entity\Content;
            $newContent->setContent($content->getContent());
            $newContent->setRenderer($content->getRenderer());
            $newContent->setPermid($content->getPermid());
            $newContent->setSortorder(++$sortorder);
            $newContent->setSection($this);

            $this->addContent($newContent);
        }
    }

    /**
     * Add new content by text and renderer
     *
     * @param string $text     text content
     * @param string $renderer renderer
     * @return Content         created content
     */
    public function addNewContent($text, $renderer)
    {
        $sortorder = $this->getContents()->count();

        $newContent = new Content;
        $newContent->setContent($text);
        $newContent->setRenderer($renderer);
        $newContent->generatePermid();
        $newContent->setSortorder(++$sortorder);

        $newContent->setSection($this);
        $this->addContent($newContent);

        return $newContent;
    }
}