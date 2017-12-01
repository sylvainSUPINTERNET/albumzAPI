<?php
/**
 * Created by PhpStorm.
 * User: SYLVAIN
 * Date: 01/12/2017
 * Time: 11:12
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Picture;
use AppBundle\Entity\User;
use AppBundle\Entity\Album;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\View; // Utilisation de la vue de FOSRestBundle

class AlbumController extends Controller
{
    //POST : add new album
    /**
     * @Post("/album/add")
     */
    public function postAlbumAction(Request $request){
        $viewHandler = $this->get('fos_rest.view_handler');

        $album_name = $request->request->get('album_name');
        $album_description = $request->request->get('album_description');
        $album_user_id = $request->request->get('album_user_id');

        if($album_name !== "" && $album_description !== "" && !empty($album_user_id)){
            $user = $this->getDoctrine()
                ->getRepository(User::class)
                ->find($album_user_id);
            if($user){
                //user found, so create new album with that id + save
                $album = new Album();
                $em = $this->getDoctrine()->getManager();
                $album->setUser($user);
                $album->setDescription($album_description);
                $album->setName($album_name);
                $em->persist($album);
                $em->flush();

                $formatted = [];
                $formatted[] = [
                    "error" => false,
                    "message" => "Album added with success !",
                    "code" => Response::HTTP_OK
                ];
                // Création d'une vue FOSRestBundle
                $view = View::create($formatted);
                $view->setFormat('json');
                return $viewHandler->handle($view);

            }else{
                $formatted = [];
                $formatted[] = [
                    "error" => true,
                    "message" => "This id correspond to no user ...",
                    "code" => Response::HTTP_NOT_FOUND
                ];
                // Création d'une vue FOSRestBundle
                $view = View::create($formatted);
                $view->setFormat('json');
                return $viewHandler->handle($view);
            }
        }else{
            $formatted = [];
            $formatted[] = [
                "error" => true,
                "message" => "Fields are not filled correctly",
                "code" => Response::HTTP_BAD_REQUEST
            ];
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');
            return $viewHandler->handle($view);
        }




    }
}