<?php

namespace TVF\AdminBundle\Controller;

use TVF\RecordBundle\Entity\Vinyl;
use TVF\RecordBundle\Entity\Artist;
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
    public function processVinyl($api, $category, $client, $line){
      $em = $this->getDoctrine()->getManager();
      $crawler = $this->container->get('tvf_admin.crawlhandler');
      $message = '';
      $vinyl_data = json_decode($line, true);
      $vinyl = new Vinyl();
      $vinyl->setCategory($category);
      $vinyl->setClient($client);
      if($vinyl_data['img'] != NULL){
        $url = $vinyl_data['img'];
        $resp = $crawler->curl_request($url);
        $image_name = md5($url).'.jpg';
        $img_path = $this->getParameter('img_dir').'/'.$image_name;
        $fp = fopen($img_path, 'wb');
        fwrite($fp, $resp);
        fclose($fp);
        $vinyl->addImage($image_name);
      }
      $vinyl->setName($vinyl_data['title']);
      $vinyl->setPrice($vinyl_data['price']);
      $vinyl->setDescription(
          'État du vinyle: '.$vinyl_data['vinyl_state']."\n".
          ($vinyl_data['sleeve_state'] != '' ? 'État de la pochette: '.$vinyl_data['sleeve_state']."\n" : '').
          $vinyl_data['details']
        );

      $artist_names = json_decode($vinyl_data['artists']);
      foreach ($artist_names as $artist_name) {
        // 1. Check if artist exists in DB
        $artist_repository = $em->getRepository('TVFRecordBundle:Artist');
        $artist = $artist_repository->findOneBy(array('name' => $artist_name));
        if($artist){
          $vinyl->addArtist($artist);
        } else {
          // 2. Else, search info on him
          $results = $api->search($artist_name, 'artist');
          if(count($results->artists->items) > 0){
            // 2.1 If Artist found by Spotify
            $spotify_id = $results->artists->items[0]->id;
            $spotify_artist_name = $results->artists->items[0]->name;
            //   2.1.1 Get reference and check if in db
            $artist = $artist_repository->findOneBy(array('name' => $spotify_artist_name));
            if($artist){
              $vinyl->addArtist($artist);
            } else {
              //   2.1.2 Add it to db if needed and add to vinyl
              $artist_info = $api->getArtist($spotify_id);
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
              $em->persist($artist);
              $vinyl->addArtist($artist);
            }
          } else {
            // 2.2 Else if artist found by discogs
            $discogs_url = 'https://www.discogs.com/fr/search/ac?searchType=artist&q='.urlencode($artist_name).'&type=a_m_r_13';
            $discogs_resp = $crawler->curl_request($discogs_url);
            $discogs_artists = json_decode($discogs_resp);
            if(count($discogs_artists) > 0){
              //   2.2.1 Get reference and check if in db
              $discogs_artist = $discogs_artists[0];
              $discogs_artist_name = $discogs_artist->title;
              //   2.2.2 Add it to db if needed
              $artist = $artist_repository->findOneBy(array('name' => $discogs_artist_name));
              if($artist){
                $vinyl->addArtist($artist);
              } else {
                $artist = new Artist();
                $artist->setName($discogs_artist->title);
                $url = $discogs_artist->thumb;
                $extension = 'jpg';
                $fileName = md5(uniqid()).'.'.$extension;
                $imagehandler = $this->container->get('tvf_store.imagehandler');
                $file = $imagehandler->download_image($url, $fileName);
                $artist->setImage($fileName);
                $em->persist($artist);
                $vinyl->addArtist($artist);
              }
            } else {
              // 2.3 Else : force manual add and report pb.
              $artist = new Artist();
              $artist->setName($artist_name);
              $artist->setImage('');
              $em->persist($artist);
              $vinyl->addArtist($artist);
              // TODO: report
              $message = 'WARNING: PB with artist: '.$artist_name;
            }
          }
        }
      }
      $em->persist($vinyl);
      $em->flush();
      $message = $vinyl->getName().' '.$message;
      return $message;
    }
    public function feedOneAction(Request $request, $id) {
      $this->denyAccessUnlessGranted(['ROLE_RECORD', 'ROLE_ADMIN'], null, 'Accès limité.');
      $em = $this->getDoctrine()->getManager();
      $session = new Session(
          $this->getParameter('client_id'),
          $this->getParameter('client_secret')
      );
      $session->requestCredentialsToken();
      $accessToken = $session->getAccessToken();
      $api = new SpotifyWebAPI();
      $api->setAccessToken($accessToken);

      $finder = new Finder();
      $finder->files()->in(__DIR__.'/../../AdminBundle/Crawl/Discogs');
      $empty = true;

      $category_repository = $em->getRepository('TVFAdminBundle:Category');
      $category = $category_repository->findOneBy(array('slug' => 'vinyle'));
      $user = $this->getUser();
      $client_repository = $em->getRepository('TVFRecordBundle:Client');
      $client = $client_repository->findOneBy(array('user' => $user));
      $message = 'Not found';
      foreach ($finder as $file) {
        $handle = fopen($file->getRealPath(), "r");
        if ($handle) {
          $i = 0;
          while (($line = fgets($handle)) !== false) {
              $i++;
              if($i == $id){
                $message = $this->processVinyl($api, $category, $client, $line);
              }
          }
        }
      }
      return new JsonResponse(array(
        'message' => $message
      ));
    }
    public function feedAction(Request $request) {
      $this->denyAccessUnlessGranted(['ROLE_RECORD', 'ROLE_ADMIN'], null, 'Accès limité.');

      $session = new Session(
          $this->getParameter('client_id'),
          $this->getParameter('client_secret')
      );
      $session->requestCredentialsToken();
      $accessToken = $session->getAccessToken();
      $api = new SpotifyWebAPI();
      $api->setAccessToken($accessToken);

      $finder = new Finder();
      $finder->files()->in(__DIR__.'/../../AdminBundle/Crawl/Discogs');
      $empty = true;

      $category_repository = $em->getRepository('TVFAdminBundle:Category');
      $category = $category_repository->findOneBy(array('slug' => 'vinyle'));
      $user = $this->getUser();
      $client_repository = $em->getRepository('TVFRecordBundle:Client');
      $client = $client_repository->findOneBy(array('user' => $user));
      foreach ($finder as $file) {
        $handle = fopen($file->getRealPath(), "r");
        if ($handle) {
          $i = 0;
          echo $i.'<br/>';
          while (($line = fgets($handle)) !== false) {
              $i++;
              $this->processVinyl($api, $category, $client, $line);
          }
        }
      }
      return $this->render('TVFAdminBundle:Crawl:show.html.twig', array(
          'vinyls' => []
      ));
    }
  }
