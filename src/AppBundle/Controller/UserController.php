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
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\View; // Utilisation de la vue de FOSRestBundle


class UserController extends Controller
{
    //TODO: sur image / album (futur) AJOUTER UN CHAMP ID_USER qui prendra sur les fetch coté mobile l'id de l'user co en session pour l'envoyer
    //TODO: React-native => ajouter une scene Login / register et utiliser cette api
    //TODO: Une fois l'user login => lui attribuer un token (quon va creer nous même) et qui servira d'id pour la création de picture + album (ajouter un champ user_id sur ces deux entité)


    //TODO: Add field id_user (correspond to token store into session after register or login return by API (token : ... password sha256)
    //TODO: on upload() (phone) add the session token into api /pictures/add et setter this session value into db (donc envoyer dans le post une new value, et la recup coté api pour setter)
    //POST user (register)
    /**
     * @Post("/user/register")
     */

    public function postUserRegisterAction(Request $request){

        $viewHandler = $this->get('fos_rest.view_handler');

        $user_email = $request->request->get('user_email');
        $user_password = $request->request->get('user_password');
        $user_firstname = $request->request->get('user_firstname');
        $user_lastname = $request->request->get('user_lastname');

        if($user_email && $user_password && $user_firstname && $user_lastname){

            //Verif user dosnt exist
            $repository = $this->getDoctrine()->getRepository(User::class);

            $userExist = $repository->findOneBy(array(
                'firstname' => $user_firstname,
                'lastname' => $user_lastname,
                'email' => $user_email,
                'password' => (hash('sha256', $user_password))
            ));

            if($userExist != null){
                $formatted = [];
                $formatted[] = [
                    "error" => true,
                    "message" => "User already exist, please try again",
                    "code" => Response::HTTP_NOT_ACCEPTABLE,
                ];
                // Création d'une vue FOSRestBundle
                $view = View::create($formatted);
                $view->setFormat('json');
                return $viewHandler->handle($view);

            }else{
                $user = new User();
                $em = $this->getDoctrine()->getManager();
                $user->setFirstname($user_firstname);
                $user->setLastname($user_lastname);
                $user->setEmail($user_email);
                $user->setPassword(hash('sha256', $user_password));
                $em->persist($user);
                $em->flush();

                $idForToken = strval($user->getId());
                $firstnameForToken = $user->getFirstname();
                $token = $idForToken . $firstnameForToken;

                $formatted = [];
                $formatted[] = [
                    "error" => false,
                    "message" => "User register with success",
                    "code" => Response::HTTP_OK,
                    "token" => $token
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
                "message" => "Error while registration new user",
                "code" => Response::HTTP_NO_CONTENT
            ];
            // Création d'une vue FOSRestBundle
            $view = View::create($formatted);
            $view->setFormat('json');
            return $viewHandler->handle($view);
        }

    }

    //POST user (login)
    /**
     * @Post("/user/login")
     */

    public function postUserLoginAction(Request $request){
        $viewHandler = $this->get('fos_rest.view_handler');

        $user_email = $request->request->get('user_email');
        $user_password = $request->request->get('user_password');
        $user_firstname = $request->request->get('user_firstname');
        $user_lastname = $request->request->get('user_lastname');


        if($user_email && $user_password && $user_firstname && $user_lastname) {

            //Verif user dosnt exist
            $repository = $this->getDoctrine()->getRepository(User::class);

            $userExist = $repository->findOneBy(array(
                'firstname' => $user_firstname,
                'lastname' => $user_lastname,
                'email' => $user_email,
                'password' => (hash('sha256', $user_password))
            ));

            if ($userExist == null) {
                $formatted = [];
                $formatted[] = [
                    "error" => true,
                    "message" => "User not found",
                    "code" => Response::HTTP_NOT_ACCEPTABLE,
                ];
                // Création d'une vue FOSRestBundle
                $view = View::create($formatted);
                $view->setFormat('json');
                return $viewHandler->handle($view);

            } else {
                $idForToken = strval($userExist->getId());
                $firstnameForToken = $userExist->getFirstname();
                $token = $idForToken . $firstnameForToken;

                $formatted = [];
                $formatted[] = [
                    "error" => false,
                    "message" => "User login with success, your token is " . $userExist->getPassword(),
                    "code" => Response::HTTP_OK,
                    "token" => $token
                ];
                // Création d'une vue FOSRestBundle
                $view = View::create($formatted);
                $view->setFormat('json');
                return $viewHandler->handle($view);
            }
        }

        }




}
