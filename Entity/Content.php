<?php
namespace Coral\ContentBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="coral_content")
 */
class Content
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=9999, nullable=false)
     */
    private $content;

    /**
     * @ORM\Column(type="string", length=32, nullable=false)
     */
    private $renderer;

    /**
     * @ORM\Column(type="string", length=40, nullable=false)
     */
    private $hash;

    /**
     * @ORM\Column(type="string", length=64, nullable=false)
     */
    private $permid;

    /**
     *
     *
     */
    private $Section;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $sortorder;

    /**
     * @ORM\ManyToOne(targetEntity="Coral\ContentBundle\Entity\Section", inversedBy="contents")
     * @ORM\JoinColumn(name="section_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $section;

    /**
     *
     *
     */
    private $Sections;

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
     * Set content
     *
     * @param string $content
     * @return Content
     */
    public function setContent($content)
    {
        $this->content = $content;

        $this->setHash(sha1($content));

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set renderer
     *
     * @param string $renderer
     * @return Content
     */
    public function setRenderer($renderer)
    {
        $this->renderer = $renderer;

        return $this;
    }

    /**
     * Get renderer
     *
     * @return string
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * Set hash
     *
     * @param string $hash
     * @return Content
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set permid
     *
     * @param string $permid
     * @return Content
     */
    public function setPermid($permid)
    {
        $this->permid = $permid;

        return $this;
    }

    /**
     * Generates relatively random permid for the account
     *
     * @return Content content instance
     */
    public function generatePermid()
    {
        if($this->permid)
        {
            return $this;
        }

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //random string always begins with a letter
        $randomString = $characters[rand(10, strlen($characters) - 1)];
        for ($i = 1; $i < 24; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        $this->permid = $randomString;

        return $this;
    }

    /**
     * Get permid
     *
     * @return string
     */
    public function getPermid()
    {
        return $this->permid;
    }

    /**
     * Set sortorder
     *
     * @param integer $sortorder
     * @return Content
     */
    public function setSortorder($sortorder)
    {
        $this->sortorder = $sortorder;

        return $this;
    }

    /**
     * Get sortorder
     *
     * @return integer
     */
    public function getSortorder()
    {
        return $this->sortorder;
    }

    /**
     * Set section
     *
     * @param \Coral\ContentBundle\Entity\Section $section
     * @return Content
     */
    public function setSection(\Coral\ContentBundle\Entity\Section $section)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * Get section
     *
     * @return \Coral\ContentBundle\Entity\Section
     */
    public function getSection()
    {
        return $this->section;
    }
}