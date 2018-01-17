<?php

namespace TVF\RecordBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Vinyl
 *
 * @ORM\Table(name="vinyl")
 * @ORM\Entity(repositoryClass="TVF\RecordBundle\Repository\VinylRepository")
 */
class Vinyl
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->artists = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

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
     * @ORM\OneToMany(targetEntity="TVF\StoreBundle\Entity\VinylUser", mappedBy="vinyl")
     */
    private $users;

    /**
     * @ORM\ManyToOne(targetEntity="TVF\RecordBundle\Entity\Client")
     * @ORM\JoinColumn(nullable=false)
     */
    private $client;

    /**
     * @ORM\ManyToMany(targetEntity="TVF\RecordBundle\Entity\Artist", cascade={"persist"})
     */
    private $artists;

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
     * @return Vinyl
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
     * Set price
     *
     * @param float $price
     *
     * @return Vinyl
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
     * @return Vinyl
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
     * @return Vinyl
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
     * @return Vinyl
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
     * Set images
     *
     * @param array $images
     *
     * @return Vinyl
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
     * @return Vinyl
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
     * @return Vinyl
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
     * Add user
     *
     * @param \TVF\StoreBundle\Entity\VinylUser $user
     *
     * @return Vinyl
     */
    public function addUser(\TVF\StoreBundle\Entity\VinylUser $user)
    {
        $this->users[] = $user;

        $user->setVinyl($this);

        return $this;
    }

    /**
     * Remove user
     *
     * @param \TVF\StoreBundle\Entity\VinylUser $user
     */
    public function removeUser(\TVF\StoreBundle\Entity\VinylUser $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Vinyl
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set client
     *
     * @param \TVF\RecordBundle\Entity\Client $client
     *
     * @return Vinyl
     */
    public function setClient(\TVF\RecordBundle\Entity\Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return \TVF\RecordBundle\Entity\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Add artist
     *
     * @param \TVF\RecordBundle\Entity\Artist $artist
     *
     * @return Vinyl
     */
    public function addArtist(\TVF\RecordBundle\Entity\Artist $artist)
    {
        $this->artists[] = $artist;

        return $this;
    }

    /**
     * Remove artist
     *
     * @param \TVF\RecordBundle\Entity\Artist $artist
     */
    public function removeArtist(\TVF\RecordBundle\Entity\Artist $artist)
    {
        $this->artists->removeElement($artist);
    }

    /**
     * Get artists
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getArtists()
    {
        return $this->artists;
    }

    public function emptyArtists()
    {
        $this->artists = [];
    }
}
