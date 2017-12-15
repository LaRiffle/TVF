<?php

namespace TVF\StoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use TVF\UserBundle\Entity\User;
use TVF\StoreBundle\Entity\Creation;

class CustomerController extends Controller
{
    /*
      Register or automatically retrieve a customer based on his fingerprint
    */
    public function register_or_loginAction(Request $request){
      // If not connected
      if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
        return $this->render('TVFStoreBundle:Utils:customer_script.html.twig');
      } else {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Creation');
        $creations = $repository->findAll();
        $nb_love = 0;
        foreach ($creations as $creation) {
          foreach ($creation->getLovers() as $lover) {
            if($lover->getUsername() == $user->getUsername()){
              $nb_love++;
            }
          }
        }
        return new Response(
            $nb_love,
            Response::HTTP_OK,
            array('Content-type' => 'text/html')
        );
      }
    }
    public function registerAction(Request $request){
        // This data is most likely to be retrieven from the Request object (from Form)
        // But to make it easy to understand ...
        if($request->request->get('username')){
            $_username = $request->request->get('username');
        } else {
          $data = ['error' => 'Param invalid'];
          return new JsonResponse($data);
        }
        $_password = "";

        // Retrieve the security encoder of symfony
        $factory = $this->get('security.encoder_factory');

        /// Start retrieve user
        // Let's retrieve the user by its username:
        // Or by yourself
        $user = $this->getDoctrine()->getManager()->getRepository("TVFUserBundle:User")
                ->findOneBy(array('username' => $_username));
        /// End Retrieve user

        // Check if the user exists ! Else we create it
        $opt_message = '';
        $nb_love = 0;
        if(!$user){
            $em = $this->getDoctrine()->getManager();
            $repository = $em->getRepository('TVFUserBundle:User');
            $user = new User;
            $user->setUsername($_username);
            $user->setPassword($_password);
            $user->setFirstname('Web User');
            $user->setSurname('Fingerprinted');
            $user->setSalt('');
            $user->setRoles(array('ROLE_USER'));
            $em->persist($user);
            $em->flush();
            $opt_message = ' (Account created)';
        } else {
          $em = $this->getDoctrine()->getManager();
          $repository = $em->getRepository('TVFRecordBundle:Creation');
          $creations = $repository->findAll();
          foreach ($creations as $creation) {
            foreach ($creation->getLovers() as $lover) {
              if($lover->getUsername() == $user->getUsername()){
                $nb_love++;
              }
            }
          }
        }

        /// Start verification
        $encoder = $factory->getEncoder($user);
        $salt = $user->getSalt();

        if(!$encoder->isPasswordValid($user->getPassword(), $_password, $salt)) {
            $data = ['output' => 'Username or Password not valid.'];
            return new JsonResponse($data);
        }
        /// End Verification

        // The password matches ! then proceed to set the user in session

        //Handle getting or creating the user entity likely with a posted form
        // The third parameter "main" can change according to the name of your firewall in security.yml
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);

        // If the firewall name is not main, then the set value would be instead:
        // $this->get('session')->set('_security_XXXFIREWALLNAMEXXX', serialize($token));
        $this->get('session')->set('_security_main', serialize($token));

        // Fire the login event manually
        $event = new InteractiveLoginEvent($request, $token);
        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

        /*
         * Now the user is authenticated !
         * Do what you need to do now, like render a view, redirect to route etc.
         */
        $data = [
          'output' => 'Welcome !'.$opt_message,
          'nb_love' => $nb_love
        ];
        return new JsonResponse($data);
    }
}
