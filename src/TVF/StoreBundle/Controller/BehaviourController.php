<?php

namespace TVF\StoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use TVF\UserBundle\Entity\User;
use TVF\RecordBundle\Entity\Creation;

class BehaviourController extends Controller
{
    /*
      Handle interactions linked to preferences
    */
    public function loveAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Creation');
        $creation = $repository->find($id);
        $user = $this->getUser();
        $creation->addLover($user);
        $em->persist($creation);
        $em->flush();
        $data = ['output' => 'Love done.'];
        return new JsonResponse($data);
    }
    public function unloveAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Creation');
        $creation = $repository->find($id);
        $user = $this->getUser();
        $creation->removeLover($user);
        $em->persist($creation);
        $em->flush();
        $data = ['output' => 'Done.'];
        return new JsonResponse($data);
    }
}
