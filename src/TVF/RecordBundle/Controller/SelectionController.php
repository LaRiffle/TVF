<?php

namespace TVF\RecordBundle\Controller;

use TVF\RecordBundle\Entity\Selection;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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

    public function certify_authorship($selection_id) {
      if (!$this->get('security.authorization_checker')->isGranted('ROLE_RECORD')
       && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
        return false;
      }

      if($this->get('security.authorization_checker')->isGranted('ROLE_RECORD')){
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('TVFRecordBundle:Client');
        $client = $repository->findOneBy(array('user' => $user));

        $repository = $em->getRepository('TVFRecordBundle:Selection');
        $selection = $repository->findOneBy(array('id'=>$selection_id, 'client' => $client));
        if($selection == null) {
          return false;
        }
      }
      return true;
    }

    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository($this->entityNameSpace);
        $selection = $repository->find($id);
        return $this->render('TVFRecordBundle:Selection:show.html.twig', array(
          'selection' => $selection,
        ));
    }

    public function addAction(Request $request, $id = 0) {
        $this->denyAccessUnlessGranted(['ROLE_RECORD','ROLE_ADMIN'], null, 'Accès limité.');

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $repository = $em->getRepository('TVFRecordBundle:Client');
        $client = $repository->findOneBy(array('user' => $user));
        $oldFileName = null;
        if($id == 0) {
            $selection = new Selection();
        } else {
            if(!$this->certify_authorship($id)){
              throw new AccessDeniedException('Accès limité.');
            }
            $selection = $em->getRepository($this->entityNameSpace)->find($id);
            // Overwrite client if admin
            if($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')){
              $client = $selection->getClient();
            }
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
        ->add('image', FileType::class, array('label' => 'Image', 'required' => ($id == 0)))
        ->add('crop_image', TextType::class, array(
          'mapped' => false,
          'required' => false,
        ))
        ->add('vinyls', EntityType::class, array(
                'class'        => 'TVFRecordBundle:Vinyl',
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ( $client ){
                    return $er->createQueryBuilder('e')
                        ->innerJoin('e.category', 'c')
                        ->innerJoin('e.client', 'r')
                        ->where("c.slug = 'vinyle'")
                        ->andWhere("r.id = :client_id")
                        ->setParameter('client_id', $client->getId())
                        ->orderBy('e.id', 'DESC');
                },
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

              // test if crop image hasn't been saved, if yes it is prioritary
              $data = $request->get('form');
              if($data['crop_image'] != ''){
                  $fileName = $data['crop_image'];
                  rename(
                    $this->getParameter('img_dir').'_tmp/'.$data['crop_image'],
                    $this->getParameter('img_dir').'/'.$fileName
                  );
              } else {
                // Else move the file to the directory where images are stored
                $file->move(
                    $this->getParameter('img_dir'),
                    $fileName
                );
              }

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

            if($id == 0){
              $user = $this->getUser();
              $repository = $em->getRepository('TVFRecordBundle:Client');
              $client = $repository->findOneBy(array('user' => $user));
              $selection->setClient($client);
            }
            $em->persist($selection);
            $em->flush();
            if($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')){
              return $this->redirect($this->generateUrl('tvf_store_selection'));
            }
            return $this->redirect($this->generateUrl('tvf_record_selection'));
        }
        return $this->render($this->entityNameSpace.':add.html.twig', array(
            'form' => $form->createView(),
            'selectionId' => $id,
            'img' => $selection_img_url,
            'client_id' => $client->getId(),
        ));
    }
    public function removeAction(Request $request, $id){
        $this->denyAccessUnlessGranted(['ROLE_RECORD','ROLE_ADMIN'], null, 'Accès limité.');
        // Check the record owns the selection
        if(!$this->certify_authorship($id)){
          throw new AccessDeniedException('Accès limité.');
        }
        $em = $this->getDoctrine()->getManager();
        $selection = $em->getRepository($this->entityNameSpace)->find($id);
        $em->remove($selection);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_record_selection'));
    }
    public function uploadAction(Request $request){
      $base64img = $request->request->get('image');
      $title = $request->request->get('title');
      $extension = 'jpg';
      if(strpos($base64img, 'data:image/jpeg;base64') !== FALSE){
        $base64img = str_replace('data:image/jpeg;base64,', '', $base64img);
      } elseif(strpos($base64img, 'data:image/png;base64') !== FALSE){
        $base64img = str_replace('data:image/png;base64,', '', $base64img);
        $extension = 'png';
      } else {
        return new JsonResponse([
          'error' => $base64img
        ]);
      }
      $data = base64_decode($base64img);
      $filename = $this->getParameter('img_dir').'_tmp/'.$title.'.'.$extension;
      file_put_contents($filename, $data);
      return new JsonResponse([
        'uploaded' => $title.'.'.$extension
      ]);
    }
  }
