<?php

namespace TVF\RecordBundle\Controller;

use TVF\RecordBundle\Entity\Artist;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

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
    public function searchAction($spotify_id)
    {
      $session = new Session(
          $this->getParameter('client_id'),
          $this->getParameter('client_secret')
      );
      $session->requestCredentialsToken();
      $accessToken = $session->getAccessToken();

      $api = new SpotifyWebAPI();
      $api->setAccessToken($accessToken);
      try{
        $artist_info = $api->getArtist($spotify_id);
        //var_dump($artist_info);
        $artist = new Artist();
        $artist->setName($artist_info->name);
        if(count($artist_info->images) >= 3){
          $artist->setImage($artist_info->images[2]->url);
        } elseif (count($artist_info->images) > 0) {
          $artist->setImage($artist_info->images[0]->url);
        } else {
          $artist->setImage('');
        }
        // TODO: Add types
        $em = $this->getDoctrine()->getManager();
        $em->persist($artist);
        $em->flush();
        return new JsonResponse([
          'id' => $artist->getId(),
          'name' => $artist->getName()
        ]);
      } catch(\SpotifyWebAPI\SpotifyWebAPIException  $e){
        return new JsonResponse(['Error' => 'Invalid spotify id: '.$spotify_id]);
      }
    }
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository($this->entityNameSpace);
        $artist = $repository->find($id);
        return $this->render($this->entityNameSpace.':show.html.twig', array(
          'artist' => $artist,
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
        ->add('bio', TextareaType::class, array(
                'required'     => false))
        ->add('image', FileType::class, array('label' => 'Image', 'required' => False))
        ->add('types', EntityType::class, array(
                'class'        => 'TVFAdminBundle:Type',
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

        $repository = $em->getRepository('TVFAdminBundle:Gender');
        $genders = $repository->findAll();
        $typeRepository = $em->getRepository('TVFAdminBundle:Type');
        foreach ($genders as $gender) {
          $gender->types = $typeRepository->whereGender($gender->getId());
        }

        return $this->render($this->entityNameSpace.':add.html.twig', array(
            'form' => $form->createView(),
            'id' => $id,
            'img' => $artist_img_url,
            'genders' => $genders,
        ));

    }
    public function curl_request($url){
      $curl = curl_init($url);
      curl_setopt($curl, CURLOPT_URL, $url);

      $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
      $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
      $header[] = "Cache-Control: max-age=0";
      $header[] = "Connection: keep-alive";
      $header[] = "Keep-Alive: 300";
      $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
      $header[] = "Accept-Language: en-us,en;q=0.5";
      $header[] = "Pragma: "; //browsers keep this blank.

      curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3');
      curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
      curl_setopt($curl, CURLOPT_REFERER, 'http://www.google.com');
      curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
      curl_setopt($curl, CURLOPT_AUTOREFERER, true);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl, CURLOPT_TIMEOUT, 10);
      $sourcecode = curl_exec($curl); //execute the curl command
      if (!$sourcecode)
      {
        echo "cURL error number:" .curl_errno($curl);
        echo "cURL error:" . curl_error($curl);
        exit;
      }
      curl_close($curl);
      return $sourcecode;
    }

    public function autoAddAction(Request $request, $name){
      $discogs_url = 'https://www.discogs.com/fr/search/ac?searchType=artist&q='.urlencode($name).'&type=a_m_r_13';
      $discogs_resp = $this->curl_request($discogs_url);
      $discogs_artists = json_decode($discogs_resp);
      $artist = new Artist();
      if(count($discogs_artists) > 0){
        $discogs_artist = $discogs_artists[0];
        $artist->setName($discogs_artist->title);
        $url = $discogs_artist->thumb;
        $extension = 'jpg';
        $fileName = md5(uniqid()).'.'.$extension;
        $imagehandler = $this->container->get('tvf_store.imagehandler');
        $file = $imagehandler->download_image($url, $fileName);
        $artist->setImage($fileName);
      } else {
        $artist->setName($name);
        $artist->setImage('');
      }
      $em = $this->getDoctrine()->getManager();
      $em->persist($artist);
      $em->flush();

      return new JsonResponse(['status' => '200']);
    }
    public function removeAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $artist = $em->getRepository($this->entityNameSpace)->find($id);
        $em->remove($artist);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_record_artist'));
    }
}
