<?php

namespace TVF\StoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use TVF\UserBundle\Entity\User;
use TVF\RecordBundle\Entity\Vinyl;
use TVF\StoreBundle\Entity\VinylUser;

class BehaviourController extends Controller
{
    /*
      Handle interactions linked to preferences
    */
    public function loveAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        $vinyl = $repository->find($id);
        $user = $this->getUser();
        $repository = $em->getRepository('TVFStoreBundle:VinylUser');
        $vinyluser = $repository->findOneBy(array('vinyl' => $vinyl, 'user' => $user));
        if($vinyluser === null) {
          $vinyluser = new VinylUser();
          $vinyluser->setUser($user);
          $vinyluser->setVinyl($vinyl);
        }
        $vinyluser->setLover(true);
        $em->persist($vinyluser);
        $em->flush();
        $data = ['output' => 'Love done.'];
        return new JsonResponse($data);
    }
    public function unloveAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        $vinyl = $repository->find($id);
        $user = $this->getUser();
        $repository = $em->getRepository('TVFStoreBundle:VinylUser');
        $vinyluser = $repository->findOneBy(array('vinyl' => $vinyl, 'user' => $user));
        if($vinyluser === null) {
          $vinyluser = new VinylUser();
          $vinyluser->setUser($user);
          $vinyluser->setVinyl($vinyl);
        }
        $vinyluser->setLover(false);
        $em->persist($vinyluser);
        $em->flush();
        $data = ['output' => 'Done.'];
        return new JsonResponse($data);
    }
}
