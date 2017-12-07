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
    public function postAlbumAction(Request $request)
    {
        $viewHandler = $this->get('fos_rest.view_handler');

        $album_name = $request->request->get('album_name');
        $album_description = $request->request->get('album_description');
        $album_user_id = $request->request->get('album_user_id');

        if ($album_name !== "" && $album_description !== "" && !empty($album_user_id)) {
            $user = $this->getDoctrine()
                ->getRepository(User::class)
                ->find($album_user_id);
            if ($user) {
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

            } else {
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
        } else {
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

    //Post : list of albums (require user_id)

    /**
     * @Post("/album/list")
     */
    public function getAlbumsAction(Request $request)
    {
        $viewHandler = $this->get('fos_rest.view_handler');

        $user_id = $request->request->get('user_id');
        //$user_id = 40;
        //debug ca + en get

        if ($user_id) {
            //find user else error
            $user = $this->getDoctrine()
                ->getRepository(User::class)
                ->find($user_id);
            if ($user) {
                if ($user->getAlbums()[0] === null) {
                    $formatted = [];
                    $formatted[] = [
                        "error" => true,
                        "message" => "You dont have any albums yet !",
                        "code" => Response::HTTP_BAD_REQUEST
                    ];
                    // Création d'une vue FOSRestBundle
                    $view = View::create($formatted);
                    $view->setFormat('json');
                    return $viewHandler->handle($view);
                } else {

                    $formatted = [];
                    $formattedPic = [];
                    //var_dump($user->getAlbums()[0]->getPictures()[1]->getName());
                    // var_dump($formattedPicsForAlbum);
                    foreach ($user->getAlbums() as $album) {
                        /*
                        if($album->getPictures()[0] !== null){
                            var_dump($album->getPictures()[0]->getId());
                            $formattedPicsForAlbum[] = [
                                'id' => $picture->getId(),
                                'picture_name' => $picture->getName(),
                                'picture_date_publication' => $picture->getDatePublication(),
                            ];
                        }
                        */
                        $formatted[] = [
                            'user' => $user->getFirstname(),
                            'user_id' => $user_id,
                            'album_id' => $album->getId(),
                            'name' => $album->getName(),
                            'description' => $album->getDescription(),
                            'album_date_creation' => $album->getDateCreation(),
                            // 'album_pictures' => $formattedPicsForAlbum,
                        ];

                    }
                    // Création d'une vue FOSRestBundle
                    $view = View::create($formatted);
                    $view->setFormat('json');
                    // Gestion de la réponse
                    return $viewHandler->handle($view);
                }

            } else {
                $formatted = [];
                $formatted[] = [
                    "error" => true,
                    "message" => "User not found for this id ! (please check if your id send is the rigth type )",
                    "code" => Response::HTTP_BAD_REQUEST
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
                "message" => "Incorrect id, are you sure your id is a correct format ?",
                "code" => Response::HTTP_BAD_REQUEST
            ];
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');
            return $viewHandler->handle($view);
        }
    }

    /**
     * @Post("/album/delete")
     */
    public function postAlbumRemoveAction(Request $request)
    {
        $viewHandler = $this->get('fos_rest.view_handler');
        $album_id = $request->request->get('album_id');
        if ($album_id) {
            $em = $this->getDoctrine()->getManager();
            $album = $em->getRepository(Album::class)->find($album_id);
            if ($album) {

                $em = $this->getDoctrine()->getManager();
                $em->remove($album);
                $em->flush();

                $formatted = [];
                $formatted[] = [
                    "error" => false,
                    "message" => "Album delete with success",
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
                    "message" => "Album not found !",
                    "code" => Response::HTTP_BAD_REQUEST
                ];
                // Création d'une vue FOSRestBundle
                $view = View::create($formatted);
                $view->setFormat('json');
                return $viewHandler->handle($view);
            }
        }
        $formatted = [];
        $formatted[] = [
            "error" => true,
            "message" => "Incorrect id, are you sure your id is a correct format ?",
            "code" => Response::HTTP_BAD_REQUEST
        ];
        // Création d'une vue FOSRestBundle
        $view = View::create($formatted);
        $view->setFormat('json');
        return $viewHandler->handle($view);
    }

    /**
     * @Post("/album/create")
     */
    public function postAlbumCreateAction(Request $request)
    {
        $viewHandler = $this->get('fos_rest.view_handler');

        $album_name = $request->request->get('album_name');
        $album_description = $request->request->get('album_description');
        $album_idUser = $request->request->get('user_id');

        $em = $this->getDoctrine()->getManager();

        if ($album_name && $album_description) {
            $user = $em->getRepository(User::class)->find($album_idUser);
            if ($user) {
                $newAlbum = new Album();
                $newAlbum->setName($album_name);
                $newAlbum->setDescription($album_description);
                $newAlbum->setUser($user);
                $em->persist($newAlbum);
                $em->flush();

                $formatted = [];
                $formatted[] = [
                    "error" => false,
                    "message" => "Album  created with success !",
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
                    "message" => "Please, check if user exist",
                    "code" => Response::HTTP_BAD_REQUEST
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
                "message" => "Please fill all fields !",
                "code" => Response::HTTP_BAD_REQUEST
            ];
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');
            return $viewHandler->handle($view);
        }
    }

    /**
     * @Post("/album/get/pictures")
     */
    public function postAlbumGetPictures(Request $request)
    {
        $viewHandler = $this->get('fos_rest.view_handler');
        $album_id = $request->request->get('album_id');


        if ($album_id) {
            $em = $this->getDoctrine()->getManager();
            $album = $em->getRepository(Album::class)->find($album_id);
            if ($album) {
                $formatted = [];
                // var_dump($album->getPictures()[1]);

                foreach ($album->getPictures() as $picture) {
                    $formatted[] = [
                        'picture_id' => $picture->getId(),
                        'picture_name' => $picture->getName(),
                        'date_publication' => $picture->getDatePublication(),
                    ];
                }
                // Création d'une vue FOSRestBundle
                if(sizeof($formatted) === 0){
                    $formatted[] = [
                        "error" => true,
                        "message" => "No piture for this album",
                        "code" => Response::HTTP_BAD_REQUEST
                    ];
                    // Création d'une vue FOSRestBundle
                    $view = View::create($formatted);
                    $view->setFormat('json');
                    return $viewHandler->handle($view);
                }else{

                    $view = View::create($formatted);
                    $view->setFormat('json');
                    return $viewHandler->handle($view);
                }


            } else {
                $formatted = [];
                $formatted[] = [
                    "error" => true,
                    "message" => "Album not found for this id !",
                    "code" => Response::HTTP_BAD_REQUEST
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
                "message" => "Incorrect id, are you sure your id is a correct format ?",
                "code" => Response::HTTP_BAD_REQUEST
            ];
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');
            return $viewHandler->handle($view);
        }


    }


    /**
     * @Post("/album/picture/add")
     */


    public function postAddPictureToAlbumAction(Request $request)
    {
        $viewHandler = $this->get('fos_rest.view_handler');
        $em = $this->getDoctrine()->getManager();


        //$album_id = $request->request->get('album_id');
        $album_name = $request->request->get('album_name');
        $picture_id = $request->request->get('picture_id');

        $album = $em->getRepository(Album::class)->findBy(array('name' => $album_name));

        if(sizeof($album) > 0){
            if ($picture_id) {
                $picture = $em->getRepository(Picture::class)->find($picture_id);
                if ($picture) {
                    $album = $em->getRepository(Album::class)->find($album[0]->getId());
                    if ($album) {
                        //return error if picture already inside
                        $album->addPicture($picture);
                        $em->persist($album);
                        $em->flush();
                        $formatted = [];
                        $formatted[] = [
                            "error" => false,
                            "message" => "Picture add in the album with success !",
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
                            "message" => "Album not found !",
                            "code" => Response::HTTP_BAD_REQUEST
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
                        "message" => "Picture not found !",
                        "code" => Response::HTTP_BAD_REQUEST
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
                    "message" => "Invalide picture or album id sended !",
                    "code" => Response::HTTP_BAD_REQUEST
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
                "message" => "No album found for the posted name !",
                "code" => Response::HTTP_BAD_REQUEST
            ];
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');
            return $viewHandler->handle($view);
        }

    }

    /**
     * @Post("/album/picture/delete")
     */
    public function postDeletePictureToAlbumAction(Request $request)
    {
        $viewHandler = $this->get('fos_rest.view_handler');
        $em = $this->getDoctrine()->getManager();


        $album_id = $request->request->get('album_id');
        $picture_id = $request->request->get('picture_id');

        if ($album_id && $picture_id) {
            $picture = $em->getRepository(Picture::class)->find($picture_id);
            if ($picture) {
                $album = $em->getRepository(Album::class)->find($album_id);
                if ($album) {
                    //return error if picture already inside
                    $album->removePicture($picture);
                    $em->persist($album);
                    $em->flush();
                    $formatted = [];
                    $formatted[] = [
                        "error" => false,
                        "message" => "Picture delete in  the album with success !",
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
                        "message" => "Album not found !",
                        "code" => Response::HTTP_BAD_REQUEST
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
                    "message" => "Picture not found !",
                    "code" => Response::HTTP_BAD_REQUEST
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
                "message" => "Invalide picture or album id sended !",
                "code" => Response::HTTP_BAD_REQUEST
            ];
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');
            return $viewHandler->handle($view);
        }
    }

}