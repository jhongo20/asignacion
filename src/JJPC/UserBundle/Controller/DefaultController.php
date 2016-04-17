<?php

namespace JJPC\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('JJPCUserBundle:Default:index.html.twig', array('name' => $name));
    }
}
