<?php

namespace TVF\StoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\Cookie;

use TVF\UserBundle\Entity\User;
use TVF\RecordBundle\Entity\Vinyl;
use TVF\StoreBundle\Entity\Cart;

class CustomerController extends Controller
{
    /*
      All actions linked to customer:
      - Manage Registration
      - Manage cart
    */

    public function cartAction($embedded = false){
      $user = $this->getUser();
      if(!$user){
        return $this->render('TVFStoreBundle:Cart:cart.html.twig', array(
          'cart' => ['products'=>[], 'total'=>0],
        ));
      }
      $em = $this->getDoctrine()->getManager();
      $repository = $em->getRepository('TVFStoreBundle:Cart');
      $cart = $repository->findOneBy(array('customer' => $user));
      if(!$cart){
        $cart = new Cart();
        $cart->setCustomer($user);
        $em->persist($cart);
        $em->flush();
      }
      $imagehandler = $this->container->get('tvf_store.imagehandler');
      foreach ($cart->getProducts() as $product) {
        $fileNames = $product->getImages();
        $product->small_image = (count($fileNames) > 0) ? $imagehandler->get_image_in_quality($fileNames[0], 'xxs') : '';
      }
      if($embedded){
        return $this->render('TVFStoreBundle:Cart:cart.html.twig', array(
          'cart' => $cart,
        ));
      }
      return $this->render('TVFStoreBundle:Cart:show.html.twig', array(
        'cart' => $cart,
      ));
    }

    /*
     Adds a product to the shopping cart
    */
    public function addToCartAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        if (!$product = $repository->find($id) or $product->getPrice() == null) {
            throw $this->createNotFoundException();
        }
        $user = $this->getUser();
        $repository = $em->getRepository('TVFStoreBundle:Cart');
        $cart = $repository->findOneBy(array('customer' =>$user));
        if(!$cart){
          $cart = new Cart();
          $cart->setCustomer($user);
        }
        if ( !$cart->getProducts()->contains($product) ) {
          $cart->addProduct($product);
        }
        $em->persist($cart);
        $em->flush();
        return $this->redirect($request->server->get('HTTP_REFERER').'?panier=rempli');
    }

    /*
       Removes a product from the shopping cart
    */
    public function removeFromCartAction($id){
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        if (!$product = $repository->find($id)) {
            throw $this->createNotFoundException();
        }
        $user = $this->getUser();
        $repository = $em->getRepository('TVFStoreBundle:Cart');
        $cart = $repository->findOneBy(array('customer' =>$user));
        if(!$cart){
          throw $this->createNotFoundException();
        }
        $cart->removeProduct($product);
        $em->persist($cart);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_store_cart'));
    }

    public function nbProductsAction(){
      $em = $this->getDoctrine()->getManager();
      $user = $this->getUser();
      $repository = $em->getRepository('TVFStoreBundle:Cart');
      $cart = $repository->findOneBy(array('customer' =>$user));
      $nbProducts = 0;
      $script = '';
      if($cart){
        $nbProducts = count($cart->getProducts());
        $script = '<script>var products_id_in_cart= [';
        $first = true;
        foreach ($cart->getProducts() as $product) {
          $id = $product->getId();
          if(!$first) {
            $script .= ',';
          } else { $first = false; }
          $script .= $id;
        }
        $script .= '];</script>';
      } else {
        $script = '<script>var products_id_in_cart= [];</script>';
      }
      $response = new Response(
          $nbProducts . $script,
          Response::HTTP_OK,
          array('Content-type' => 'text/html')
      );
      return $response;
    }

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
        $repository = $em->getRepository('TVFStoreBundle:VinylUser');
        $vinyls_loved = $repository->findBy(array('user' => $user, 'lover' => true));
        $nb_love = count($vinyls_loved);

        $response = new Response(
            $nb_love,
            Response::HTTP_OK,
            array('Content-type' => 'text/html')
        );
        //$cookie = new Cookie('foo', 'bar', time() + 600, '/', null, false, false);
        //$response->headers->setCookie($cookie);
        return $response;
      }
    }
    public function registerAction(Request $request){
        /*
          If there is no active session for this user
          - We check that it has a cookie to retrieve him ; else
          - We use the fingerprint to authenticate if it is known ; else
          - We use the fingerprint to create a new account
        */
        $opt_message = '';

        $cookies = $request->cookies;
        if ($cookies->has('user_id') && false)
        {
            $_username = $cookies->get('user_id');
        } else {
          // Get the fingerprint sent by ajax
          if($request->request->get('username')){
              $_username = $request->request->get('username');
          } else {
            $data = ['error' => 'Param invalid'];
            return new JsonResponse($data);
          }
        }
        $_password = "";

        // Retrieve the security encoder of symfony
        $factory = $this->get('security.encoder_factory');

        /// Start retrieve user
        // Retrieve the user by its username:
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository("TVFUserBundle:User")
                ->findOneBy(array('username' => $_username));

        // If cookie or fingerprint weren't enough, try an IP match
        $ip = $request->getClientIp();
        // TODO

        // If user doesn't exist, we create it. Else we load its preference
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
            $opt_message = $opt_message.' (Account created)';
        } else {
          $em = $this->getDoctrine()->getManager();
          $repository = $em->getRepository('TVFStoreBundle:VinylUser');
          $vinyls_loved = $repository->findBy(array('user' => $user, 'lover' => true));
          $nb_love = count($vinyls_loved);
        }

        // We verify the account (in our case it is almost a formality)
        $encoder = $factory->getEncoder($user);
        $salt = $user->getSalt();

        if(!$encoder->isPasswordValid($user->getPassword(), $_password, $salt)) {
            $data = ['output' => 'Username or Password not valid.'];
            return new JsonResponse($data);
        }

        // Proceed to set the user in session

        // Handle getting or creating the user entity likely with a posted form
        // "main" is name of your firewall in security.yml
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);

        // $this->get('session')->set('_security_XXXFIREWALLNAMEXXX', serialize($token));
        $this->get('session')->set('_security_main', serialize($token));

        // Fire the login event manually
        $event = new InteractiveLoginEvent($request, $token);
        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

        // Now the user is authenticated
        $data = [
          'output' => 'Welcome !'.$opt_message,
          'nb_love' => $nb_love
        ];
        return new JsonResponse($data);
    }
}
