<?php

namespace TVF\RecordBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Creation
 *
 * @ORM\Table(name="creation")
 * @ORM\Entity(repositoryClass="TVF\RecordBundle\Repository\CreationRepository")
 */
class Creation
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->types = new \Doctrine\Common\Collections\ArrayCollection();
        $this->images = new \Doctrine\Common\Collections\ArrayCollection();
        $this->sizes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->onsold = false;
    }
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="title1", type="string", length=255)
     */
    private $title1;

    /**
     * @var string
     *
     * @ORM\Column(name="text1", type="text")
     */
    private $text1;

    /**
     * @var string
     *
     * @ORM\Column(name="title2", type="string", length=255, nullable=true)
     */
    private $title2;

    /**
     * @var string
     *
     * @ORM\Column(name="text2", type="text", nullable=true)
     */
    private $text2;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float", nullable=true)
     */
    private $price;

    /**
     * @var bool
     *
     * @ORM\Column(name="onsold", type="boolean")
     */
    private $onsold;

    /**
     * @ORM\ManyToMany(targetEntity="TVF\AdminBundle\Entity\Type", cascade={"persist"})
     */
    private $types;

    /**
     * @var array
     *
     * @ORM\Column(name="images", type="array")
     */
    private $images;

    /**
     * @ORM\ManyToOne(targetEntity="TVF\RecordBundle\Entity\Collection")
     * @ORM\JoinColumn(nullable=false)
     */
    private $collection;

    /**
     * @ORM\ManyToOne(targetEntity="TVF\AdminBundle\Entity\Category")
     * @ORM\JoinColumn(nullable=false, columnDefinition="INT NOT NULL DEFAULT 1")
     */
    private $category;

    /**
     * Collection of Attributes
     * @ORM\ManyToMany(targetEntity="TVF\RecordBundle\Entity\Attribute", cascade={"persist"})
     */
    private $attributes;

    /**
     * @ORM\ManyToMany(targetEntity="TVF\UserBundle\Entity\User", cascade={"persist"})
     */
    private $lovers;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Creation
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
     * Set title1
     *
     * @param string $title1
     *
     * @return Creation
     */
    public function setTitle1($title1)
    {
        $this->title1 = $title1;

        return $this;
    }

    /**
     * Get title1
     *
     * @return string
     */
    public function getTitle1()
    {
        return $this->title1;
    }

    /**
     * Set text1
     *
     * @param string $text1
     *
     * @return Creation
     */
    public function setText1($text1)
    {
        $this->text1 = $text1;

        return $this;
    }

    /**
     * Get text1
     *
     * @return string
     */
    public function getText1()
    {
        return $this->text1;
    }

    /**
     * Set title2
     *
     * @param string $title2
     *
     * @return Creation
     */
    public function setTitle2($title2)
    {
        $this->title2 = $title2;

        return $this;
    }

    /**
     * Get title2
     *
     * @return string
     */
    public function getTitle2()
    {
        return $this->title2;
    }

    /**
     * Set text2
     *
     * @param string $text2
     *
     * @return Creation
     */
    public function setText2($text2)
    {
        $this->text2 = $text2;

        return $this;
    }

    /**
     * Get text2
     *
     * @return string
     */
    public function getText2()
    {
        return $this->text2;
    }

    /**
     * Set price
     *
     * @param float $price
     *
     * @return Creation
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set onsold
     *
     * @param boolean $onsold
     *
     * @return Creation
     */
    public function setOnsold($onsold)
    {
        $this->onsold = $onsold;

        return $this;
    }

    /**
     * Get onsold
     *
     * @return bool
     */
    public function getOnsold()
    {
        return $this->onsold;
    }

    /**
     * Add type
     *
     * @param \TVF\AdminBundle\Entity\Type $type
     *
     * @return Creation
     */
    public function addType(\TVF\AdminBundle\Entity\Type $type)
    {
        $this->types[] = $type;

        return $this;
    }

    /**
     * Remove type
     *
     * @param \TVF\AdminBundle\Entity\Type $type
     */
    public function removeType(\TVF\AdminBundle\Entity\Type $type)
    {
        $this->types->removeElement($type);
    }

    /**
     * Get types
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTypes()
    {
        return $this->types;
    }

    public function emptyTypes()
    {
        $this->types = [];
    }

    /**
     * Add image
     *
     * @return Creation
     */
    public function addImage($image)
    {
        $this->images[] = $image;

        return $this;
    }

    /**
     * Remove image
     *
     */
    public function removeImage($image)
    {
        $this->images->removeElement($image);
    }

    /**
     * Get images
     *
     * @return array
     */
    public function getImages()
    {
        return $this->images;
    }

    public function emptyImages()
    {
        $this->images = [];
    }

    /**
     * Set collection
     *
     * @param \TVF\RecordBundle\Entity\Collection $collection
     *
     * @return Comment
     */
    public function setCollection(\TVF\RecordBundle\Entity\Collection $collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Get collection
     *
     * @return \TVF\RecordBundle\Entity\Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Set images
     *
     * @param array $images
     *
     * @return Creation
     */
    public function setImages($images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * Set category
     *
     * @param \TVF\AdminBundle\Entity\Category $category
     *
     * @return Creation
     */
    public function setCategory(\TVF\AdminBundle\Entity\Category $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \TVF\AdminBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    public function emptyAttributes()
    {
        $this->attributes = [];
    }

    /**
     * Add attribute
     *
     * @param \TVF\RecordBundle\Entity\Attribute $attribute
     *
     * @return Creation
     */
    public function addAttribute(\TVF\RecordBundle\Entity\Attribute $attribute)
    {
        $this->attributes[] = $attribute;

        return $this;
    }

    /**
     * Remove attribute
     *
     * @param \TVF\RecordBundle\Entity\Attribute $attribute
     */
    public function removeAttribute(\TVF\RecordBundle\Entity\Attribute $attribute)
    {
        $this->attributes->removeElement($attribute);
    }

    /**
     * Get attributes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Add lover
     *
     * @param \TVF\UserBundle\Entity\User $user
     *
     * @return Creation
     */
    public function addLover(\TVF\UserBundle\Entity\User $user)
    {
        $this->lovers[] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param \TVF\UserBundle\Entity\User $user
     */
    public function removeLover(\TVF\UserBundle\Entity\User $user)
    {
        $this->lovers->removeElement($user);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLovers()
    {
        return $this->lovers;
    }

    public function emptyLovers()
    {
        $this->lovers = [];
    }
}
