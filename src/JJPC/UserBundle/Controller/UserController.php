<?php

namespace JJPC\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use JJPC\UserBundle\Entity\User;
use JJPC\UserBundle\Form\UserType;

class UserController extends Controller
{
    public function indexAction()
    {
       # return $this->render('JJPCUserBundle:Default:index.html.twig', array('name' => $name));
       #return new Response('Bienvenido al modulo de usuarios');
       
       $conexion = $this->getDoctrine()->getManager();
        
        $users = $conexion->getRepository('JJPCUserBundle:User')->findAll();
        
        /*
        $res = 'Lista de Usuarios. <br />';
        
        foreach ($users as $user) 
        {
            $res .= "Usuario: " . $user->getUsername() . " - Email: " . $user->getEmail() . "<br />"; 
        }
        
        return new Response($res);
        */
        
        return $this->render('JJPCUserBundle:User:index.html.twig', array('users' => $users));
    }
    
    public function addAction()
    {
        $user = new User();
        
        $form = $this->createCreateForm($user);
        
        return $this->render('JJPCUserBundle:User:add.html.twig', array('form' => $form->createView()));
        
        
    }
    
    private function createCreateForm(User $entity)
    {
        $form = $this->createForm(new UserType(), $entity, array(
            'action' => $this->generateUrl('jjpc_user_create'),
            'method' => 'POST'    
        ));
        
        return $form;
    }
    
    public function createAction(Request $request)
    {
        $user = new User();
        $form = $this->createCreateForm($user);
        $form->handleRequest($request);
        
        if($form->isValid())
        {
                $password = $form->get('password')->getData();
                
                $passwordConstraint = new Assert\NotBlank();
                $errorList = $this->get('validator')->validate($password, $passwordConstraint);
                
                if(count($errorList) == 0)
                {
                    $encoder = $this->container->get('security.password_encoder');
                    $encoded = $encoder->encodePassword($user, $password);
                    
                    $user->setPassword($encoded);
                
                    $jp = $this->getDoctrine()->getManager();
                    $jp->persist($user);
                    $jp->flush();
                    
                    $successMessage = $this->get('translator')->trans('The user has been created.');
                    $this->addFlash('mensaje', $successMessage);
             
                    return $this->redirectToRoute('jjpc_user_index');
       
                }
                else
                {
                    $errorMessage = new FormError($errorList[0]->getMessage());
                    $form->get('password')->addError($errorMessage);
                }
                
        
        }
        
            return $this->render('JJPCUserBundle:User:add.html.twig', array('form' => $form->createView()));
    }
    
    public function editAction($id)
    {
        $jp = $this->getDoctrine()->getManager();
        $user = $jp->getRepository('JJPCUserBundle:User')->find($id);
        
        if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found.');
            throw $this->createNotFoundException($messageException);
            
        }
        
        $form = $this->createEditForm($user);
        
        return $this->render('JJPCUserBundle:User:edit.html.twig', array('user' => $user, 'form' => $form->createView()));
        
    }
    
    private function createEditForm(User $entity)
    {
        $form = $this->createForm(new UserType(), $entity, array(
            'action' => $this->generateUrl(
                'jjpc_user_update', array(
                    'id' => $entity->getId())), 'method' => 'PUT'));
        
        return $form;
    }
    
    
    public function updateAction($id, Request $request)
    {
        $jp = $this->getDoctrine()->getManager();
        $user = $jp->getRepository('JJPCUserBundle:User')->find($id);
        
        if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found.');
            throw $this->createNotFoundException($messageException);
            
        }
        
        $form = $this->createEditForm($user);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid())
        {
           // $password = $form->createEditForm($user);//para recuperar el password
           // print_r($password); muestra la clave modificada
            //exit(); cierra y no permite continuar ejecutando para mostrar el password
            
            $password =$form->get('password')->getData();
            
            if(!empty($password))
            {
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $password);
                $user->setPassword($encoded);
            }
            else
            {
                $recoverPass = $this->recoverPass($id);
                
               // print_r($recoverPass);
                //exit();
                
                $user->setPassword($recoverPass[0]['password']);
            }
            
            if($form->get('role')->getData() == 'ROLE_ADMIN')
            {
                $user->setIsActive(1);
            }
            
            $jp->flush();
            
            $successMessage = $this->get('translator')->trans('The user has been modified.');
            $this->addflash('mensaje', $successMessage);
            
            return $this->redirectToRoute('jjpc_user_edit', array('id' => $user->getId()));
        }
        
        return $this->render('JJPCUserBundle:User:edit.html.twig', array('user' => $user, 'form' => $form->createView()));
    }
    
    public function recoverPass($id)
    {
        $jp = $this->getDoctrine()->getManager();
        $query = $jp->createQuery(
            'SELECT u.password
            FROM JJPCUserBundle:User u
            WHERE u.id = :id'
            )->setParameter('id',$id);
            
            $currentPass = $query->getResult();
            
            return $currentPass;
    }
    
    public function viewAction($id)
    {
        $repository = $this->getDoctrine()->getRepository('JJPCUserBundle:User');
        
        $user = $repository->find($id);
        #$user = $repository->findOneById($id);
        
       # return new Response('Usuario: ' . $user->getUsername() . ' con Email: ' . $user->getEmail() );
       
       if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found.');
            throw $this->createNotFoundException($messageException);
            
        }
        
        $deleteForm = $this->createDeleteForm($user);
        
        return $this->render('JJPCUserBundle:User:view.html.twig', array('user' => $user, 'delete_form'
            => $deleteForm->createView()));
    }
    
    
    private function createDeleteForm($user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('jjpc_user_delete', array('id' => $user->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }
    
    public function deleteAction(Request $request, $id)
    {
        $jp = $this->getDoctrine()->getManager();
        
        $user = $jp->getRepository('JJPCUserBundle:User')->find($id);
        
        if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found.');
            throw $this->createNotFoundException($messageException);
            
        }
        
        $form = $this->createDeleteForm($user);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid())
        {
            $jp->remove($user);
            $jp->flush();
            
            $successMessage = $this->get('translator')->trans('The user has been deleted.');
            $this->addflash('mensaje', $successMessage);
            
            return $this->redirectToRoute('jjpc_user_index');
            
        }
    }
    
  /*  public function articlesAction($page){
        
        return new Response('Este es mi articulo '. $page);
        
    }
    
  */
}
