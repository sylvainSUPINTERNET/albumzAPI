<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Picture;
use AppBundle\Entity\User;
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
        $user_token= $request->request->get('picture_user_token');


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
                    $repository = $this->getDoctrine()->getRepository(User::class);

                    $user = $repository->find($user_token);
                    if(!$user){
                        $formatted = [];
                        $formatted[] = [
                            "error" => true,
                            "message" => "User for token sended dosnt exist ! Upload failed",
                            "code" => Response::HTTP_BAD_REQUEST,
                        ];
                        // Création d'une vue FOSRestBundle
                        $view = View::create($formatted);
                        $view->setFormat('json');
                        return $viewHandler->handle($view);
                    }

                    // upload image when all is secured
                    $pictureUpload = new Picture();
                    $em = $this->getDoctrine()->getManager();
                    $pictureUpload->setPictureFile($pictureFile);
                    $pictureUpload->setUser($user);
                    $em->persist($pictureUpload);
                    $em->flush();


                    //TODO: set PROPERLY PATH with getcwd or __DIR__ !!!!!!!!
                    //copy file into public directory (to get the access for react-native on display image for user)
                    copy('C:\wamp64\www\albumzAPI\web\upload\pictures\\' . $pictureUpload->getName(),'C:\wamp64\www\albumzAPI\var\public\upload\pictures\\' . $pictureUpload->getName());

                    $formatted = [];
                    $formatted[] = [
                        "error" => false,
                        "message" => "Uploaded with success !",
                        "code" => Response::HTTP_OK,
                        "picture_id" => $pictureUpload->getId()
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




    /**
     * @Post("/pictures/my/uploaded")
     */
    public function getMyUploadedPictures(Request $request)
    {
        $viewHandler = $this->get('fos_rest.view_handler');

        $user_token= $request->request->get('pictures_uploaded_by_user_token'); //id
        if($user_token){
            $repository = $this->getDoctrine()->getRepository(User::class);
            $user = $repository->find($user_token);
            if($user){
                $formatted = [];
                if(!$user->getPictures()[0]){
                    //error param
                    $formatted = [];
                    $formatted[] = [
                        "error" => true,
                        "message" => "Vous avez pas d'images uploaded ! ",
                        "code" => Response::HTTP_NO_CONTENT
                    ];
                    // Création d'une vue FOSRestBundle
                    $view = View::create($formatted);
                    $view->setFormat('json');
                    return $viewHandler->handle($view);
                }
                foreach ($user->getPictures() as $picture) {
                    $formatted[] = [
                        'id' => $picture->getId(),
                        'name' => $picture->getName(),
                        'date_publication' => $picture->getDatePublication(),
                    ];
                }
                $view = View::create($formatted);
                $view->setFormat('json');
                return $viewHandler->handle($view);
            }else{
                //error param
                $formatted = [];
                $formatted[] = [
                    "error" => true,
                    "message" => "No user found for this token ! (please check if you clear your stringWithDigit as only digit",
                    "code" => Response::HTTP_BAD_REQUEST
                ];
                // Création d'une vue FOSRestBundle
                $view = View::create($formatted);
                $view->setFormat('json');
                return $viewHandler->handle($view);
            }

        }else{
            //error param
            $formatted = [];
            $formatted[] = [
                "error" => true,
                "message" => "Empty id or Please post a validate ID (numeric only accepted, tirm your token string as only digit if is not done !)",
                "code" => Response::HTTP_BAD_REQUEST
            ];
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');
            return $viewHandler->handle($view);
        }

    }



    /**
     * @Get("/pictures/{namePicture_extension}/display")
     */
    public function getPictureDisplay(Request $request)
    {
        $viewHandler = $this->get('fos_rest.view_handler');

        $namePicture_extension = $request->get('namePicture_extension');

        $repository = $this->getDoctrine()->getRepository(Picture::class);
        $picture = $repository->findBy(array(
            'name' => $namePicture_extension
        ));

        if(!$picture){
            //error no pic found !
            $formatted = [];
            $formatted[] = [
                "error" => true,
                "message" => "Picture not found !",
                "code" => Response::HTTP_NOT_FOUND
            ];
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');
            return $viewHandler->handle($view);
        }else{
            return $this->render('AppBundle:Pictures:pictures_display.html.twig', array(
                    'picture' => $picture[0],
                )
            );
        }

    }




    /**
     * @Delete("/pictures/delete/{picture_id}")
     */
    public function deletePicture(Request $request)
    {
        $viewHandler = $this->get('fos_rest.view_handler');


        $em = $this->getDoctrine()->getManager();
        $picture = $em->getRepository(Picture::class)->find($request->get('picture_id'));

        if(!$picture){
            //error no pic found !
            $formatted = [];
            $formatted[] = [
                "error" => true,
                "message" => "Cannot delete a non existing picture !",
                "code" => Response::HTTP_NOT_FOUND
            ];
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');
            return $viewHandler->handle($view);
        }else{

            //delete file for web/upload (server)
            //delete file for src/appbundle/public/... (client accessible)
            unlink('C:\wamp64\www\albumzAPI\web\upload\pictures\\'.$picture->getName());
            unlink('C:\wamp64\www\albumzAPI\var\public\upload\pictures\\'.$picture->getName());

            $em->remove($picture);
            $em->flush();



            $formatted = [];
            $formatted[] = [
                "error" => false,
                "message" => "Picture delete with success",
                "code" => Response::HTTP_OK
            ];
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');
            return $viewHandler->handle($view);
        }

    }


    /**
     * @Get("/pictures/{namePicture_extension}/QRCode/display")
     */
    public function getPictureQRCode(Request $request)
    {
        $viewHandler = $this->get('fos_rest.view_handler');


        $em = $this->getDoctrine()->getManager();
        $picture = $em->getRepository(Picture::class)->findBy(array("name" => $request->get('namePicture_extension')));
        if(!$picture){
            $formatted = [];
            $formatted[] = [
                "error" => true,
                "message" => "An error was occurred ! Please retry ...",
                "code" => Response::HTTP_NOT_FOUND
            ];
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');
            return $viewHandler->handle($view);

        }else{
            $options = array(
                'code'   => 'string to encode',
                'type'   => 'qrcode',
                'format' => 'png',
                'width'  => 10,
                'height' => 10,
                'color'  => array(127, 127, 127),
            );

            $barcode =
                $this->get('skies_barcode.generator')->generate($options);

            return new Response('<img src="data:image/png;base64,'.$barcode.'" />');
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
