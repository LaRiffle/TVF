<?php

namespace TVF\StoreBundle\Handler;

use TVF\AdminBundle\Entity\Text;
use TVF\AdminBundle\Entity\Image;

class TVFFieldhandler {
  /*
    Handle the text and image fields that can be added anywhere
  */
  public function __construct($em, $imagehandler)
  {
      $this->em = $em;
      $this->textRepository = $this->em->getRepository('TVFAdminBundle:Text');
      $this->imageRepository = $this->em->getRepository('TVFAdminBundle:Image');

      $this->imagehandler = $imagehandler;
  }
  public function fetchText($role){
    $results = $this->textRepository->findBy(array('role' => $role));
    if(count($results) > 0){
      $text = $results[0];
    } else {
      $text = new Text();
      $text->setText('(undefined)');
      $text->setRole($role);
      $this->em->persist($text);
      $this->em->flush();
    }
    return $text;
  }
  public function fetchImage($role){
    $results = $this->imageRepository->findBy(array('role' => $role));
    if(count($results) > 0){
      $image = $results[0];
      $filename = $image->getImage();
      $path_small_image = $this->imagehandler->get_image_in_quality($filename, 'md');
      $image->small_image = $path_small_image;
    } else {
      $image = new Image();
      $image->setImage('undefined.jpeg');
      $image->setRole($role);
      $this->em->persist($image);
      $this->em->flush();
      $image->small_image = 'undefined.jpeg';
    }
    return $image;
  }
}
