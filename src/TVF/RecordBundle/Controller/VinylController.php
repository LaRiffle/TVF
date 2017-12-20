<?php

namespace TVF\RecordBundle\Controller;

use TVF\RecordBundle\Entity\Vinyl;
use TVF\AdminBundle\Entity\Type;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\File;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class  VinylController extends Controller
{
    public $entityNameSpace = 'TVFRecordBundle:Vinyl';

    public function addAction(Request $request, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        if($id == 0) {
            $vinyl = new Vinyl();
        } else {
            $repository = $em->getRepository($this->entityNameSpace);
            $vinyl = $repository->find($id);
            $images = $vinyl->getImages();
            $vinyl->emptyImages();
            foreach ($images as $image) {
              $vinyl->addImage(
                  new File($this->getParameter('img_dir').'/'.$image)
              );
            }
            $new_vinyl = new Vinyl();
            $new_vinyl->setName($vinyl->getName());
            $new_vinyl->setTitle1($vinyl->getTitle1());
            $new_vinyl->setText1($vinyl->getText1());
            $new_vinyl->setTitle2($vinyl->getTitle2());
            $new_vinyl->setText2($vinyl->getText2());
            $new_vinyl->setOnsold($vinyl->getOnsold());
            $new_vinyl->setPrice($vinyl->getPrice());
            $new_vinyl->setCollection($vinyl->getCollection());
            $new_vinyl->setCategory($vinyl->getCategory());
            foreach($vinyl->getTypes() as $type){
              $new_vinyl->addType($type);
            }
            foreach($vinyl->getAttributes() as $type){
              $new_vinyl->addAttribute($type);
            }
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, ($id == 0 ? $vinyl : $new_vinyl))
        ->add('name', TextType::class)
        ->add('collection', EntityType::class, array(
                'class'        => 'TVFRecordBundle:Collection',
                'choice_label' => 'title',
        ))
        ->add('category', EntityType::class, array(
                'class'        => 'TVFAdminBundle:Category',
                'choice_label' => 'name',
        ))
        ->add('title1', TextType::class)
        ->add('text1', TextareaType::class)
        ->add('title2', TextType::class, array(
          'required' => false
        ))
        ->add('text2', TextareaType::class, array(
          'required' => false
        ))
        ->add('onsold', CheckboxType::class, array(
          'required' => false
        ))
        ->add('price', NumberType::class, array(
          'required' => false
        ))
        ->add('images', CollectionType::class, array(
            // each entry in the array will be an "image" field
            'entry_type'   => FileType::class,
            'allow_add'    => true,
            'allow_delete' => true,
            'required'     => false,
            // these options are passed to each "image" type
            'entry_options'  => array(
                'attr'      => array('class' => 'image-box')
            ),
        ))
        ->add('imgs', ChoiceType::class, array(
          'mapped' => false,
          'multiple' => true,
          'expanded' => true,
          'choices' => $vinyl->getImages(),
          'choice_label' => function ($value, $key, $index) {
              return $value;
          }
        ))
        ->add('types', EntityType::class, array(
                'class'        => 'TVFAdminBundle:Type',
                'choice_label' => 'name',
                'multiple'     => true,
                'expanded'     => true,
                'required'     => false))
        ->add('attributes', EntityType::class, array(
                'class'        => 'TVFRecordBundle:Attribute',
                'choice_label' => 'name',
                'multiple'     => true,
                'expanded'     => true,
                'required'     => false))
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isValid()) {
            if($id == 0){
              $files = $vinyl->getImages();
              $vinyl->emptyImages();
              if($files != null) {
                foreach ($files as $file) {
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
                  $vinyl->addImage($fileName);
                }
              }
            } else {
              $files = $new_vinyl->getImages();
              $vinyl->emptyImages();
              $new_vinyl->emptyImages();
              // On met les ancienns images gardées
              $old_files = $form['imgs']->getData();
              foreach ($old_files as $old_file) {
                $fileName = $old_file->getFilename();
                $vinyl->addImage($fileName);
              }
              // And the new ones
              if($files != null) {
                foreach ($files as $file) {
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
                  $vinyl->addImage($fileName);
                }
              }
              $vinyl->setName($new_vinyl->getName());
              $vinyl->setTitle1($new_vinyl->getTitle1());
              $vinyl->setText1($new_vinyl->getText1());
              $vinyl->setTitle2($new_vinyl->getTitle2());
              $vinyl->setText2($new_vinyl->getText2());
              $vinyl->setOnsold($new_vinyl->getOnsold());
              $vinyl->setPrice($new_vinyl->getPrice());
              $vinyl->setCollection($new_vinyl->getCollection());
              $vinyl->setCategory($new_vinyl->getCategory());
              $vinyl->emptyTypes();
              foreach($new_vinyl->getTypes() as $type){
                $vinyl->addType($type);
              }
              $vinyl->emptyAttributes();
              if(count($new_vinyl->getAttributes()) > 0){
                foreach($new_vinyl->getAttributes() as $type){
                  $vinyl->addAttribute($type);
                }
              }
            }
            $em->persist($vinyl);
            $em->flush();
            //return new Response();
            return $this->redirect($this->generateUrl('tvf_store_explore'));
        }
        return $this->render($this->entityNameSpace.':add.html.twig', array(
            'form' => $form->createView(),
            'id' => $id
        ));
    }
    public function removeAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $vinyl = $em->getRepository($this->entityNameSpace)->find($id);
        $em->remove($vinyl);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_store_explore'));
    }
  }
