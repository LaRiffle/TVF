<?php

namespace TVF\RecordBundle\Controller;

use TVF\RecordBundle\Entity\Vinyl;
use TVF\AdminBundle\Entity\Type;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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

    public function certify_authorship($vinyl_id) {
      if (!$this->get('security.authorization_checker')->isGranted('ROLE_RECORD')
       && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
        return false;
      }

      if($this->get('security.authorization_checker')->isGranted('ROLE_RECORD')){
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('TVFRecordBundle:Client');
        $client = $repository->findOneBy(array('user' => $user));

        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        $vinyl = $repository->findOneBy(array('id'=>$vinyl_id, 'client' => $client));
        if($vinyl == null) {
          return false;
        }
      }
      return true;
    }

    public function addAction(Request $request, $id = 0) {
        $this->denyAccessUnlessGranted(['ROLE_RECORD','ROLE_ADMIN'], null, 'Accès limité.');

        $em = $this->getDoctrine()->getManager();
        if($id == 0) {
            $vinyl = new Vinyl();
            $category_repository = $em->getRepository('TVFAdminBundle:Category');
            $category = $category_repository->findOneBy(array('slug' => 'vinyle'));
            $vinyl->setCategory($category);
        } else {
            if(!$this->certify_authorship($id)){
              throw new AccessDeniedException('Accès limité.');
            }
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
            $new_vinyl->setDescription($vinyl->getDescription());
            $new_vinyl->setOnsold($vinyl->getOnsold());
            $new_vinyl->setPrice($vinyl->getPrice());
            //$new_vinyl->setCategory($vinyl->getCategory());
            $new_vinyl->setClient($vinyl->getClient());
            foreach($vinyl->getArtists() as $artist){
              $new_vinyl->addArtist($artist);
            }
            foreach($vinyl->getTypes() as $type){
              $new_vinyl->addType($type);
            }
            foreach($vinyl->getAttributes() as $type){
              $new_vinyl->addAttribute($type);
            }
        }
        /* Set low quality imgs for the already existing img in the form */
        $imagehandler = $this->container->get('tvf_store.imagehandler');
        $vinyl_images = [];
        foreach ($vinyl->getImages() as $image) {
          $fileName = basename($image);
          $path_small_image = $imagehandler->get_image_in_quality($fileName, 'xxs');
          $vinyl_images[] = $path_small_image;
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, ($id == 0 ? $vinyl : $new_vinyl))
        ->add('name', TextType::class)
        ->add('artists', EntityType::class, array(
                'class'        => 'TVFRecordBundle:Artist',
                'choice_label' => 'name',
                'multiple'     => true
        ))
        ->add('description', TextareaType::class, array(
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
          'choices' => $vinyl_images,
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
                $imagehandler = $this->container->get('tvf_store.imagehandler');
                $fileName;
                foreach ($files as $file) {
                  // File can be either a real image or the url of an image
                  if(is_string($file)) {
                    $extension = 'jpg';
                    $fileName = md5(uniqid()).'.'.$extension;
                    $file = $imagehandler->download_image($file, $fileName);
                  } else {
                    // Generate a unique name for the file before saving it
                    $fileName = md5(uniqid()).'.'.$file->guessExtension();

                    // Move the file to the directory where images are stored
                    $file->move(
                        $this->getParameter('img_dir'),
                        $fileName
                    );
                    // Check orientation
                    $path = $this->getParameter('img_dir').'/'.$fileName;
                    $imagehandler->image_fix_orientation($path);

                    // Update the 'image' property to store the file name
                    // instead of its contents
                  }
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
                //$fileName = $old_file->getFilename();
                $fileName = str_replace(['-xxs', '-xs', '-sm', '-md', '-lg'],'', $old_file);
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
              $vinyl->setDescription($new_vinyl->getDescription());
              $vinyl->setOnsold($new_vinyl->getOnsold());
              $vinyl->setPrice($new_vinyl->getPrice());
              //$vinyl->setCategory($new_vinyl->getCategory());
              $vinyl->emptyArtists();
              foreach($new_vinyl->getArtists() as $artist){
                $vinyl->addArtist($artist);
              }
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
            $user = $this->getUser();
            $repository = $em->getRepository('TVFRecordBundle:Client');
            $client = $repository->findOneBy(array('user' => $user));
            $vinyl->setClient($client);
            $em->persist($vinyl);
            $em->flush();
            //return new Response();
            return $this->redirect($this->generateUrl('tvf_record_collection'));
        }

        $repository = $em->getRepository('TVFAdminBundle:Gender');
        $genders = $repository->findAll();
        $typeRepository = $em->getRepository('TVFAdminBundle:Type');
        foreach ($genders as $gender) {
          $gender->types = $typeRepository->whereGender($gender->getId());
        }

        $repository = $em->getRepository('TVFRecordBundle:Artist');
        $artists = $repository->findBy(array(), array('name'=>'ASC'));

        return $this->render($this->entityNameSpace.':add.html.twig', array(
            'form' => $form->createView(),
            'id' => $id,
            'genders' => $genders,
            'artists' => $artists,
        ));
    }
    public function removeAction(Request $request, $id){
        if(!$this->certify_authorship($id)){
          throw new AccessDeniedException('Accès limité.');
        }

        $em = $this->getDoctrine()->getManager();
        $vinyl = $em->getRepository($this->entityNameSpace)->find($id);
        $vinyl_users = $em->getRepository('TVFStoreBundle:VinylUser')->findBy(array('vinyl' => $vinyl));
        foreach ($vinyl_users as $vinyl_user) {
          $em->remove($vinyl_user);
        }
        $em->remove($vinyl);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_record_collection'));
    }
  }
