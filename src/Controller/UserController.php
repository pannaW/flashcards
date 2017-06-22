<?php
/**
 * User Controller
 */

namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Form\UserType;
use Repository\UserRepository;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * Class UserController
 *
 * @package Controller
 */
class UserController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this, 'indexAction'])
            ->bind('user_index');
        $controller->get('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('user_add');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->bind('user_view')
            ->assert('id', '[1-9]\d*');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('user_edit');
        $controller->match('{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('user_delete');

        return $controller;

    }


    /**
     * @param Application $app
     * @return mixed
     */
    public function indexAction(Application $app)
    {
        $userRepository = new UserRepository($app['db']);

        return $app['twig']->render(
            'user/index.html.twig',
            ['user' => $userRepository->findAll()]
        );
    }


    /**
     * @param Application $app
     * @param Request     $request
     * @return mixed
     */
    public function addAction(Application $app, Request $request)
    {
        $user = [];
        $form = $app['form.factory']
            ->createBuilder(UserType::class, $user)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository = new userRepository($app['db']);
            $userRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('user_index'), 301);
        }

        return $app['twig']->render(
            'user/add.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );

    }

    /**
     * @param Application $app
     * @param id          $id
     * @return mixed
     */
    public function viewAction(Application $app, $id)
    {
        $userRepository = new UserRepository($app['db']);

        return $app['twig']->render(
            'user/view.html.twig',
            [
                'user' => $userRepository->findOneById($id),
                'id' => $id,
            ]
        );

    }

    /**
     * @param Application $app
     * @param id          $id
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Application $app, $id, Request $request)
    {
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->findOneById($id);

        if (!$user) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('user_index'));
        }

        $form = $app['form.factory']
            ->createBuilder(UserType::class, $user)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->save($form->getData());
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('user_index'), 301);
        }

        return $app['twig']->render(
            'user/edit.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @param Application $app
     * @param id          $id
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Application $app, $id, Request $request)
    {
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->findOneById($id);

        if (!$user) {
            $app['session']->getFlashBag->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('user_index')
            );
        }

        $form = $app['form.factory']
            ->createBuilder(FormType::class, $user)
            ->add('id', HiddenType::class)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                '/messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('user_index'),
                301
            );
        }

        return $app['twig']->render(
            'user/delete.html.twig',
            [
                'form' => $form->createView(),
                'user' => $user,
            ]
        );
    }
}
