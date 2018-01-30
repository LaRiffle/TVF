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
use TVF\StoreBundle\Entity\GenderUser;

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
    public function setProfileAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $data = $request->request->get('profile');
        $gender_repository = $em->getRepository('TVFAdminBundle:Gender');
        $repository = $em->getRepository('TVFStoreBundle:GenderUser');
        foreach($data['liked_genders'] as $gender_id){
          $gender = $gender_repository->find($gender_id);
          $gender_user = $repository->findOneBy(array('gender' => $gender, 'user' => $user));
          if($gender_user === null) {
            $gender_user = new GenderUser();
            $gender_user->setUser($user);
            $gender_user->setGender($gender);
          }
          $gender_user->setLikes(true);
          $em->persist($gender_user);
        }
        foreach($data['disliked_genders'] as $gender_id){
          $gender = $gender_repository->find($gender_id);
          $gender_user = $repository->findOneBy(array('gender' => $gender, 'user' => $user));
          if($gender_user === null) {
            $gender_user = new GenderUser();
            $gender_user->setUser($user);
            $gender_user->setGender($gender);
          }
          $gender_user->setLikes(false);
          $em->persist($gender_user);
        }
        $em->flush();
        $data = ['output' => 'Done'];
        return new JsonResponse($data);
    }
}
