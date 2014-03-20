<?php
namespace Coral\ContentBundle\Entity;
use Doctrine\ORM\Mapping AS ORM;

/** 
 * @ORM\Entity
 * @ORM\Table(
 *     name="coral_node_attribute", 
 *     indexes={
 *         @ORM\Index(name="NodeAttributeNameValueIndex", columns={"name","value"}),
 *         @ORM\Index(name="NodeAttributeNameIndex", columns={"name"})
 *     }
 * )
 */
class NodeAttribute
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
     * @ORM\Column(type="string", length=128, nullable=false)
     */
    private $value;

    /** 
     * 
     */
    private $cascades;

    /** 
     * @ORM\ManyToOne(targetEntity="Coral\ContentBundle\Entity\Node", inversedBy="nodeAttributes")
     * @ORM\JoinColumn(name="node_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $node;

    /** 
     * 
     * 
     */
    private $sitemap;

    /** 
     * 
     * 
     */
    private $Sitemap;

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
     * @return NodeAttribute
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
     * Set value
     *
     * @param string $value
     * @return NodeAttribute
     */
    public function setValue($value)
    {
        $this->value = $value;
    
        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set sitemap
     *
     * @param \Coral\ContentBundle\Entity\Node $sitemap
     * @return NodeAttribute
     */
    public function setSitemap(\Coral\ContentBundle\Entity\Node $sitemap)
    {
        $this->sitemap = $sitemap;
    
        return $this;
    }

    /**
     * Get sitemap
     *
     * @return \Coral\ContentBundle\Entity\Node 
     */
    public function getSitemap()
    {
        return $this->sitemap;
    }

    /**
     * Set node
     *
     * @param \Coral\ContentBundle\Entity\Node $node
     * @return NodeAttribute
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
}