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
      /* Used to search for specific vinyls */
      if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
        throw new AccessDeniedException('Accès limité.');
      }
      $user = $this->getUser();
      $auth = $request->request->get('auth');
      // TODO Check authentication

      $imagehandler = $this->container->get('tvf_store.imagehandler');
      $em = $this->getDoctrine()->getManager();
      $repository = $em->getRepository('TVFRecordBundle:Vinyl');
      $data = [];
      $filter = $request->request->get('filter');
      $offset = $request->request->get('offset');
      $client_id = ($filter['client'] != '_') ? $filter['client'] : null;
      $vinyls = $repository->search($filter, $offset, 6, $client_id, false);
      $nb_vinyls = $repository->search($filter, -1, -1, $client_id, true);
      $vinyls = $imagehandler->convert_vinyl_images($vinyls, 'xs');

      foreach ($vinyls as $vinyl) {
        $artists = [];
        // TODO Add loved or not loved
        $viny_data = json_encode($vinyl);
        $data[] = $viny_data;
      }

      $data = [
        'results' => $data,
        'nb_results' => $nb_vinyls
      ];
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
        $limit = 6;
        if($client){
          $client_id = $client->getId();
          $repository->getVinylsFromClient($client_id, $limit);
          $nb_results = $repository->countVinylsFromClient($client_id);
        } else {
          $vinyls = $repository->getVinyls($limit);
          $nb_results = $repository->countVinyls();
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
          'nb_results' => $nb_results,
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
