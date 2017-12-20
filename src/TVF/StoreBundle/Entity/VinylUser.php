<?php

namespace TVF\StoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * VinylUser
 *
 * @ORM\Table(name="vinyl_user")
 * @ORM\Entity(repositoryClass="TVF\StoreBundle\Repository\VinylUserRepository")
 */
class VinylUser
{
    public function __construct()
    {
        $this->lover = false;
        $this->nbViews = 0;
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
     * @var int
     *
     * @ORM\Column(name="nb_views", type="integer", options={"default" : 0})
     */
    private $nbViews;

    /**
     * @var bool
     *
     * @ORM\Column(name="lover", type="boolean")
     */
    private $lover;

    /**
     * @ORM\ManyToOne(targetEntity="\TVF\UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="\TVF\RecordBundle\Entity\Vinyl", inversedBy="users")
     * @ORM\JoinColumn(nullable=false)
     */
    private $vinyl;


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
     * Set nbViews
     *
     * @param integer $nbViews
     *
     * @return VinylUser
     */
    public function setNbViews($nbViews)
    {
        $this->nbViews = $nbViews;

        return $this;
    }

    /**
     * Get nbViews
     *
     * @return int
     */
    public function getNbViews()
    {
        return $this->nbViews;
    }

    /**
     * Set lover
     *
     * @param boolean $lover
     *
     * @return VinylUser
     */
    public function setLover($lover)
    {
        $this->lover = $lover;

        return $this;
    }

    /**
     * Get lover
     *
     * @return boolean
     */
    public function getLover()
    {
        return $this->lover;
    }

    /**
     * Set user
     *
     * @param \TVF\UserBundle\Entity\User $user
     *
     * @return VinylUser
     */
    public function setUser(\TVF\UserBundle\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \TVF\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set vinyl
     *
     * @param \TVF\RecordBundle\Entity\Vinyl $vinyl
     *
     * @return VinylUser
     */
    public function setVinyl(\TVF\RecordBundle\Entity\Vinyl $vinyl)
    {
        $this->vinyl = $vinyl;

        return $this;
    }

    /**
     * Get vinyl
     *
     * @return \TVF\RecordBundle\Entity\Vinyl
     */
    public function getVinyl()
    {
        return $this->vinyl;
    }
}
