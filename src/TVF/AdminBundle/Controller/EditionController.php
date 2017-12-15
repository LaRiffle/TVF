<?php

namespace TVF\AdminBundle\Controller;

use TVF\AdminBundle\Entity\Category;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class EditionController extends Controller
{
  /*
    Handle the Text and Image entities (Text zone or Image that are editable
    directly from the website by the admin)
  */
  public function textAddAction(Request $request, $id = 0) {
      $em = $this->getDoctrine()->getManager();
      $repository = $em->getRepository('TVFAdminBundle:Text');
      if($id == 0) {
          $text = new Text();
      } else {
          $text = $repository->find($id);
          $text_role = $text->getRole();
      }
      $form = $this->get('form.factory')->createBuilder(FormType::class, $text)
      ->add('role', TextType::class)
      ->add('text', TextareaType::class)
      ->add('save',	SubmitType::class)
      ->getForm();

      $form->handleRequest($request);
      if($form->isValid()) {
          $em->persist($text);
          $em->flush();
          return $this->redirect($this->generateUrl('tvf_store_homepage'));
      } elseif ($request->getMethod() == 'POST') {
          $data = $request->get('form');
          $text = $repository->find($id);
          $text->setText($data['text']);
          $text->setRole($text_role);
          $em->persist($text);
          $em->flush();
          return $this->redirect($request->headers->get('referer'));
      }
      return $this->render('TVFAdminBundle:Text:add.html.twig', array(
          'form' => $form->createView(),
          'id' => $id
      ));
  }

  public function imageAddAction(Request $request, $id = 0) {
      $em = $this->getDoctrine()->getManager();
      $repository = $em->getRepository('TVFAdminBundle:Image');
      $oldFileName = null;
      if($id == 0) {
          $image = new Image();
      } else {
          $image = $repository->find($id);
          $image_role = $image->getRole();
          if($image->getImage() != ''){
            $oldFileName = $image->getImage();
            $image->setImage(
                new File($this->getParameter('img_dir').'/'.$image->getImage())
            );
          }
      }
      if($oldFileName != null) {
        $article_img_url = $oldFileName;
      } else {
        $article_img_url = '';
      }
      $form = $this->get('form.factory')->createBuilder(FormType::class, $image)
      ->add('role', TextType::class)
      ->add('image', FileType::class, array('label' => 'Image', 'required' => False))
      ->add('save',	SubmitType::class)
      ->getForm();

      $form->handleRequest($request);
      if($form->isSubmitted() && $form->isValid()) {
          $path = $this->process_image($image);
          $image->setImage($path);
          $em->persist($image);
          $em->flush();
          return $this->redirect($this->generateUrl('tvf_store_homepage'));
      } elseif ($request->getMethod() == 'POST') {
          $data = $request->get('form');
          $image = $repository->find($id);
          $image->setRole($image_role);
          $path = $this->process_image($image);
          $image->setImage($path);
          $em->persist($image);
          $em->flush();
          return $this->redirect($request->headers->get('referer'));
      }
      return $this->render('TVFAdminBundle:Image:add.html.twig', array(
          'form' => $form->createView(),
          'id' => $id,
          'img' => $article_img_url,
      ));
    }

    private function process_image($image){
      $file = $image->getImage();
      if($file != null) {
        $fileName = md5(uniqid()).'.'.$file->guessExtension();
        $file->move(
            $this->getParameter('img_dir'),
            $fileName
        );
        // Check orientation
        $path = $this->getParameter('img_dir').'/'.$fileName;
        $imagehandler = $this->container->get('tvf_store.imagehandler');
        $imagehandler->image_fix_orientation($path);
        // Update the 'image' property to store the file name instead of its contents
        return $fileName;
      } elseif($oldFileName != null) {
        return $oldFileName;
      } else {
        return '';
      }
    }

  }
