<?php

namespace TVF\RecordBundle\Controller;

use TVF\RecordBundle\Entity\Artist;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class ArtistController extends Controller
{
    public $entityNameSpace = 'TVFRecordBundle:Artist';

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository($this->entityNameSpace);
        $artists = $repository->findAll();
        return $this->render($this->entityNameSpace.':index.html.twig', array(
          'artists' => $artists,
        ));
    }
    public function addAction(Request $request, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        $oldFileName = null;
        if($id == 0) {
            $artist = new Artist();
        } else {
            $repository = $em->getRepository($this->entityNameSpace);
            $artist = $repository->find($id);
            if($artist->getImage() != ''){
              $oldFileName = $artist->getImage();
              $artist->setImage(
                  new File($this->getParameter('img_dir').'/'.$artist->getImage())
              );
            }
        }
        if($oldFileName != null) {
          $artist_img_url = $oldFileName;
        } else {
          $artist_img_url = '';
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, $artist)
        ->add('name', TextType::class)
        ->add('bio', TextareaType::class)
        ->add('image', FileType::class, array('label' => 'Image', 'required' => False))
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            // $file stores the uploaded PDF file
            /** @var Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $artist->getImage();
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
              $artist->setImage($fileName);
            } elseif($oldFileName != null) {
              $artist->setImage($oldFileName);
            } else {
              $artist->setImage('');
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($artist);
            $em->flush();
            return $this->redirect($this->generateUrl('tvf_record_artist'));
        }
        return $this->render($this->entityNameSpace.':add.html.twig', array(
            'form' => $form->createView(),
            'id' => $id,
            'img' => $artist_img_url,
        ));

    }
    public function removeAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $artist = $em->getRepository($this->entityNameSpace)->find($id);
        $em->remove($artist);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_record_artist'));
    }
}
