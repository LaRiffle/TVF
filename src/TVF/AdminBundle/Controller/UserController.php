<?php

namespace TVF\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use TVF\UserBundle\Entity\User;

class UserController extends Controller
{
    /*
      Controller to deal with all the users:
      - Admin (on init)
      - Record store owners aka User
    */
    public function createAdminAction($key){
      /* Create the first account */
      if($key == $this->getParameter('admin_init')){
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('TVFUserBundle:User');
        // On crée l'utilisateur
        $user = new User;

        // Le nom d'utilisateur et le mot de passe sont identiques pour l'instant
        $user->setUsername($this->getParameter('admin_username'));
        $user->setPassword($this->getParameter('admin_password  '));
        $user->setFirstname($this->getParameter('admin_username'));
        $user->setSurname($this->getParameter('admin_username'));

        // On ne se sert pas du sel pour l'instant
        $user->setSalt('');
        $user->setRoles(array('ROLE_ADMIN'));

        // On le persiste
        $em->persist($user);
        $em->flush();
        return new Response(
              '<html><body>Admin created</body></html>'
          );
      } else {
        return new Response(
              '<html><body>Access denied</body></html>'
          );
      }
    }
    public function addUserAction(Request $request, $id = 0) {
        $em = $this->getDoctrine()->getManager();
        if($id == 0) {
            $attribute = new User();
        } else {
            $repository = $em->getRepository('TVFUserBundle:User');
            $attribute = $repository->find($id);
        }
        $form = $this->get('form.factory')->createBuilder(FormType::class, $attribute)
        ->add('username', TextType::class)
        ->add('firstname', TextType::class)
        ->add('surname', TextType::class)
        ->add('password', TextType::class)
        ->add('salt', TextType::class)
        ->add('roles', ChoiceType::class, array(
            'choices' => array(
                'ROLE_USER' => 'ROLE_RECORD'
            ),
            'multiple' => true,
            'expanded' => true
        ))
        ->add('save',	SubmitType::class)
        ->getForm();

        $form->handleRequest($request);
        if($form->isValid()) {
            #$encoded = $encoder->encodePassword($user, $attribute->getPassword());
            #$attribute->setPassword($encoded);
            $attribute->setSalt('');
            $em->persist($attribute);
            $em->flush();
            return $this->redirect($this->generateUrl('tvf_admin_users_show'));
        }
        return $this->render('TVFAdminBundle:User:add.html.twig', array(
            'form' => $form->createView(),
            'id' => $id,
        ));

    }
    public function removeUserAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $type = $em->getRepository('TVFUserBundle:User')->find($id);
        $em->remove($type);
        $em->flush();
        return $this->redirect($this->generateUrl('tvf_admin_users_show'));
    }
    public function showUsersAction(){
      $em = $this->getDoctrine()->getManager();
      $repository = $em->getRepository('TVFUserBundle:User');
      $users = $repository->findAll();
      return $this->render('TVFAdminBundle:User:show.html.twig', array(
          'users' => $users
      ));
    }
}
