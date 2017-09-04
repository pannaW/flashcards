<?php
/**
 * User Controller
 */

namespace Controller;

use Form\UserDataType;
use Form\UserType;
use Form\ResetPasswordType;
use Form\AdminResetPasswordType;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Repository\UserRepository;
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
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('user_index_paginated');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->bind('user_view')
            ->assert('id', '[1-9]\d*');
        $controller->match('/{id}/edit_data', [$this, 'editDataAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('edit_data');
        $controller->match('/{id}/reset_password', [$this, 'resetPasswordAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('reset_password');
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
     * @param int $id     Element id
     * @param int $userId User id
     * @return bool
     */
    public function checkOwnership($id, $userId)
    {
        return ($id == $userId) ? true : false;
    }

    /**
     * Index action
     *
     * @param Application $app
     * @param int         $page Number of a current page
     * @return mixed
     */
    public function indexAction(Application $app, $page = 1)
    {
        if (!($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY'))) {
            return $app->redirect($app['url_generator']->generate('homepage'));
        } elseif (!($app['security.authorization_checker']->isGranted('ROLE_ADMIN'))) {
            return $app->redirect($app['url_generator']->generate('set_index'));
        } else {
            $token = $app['security.token_storage']->getToken();
            if (null !== $token) {
                $username = $app['security.token_storage']->getToken()->getUsername();
            } else {
                return $app->redirect($app['url_generator']->generate('homepage'));
            }
            $userRepository = new UserRepository($app['db']);
            $user = $userRepository->getUserByLogin($username);

            $userRepository = new UserRepository($app['db']);

            return $app['twig']->render(
                'user/index.html.twig',
                [
                    'paginator' => $userRepository->findAllPaginated($page),
                    'username' => $username,
                    'userId' => $user['id'],

                ]
            );
        }
    }

    /**
     * View action
     *
     * @param Application $app
     * @param int         $id  Element id
     * @return mixed
     */
    public function viewAction(Application $app, $id)
    {
        if (!($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY'))) {
            return $app->redirect($app['url_generator']->generate('homepage'));
        } else {
            $token = $app['security.token_storage']->getToken();
            if (null !== $token) {
                $username = $app['security.token_storage']->getToken()->getUsername();
            } else {
                return $app->redirect($app['url_generator']->generate('homepage'));
            }
            $userRepository = new UserRepository($app['db']);
            $user = $userRepository->getUserByLogin($username);

            if ($app['security.authorization_checker']->isGranted('ROLE_ADMIN') ||
                ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY') && $this->checkOwnership($id, $user['id']))) {
                return $app['twig']->render(
                    'user/view.html.twig',
                    [
                        'user' => $userRepository->findOneById($id),
                        'userData' => $userRepository->findUserDataByUserId($id),
                        'id' => $id,
                        'username' => $username,
                        'userId' => $user['id'],
                    ]
                );
            } elseif ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {

                return $app->redirect($app['url_generator']->generate('set_index'));
            }
        }

        return $app->redirect($app['url_generator']->generate('set_index'));
    }

    /**
     * Edit user's login and role
     *
     * @param Application $app
     * @param int         $id      Element id
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Application $app, $id, Request $request)
    {
        if (!($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY'))) {
            return $app->redirect($app['url_generator']->generate('homepage'));
        } elseif (!($app['security.authorization_checker']->isGranted('ROLE_ADMIN'))) {
            return $app->redirect($app['url_generator']->generate('set_index'));
        } else {
            $token = $app['security.token_storage']->getToken();
            if (null !== $token) {
                $username = $app['security.token_storage']->getToken()->getUsername();
            } else {
                return $app->redirect($app['url_generator']->generate('homepage'));
            }
            $userRepository = new UserRepository($app['db']);
            $currentuser = $userRepository->getUserByLogin($username);

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
                    ->createBuilder(UserType::class, $user, ['user_repository' => new UserRepository($app['db']), 'userId' => $user['id']])
                    ->add('id', HiddenType::class, ['data' => $id])
                    ->getForm();

                $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $userRepository->updateUser($form->getData());
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_edited',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('user_index'), 301);
            }

                return $app['twig']->render(
                    'user/edit.html.twig',
                    [
                        'id' => $id,
                        'form' => $form->createView(),
                        'userId' => $currentuser['id'],
                        'username' => $username,
                        'user' => $user,
                    ]
                );
        }
    }

    /**
     * Edit user's data
     *
     * @param Application $app
     * @param int         $id      id of user being modyfied
     * @param Request     $request
     * @var   array       $user    Logged user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editDataAction(Application $app, $id, Request $request)
    {
        if (!($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY'))) {
            return $app->redirect($app['url_generator']->generate('homepage'));
        } else {
            $token = $app['security.token_storage']->getToken();
            if (null !== $token) {
                $username = $app['security.token_storage']->getToken()->getUsername();
            } else {
                return $app->redirect($app['url_generator']->generate('homepage'));
            }
            $userRepository = new UserRepository($app['db']);
            $user = $userRepository->getUserByLogin($username);

            if ($app['security.authorization_checker']->isGranted('ROLE_ADMIN') ||
                ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY') && $this->checkOwnership($id, $user['id']))) {
                $userData = $userRepository->findUserDataByUserId($id);

                if (!$userData) {
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
                    ->createBuilder(
                        UserDataType::class,
                        $userData,
                        ['user_repository' => new UserRepository($app['db']), 'userId' => $id ]
                    )
                    ->getForm();

                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $userRepository->editUserData($form->getData());
                    $app['session']->getFlashBag()->add(
                        'messages',
                        [
                            'type' => 'success',
                            'message' => 'message.element_successfully_edited',
                        ]
                    );
                    if ($app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
                            return $app->redirect($app['url_generator']->generate('user_index'), 301);
                    } else {
                        return $app->redirect($app['url_generator']->generate('set_index'), 301);
                    }
                }

                return $app['twig']->render(
                    'user/edit_data.html.twig',
                    [
                        'id' => $id,
                        'form' => $form->createView(),
                        'userId' => $user['id'],
                        'username' => $username,
                    ]
                );
            } elseif ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
                return $app->redirect($app['url_generator']->generate('set_index'));
            }
        }

        return $app->redirect($app['url_generator']->generate('set_index'));
    }

    /**
     * Reset password action
     *
     * @param Application $app
     * @param int         $id      Element id
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function resetPasswordAction(Application $app, $id, Request $request)
    {
        if (!($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY'))) {
            return $app->redirect($app['url_generator']->generate('homepage'));
        } else {
            $token = $app['security.token_storage']->getToken();
            if (null !== $token) {
                $username = $app['security.token_storage']->getToken()->getUsername();
            } else {
                return $app->redirect($app['url_generator']->generate('homepage'));
            }
            $userRepository = new UserRepository($app['db']);
            $currentUser = $userRepository->getUserByLogin($username);

            if ($app['security.authorization_checker']->isGranted('ROLE_ADMIN') ||
                ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')
                    && $this->checkOwnership($id, $currentUser['id']))) {
                $user = $userRepository->findOneById($id);

                if (!$user) {
                    $app['session']->getFlashBag()->add(
                        'messages',
                        [
                            'type' => 'warning',
                            'message' => 'message.record_not_found',
                        ]
                    );

                    return $app->redirect($app['url_generator']->generate('set_index'));
                }

                $form = $app['form.factory'];
                if (!($app['security.authorization_checker']->isGranted('ROLE_ADMIN'))) {
                    $form = $app['form.factory']
                        ->createBuilder(ResetPasswordType::class)
                        ->getForm();
                    $form->handleRequest($request);
                } elseif ($app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
                    $form = $app['form.factory']
                        ->createBuilder(AdminResetPasswordType::class)
                        ->getForm();
                    $form->handleRequest($request);
                }
                if ($form->isSubmitted() && $form->isValid()) {
                    $data = $form->getData();
                    if (!($app['security.authorization_checker']->isGranted('ROLE_ADMIN'))) {
                        if ($app['security.encoder.bcrypt']->isPasswordValid($user['password'], $data['old_password'], '')) {
                            //if old password correct
                            $data['password'] = $app['security.encoder.bcrypt']
                                ->encodePassword($data['new_password'], '');
                            unset($data['old_password']);
                            unset($data['new_password']);

                            $data['id'] = $user['id'];
                            $userRepository->resetPassword($data);

                            $app['session']->getFlashBag()->add(
                                'messages',
                                [
                                    'type' => 'success',
                                    'message' => 'message.password_successfully_changed',
                                ]
                            );

                            return $app->redirect($app['url_generator']->generate('set_index'), 301);
                        } else {
                            $app['session']->getFlashBag()->add(
                                'messages',
                                [
                                    'type' => 'warning',
                                    'message' => 'messsage.old_password_wrong',
                                ]
                            );
                        }
                    } elseif ($app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
                        $data['password'] = $app['security.encoder.bcrypt']
                            ->encodePassword($data['new_password'], '');

                        unset($data['new_password']);

                        $data['id'] = $user['id'];
                        $userRepository->resetPassword($data);

                        $app['session']->getFlashBag()->add(
                            'messages',
                            [
                                'type' => 'success',
                                'message' => 'message.password_successfully_changed',
                            ]
                        );

                        return $app->redirect($app['url_generator']->generate('set_index'), 301);
                    }
                }

                return $app['twig']->render(
                    'user/reset_password.html.twig',
                    [
                        'username' => $username,
                        'id' => $id,
                        'userId' => $currentUser['id'],
                        'form' => $form->createView(),
                    ]
                );
            } elseif ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
                return $app->redirect($app['url_generator']->generate('set_index'));
            }
        }

        return $app->redirect($app['url_generator']->generate('set_index'));
    }


    /**
     * Delete Action
     *
     * @param Application $app
     * @param int         $id
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Application $app, $id, Request $request)
    {
        if (!($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY'))) {
            return $app->redirect($app['url_generator']->generate('homepage'));
        } elseif (!($app['security.authorization_checker']->isGranted('ROLE_ADMIN'))) {
            return $app->redirect($app['url_generator']->generate('set_index'));
        } else {
            $token = $app['security.token_storage']->getToken();
            if (null !== $token) {
                $username = $app['security.token_storage']->getToken()->getUsername();
            } else {
                return $app->redirect($app['url_generator']->generate('homepage'));
            }
            $userRepository = new UserRepository($app['db']);
            $currentUser = $userRepository->getUserByLogin($username);

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
                    'messages',
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
                    'username' => $username,
                    'userId' => $currentUser['id'],
                ]
            );
        }
    }
}
