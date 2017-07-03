<?php
/**
 * Flashcard Controller
 */

namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Form\FlashcardType;
use Repository\FlashcardRepository;
use Repository\UserRepository;
use Repository\SetRepository;
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * Class FlashcardController
 *
 * @package Controller
 */
class FlashcardController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this, 'indexAction'])
            ->bind('flashcard_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('flashcard_index_paginated');
        $controller->get('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('flashcard_add');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->bind('flashcard_view')
            ->assert('id', '[1-9]\d*');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('flashcard_edit');
        $controller->match('{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('flashcard_delete');

        return $controller;

    }

    /**
     * Checks an access
     *
     * @param Application $app
     * @return string
     */
    public function checkAccess(Application $app)
    {
        if ($app['security.authorization_checker']->isGranted('ROLE_ADMIN'))
            return "admin";

        else if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY'))
            return "user";
        else
            return "anonymous";
    }

    /**
     * @param Application $app
     * @return mixed
     */
    public function indexAction(Application $app, $page = 1)
    {
        $access = $this->checkAccess($app);
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $username = $token->getUsername();
        }
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin($username);

        switch ($access) {
            case "admin":
                $flashcardRepository = new FlashcardRepository($app['db']);

                return $app['twig']->render(
                    'flashcard/index.html.twig',
                    [
                        'paginator' => $flashcardRepository->findAllPaginated($page),
                        'userId' => $user['id'],
                        'username' => $username,
                    ]
                );
            case "user":
                return $app->redirect($app['url_generator']->generate('set_index'));
            case "anonymous":
                return $app->redirect($app['url_generator']->generate('homepage'));
        }
    }


    /**
     * @param Application $app
     * @param Request     $request
     * @return mixed
     */
    public function addAction(Application $app, Request $request)
    {
        if($app['session']->get('setId')) {
            //if foreign key from set passed

            $access = $this->checkAccess($app);

            $token = $app['security.token_storage']->getToken();
            if (null !== $token) {
                $username = $token->getUsername();
            }
            $userRepository = new UserRepository($app['db']);
            $user = $userRepository->getUserByLogin($username);

            $flashcard['sets_id'] = $app['session']->get('setId');
            $setRepository = new SetRepository($app['db']);

            if ($access == 'admin' || ($access == 'user' && $setRepository->checkOwnership($flashcard['sets_id'], $user['id']))) {
                $form = $app['form.factory']
                    ->createBuilder(FlashcardType::class, $flashcard, ['flashcard_repository' => new FlashcardRepository($app['db']),
                        'userId' => $user['id']])
                    ->getForm();

                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $flashcardRepository = new flashcardRepository($app['db']);
                    $flashcardRepository->save($form->getData());

                    $app['session']->getFlashBag()->add(
                        'messages',
                        [
                            'type' => 'success',
                            'message' => 'message.element_successfully_added',
                        ]
                    );

                    return $app->redirect($app['url_generator']->generate('set_view', ['id' => $flashcard['sets_id']]), 301);
                }

                return $app['twig']->render(
                    'flashcard/add.html.twig',
                    [
                        'flashcard' => $flashcard,
                        'form' => $form->createView(),
                        'username' => $username,
                        'userId' => $user['id'],
                    ]
                );
            }
        }$app['session']->getFlashBag()->add(
            'messages',
            [
                'type' => 'danger',
                'message' => 'message.adding_to_elses_set_forbidden',
            ]
        );
        return $app->redirect($app['url_generator']->generate('set_view', ['id' => $flashcard['sets_id']]));
    }

    /**
     * @param Application $app
     * @param id          $id
     * @return mixed
     */
    public function viewAction(Application $app, $id)
    {
        $access = $this->checkAccess($app);

        //check if public
        $flashcardRepository = new FlashcardRepository($app['db']);
        $flashcard = $flashcardRepository->findOneById($id);

        $setRepository = new SetRepository($app['db']);
        $set = $setRepository->findOneById($flashcard['sets_id']);
        if($set['public'] && $access == "anonymous"){
            $flashcardRepository = new FlashcardRepository($app['db']);

            return $app['twig']->render(
                'flashcard/view.html.twig',
                [
                    'flashcard' => $flashcardRepository->findOneById($id),
                    'id' => $id,
                ]
            );
        }

        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $username = $token->getUsername();
        }
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin($username);

        $flashcardRepository = new FlashcardRepository($app['db']);


        if($access == 'admin' ||
            ($access == 'user' && $flashcardRepository->checkOwnership($id, $user['id'])) ||
            (($set['public'] && $access == 'user'))) {
            //check access
            return $app['twig']->render(
                'flashcard/view.html.twig',
                [
                    'flashcard' => $flashcardRepository->findOneById($id),
                    'username' => $username,
                    'userId' => $user['id'],
                    'id' => $id,
                ]
            );
        }else if ($access == 'user') {
            return $app->redirect($app['url_generator']->generate('set_index'));
        } else {
            $app->redirect($app['url_generator']->generate('homepage'));
        }
    }


    /**
     * @param Application $app
     * @param id          $id
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Application $app, $id, Request $request)
    {
        $access = $this->checkAccess($app);

        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $username = $token->getUsername();
        }
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin($username);

        $flashcardRepository = new FlashcardRepository($app['db']);

        if ($access == 'admin' || ($access == 'user' && $flashcardRepository->checkOwnership($id, $user['id']))) {

            $flashcard = $flashcardRepository->findOneById($id);

            if (!$flashcard) {
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'warning',
                        'message' => 'message.record_not_found',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('set_index'));
            }

            $form = $app['form.factory']
                ->createBuilder(FlashcardType::class, $flashcard, ['flashcard_repository' => new FlashcardRepository($app['db']),
                    'userId' => $user['id']])
                ->getForm();

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $flashcardRepository->save($form->getData());
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_edited',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('set_view', ['id' => $flashcard['sets_id'] ]), 301);
            }

            return $app['twig']->render(
                'flashcard/edit.html.twig',
                [
                    'flashcard' => $flashcard,
                    'form' => $form->createView(),
                    'username' => $username,
                    'userId' => $user['id'],
                ]
            );
        } else if ($access == 'user') {
            return $app->redirect($app['url_generator']->generate('set_index'));
        } else {
            $app->redirect($app['url_generator']->generate('homepage'));
        }
    }

    /**
     * @param Application $app
     * @param id          $id
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Application $app, $id, Request $request)
    {
        $access = $this->checkAccess($app);

        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $username = $token->getUsername();
        }
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin($username);

        $flashcardRepository = new FlashcardRepository($app['db']);

        if ($access == 'admin' || ($access == 'user' && $flashcardRepository->checkOwnership($id, $user['id']))) {

            $flashcard = $flashcardRepository->findOneById($id);

            if (!$flashcard) {
                $app['session']->getFlashBag->add(
                    'messages',
                    [
                        'type' => 'warning',
                        'message' => 'message.record_not_found',
                    ]
                );

                return $app->redirect(
                    $app['url_generator']->generate('flashcard_index')
                );
            }

            $form = $app['form.factory']
                ->createBuilder(FormType::class, $flashcard)
                ->add('id', HiddenType::class)
                ->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $flashcardRepository->delete($form->getData());

                $app['session']->getFlashBag()->add(
                    '/messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_deleted',
                    ]
                );

                return $app->redirect(
                    $app['url_generator']->generate('flashcard_index'),
                    301
                );
            }

            return $app['twig']->render(
                'flashcard/delete.html.twig',
                [
                    'form' => $form->createView(),
                    'flashcard' => $flashcard,
                    'username' => $username,
                    'userId' => $user['id'],
                ]
            );
        } else if ($access == 'user') {
            return $app->redirect($app['url_generator']->generate('set_index'));
        } else {
            $app->redirect($app['url_generator']->generate('homepage'));
        }
    }
}
