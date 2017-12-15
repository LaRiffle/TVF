<?php

namespace TVF\StoreBundle\Controller;

use TVF\RecordBundle\Entity\Creation;
use TVF\AdminBundle\Entity\Gender;
use TVF\AdminBundle\Entity\Type;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SearchController extends Controller
{
    public $entityNameSpace = 'TVFStoreBundle:Search';

    public function indexCreationsAction()
    {
      /*
        This function shouldn't be useful anymore
      */
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Creation');
        $creations = $repository->findBy(array(), array('id' => 'desc'));
        $imagehandler = $this->container->get('tvf_store.imagehandler');
        foreach ($creations as $creation) {
          $fileNames = $creation->getImages();
          $path_small_image = $imagehandler->get_image_in_quality($fileNames[0], 'xs');
          $creation->small_image = $path_small_image;
        }
        return $this->render($this->entityNameSpace.':index_insta.html.twig', array(
          'creations' => $creations,
        ));
    }
    public function showCreationAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Creation');
        $creation = $repository->find($id);
        $attributes = $creation->getAttributes();
        $creation->sizes = [];
        $creation->colors = [];
        $creation->others = [];
        foreach ($attributes as $attribute) {
          if($attribute->getCategory() == 'size'){
            $creation->sizes[] = $attribute;
          } elseif($attribute->getCategory() == 'color'){
            $creation->colors[] = $attribute;
          } else {
            $creation->others[] = $attribute;
          }
        }
        $user = $this->getUser();
        $creation->love = false;
        foreach ($creation->getLovers() as $lover) {
          if($lover->getUsername() == $user->getUsername()){
            $creation->love = true;
          }
        }
        return $this->render('TVFStoreBundle:Search:show.html.twig', array(
          'creation' => $creation,
        ));
    }

    public function collectionAction($collection = '_', $category = '_')
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Creation');
        $creations = $repository->findBy(array(), array('id' => 'desc'));
        $imagehandler = $this->container->get('tvf_store.imagehandler');
        foreach ($creations as $creation) {
          $fileNames = $creation->getImages();
          $path_small_image = $imagehandler->get_image_in_quality($fileNames[0], 'xs');
          $creation->small_image = $path_small_image;
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
          'creations' => $creations,
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
        $repository = $em->getRepository('TVFRecordBundle:Creation');
        $creations = $repository->findBy(array(), array('id' => 'desc'));
        $user = $this->getUser();
        $loved_creations = [];
        foreach ($creations as $creation) {
          foreach ($creation->getLovers() as $lover) {
            if($lover->getUsername() == $user->getUsername()){
              $loved_creations[] = $creation;
            }
          }
        }
        $imagehandler = $this->container->get('tvf_store.imagehandler');
        foreach ($loved_creations as $creation) {
          $fileNames = $creation->getImages();
          $path_small_image = $imagehandler->get_image_in_quality($fileNames[0], 'xs');
          $creation->small_image = $path_small_image;
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
            'creations' => $loved_creations,
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
