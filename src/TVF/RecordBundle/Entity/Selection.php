<?php

namespace TVF\RecordBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Selection
 *
 * @ORM\Table(name="selection")
 * @ORM\Entity(repositoryClass="TVF\RecordBundle\Repository\SelectionRepository")
 */
class Selection
{
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
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255, options={"default":"undefined"})
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\File(mimeTypes={ "image/*" })
     */
    private $image;

    /**
     * Collection of Vinyls
     * @ORM\ManyToMany(targetEntity="TVF\RecordBundle\Entity\Vinyl", cascade={"persist"})
     */
    private $vinyls;

    /**
     * @ORM\ManyToOne(targetEntity="TVF\RecordBundle\Entity\Client")
     * @ORM\JoinColumn(nullable=false)
     */
    private $client;


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
     * Set title
     *
     * @param string $title
     *
     * @return Selection
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return Selection
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
     * Set description
     *
     * @param string $description
     *
     * @return Selection
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
     * Set image
     *
     * @param string $image
     *
     * @return Selection
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->vinyls = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add vinyl
     *
     * @param \TVF\RecordBundle\Entity\Vinyl $vinyl
     *
     * @return Selection
     */
    public function addVinyl(\TVF\RecordBundle\Entity\Vinyl $vinyl)
    {
        $this->vinyls[] = $vinyl;

        return $this;
    }

    /**
     * Remove vinyl
     *
     * @param \TVF\RecordBundle\Entity\Vinyl $vinyl
     */
    public function removeVinyl(\TVF\RecordBundle\Entity\Vinyl $vinyl)
    {
        $this->vinyls->removeElement($vinyl);
    }

    /**
     * Get vinyls
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVinyls()
    {
        return $this->vinyls;
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
}
