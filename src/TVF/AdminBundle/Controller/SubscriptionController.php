<?php

namespace TVF\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use TVF\RecordBundle\Entity\Vinyl;
use TVF\AdminBundle\Entity\Category;

class SubscriptionController extends Controller
{
    /*
      Handle subscription
    */
    public function addAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $subscription = new Vinyl();
        $name = $request->request->get('form_name');
        $subscription->setName($name);
        $price = $request->request->get('form_price');
        $subscription->setPrice($price);
        // set type amateur or connaisseur
        $type = $request->query->get('type');
        $subscription->setDescription($type);

        $repository = $em->getRepository('TVFAdminBundle:Category');
        $category = $repository->findOneBy(array('slug'=>'abonnement'));
        $subscription->setCategory($category);

        $repository = $em->getRepository('TVFRecordBundle:Client');
        $client = $repository->findOneBy(array('slug' => 'lebonsillon'));
        $subscription->setClient($client);
        $em->persist($subscription);
        $em->flush();

        return $this->redirect($this->generateUrl('tvf_store_discover_box'));
    }

    public function removeAction($id){
        $em = $this->getDoctrine()->getManager();
        $subscription = $em->getRepository('TVFRecordBundle:Vinyl')->find($id);
        $em->remove($subscription);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_store_discover_box'));
    }
}
