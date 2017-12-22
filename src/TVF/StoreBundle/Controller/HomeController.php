<?php

namespace TVF\StoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class HomeController extends Controller
{
    /*
      All basic pages that are mainly static and correspond to
      the main tabs + homepage
    */
    public $entityNameSpace = 'TVFStoreBundle:Home';

    public function workAction()
    {
        return $this->render($this->entityNameSpace.':work.html.twig');
    }
    public function homepageAction()
    {
        return $this->render($this->entityNameSpace.':home_page.html.twig');
    }
    public function homeAction()
    {
        $text = [];
        $this->handler = $this->container->get('tvf_store.fieldhandler');
        $text['home']['title']['left'] = $this->handler->fetchText('home:title:left');
        $text['home']['text']['left'] = $this->handler->fetchText('home:text:left');
        $text['home']['btn']['left'] = $this->handler->fetchText('home:btn:left');
        $text['home']['left_img'] = $this->handler->fetchImage('home:left_img');
        $text['home']['title']['right'] = $this->handler->fetchText('home:title:right');
        $text['home']['text']['right'] = $this->handler->fetchText('home:text:right');
        $text['home']['btn']['right'] = $this->handler->fetchText('home:btn:right');
        $text['home']['right_img'] = $this->handler->fetchImage('home:right_img');

        $text['home']['collection']['title'] = $this->handler->fetchText('home:collection:title');
        $text['home']['vinyl']['title'] = $this->handler->fetchText('home:vinyl:title');

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Collection');
        $collections = $repository->findBy(array(), array('id' => 'desc'));
        $imagehandler = $this->container->get('tvf_store.imagehandler');
        foreach ($collections as $collection) {
          $filename = $collection->getImage();
          $path_small_image = $imagehandler->get_image_in_quality($filename, 'sm');
          $collection->small_image = $path_small_image;
        }

        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        $vinyls = $repository->findBy(array(), array('id' => 'desc'));
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

        $repository = $em->getRepository('TVFRecordBundle:Client');
        $clients = $repository->findBy(array(), array('joindate' => 'asc'));
        foreach ($clients as $client) {
          $fileName = $client->getImage();
          if($fileName !== ''){
            $path_small_image = $imagehandler->get_image_in_quality($fileName, 'xs');
          } else {
            $path_small_image = '';
          }
          $client->small_image = $path_small_image;
        }
        return $this->render($this->entityNameSpace.':home.html.twig', array(
          'data' => $text,
          'collections' => $collections,
          'vinyls' => $vinyls,
          'clients' => $clients,
        ));
    }
    public function historyAction()
    {
      $text = [];
      $this->handler = $this->container->get('tvf_store.fieldhandler');
      $text['history']['title'] = $this->handler->fetchText('history:title');
      $text['history']['content'] = $this->handler->fetchText('history:text');
      $text['history']['image_message'] = $this->handler->fetchImage('history:image_message');
      $text['history']['under'] = $this->handler->fetchImage('history:under');
      $text['history']['over'] = $this->handler->fetchImage('history:over');
      $text['author']['title'] = $this->handler->fetchText('author:title');
      $text['author']['content'] = $this->handler->fetchText('author:text');
      $text['author']['img'] = $this->handler->fetchImage('author:img');
      return $this->render($this->entityNameSpace.':history.html.twig', array(
        'data' => $text
      ));
    }

    public function collectionAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Collection');
        $collections = $repository->findBy(array(), array('id' => 'desc'));
        $imagehandler = $this->container->get('tvf_store.imagehandler');
        foreach ($collections as $collection) {
          $em->persist($collection);
          $filename = $collection->getImage();
          $path_small_image = $imagehandler->get_image_in_quality($filename, 'sm');
          $collection->small_image = $path_small_image;
        }
        $em->flush();
        return $this->render($this->entityNameSpace.':collection.html.twig', array(
          'collections' => $collections,
        ));
    }

    public function contactAction()
    {
        $text = [];
        $this->handler = $this->container->get('tvf_store.fieldhandler');
        $text['mail']['title'] = $this->handler->fetchText('mail:title');
        $text['mail']['info'] = $this->handler->fetchText('mail:info');
        $text['mail']['content'] = $this->handler->fetchText('mail:content');
        $text['follow']['title'] = $this->handler->fetchText('follow:title');
        $text['follow']['content'] = $this->handler->fetchText('follow:content');
        return $this->render($this->entityNameSpace.':contact.html.twig', array(
          'data' => $text
        ));
    }

    public function joinAction(Request $request)
    {
        $text = [];
        $text['info'] = '';
        if ($request->getMethod() == 'POST') {
            $data = $request->get('form');
            if($data['key'] == $this->getParameter('record_key')){
              // We upgrade the rights of this account to enable Registration
              $user = $this->getUser();
              $roles = $user->getRoles();
              if(!in_array('ROLE_RECORD', $roles)){
                  $roles[] = 'ROLE_RECORD';
              }
              $user->setRoles($roles);
              $em = $this->getDoctrine()->getManager();
              $em->persist($user);
              $em->flush();
              // Update the infos of logged user
              $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
              $this->get('security.token_storage')->setToken($token);

              $flashbag = $this->get('session')->getFlashBag();
              $flashbag->add('success', 'La clef a été approuvée, vous pouvez désormais remplir quelques informations sur vous.');
              return $this->redirect($this->generateUrl('tvf_record_client_add'));
            } else {
              $text['info'] = "La clef n'est pas valide. Merci de nous contacter.";
            }
        }
        $this->handler = $this->container->get('tvf_store.fieldhandler');
        $text['join']['title'] = $this->handler->fetchText('join:title');
        $text['join']['text'] = $this->handler->fetchText('join:text');
        $text['join']['method'] = $this->handler->fetchText('join:method');
        return $this->render($this->entityNameSpace.':join.html.twig', array(
          'data' => $text
        ));

    }
}
