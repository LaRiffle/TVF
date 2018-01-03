<?php

namespace TVF\RecordBundle\Controller;

use TVF\RecordBundle\Entity\Selection;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class  SelectionController extends Controller
{
    public $entityNameSpace = 'TVFRecordBundle:Selection';
    /* Index is in Information Controller */

    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository($this->entityNameSpace);
        $selection = $repository->find($id);
        return $this->render('TVFStoreBundle:Selection:show.html.twig', array(
          'selection' => $selection,
        ));
    }

    public function addAction(Request $request, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        $oldFileName = null;
        if($id == 0) {
            $selection = new Selection();
        } else {
            $repository = $em->getRepository($this->entityNameSpace);
            $selection = $repository->find($id);
            if($selection->getImage() != ''){
              $oldFileName = $selection->getImage();
              $selection->setImage(
                  new File($this->getParameter('img_dir').'/'.$selection->getImage())
              );
            }
        }
        if($oldFileName != null) {
          $selection_img_url = $oldFileName;
        } else {
          $selection_img_url = '';
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, $selection)
        ->add('title', TextType::class)
        ->add('description', TextareaType::class)
        ->add('image', FileType::class, array('label' => 'Image', 'required' => False))
        ->add('vinyls', EntityType::class, array(
                'class'        => 'TVFRecordBundle:Vinyl',
                'choice_label' => 'name',
                'multiple'     => true,
                'expanded'     => true,
                'required'     => false))
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            // $file stores the uploaded PDF file
            /** @var Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $selection->getImage();
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
              $selection->setImage($fileName);
            } elseif($oldFileName != null) {
              $selection->setImage($oldFileName);
            } else {
              $selection->setImage('');
            }

            $slughandler = $this->container->get('tvf_record.slugifyhandler');
            $slug = $slughandler->slugify($selection->getTitle());
            $selection->setSlug($slug);

            $em = $this->getDoctrine()->getManager();
            $em->persist($selection);
            $em->flush();
            return $this->redirect($this->generateUrl('tvf_store_selection'));
        }
        return $this->render($this->entityNameSpace.':add.html.twig', array(
            'form' => $form->createView(),
            'selectionId' => $id,
            'img' => $selection_img_url,
        ));
    }
    public function removeAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $selection = $em->getRepository($this->entityNameSpace)->find($id);
        $em->remove($selection);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_store_selection'));
    }
  }
