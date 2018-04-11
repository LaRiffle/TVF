<?php

namespace TVF\RecordBundle\Controller;

use TVF\RecordBundle\Entity\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

use SpotifyWebAPI\Session;

class ClientController extends Controller
{
    public $entityNameSpace = 'TVFRecordBundle:Client';

    public function certify_authorship($client_id) {
      if (!$this->get('security.authorization_checker')->isGranted('ROLE_RECORD')
       && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
        return false;
      }

      if($this->get('security.authorization_checker')->isGranted('ROLE_RECORD')){
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('TVFRecordBundle:Client');
        $client = $repository->findOneBy(array('user' => $user));

        if($client->getId() != $client_id) {
          return false;
        }
      }
      return true;
    }

    public function indexAction()
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
          throw new AccessDeniedException('Accès limité.');
        }
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository($this->entityNameSpace);
        $clients = $repository->findAll();
        return $this->render($this->entityNameSpace.':index.html.twig', array(
          'clients' => $clients
        ));
    }
    public function tokenAction() {
      if (!$this->get('security.authorization_checker')->isGranted('ROLE_RECORD')) {
        throw new AccessDeniedException('Accès limité.');
      }
      $session = new Session(
          $this->getParameter('client_id'),
          $this->getParameter('client_secret')
      );
      $session->requestCredentialsToken();
      $accessToken = $session->getAccessToken();
      return new JsonResponse(['token' => $accessToken]);
    }

    public function showAction($id = 0){
        if ($this->get('security.authorization_checker')->isGranted('ROLE_RECORD')) {
          return $this->redirect($this->generateUrl('tvf_record_my_account'));
        }
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
          throw new AccessDeniedException('Accès limité.');
        }
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository($this->entityNameSpace);
        $client = $repository->find($id);
        return $this->render($this->entityNameSpace.':show.html.twig', array(
          'client' => $client
        ));
    }

    public function presentAction($slug){
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository($this->entityNameSpace);
        $client = $repository->findOneBy(array('slug' => $slug));
        return $this->render($this->entityNameSpace.':present.html.twig', array(
          'client' => $client
        ));
    }

    public function locationAction($slug){
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository($this->entityNameSpace);
        $client = $repository->findOneBy(array('slug' => $slug));
        $location = $client->getAddress();
        return new JsonResponse(['address' => $location]);
    }

    public function myAccountAction(Request $request) {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository($this->entityNameSpace);
        $client = $repository->findOneBy(array('user' => $user));
        if($client == null){
          return $this->redirect($this->generateUrl('login'));
        }
        if($request->query->get('premiere_connexion')){
          $first_visit = true;
        } else {
          $first_visit = false;
        }
        return $this->render($this->entityNameSpace.':show.html.twig', array(
          'client' => $client,
          'first_visit' => $first_visit
        ));
    }

    public function collectionAction()
    {
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
          return $this->redirect($this->generateUrl('tvf_store_homepage'));
        }
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_RECORD')) {
          throw new AccessDeniedException('Accès limité.');
        }
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository($this->entityNameSpace);
        $client = $repository->findOneBy(array('user' => $user));

        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        $vinyls = $repository->getVinylsFromClient($client->getId());

        $imagehandler = $this->container->get('tvf_store.imagehandler');
        foreach ($vinyls as $vinyl) {
          $fileNames = $vinyl->getImages();
          $path_small_image = $imagehandler->get_image_in_quality($fileNames[0], 'xs');
          $vinyl->small_image = $path_small_image;
          $vinyl->loved = '0';
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
        return $this->render($this->entityNameSpace.':collection.html.twig', array(
            'vinyls' => $vinyls,
            'is_owner' => true,
            'genders' => $genders,
            'selections' => $selections,
            'selection_id' => '_',
            'categories' => $categories,
            'category_id' => '_'
        ));
    }

    public function selectionAction()
    {
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
          return $this->redirect($this->generateUrl('tvf_store_selection'));
        }
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_RECORD')) {
          throw new AccessDeniedException('Accès limité.');
        }
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Client');
        $client = $repository->findOneBy(array('user' => $user));

        $repository = $em->getRepository('TVFRecordBundle:Selection');
        $selections = $repository->findBy(array(), array('id' => 'desc'));
        $imagehandler = $this->container->get('tvf_store.imagehandler');
        $client_selections = [];
        foreach ($selections as $selection) {
          if($client->getId() == $selection->getClient()->getId()){
            $filename = $selection->getImage();
            $path_small_image = $imagehandler->get_image_in_quality($filename, 'sm');
            $selection->small_image = $path_small_image;
            $selection->is_owner = true;
            $client_selections[] = $selection;
          }
        }
        return $this->render($this->entityNameSpace.':selection.html.twig', array(
          'selections' => $client_selections
        ));
    }

    public function addAction(Request $request, $id = 0) {
        $this->denyAccessUnlessGranted(['ROLE_RECORD','ROLE_ADMIN'], null, 'Accès limité.');

        $em = $this->getDoctrine()->getManager();
        $oldFileName = null;
        if($id == 0) {
            $client = new Client();
        } else {
            if(!$this->certify_authorship($id)){
              throw new AccessDeniedException('Ce compte est-il le vôtre ?');
            }
            $repository = $em->getRepository($this->entityNameSpace);
            $client = $repository->find($id);
            if($client->getImage() != ''){
              $oldFileName = $client->getImage();
              $client->setImage(
                  new File($this->getParameter('img_dir').'/'.$client->getImage())
              );
            }
        }
        if($oldFileName != null) {
          $client_img_url = $oldFileName;
        } else {
          $client_img_url = '';
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, $client)
        ->add('name', TextType::class)
        ->add('website', TextType::class, array(
          'required' => false
        ))
        ->add('address', TextAreaType::class, array(
          'required' => false
        ))
        ->add('description', TextAreaType::class, array(
          'required' => false
        ))
        ->add('image', FileType::class, array('label' => 'Image', 'required' => False))
        ->add('phone', TextType::class, array(
          'required' => false
        ))
        ->add('email', EmailType::class)
        ->add('password', RepeatedType::class, array(
            'type' => PasswordType::class,
            'mapped' => false,
            'required' => ($id == 0) ? true : false,
            'invalid_message' => 'Les valeurs sont différentes',
            'options' => array('attr' => array('class' => 'password-field')),
            'first_options'  => array('label' => 'Mot de passe'),
            'second_options' => array('label' => 'Confirmez le mot de passe'),
        ))
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {

            $repository = $em->getRepository($this->entityNameSpace);
            $existing_client = $repository->findOneBy(array('email' => $client->getEmail()));
            if($existing_client != null && $id == 0){
              $request->getSession()->getFlashBag()->add('warning', "Cette adresse mail est déjà utilisée. Si vous avez perdu votre mot de passe, contactez-nous.");
              return $this->redirect($this->generateUrl('logout'));
            }

            // return redirect()
            $file = $client->getImage();
            if($file != null) {
              // Generate a unique name for the file before saving it
              $fileName = md5(uniqid()).'.'.$file->guessExtension();

              // Move the file to the directory where images are stored
              $file->move(
                  $this->getParameter('img_dir'),
                  $fileName
              );
              // Check orientation
              $path = $this->getParameter('img_dir').'/'.$fileName;
              $imagehandler = $this->container->get('tvf_store.imagehandler');
              $imagehandler->image_fix_orientation($path);

              // Update the 'image' property to store the file name
              // instead of its contents
              $client->setImage($fileName);
            } elseif($oldFileName != null) {
              $client->setImage($oldFileName);
            } else {
              $client->setImage('');
            }
            $slughandler = $this->container->get('tvf_record.slugifyhandler');
            $slug = $slughandler->slugify($client->getName());
            $client->setSlug($slug);
            if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
              $em->persist($client);
              $em->flush();
              return $this->redirect($this->generateUrl('tvf_store_homepage'));
            } else {
              $user = $this->getUser();
              // On creation only, set the username
              if($id == 0){
                $user->setUsername($client->getEmail());
              }
              $data = $request->get('form');
              if($data['password']['first'] != ''){
                  $user->setPassword($data['password']['first']);
              }
              $client->setUser($user);
              $em->persist($client);
              $em->flush();
            }
            return $this->redirect($this->generateUrl('tvf_record_my_account').(($id == 0) ? '?premiere_connexion=1':''));
        }
        return $this->render($this->entityNameSpace.':add.html.twig', array(
            'form' => $form->createView(),
            'id' => $id,
            'img' => $client_img_url,
        ));

    }
    public function removeAction(Request $request, $id){
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
          throw new AccessDeniedException('Accès limité.');
        }
        $em = $this->getDoctrine()->getManager();
        $client = $em->getRepository($this->entityNameSpace)->find($id);
        $em->remove($client);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_store_homepage'));
    }
}
