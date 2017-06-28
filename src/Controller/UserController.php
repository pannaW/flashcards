<?php
/**
 * User Controller
 */

namespace Controller;

use Form\UserDataType;
use Form\UserType;
use Form\ResetPasswordType;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Repository\UserRepository;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints as Assert;

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

    public function checkAccess(Application $app)
    {
        if ($app['security.authorization_checker']->isGranted('ROLE_ADMIN'))
            return "admin";

        else if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY'))
            return "user";
        else
            return "anonymous";
    }

    public function checkOwnership($id, $userId)
    {
        return ($id == $userId) ? true : false;
    }

    /**
     * @param Application $app
     * @return mixed
     */
    public function indexAction(Application $app)
    {
        $access = $this->checkAccess($app);
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $username = $token->getUsername();
        }
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin($username);

        switch($access){
            case "admin":
                $userRepository = new UserRepository($app['db']);

                return $app['twig']->render(
                    'user/index.html.twig',
                    [
                        'user' => $userRepository->findAll(),
                        'username' => $username,
                        'userId' => $user['id'],

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
     * @param             $id
     * @return mixed
     */
    public function viewAction(Application $app, $id)
    {
        $access = $this->checkAccess($app);

        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $username = $token->getUsername();
        }
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin($username);


        if($access == 'admin' || ($access == 'user' && $this->checkOwnership($id, $user['id']))) {

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
        }else if ($access == 'user') {
            return $app->redirect($app['url_generator']->generate('set_index'));
        } else {
            $app->redirect($app['url_generator']->generate('homepage'));
        }
    }

    public function editAction(Application $app, $id, Request $request)
    {
        $access = $this->checkAccess($app);
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $username = $token->getUsername();
        }
        $userRepository = new UserRepository($app['db']);
        $currentuser = $userRepository->getUserByLogin($username);

        if($access == 'admin'){
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
            }else if ($access == 'user') {
            return $app->redirect($app['url_generator']->generate('set_index'));
        } else {
            $app->redirect($app['url_generator']->generate('homepage'));
        }
    }

    /**
     * @param Application $app
     * @param             $id
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editDataAction(Application $app, $id, Request $request)
    {
        $access = $this->checkAccess($app);
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $username = $token->getUsername();
        }
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin($username);

        if($access == 'admin' || ($access == 'user' && $this->checkOwnership($id, $user['id']))) {

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
                ->createBuilder(UserDataType::class, $userData,
                    ['user_repository' => new UserRepository($app['db']), 'userId' => $user['id']])
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

                return $app->redirect($app['url_generator']->generate('set_index'), 301);
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
        }else if ($access == 'user') {
            return $app->redirect($app['url_generator']->generate('set_index'));
        } else {
            $app->redirect($app['url_generator']->generate('homepage'));
        }
    }


    public function resetPasswordAction(Application $app, $id, Request $request)
    {
        $access = $this->checkAccess($app);
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $username = $token->getUsername();
        }
        $userRepository = new UserRepository($app['db']);
        $currentUser = $userRepository->getUserByLogin($username);

        if($access == 'admin' || ($access == 'user' && $this->checkOwnership($id, $currentUser['id']))) {
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

            if ($access == 'user'){
                $form = $app['form.factory']
                    ->createBuilder(ResetPasswordType::class)
                    ->add('old_password', PasswordType::class, ['label' => 'label.old_password', 'constraints' =>
                            [new Assert\NotBlank(['groups' => ['resetPassword-default']]),
                                new Assert\Length(['groups' => ['resetPassword-default'], 'min'=>4,]),],])
                    ->getForm();
            } else if ($access == 'admin'){
                $form = $app['form.factory']
                    ->createBuilder(ResetPasswordType::class)
                    ->getForm();
            }

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();

                if ($access == 'user'){
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
                    }else{
                        $app['session']->getFlashBag()->add(
                            'messages',
                            [
                                'type' => 'warning',
                                'message' => 'Old password is not correct',
                            ]
                        );
                    }

                } else if ($access == 'admin') {

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
            } else if ($access == 'user') {
                return $app->redirect($app['url_generator']->generate('set_index'));
            } else {
                return $app->redirect($app['url_generator']->generate('homepage'));
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
        $currentUser = $userRepository->getUserByLogin($username);

        switch ($access) {
            case "admin":
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
            case "user":
                return $app->redirect($app['url_generator']->generate('set_index'));
            case "anonymous":
                return $app->redirect($app['url_generator']->generate('homepage'));
        }
    }
}
