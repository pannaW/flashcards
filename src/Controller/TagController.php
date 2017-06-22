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
     * @param Application $app
     * @return mixed
     */
    public function indexAction(Application $app)
    {
        $tagRepository = new TagRepository($app['db']);

        return $app['twig']->render(
            'tag/index.html.twig',
            ['tag' => $tagRepository->findAll()]
        );
    }

    /**
     * @param Application $app
     * @param id          $id
     * @return mixed
     */
    public function viewAction(Application $app, $id)
    {
        $tagRepository = new TagRepository($app['db']);

        return $app['twig']->render(
            'tag/view.html.twig',
            [
                'tag' => $tagRepository->findOneById($id),
                'id' => $id,
            ]
        );
    }

    /**
     * @param Application $app
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addAction(Application $app, Request $request)
    {
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

            return $app->redirect($app['url_generator']->generate('tag_index'), 301);
        }

        return $app['twig']->render(
            'tag/add.html.twig',
            [
                'form' => $form->createView(),
                'tag' => $tag,
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
            // return redirect
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

        return $app->redirect($app['url_generator']->generate('tag_index'));
    }
}
