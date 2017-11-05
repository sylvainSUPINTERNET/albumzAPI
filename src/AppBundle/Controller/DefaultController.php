<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Picture;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;



class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $picture = new Picture();

        $form = $this->createFormBuilder($picture)
            ->add('picture', FileType::class)
            ->add('save', SubmitType::class, array('label' => 'upload'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $dataPosted = $form->getData();
            $picture->setPictureFile($dataPosted->getPictureFile());

            $em->persist($picture);



            $em->flush();

        }
        return $this->render('AppBundle:Home:index.html.twig', array(
                "testForm" => $form->createView(),
            )
        );

    }

    /**
     * @Route("/picture/testDisplay", name="display_test")
     */

    public function testDisplayAction(Request $request){

        $pictures = $this->getDoctrine()
            ->getRepository(Picture::class)
            ->findAll();

        if (!$pictures) {
            throw $this->createNotFoundException(
                'No product found for id '.$pictures
            );
        }

        return $this->render('AppBundle:Test:product_test_display.html.twig', array(
                "products" => $pictures,
            )
        );
    }
}
