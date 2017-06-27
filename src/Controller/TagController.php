<?php
/**
 * Tag Controller
 *
 */

namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Repository\TagRepository;
use Repository\UserRepository;
use Form\TagType;

/**
 * Class TagController
 *
 * @package Controller
 */
class TagController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this , 'indexAction'])
           ->bind('tag_index');
        $controller->get('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('tag_add');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->bind('tag_view')
            ->assert('id', '[1-9]\d*');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('tag_edit');
        $controller->match('{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('tag_delete');

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
    public function indexAction(Application $app)
    {
        $access = $this->checkAccess($app);

        switch ($access) {
            case "admin":
                $tagRepository = new TagRepository($app['db']);

                return $app['twig']->render(
                    'tag/index.html.twig',
                    ['tag' => $tagRepository->findAll()]
                );
            case "user":
                return $app->redirect($app['url_generator']->generate('set_index'));
            case "anonymous":
                return $app->redirect($app['url_generator']->generate('homepage'));
        }
    }

    /**
     * @param Application $app
     * @param id          $id
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

        $tagRepository = new TagRepository($app['db']);

        if($access == 'admin' || ($access == 'user' && $tagRepository->checkOwnership($id, $user['id']))) {

            return $app['twig']->render(
                'tag/view.html.twig',
                [
                    'tag' => $tagRepository->findOneById($id),
                    'id' => $id,
                    'username' => $username,
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
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addAction(Application $app, Request $request)
    {
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $username = $token->getUsername();
        }

        $tag = [];

        $form = $app['form.factory']
            ->createBuilder(TagType::class, $tag, ['tag_repository' => new TagRepository($app['db'])])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tagRepository = new TagRepository($app['db']);
            $tagRepository->save($form->getData());

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
            'tag/add.html.twig',
            [
                'form' => $form->createView(),
                'tag' => $tag,
                'username' => $username,
            ]
        );

    }

    /**
     * @param Application $app
     * @param Id          $id
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

        $tagRepository = new TagRepository($app['db']);

        if($access == 'admin' || ($access == 'user' && $tagRepository->checkOwnership($id, $user['id']))) {

            $tag = $tagRepository->findOneById($id);

            if (!$tag) {
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'warning',
                        'message' => 'message.record_not_found',
                    ]
                );
                return $app->redirect($app['url_generator']->generate('set_index'));
            }

            $form = $app['form.factory']->createBuilder(TagType::class, $tag, ['tag_repository' => new TagRepository($app['db'])])
                ->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $tagRepository->save($form->getData());
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_added',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('tag_index'), 301);
            }

            return $app['twig']->render(
                'tag/edit.html.twig',
                [
                    'form' => $form->createView(),
                    'tag' => $tag,
                    'id' => $id,
                    'username' => $username,
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
    public function deleteAction(Application $app, $id, Request $request)
    {
        $access = $this->checkAccess($app);

        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $username = $token->getUsername();
        }
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->getUserByLogin($username);

        $tagRepository = new TagRepository($app['db']);

        if ($access == 'admin' || ($access == 'user' && $tagRepository->checkOwnership($id, $user['id']))) {
            $tag = $tagRepository->findOneById($id);

            if (!$tag) {
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'warning',
                        'message' => 'message.record_not_found',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('tag_index'));
            }

            if (!($tagRepository->findIfTagLinked($id))) {
                //if tag not linked with set
                $form = $app['form.factory']->createBuilder(FormType::class, $tag)->add('id', HiddenType::class)->getForm();
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $tagRepository->delete($form->getData());

                    $app['session']->getFlashBag()->add(
                        'messages',
                        [
                            'type' => 'success',
                            'message' => 'message.element_successfully_deleted',
                        ]
                    );

                    return $app->redirect(
                        $app['url_generator']->generate('tag_index'),
                        301
                    );
                }

                return $app['twig']->render(
                    'tag/delete.html.twig',
                    [
                        'tag' => $tag,
                        'form' => $form->createView(),
                        'username' => $username,
                    ]
                );
            }

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.tag_linked_to_set',
                ]
            );

            return $app->redirect($app['url_generator']->generate('set_index'));
        }
    }
}
