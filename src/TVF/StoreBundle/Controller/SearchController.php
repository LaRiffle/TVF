<?php

namespace TVF\StoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
      if (!$this->get('security.authorization_checker')->isGranted('ROLE_RECORD')) {
        throw new AccessDeniedException('Accès limité.');
      }
      $imagehandler = $this->container->get('tvf_store.imagehandler');
      $query = $request->request->get('request');
      $client_id = $request->request->get('client_id');

      $user = $this->getUser();
      $em = $this->getDoctrine()->getManager();
      $repository = $em->getRepository('TVFRecordBundle:Client');
      $client = $repository->findOneBy(array('user' => $user));
      if($client == null){
        throw new AccessDeniedException('Accès limité.');
      }
      $repository = $em->getRepository('TVFRecordBundle:Vinyl');
      $vinyls = $repository->search($query, $client_id);
      $data = [];
      foreach ($vinyls as $vinyl) {
        if($vinyl->getClient()->getId() == $client->getId()){
          $fileNames = $vinyl->getImages();
          if(count($fileNames) > 0){
            $path_small_image = $imagehandler->get_image_in_quality($fileNames[0], 'xs');
          } else {
            $path_small_image = '';
          }
          $artists = '';
          $first = true;
          foreach ($vinyl->getArtists() as $artist) {
            if(!$first){
              $artists .= ', ';
            } else {
              $first = false;
            }
            $artists .= $artist->getName();
          }
          $data[] = [
            'id' => $vinyl->getId(),
            'name' => $vinyl->getName(),
            'artists'=> $artists,
            'image' => $path_small_image
          ];
        }
      }
      /* Used to search for specific vinyls */
      $data = ['results' => $data];
      return new JsonResponse($data);
    }
    public function recordCollectionAction(Request $request, $slug){
      $em = $this->getDoctrine()->getManager();
      $repository = $em->getRepository('TVFRecordBundle:Client');
      $client = $repository->findOneBy(array('slug' => $slug));
      if($client){
        //$repository = $em->getRepository('TVFRecordBundle:Vinyl');
        //$vinyls = $repository->findBy(array('client' => $client))
        return $this->selectionAction($request, '_', '_', $client);
      } else {
        return $this->redirect($this->generateUrl('tvf_store_homepage'));
      }
    }
    public function indexVinylsAction()
    {
      /*
        This function shouldn't be useful anymore
      */
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        $vinyls = $repository->getVinyls();
        $imagehandler = $this->container->get('tvf_store.imagehandler');
        $vinyls = $imagehandler->convert_vinyl_images($vinyls, 'sm');
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
        if($user != null){
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
        } else {
            $vinyl->love = false;
        }

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

    public function selectionAction(Request $request, $selection = '_', $category = '_', $client = NULL)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        $vinyls = $repository->getVinyls();
        if($client){
          $client_id = $client->getId();
          $client_vinyls = [];
          foreach ($vinyls as $vinyl) {
            if($vinyl->getClient()->getId() == $client_id){
              $client_vinyls[] = $vinyl;
            }
          }
          $vinyls = $client_vinyls;
        }
        $imagehandler = $this->container->get('tvf_store.imagehandler');
        $vinyls = $imagehandler->convert_vinyl_images($vinyls, 'xs');

        $love_repository = $em->getRepository('TVFStoreBundle:VinylUser');
        $user = $this->getUser();
        foreach ($vinyls as $vinyl) {
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
          $selection_ = $repository->findOneBy(array('slug' => $selection));
          $selection_id = $selection_->getId();
          $selection_name = $selection_->getTitle();
          $selection_record = $selection_->getClient();
        } else {
          $selection_id = $selection;
          $selection_name = '';
          $selection_record = null;
        }

        $selections = $repository->findAll();
        $repository = $em->getRepository('TVFAdminBundle:Category');
        $categories = $repository->findAll();

        $variables = array(
          'vinyls' => $vinyls,
          'genders' => $genders,
          'selections' => $selections,
          'selection_id' => $selection_id,
          'selection_name' => $selection_name,
          'selection_record' => $selection_record,
          'categories' => $categories,
          'category_id' => $category
        );
        if($request->query->get('panier') != null){
          $variables['show_cart'] = true;
        }
        if($client){
          $variables['record'] = $client;
        }
        return $this->render($this->entityNameSpace.':selection.html.twig', $variables);
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
        $loved_vinyls = $imagehandler->convert_vinyl_images($loved_vinyls, 'xs');

        $imagehandler = $this->container->get('tvf_store.imagehandler');
        foreach ($loved_vinyls as $vinyl) {
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

        $repository = $em->getRepository('TVFStoreBundle:GenderUser');
        $liked_genders = $repository->getLikedGenders($user);
        return $this->render($this->entityNameSpace.':loved_items.html.twig', array(
            'vinyls' => $loved_vinyls,
            'genders' => $genders,
            'selections' => $selections,
            'selection_id' => '_',
            'categories' => $categories,
            'category_id' => '_',
            'liked_genders' => $liked_genders,
        ));
    }
    public function showAction()
    {
        return $this->render($this->entityNameSpace.':show.html.twig');
    }
}
