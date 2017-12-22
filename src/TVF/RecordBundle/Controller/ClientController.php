<?php

namespace TVF\RecordBundle\Controller;

use TVF\RecordBundle\Entity\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
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

class ClientController extends Controller
{
    public $entityNameSpace = 'TVFRecordBundle:Client';

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository($this->entityNameSpace);
        $clients = $repository->findAll();
        return $this->render($this->entityNameSpace.':index.html.twig', array(
          'clients' => $clients
        ));
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
    public function myAccountAction() {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository($this->entityNameSpace);
        $client = $repository->findOneBy(array('user' => $user));
        return $this->render($this->entityNameSpace.':show.html.twig', array(
          'client' => $client
        ));
    }
    public function addAction(Request $request, $id = 0) {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_RECORD')) {
          throw new AccessDeniedException('Accès limité.');
        }
        $em = $this->getDoctrine()->getManager();
        $oldFileName = null;
        if($id == 0) {
            $client = new Client();
        } else {
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
        ->add('description', TextAreaType::class, array(
          'required' => false
        ))
        ->add('image', FileType::class, array('label' => 'Image', 'required' => False))
        ->add('phone', TextType::class, array(
          'required' => false
        ))
        ->add('email', EmailType::class)
        ->add('password', PasswordType::class, array(
            'mapped' => false,
            'required' => False
        ))
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
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

            $user = $this->getUser();
            // On creation only, set the username
            if($id == 0){
              $user->setUsername($client->getEmail());
            }
            $data = $request->get('form');
            if($data['password'] != ''){
                $user->setPassword($data['password']);
            }
            $client->setUser($user);
            $em->persist($client);
            $em->flush();
            return $this->redirect($this->generateUrl('tvf_record_my_account'));
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
