<?php

namespace TVF\StoreBundle\Controller;

use TVF\RecordBundle\Entity\Vinyl;
use TVF\AdminBundle\Entity\Gender;
use TVF\AdminBundle\Entity\Type;
use TVF\StoreBundle\Entity\VinylUser;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SearchController extends Controller
{
    public $entityNameSpace = 'TVFStoreBundle:Search';

    public function indexVinylsAction()
    {
      /*
        This function shouldn't be useful anymore
      */
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        $vinyls = $repository->findBy(array(), array('id' => 'desc'));
        $imagehandler = $this->container->get('tvf_store.imagehandler');
        foreach ($vinyls as $vinyl) {
          $fileNames = $vinyl->getImages();
          $path_small_image = $imagehandler->get_image_in_quality($fileNames[0], 'xs');
          $vinyl->small_image = $path_small_image;
        }
        return $this->render($this->entityNameSpace.':index_insta.html.twig', array(
          'vinyls' => $vinyls,
        ));
    }
    public function showVinylAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        $vinyl = $repository->find($id);
        $attributes = $vinyl->getAttributes();
        $vinyl->sizes = [];
        $vinyl->colors = [];
        $vinyl->others = [];
        foreach ($attributes as $attribute) {
          if($attribute->getCategory() == 'size'){
            $vinyl->sizes[] = $attribute;
          } elseif($attribute->getCategory() == 'color'){
            $vinyl->colors[] = $attribute;
          } else {
            $vinyl->others[] = $attribute;
          }
        }
        $user = $this->getUser();
        $repository = $em->getRepository('TVFStoreBundle:VinylUser');
        $vinyluser = $repository->findOneBy(array('vinyl' => $vinyl, 'user' => $user));
        if($vinyluser === null) {
          $vinyluser = new VinylUser();
          $vinyluser->setUser($user);
          $vinyluser->setVinyl($vinyl);
        }
        $vinyluser->setNbViews($vinyluser->getNbViews() + 1);
        $vinyl->love = $vinyluser->getLover();
        return $this->render($this->entityNameSpace.':show.html.twig', array(
          'vinyl' => $vinyl,
        ));
    }

    public function collectionAction($collection = '_', $category = '_')
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        $vinyls = $repository->findBy(array(), array('id' => 'desc'));
        $imagehandler = $this->container->get('tvf_store.imagehandler');
        foreach ($vinyls as $vinyl) {
          $fileNames = $vinyl->getImages();
          $path_small_image = $imagehandler->get_image_in_quality($fileNames[0], 'xs');
          $vinyl->small_image = $path_small_image;
        }
        $repository = $em->getRepository('TVFAdminBundle:Gender');
        $genders = $repository->findAll();
        $typeRepository = $em->getRepository('TVFAdminBundle:Type');
        foreach ($genders as $gender) {
          $gender->types = $typeRepository->whereGender($gender->getId());
        }
        $repository = $em->getRepository('TVFRecordBundle:Collection');
        if($collection != '_'){
          $collection_id = $repository->findOneBy(array('slug' => $collection))->getId();
        } else {
          $collection_id = $collection;
        }

        $collections = $repository->findAll();
        $repository = $em->getRepository('TVFAdminBundle:Category');
        $categories = $repository->findAll();
        return $this->render($this->entityNameSpace.':collection.html.twig', array(
          'vinyls' => $vinyls,
          'genders' => $genders,
          'collections' => $collections,
          'collection_id' => $collection_id,
          'categories' => $categories,
          'category_id' => $category
        ));
    }
    public function selectionAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        $vinyls = $repository->findBy(array(), array('id' => 'desc'));
        $user = $this->getUser();
        $loved_vinyls = [];
        $repository = $em->getRepository('TVFStoreBundle:VinylUser');
        $user_vinyl_loved = $repository->findBy(array('user' => $user, 'lover' => true));
        foreach ($user_vinyl_loved as $user_vinyl) {
          $loved_vinyls[] = $user_vinyl->getVinyl();
        }

        $imagehandler = $this->container->get('tvf_store.imagehandler');
        foreach ($loved_vinyls as $vinyl) {
          $fileNames = $vinyl->getImages();
          $path_small_image = $imagehandler->get_image_in_quality($fileNames[0], 'xs');
          $vinyl->small_image = $path_small_image;
        }
        $repository = $em->getRepository('TVFAdminBundle:Gender');
        $genders = $repository->findAll();
        $typeRepository = $em->getRepository('TVFAdminBundle:Type');
        foreach ($genders as $gender) {
          $gender->types = $typeRepository->whereGender($gender->getId());
        }
        $repository = $em->getRepository('TVFRecordBundle:Collection');
        $collections = $repository->findAll();
        $repository = $em->getRepository('TVFAdminBundle:Category');
        $categories = $repository->findAll();
        return $this->render($this->entityNameSpace.':selection.html.twig', array(
            'vinyls' => $loved_vinyls,
            'genders' => $genders,
            'collections' => $collections,
            'collection_id' => '_',
            'categories' => $categories,
            'category_id' => '_'
        ));
    }
    public function showAction()
    {
        return $this->render($this->entityNameSpace.':show.html.twig');
    }
}
