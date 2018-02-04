<?php

namespace TVF\StoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class BoxController extends Controller
{
    /*
      All basic pages related to the box offers
    */
    public $entityNameSpace = 'TVFStoreBundle:Box';

    public function discoverAction()
    {
        $text = [];
        $this->handler = $this->container->get('tvf_store.fieldhandler');
        $text['amateur']['title'] = $this->handler->fetchText('amateur:title');
        $text['amateur']['price'] = $this->handler->fetchText('amateur:price');
        $text['amateur']['content'] = $this->handler->fetchText('amateur:content');
        $text['amateur']['image'] = $this->handler->fetchImage('amateur:image');
        $text['connaisseur']['title'] = $this->handler->fetchText('connaisseur:title');
        $text['connaisseur']['price'] = $this->handler->fetchText('connaisseur:price');
        $text['connaisseur']['content'] = $this->handler->fetchText('connaisseur:content');
        $text['connaisseur']['image'] = $this->handler->fetchImage('connaisseur:image');

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFAdminBundle:Category');
        $subscription = $repository->findOneBy(array('slug'=>'abonnement'));
        $repository = $em->getRepository('TVFRecordBundle:Vinyl');
        $subscriptions = $repository->findBy(array('category' => $subscription));
        return $this->render($this->entityNameSpace.':discover.html.twig', array(
          'data' => $text,
          'subscriptions' => $subscriptions
        ));
    }
}
