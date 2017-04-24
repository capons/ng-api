<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\User;
use AppBundle\Entity\Group;
use AppBundle\Entity\Address;
use AppBundle\Entity\Image;
use AppBundle\Entity\Country;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Validator\Constraints\Url;

class UserController extends Controller
{

    /**
     * @Route("/users", name="test")
     * @Method({"GET"})
     */
    public function indexAction(Request $request)
    {

        $response = array();
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User');
        $all_user = $user->findAll();
        $serializer = $this->get('jms_serializer');
        $response['body'] = $all_user;//$array;
        $res = new Response($serializer->serialize($response,'json'));
        $res->headers->set('Content-Type', 'application/json');
        $res->headers->set('Access-Control-Allow-origin','*');
        return $res;
    }

    /**
     * @Route("/users", name="save_user")
     * @Method({"POST"})
     */
    public function addUserAction(Request $request)
    {

       //get file from request
        $file = $request->files->get('file');
        $fileName = $this->get('app.file_uploader')->upload($file);

        $formData = $request->request->all();

        //save form data
        //$request->query->get('id'); retrive post get data example
        $user = new User();
        $address = new Address();
        $user_permission = new Group();
        $image = new Image();

        //response to API
        $response = array();
        $serializer = $this->get('jms_serializer');

        //NEED VALIDATE FORM DATA IN FUTURE

        $repository = $this->getDoctrine()->getRepository('AppBundle:User');

        $check_duplicat_name = $repository->findOneByUsername($formData['name']);


        //check duplicat
        if($check_duplicat_name){

            $response['error'] = 'Username already exist!';
            $array = $serializer->toArray($response);
            return new JsonResponse($array);


        }

        $check_duplicat_email = $repository->findOneByEmail($formData['email']);
        //check duplicat
        if($check_duplicat_email){

            $response['error'] = 'Email already exist!';
            $array = $serializer->toArray($response);
            return new JsonResponse($array);
        }

        $em = $this->getDoctrine()->getManager();
        $user_role = $em->getRepository('AppBundle:Role')
            ->loadRoleByRolename('ROLE_USER'); //my custom repository

        $user_country = $em->getRepository('AppBundle:Country')
            ->loadCountryByName($formData['country']);
        //*/
        //save form data to database
       // $image->setPath($form_data['file_path']);
        $image->setPath($request->getScheme() . '://' . $request->getHttpHost().'/upload/'.$fileName);
        $address->setAddress($formData['address']);
        $pwd=$user->getPassword();
        $encoder=$this->container->get('security.password_encoder');
        $pwd=$encoder->encodePassword($user, $pwd);
        $user->setPassword($pwd);
        $user->setUsername($formData['name']);
        $user->setEmail($formData['email']);

        //set user relation
        $user->setAddress($address);
        $user->setCountry($user_country);
        $user->setImage($image);
        //add user permission
        $user_permission->setName($formData['name']);
        $user_permission->setUserRole($user_role);

        $user->addGroup($user_permission);
        $em = $this->getDoctrine()->getManager();

        $em->persist($address);
        $em->persist($user);
        $em->persist($user_permission);
        $em->persist($image);
        $em->flush();

        //return last save object
        $last_object = $repository->find($user->getId());

        // $serializer = $serializer::create()->build();
        $array = $serializer->toArray($last_object);
        $response['body'] = $array;

        return new Response($serializer->serialize($response,'json'));
        die();
    }

    /**
     * @Route("/users/{id}", name="delete_user")
     * @Method({"DELETE"})
     */
    public function deleteUserAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')->find($id);
        $em->remove($user);
        $em->flush();
        $res = new Response(json_encode(true));
        return $res;
    }

    //public route to display image
    /**
     * @Route("/test", name="test_image")
     * @Method({"GET"})
     */
    public function test(request $request)
    {
        $file = $this->getParameter('upload_path').'/'.'1a2e824562e22ec6956a9fdb4a0328ee.jpeg';
        $response = new BinaryFileResponse($file);
        // you can modify headers here, before returning
        return $response;
    }
}
