<?php

namespace TVF\StoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GenderUser
 *
 * @ORM\Table(name="gender_user")
 * @ORM\Entity(repositoryClass="TVF\StoreBundle\Repository\GenderUserRepository")
 */
class GenderUser
{
    public function __construct()
    {
        $this->likes = false;
        $this->confidence = 0;
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
     * @ORM\Column(name="confidence", type="integer", options={"default" : 0})
     */
    private $confidence;

    /**
     * @var bool
     *
     * @ORM\Column(name="likes", type="boolean")
     */
    private $likes;

    /**
     * @ORM\ManyToOne(targetEntity="\TVF\UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="\TVF\AdminBundle\Entity\Gender", inversedBy="users")
     * @ORM\JoinColumn(nullable=false)
     */
    private $gender;


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
     * Set confidence
     *
     * @param integer $confidence
     *
     * @return GenderUser
     */
    public function setConfidence($confidence)
    {
        $this->confidence = $confidence;

        return $this;
    }

    /**
     * Get confidence
     *
     * @return int
     */
    public function getConfidence()
    {
        return $this->confidence;
    }

    /**
     * Set likes
     *
     * @param boolean $likes
     *
     * @return GenderUser
     */
    public function setLikes($likes)
    {
        $this->likes = $likes;

        return $this;
    }

    /**
     * Get likes
     *
     * @return boolean
     */
    public function getLikes()
    {
        return $this->likes;
    }

    /**
     * Set user
     *
     * @param \TVF\UserBundle\Entity\User $user
     *
     * @return GenderUser
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
     * Set gender
     *
     * @param \TVF\AdminBundle\Entity\Gender $gender
     *
     * @return GenderUser
     */
    public function setGender(\TVF\AdminBundle\Entity\Gender $gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return \TVF\AdminBundle\Entity\Gender
     */
    public function getGender()
    {
        return $this->gender;
    }
}
