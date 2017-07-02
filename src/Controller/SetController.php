<?php
/**
 * Set controller.
 */

namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Repository\TagRepository;
use Repository\SetRepository;
use Repository\UserRepository;
use Form\SetType;

/**
 * Class SetController
 *
 * @package Controller
 */
class SetController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this, 'indexAction'])
            ->bind('set_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('set_index_paginated');
        $controller->get('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('set_add');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->bind('set_view')
            ->assert('id', '[1-9]\d*');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('set_edit');
        $controller->match('{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('set_delete');

        return $controller;

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
            case "user":
                $setRepository = new SetRepository($app['db']);
                $sets = $setRepository->loadUserSets($user['id']);

                if( $sets && is_array($sets)) {
                    foreach ($sets as $set) {
                        $setIds[] = $set['id'];
                    }
                    foreach ($setIds as $setId) {
                        $result[] = $setRepository->findOneById($setId);
                    }
                } else {
                    $result = [];
                }
                    return $app['twig']->render(
                        'set/index.html.twig',
                        [
                            'sets' => $result,
                            'username' => $username,
                            'userId' => $user['id'],
                        ]
                    );
            case "admin":
                $setRepository = new SetRepository($app['db']);
                $paginator = $setRepository->findAllPaginated($page);
                $sets = $paginator['data'];
                if( $sets && is_array($sets)) {
                    foreach ($sets as $set) {
                        $setIds[] = $set['id'];
                    }

                    foreach ($setIds as $setId) {
                        $result[] = $setRepository->findOneById($setId);
                    }
                }
                else {
                    $result = [];
                }
                return $app['twig']->render(
                    'set/index.html.twig',
                    [
                        'paginator' => $paginator,
                        'sets' => $result,
                        'username' => $username,
                        'userId' => $user['id'],
                    ]
                );
            case "anonymous":
                return $app->redirect($app['url_generator']->generate('homepage'));
        }
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
     * @param Request     $request
     * @return mixed
     */
    public function addAction(Application $app, Request $request)
    {
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $username = $token->getUsername();
        }
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin($username);

        $set = [];
        $form = $app['form.factory']
           ->createBuilder(SetType::class, $set, ['set_repository' => new SetRepository($app['db']),
               'tag_repository' => new TagRepository($app['db']), 'userId' => $user['id']])
            ->add('users_id', HiddenType::class, ['data' => $user['id']])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $setRepository = new SetRepository($app['db']);

            $setId = $setRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('set_view', ['id' => $setId ]), 301);
        }

        return $app['twig']->render(
            'set/add.html.twig',
            [
                'set' => $set,
                'form' => $form->createView(),
                'username' => $username,
                'userId' => $user['id'],
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
        $access = $this->checkAccess($app);

        $setRepository = new SetRepository($app['db']);
        $set = $setRepository->findOneById($id);
        //check if public
        if($set['public'] && $access == "anonymous"){
            $flashcards = $setRepository->findLinkedFlashcards($id);

            return $app['twig']->render(
                'set/view.html.twig',
                [
                    'set' => $set,
                    'flashcards' => $flashcards,
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

        if ($access == 'admin' ||
            ($access == 'user' && $setRepository->checkOwnership($id, $user['id'])) ||
            (($set['public'] && $access == 'user'))) {
            //check access

            $app['session']->set('setId', $id);

            $flashcards = $setRepository->findLinkedFlashcards($id);

            return $app['twig']->render(
                'set/view.html.twig',
                [
                    'set' => $set,
                    'username' => $username,
                    'flashcards' => $flashcards,
                    'id' => $id,
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
     * @param Set id      $id
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

        $setRepository = new SetRepository($app['db']);

        if ($access == 'admin' || ($access == 'user' && $setRepository->checkOwnership($id, $user['id']))) {
            $setRepository = new SetRepository($app['db']);
            $set = $setRepository->findOneById($id);

            if (!$set) {
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
                ->createBuilder(SetType::class, $set, ['set_repository' => new SetRepository($app['db']), 'tag_repository' => new TagRepository($app['db'])])
                ->add('users_id', HiddenType::class, ['data' => $user['id']])
                ->getForm();

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $setRepository->save($form->getData());
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_added',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('set_index'), 301);
            }

            return $app['twig']->render(
                'set/edit.html.twig',
                [
                    'set' => $set,
                    'form' => $form->createView(),
                    'username' => $username,
                    'userId' => $user['id'],
                ]
            );
        } else if ($access == 'user') {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'danger',
                    'message' => 'message.editing_elses_sets_forbidden',
                ]
            );
            return $app->redirect($app['url_generator']->generate('set_view', ['id' => $id ]));
        } else {
            $app->redirect($app['url_generator']->generate('homepage'));
        }
    }

    /**
     * @param Application $app
     * @param Set id      $id
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

        $setRepository = new SetRepository($app['db']);

        if ($access == 'admin' || ($access == 'user' && $setRepository->checkOwnership($id, $user['id']))) {
            $setRepository = new SetRepository($app['db']);
            $set = $setRepository->findOneById($id);

            if (!$set) {
                $app['session']->getFlashBag->add(
                    'messages',
                    [
                        'type' => 'warning',
                        'message' => 'message.record_not_found',
                    ]
                );

                return $app->redirect(
                    $app['url_generator']->generate('set_index')
                );
            }

            $form = $app['form.factory']
                ->createBuilder(FormType::class, $set)
                ->add('id', HiddenType::class)
                ->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $setRepository->delete($form->getData());

                $app['session']->getFlashBag()->add(
                    '/messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_deleted',
                    ]
                );

                return $app->redirect(
                    $app['url_generator']->generate('set_index'),
                    301
                );
            }

            return $app['twig']->render(
                'set/delete.html.twig',
                [
                    'form' => $form->createView(),
                    'username' => $username,
                    'userId' => $user['id'],
                    'set' => $set,
                ]
            );
        } else if ($access == 'user') {
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'danger',
                        'message' => 'message.deleting_elses_set_forbidden',
                    ]
                );
                return $app->redirect($app['url_generator']->generate('set_view', ['id' => $id ]));
        } else {
            $app->redirect($app['url_generator']->generate('homepage'));
        }
    }
}
