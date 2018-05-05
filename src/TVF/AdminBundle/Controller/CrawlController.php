<?php

namespace TVF\AdminBundle\Controller;

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

use Symfony\Component\Finder\Finder;

use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

class CrawlController extends Controller
{
    public $entityNameSpace = 'TVFRecordBundle:Vinyl';

    public function dropCollectionAction(){
      $em = $this->getDoctrine()->getManager();
      $repository = $em->getRepository($this->entityNameSpace);
      $vinyls = $repository->findAll();
      $interaction_repository = $em->getRepository('TVFStoreBundle:VinylUser');
      foreach ($vinyls as $vinyl) {
        $interactions = $interaction_repository->findBy(array('vinyl' => $vinyl));
        foreach ($interactions as $interaction) {
          $em->remove($interaction);
        }
        $em->remove($vinyl);
      }
      $em->flush();
      return $this->redirect($this->generateUrl('tvf_store_explore'));
    }
    public function feedAction(Request $request) {
      $this->denyAccessUnlessGranted(['ROLE_RECORD', 'ROLE_ADMIN'], null, 'Accès limité.');
      $artist = 'Guetta';
      $session = new Session(
          $this->getParameter('client_id'),
          $this->getParameter('client_secret')
      );
      $session->requestCredentialsToken();
      $accessToken = $session->getAccessToken();

      $api = new SpotifyWebAPI();
      $api->setAccessToken($accessToken);
      $results = $api->search($artist, 'artist');

      foreach ($results->artists->items as $artist) {
          echo $artist->name, '<br>';
      }
      return new JsonResponse(['results' => $results]);

      //--------
      $finder = new Finder();
      $finder->files()->in(__DIR__.'/../Crawl');
      $vinyls = [];
      foreach ($finder as $file) {
        $handle = fopen($file->getRealPath(), "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $vinyl_data = json_decode($line, true);

                $url = $vinyl_data['img'];
                $ch = curl_init($url);
                $image_name = md5(uniqid()).'.jpg';
                $img_path = $this->getParameter('img_dir').'/'.$image_name;
                $fp = fopen($img_path, 'wb');
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_exec($ch);
                curl_close($ch);
                fclose($fp);
                $vinyl_data['img'] = $image_name;
                $vinyls[] = $vinyl_data;

            }
            fclose($handle);
        }
      }
      $em = $this->getDoctrine()->getManager();
      $repository = $em->getRepository($this->entityNameSpace);
      $collection = $em->getRepository('TVFRecordBundle:Collection')->findOneBy(array('slug' => 'fnac'));
      $category = $em->getRepository('TVFAdminBundle:Category')->find(1);
      foreach ($vinyls as $vinyl_data) {
        $vinyl = new Vinyl();
        $vinyl->setName($vinyl_data['title']);
        $vinyl->setTitle1($vinyl_data['artist']);
        $text = ($vinyl_data['description'] != null ? $vinyl_data['description'] : '(undefined)');
        $vinyl->setText1($text);
        $vinyl->addImage($vinyl_data['img']);
        // fake
        $vinyl->setOnsold(false);
        $vinyl->setCollection($collection);
        $vinyl->setCategory($category);
        $em->persist($vinyl);
      }
      $em->flush();
      return $this->render('TVFAdminBundle:Crawl:show.html.twig', array(
          'vinyls' => $vinyls
      ));
    }
  }
