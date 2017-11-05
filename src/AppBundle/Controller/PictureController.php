<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Picture;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\View; // Utilisation de la vue de FOSRestBundle


class PictureController extends Controller
{


    //GET pictures
    public function getPicturesAction($id, Request $request)
    {
        /**
         * @Get("/pictures/list")
         */

        //get data
        $pictures = $this->getDoctrine()
            ->getRepository(Picture::class)
            ->findAll();
        // Récupération du view handler
        $viewHandler = $this->get('fos_rest.view_handler');

        if (!$pictures) {
            //Return error
            $formatted = [];
            $formatted[] = [
                "error" => true,
                "message" => "Aucune images trouvés !",
                "code" => Response::HTTP_NO_CONTENT
            ];
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');
            return $viewHandler->handle($view);

        } else {
            //Return images
            $formatted = [];
            foreach ($pictures as $picture) {
                $formatted[] = [
                    'id' => $picture->getId(),
                    'name' => $picture->getName(),
                    'date_publication' => $picture->getDatePublication(),
                ];
            }
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');

            // Gestion de la réponse
            return $viewHandler->handle($view);
        }
    }

    //POST picture
    /**
     * @Post("/pictures/add")
     */
    public function postPictureAction(Request $request)
    {

        //var_dump($request->files->get('picture_upload')); for file use files not request
        //var_dump($request->request->get('test')); if want other data


        $pictureFile = $request->files->get('picture_upload');
        $viewHandler = $this->get('fos_rest.view_handler');


        if($pictureFile){
            if ($pictureFile->getMimeType() === "image/png" || $pictureFile->getMimeType() === "image/jpg" || $pictureFile->getMimeType() === "image/jpeg") {
                //check size
                $mimeType = $pictureFile->getMimeType();
                if ($pictureFile->getSize() < 700000) {

                    //rename pic (hash + original nom) for security then upload

                    /*
                    $fileName = $pictureFile->getClientOriginalName();
                    $extension = $this->getExtension($mimeType);
                    $nameWithouExtension = preg_replace('/\\.[^.\\s]{3,4}$/', '', $fileName);
                    $salt = $this->generateRandomString();
                    $secureName = sha1($salt.$nameWithouExtension).$extension;
                    */


                    // upload image when all is secured
                    $pictureUpload = new Picture();
                    $em = $this->getDoctrine()->getManager();
                    $pictureUpload->setPictureFile($pictureFile);
                    $em->persist($pictureUpload);
                    $em->flush();

                    $formatted = [];
                    $formatted[] = [
                        "error" => false,
                        "message" => "Uploaded with success !",
                        "code" => Response::HTTP_OK
                    ];
                    // Création d'une vue FOSRestBundle
                    $view = View::create($formatted);
                    $view->setFormat('json');
                    return $viewHandler->handle($view);



                } else {
                    $formatted = [];
                    $formatted[] = [
                        "error" => true,
                        "message" => "Size picture is too big",
                        "code" => Response::HTTP_ACCEPTED
                    ];
                    // Création d'une vue FOSRestBundle
                    $view = View::create($formatted);
                    $view->setFormat('json');
                    return $viewHandler->handle($view);
                }

            } else {
                $formatted = [];
                $formatted[] = [
                    "error" => true,
                    "message" => "Only jpg / jpeg / png allowed",
                    "code" => Response::HTTP_ACCEPTED
                ];
                // Création d'une vue FOSRestBundle
                $view = View::create($formatted);
                $view->setFormat('json');
                return $viewHandler->handle($view);
            }
        }else{
            //file empty
            $formatted = [];
            $formatted[] = [
                "error" => true,
                "message" => "No picture to upload",
                "code" => Response::HTTP_NO_CONTENT
            ];
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');
            return $viewHandler->handle($view);
        }

    }




    //utils
    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function getExtension ($mime_type){

        $extensions = array(
            'image/jpeg' => '.jpeg',
            'image/jpg' => '.jpg',
            'image/png' => '.png',
        );

        // Add as many other Mime Types / File Extensions as you like

        return $extensions[$mime_type];

    }



}
