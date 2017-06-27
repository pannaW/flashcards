<?php
/**
 * RegisterController
 *
 */

namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Form\RegisterType;
use Repository\UserRepository;

/**
 * Class RegisterController
 *
 * @package Controller
 */
class RegisterController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->post('/', [$this, 'registerAction' ])
            ->method('POST|GET')
            ->bind('register');

        return $controller;
    }

    /**
     * @param Application $app
     * @param Request     $request
     * @return mixed
     */
    public function registerAction(Application $app, Request $request)
    {
            $user = [];
            $form = $app['form.factory']
                ->createBuilder(RegisterType::class, $user, ['user_repository' => new UserRepository($app['db'])])
                ->getForm();
            $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $data['password'] = $app['security.encoder.bcrypt']
                ->encodePassword($data['password'], '');

            $userRepository = new UserRepository($app['db']);
            $userRepository->save($data);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.registration_succedded',
                ]
            );

            return $app->redirect($app['url_generator']->generate('auth_login'), 301);
        }

        return $app['twig']->render(
            'registration/register.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }
}
