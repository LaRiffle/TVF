<?php

namespace TVF\StoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cart
 *
 * @ORM\Table(name="cart")
 * @ORM\Entity(repositoryClass="TVF\StoreBundle\Repository\CartRepository")
 */
class Cart
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->products = new \Doctrine\Common\Collections\ArrayCollection();
        $this->total = 0;
        $this->mean = 'unspecified';
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
     * @ORM\Column(name="total", type="string", length=255)
     */
    private $total;

    /**
     * @var string
     *
     * @ORM\Column(name="mean", type="string", length=255)
     */
    private $mean;

    /**
     * @ORM\ManyToMany(targetEntity="TVF\RecordBundle\Entity\Vinyl", cascade={"persist"})
     */
    private $products;

    /**
     * @ORM\ManyToOne(targetEntity="TVF\UserBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $customer;


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
     * Set total
     *
     * @param string $total
     *
     * @return Cart
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return string
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set mean
     *
     * @param string $mean
     *
     * @return Cart
     */
    public function setMean($mean)
    {
        $this->mean = $mean;

        return $this;
    }

    /**
     * Get mean
     *
     * @return string
     */
    public function getMean()
    {
        return $this->mean;
    }

    /**
     * Add product
     *
     * @param \TVF\RecordBundle\Entity\Vinyl $product
     *
     * @return Cart
     */
    public function addProduct(\TVF\RecordBundle\Entity\Vinyl $product)
    {
        $this->products[] = $product;
        $this->total = 0;
        foreach ($this->products as $product) {
          $this->total += $product->getPrice();
        }
        return $this;
    }

    /**
     * Remove product
     *
     * @param \TVF\RecordBundle\Entity\Vinyl $product
     */
    public function removeProduct(\TVF\RecordBundle\Entity\Vinyl $product)
    {
        $this->products->removeElement($product);
        $this->total = 0;
        foreach ($this->products as $product) {
          $this->total += $product->getPrice();
        }
    }

    /**
     * Get products
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Set customer
     *
     * @param \TVF\UserBundle\Entity\User $customer
     *
     * @return Cart
     */
    public function setCustomer(\TVF\UserBundle\Entity\User $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Get customer
     *
     * @return \TVF\UserBundle\Entity\User
     */
    public function getCustomer()
    {
        return $this->customer;
    }
}
