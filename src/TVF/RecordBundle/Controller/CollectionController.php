<?php

namespace TVF\RecordBundle\Controller;

use TVF\RecordBundle\Entity\Collection;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class  CollectionController extends Controller
{
    public $entityNameSpace = 'TVFRecordBundle:Collection';
    /* Index is in Information Controller */

    static public function slugify($text){
      //on sauve les accents classiques
      $text = preg_replace('#Ç#', 'C', $text);
      $text = preg_replace('#ç#', 'c', $text);
      $text = preg_replace('#è|é|ê|ë#', 'e', $text);
      $text = preg_replace('#È|É|Ê|Ë#', 'E', $text);
      $text = preg_replace('#à|á|â|ã|ä|å#', 'a', $text);
      $text = preg_replace('#@|À|Á|Â|Ã|Ä|Å#', 'A', $text);
      $text = preg_replace('#ì|í|î|ï#', 'i', $text);
      $text = preg_replace('#Ì|Í|Î|Ï#', 'I', $text);
      $text = preg_replace('#ð|ò|ó|ô|õ|ö#', 'o', $text);
      $text = preg_replace('#Ò|Ó|Ô|Õ|Ö#', 'O', $text);
      $text = preg_replace('#ù|ú|û|ü#', 'u', $text);
      $text = preg_replace('#Ù|Ú|Û|Ü#', 'U', $text);
      $text = preg_replace('#ý|ÿ#', 'y', $text);
      $text = preg_replace('#Ý#', 'Y', $text);
      // replace non letter or digits by -
      $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
      // trim
      $text = trim($text, '-');
        // transliterate
      $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
      // lowercase
      $text = strtolower($text);
      // remove unwanted characters
      $text = preg_replace('~[^-\w]+~', '', $text);
      if (empty($text))
        return 'n-a';
      return $text;
    }

    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository($this->entityNameSpace);
        $collection = $repository->find($id);
        return $this->render('TVFStoreBundle:Collection:show.html.twig', array(
          'collection' => $collection,
        ));
    }

    public function addAction(Request $request, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        $oldFileName = null;
        if($id == 0) {
            $collection = new Collection();
        } else {
            $repository = $em->getRepository($this->entityNameSpace);
            $collection = $repository->find($id);
            if($collection->getImage() != ''){
              $oldFileName = $collection->getImage();
              $collection->setImage(
                  new File($this->getParameter('img_dir').'/'.$collection->getImage())
              );
            }
        }
        if($oldFileName != null) {
          $collection_img_url = $oldFileName;
        } else {
          $collection_img_url = '';
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, $collection)
        ->add('title', TextType::class)
        ->add('description', TextareaType::class)
        ->add('image', FileType::class, array('label' => 'Image', 'required' => False))
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            // $file stores the uploaded PDF file
            /** @var Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $collection->getImage();
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
              $collection->setImage($fileName);
            } elseif($oldFileName != null) {
              $collection->setImage($oldFileName);
            } else {
              $collection->setImage('');
            }

            $slug = $this->slugify($collection->getTitle());
            $collection->setSlug($slug);

            $em = $this->getDoctrine()->getManager();
            $em->persist($collection);
            $em->flush();
            return $this->redirect($this->generateUrl('tvf_store_collection'));
        }
        return $this->render($this->entityNameSpace.':add.html.twig', array(
            'form' => $form->createView(),
            'collectionId' => $id,
            'img' => $collection_img_url,
        ));
    }
    public function removeAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $collection = $em->getRepository($this->entityNameSpace)->find($id);
        $em->remove($collection);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_store_collection'));
    }
  }
