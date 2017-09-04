<?php
/**
 * Tag Controller
 *
 */

namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Repository\TagRepository;
use Repository\SetRepository;
use Repository\UserRepository;

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
        $controller->get('/', [$this, 'indexAction'])
            ->bind('tag_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('tag_index_paginated');
        $controller->match('{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('tag_delete');

        return $controller;
    }
    /**
     * Index action
     *
     * @param Application $app
     * @param int         $page Number of current page
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

            $tagRepository = new TagRepository($app['db']);

                return $app['twig']->render(
                    'tag/index.html.twig',
                    [
                        'paginator' => $tagRepository->findAllPaginated($page),
                        'username' => $username,
                        'userId' => $user['id'],
                    ]
                );
        }
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
            $user = $userRepository->getUserByLogin($username);

            $tagRepository = new TagRepository($app['db']);
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
            $form = $app['form.factory']->createBuilder(FormType::class, $tag)->add('id', HiddenType::class)->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                if (($tagRepository->findLinkedTags($id))) {
                    //if tag linked with set
                    $setRepository = new SetRepository($app['db']);
                    $connectedSets = $tagRepository->findLinkedTags($id);
                    if (is_array($connectedSets)) {
                        foreach ($connectedSets as $connectedSet) {
                            $setRepository->removeLinkedTags($connectedSet['sets_id']);
                        }
                    } else {
                        $setRepository->removeLinkedTags($connectedSets['sets_id']);
                    }
                }

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
                    'userId' => $user['id'],
                ]
            );
        }
    }
}
