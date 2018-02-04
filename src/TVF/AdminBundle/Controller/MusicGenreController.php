<?php

namespace TVF\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use TVF\AdminBundle\Entity\Gender;
use TVF\AdminBundle\Entity\Type;

class MusicGenreController extends Controller
{
    /*
      Handle music and sous-music genres
      ex:
        * Electro
          - House
          - Dance
    */
    public function genderAddAction(Request $request, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        if($id == 0) {
            $gender = new Gender();
        } else {
            $repository = $em->getRepository('TVFAdminBundle:Gender');
            $gender = $repository->find($id);
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, $gender)
        ->add('name', TextType::class)
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isValid()) {
            $slughandler = $this->container->get('tvf_record.slugifyhandler');
            $slug = $slughandler->slugify($gender->getName());
            $gender->setSlug($slug);
            $em->persist($gender);
            // Add also a gender with the same name
            $type = new Type();
            $type->setName($gender->getName());
            $type->setSlug($slug);
            $type->setGender($gender);
            $em->persist($type);
            $em->flush();
            return $this->redirect($this->generateUrl('tvf_store_explore'));
        }
        return $this->render('TVFAdminBundle:Filter:gender_add.html.twig', array(
            'form' => $form->createView(),
            'id' => $id
        ));
    }
    public function genderRemoveAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $gender= $em->getRepository('TVFAdminBundle:Gender')->find($id);

        $typeRepository = $em->getRepository('TVFAdminBundle:Type');
        $types = $typeRepository->whereGender($gender->getId());
        foreach ($types as $type) {
            $em->remove($type);
        }
        $gender_users = $em
          ->getRepository('TVFStoreBundle:GenderUser')
          ->findBy(array('gender' => $gender));
        foreach ($gender_users as $gender_user) {
          $em->remove($gender_user);
        }
        $em->remove($gender);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_store_explore'));
    }

    public function typeAddAction(Request $request, $gender_id, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        if($id == 0) {
            $type = new Type();
        } else {
            $repository = $em->getRepository('TVFAdminBundle:Type');
            $type = $repository->find($id);
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, $type)
        ->add('name', TextType::class)
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isValid()) {
            $slughandler = $this->container->get('tvf_record.slugifyhandler');
            $slug = $slughandler->slugify($type->getName());
            $type->setSlug($slug);
            $genderRepository = $em->getRepository('TVFAdminBundle:Gender');
            $gender = $genderRepository->find($gender_id);
            $type->setGender($gender);

            $em->persist($type);
            $em->flush();
            return $this->redirect($this->generateUrl('tvf_store_explore'));
        }
        return $this->render('TVFAdminBundle:Filter:type_add.html.twig', array(
            'form' => $form->createView(),
            'gender_id' => $gender_id,
            'id' => $id
        ));
    }
    public function typeRemoveAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $type = $em->getRepository('TVFAdminBundle:Type')->find($id);
        $em->remove($type);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_store_explore'));
    }
  }
