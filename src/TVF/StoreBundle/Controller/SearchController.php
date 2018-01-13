<?php

namespace TVF\StoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use TVF\RecordBundle\Entity\Vinyl;
use TVF\AdminBundle\Entity\Gender;
use TVF\AdminBundle\Entity\Type;
use TVF\StoreBundle\Entity\VinylUser;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SearchController extends Controller
{
    public $entityNameSpace = 'TVFStoreBundle:Search';

    public function searchAction(Request $request)
    {
      $query = $request->request->get('request');

      $em = $this->getDoctrine()->getManager();
      $repository = $em->getRepository('TVFRecordBundle:Vinyl');
      $vinyls = $repository->search($query);
      $data = [];
      foreach ($vinyls as $vinyl) {
        $data[] = [
          'id' => $vinyl->getId(),
          'name' => $vinyl->getName(),
          'image' => $vinyl->getImages()[0]
        ];
      }
      /* Used to search for specific vinyls */
      $data = ['results' => $data];
      return new JsonResponse($data);
    }
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
        $em->persist($vinyluser);
        $em->flush();
        $vinyl->love = $vinyluser->getLover();

        $is_owner = false;
        if ($this->get('security.authorization_checker')->isGranted('ROLE_RECORD')) {
          $repository = $em->getRepository('TVFRecordBundle:Client');
          $client = $repository->findOneBy(array('user' => $user));
          if($vinyl->getClient()->getId() == $client->getId()){
            $is_owner = true;
          }
        }
        return $this->render($this->entityNameSpace.':show.html.twig', array(
          'vinyl' => $vinyl,
          'is_owner' => $is_owner,
        ));
    }

    public function selectionAction($selection = '_', $category = '_')
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        $vinyls = $repository->findBy(array(), array('id' => 'desc'));
        $imagehandler = $this->container->get('tvf_store.imagehandler');
        $love_repository = $em->getRepository('TVFStoreBundle:VinylUser');
        $user = $this->getUser();
        foreach ($vinyls as $vinyl) {
          $fileNames = $vinyl->getImages();
          $path_small_image = $imagehandler->get_image_in_quality($fileNames[0], 'xs');
          $vinyl->small_image = $path_small_image;
          $vinyluser = $love_repository->findOneBy(array('vinyl' => $vinyl, 'user' => $user));
          if($vinyluser === null) {
            $vinyl->loved = '0';
          } else {
            $vinyl->loved = $vinyluser->getLover();
          }
        }
        $repository = $em->getRepository('TVFAdminBundle:Gender');
        $genders = $repository->findAll();
        $typeRepository = $em->getRepository('TVFAdminBundle:Type');
        foreach ($genders as $gender) {
          $gender->types = $typeRepository->whereGender($gender->getId());
        }
        $repository = $em->getRepository('TVFRecordBundle:Selection');
        if($selection != '_'){
          $selection_id = $repository->findOneBy(array('slug' => $selection))->getId();
        } else {
          $selection_id = $selection;
        }

        $selections = $repository->findAll();
        $repository = $em->getRepository('TVFAdminBundle:Category');
        $categories = $repository->findAll();
        return $this->render($this->entityNameSpace.':selection.html.twig', array(
          'vinyls' => $vinyls,
          'genders' => $genders,
          'selections' => $selections,
          'selection_id' => $selection_id,
          'categories' => $categories,
          'category_id' => $category
        ));
    }
    public function lovedAction()
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
          $vinyl->loved = '1';
        }
        $repository = $em->getRepository('TVFAdminBundle:Gender');
        $genders = $repository->findAll();
        $typeRepository = $em->getRepository('TVFAdminBundle:Type');
        foreach ($genders as $gender) {
          $gender->types = $typeRepository->whereGender($gender->getId());
        }
        $repository = $em->getRepository('TVFRecordBundle:Selection');
        $selections = $repository->findAll();
        $repository = $em->getRepository('TVFAdminBundle:Category');
        $categories = $repository->findAll();
        return $this->render($this->entityNameSpace.':loved_items.html.twig', array(
            'vinyls' => $loved_vinyls,
            'genders' => $genders,
            'selections' => $selections,
            'selection_id' => '_',
            'categories' => $categories,
            'category_id' => '_'
        ));
    }
    public function showAction()
    {
        return $this->render($this->entityNameSpace.':show.html.twig');
    }
}
