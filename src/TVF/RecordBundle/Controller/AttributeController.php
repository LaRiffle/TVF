<?php

namespace TVF\RecordBundle\Controller;

use TVF\RecordBundle\Entity\Attribute;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AttributeController extends Controller
{
    public $entityNameSpace = 'TVFRecordBundle:Attribute';

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository($this->entityNameSpace);
        $attributes = $repository->findAll();
        $sizes = [];
        $colors = [];
        $others = [];
        foreach ($attributes as $attribute) {
          if($attribute->getCategory() == 'size'){
            $sizes[] = $attribute;
          } elseif($attribute->getCategory() == 'color'){
            $colors[] = $attribute;
          } else {
            $others[] = $attribute;
          }
        }
        return $this->render($this->entityNameSpace.':index.html.twig', array(
          'sizes' => $sizes,
          'colors' => $colors,
          'others' => $others,
        ));
    }
    public function addAction(Request $request, $id = 0, $category) {
        $em = $this->getDoctrine()->getManager();
        if($id == 0) {
            $attribute = new Attribute();
            $attribute->setCategory($category);
        } else {
            $repository = $em->getRepository($this->entityNameSpace);
            $attribute = $repository->find($id);
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, $attribute)
        ->add('name', TextType::class)
        ->add('value', TextType::class)
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isValid()) {
            $em->persist($attribute);
            $em->flush();
            return $this->redirect($this->generateUrl('tvf_record_vinyl_attributes'));
        }
        return $this->render($this->entityNameSpace.':add.html.twig', array(
            'form' => $form->createView(),
            'id' => $id,
            'category' => $category
        ));

    }
    public function removeAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $type = $em->getRepository($this->entityNameSpace)->find($id);
        $em->remove($type);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_record_vinyl_attributes'));
    }
}
