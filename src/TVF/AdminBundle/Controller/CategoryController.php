<?php

namespace TVF\AdminBundle\Controller;

use TVF\AdminBundle\Entity\Category;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CategoryController extends Controller
{
    /*
      Manage the category of products sold (basically vinyle & turntable)
    */
    public $entityNameSpace = 'TVFAdminBundle:Category';

    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository($this->entityNameSpace);
        $category = $repository->find($id);
        return $this->render($this->entityNameSpace.':show.html.twig', array(
          'category' => $category,
        ));
    }

    public function addAction(Request $request, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        if($id == 0) {
            $category = new Category();
        } else {
            $repository = $em->getRepository($this->entityNameSpace);
            $category = $repository->find($id);
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, $category)
        ->add('name', TextType::class)
        ->add('slug', TextType::class)
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            return $this->redirect($this->generateUrl('tvf_store_explore'));
        }
        return $this->render($this->entityNameSpace.':add.html.twig', array(
            'form' => $form->createView(),
            'categoryId' => $id
        ));
    }
    public function removeAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository($this->entityNameSpace)->find($id);
        $em->remove($category);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_store_explore'));
    }
  }
