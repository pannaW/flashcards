<?php
/**
 * Set controller.
 */

namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Form\SetType;
use Repository\SetRepository;
use Repository\TagRepository;

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
    public function indexAction(Application $app)
    {
        $setRepository = new SetRepository($app['db']);

        return $app['twig']->render(
            'set/index.html.twig',
            ['set' => $setRepository->findAll()]
        );
    }


    /**
     * @param Application $app
     * @param Request     $request
     * @return mixed
     */
    public function addAction(Application $app, Request $request)
    {
        $set = [];
        $form = $app['form.factory']
           ->createBuilder(SetType::class, $set, ['set_repository' => new SetRepository($app['db']), 'tag_repository' => new TagRepository($app['db'])])
           ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $setRepository = new SetRepository($app['db']);
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
            'set/add.html.twig',
            [
                'set' => $set,
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
        $setRepository = new SetRepository($app['db']);

        return $app['twig']->render(
            'set/view.html.twig',
            [
                'set' => $setRepository->findOneById($id),
                'id' => $id,
            ]
        );

    }

    /**
     * @param Application $app
     * @param Set id      $id
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Application $app, $id, Request $request)
    {
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
            ]
        );
    }

    /**
     * @param Application $app
     * @param Set id      $id
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Application $app, $id, Request $request)
    {
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
                'set' => $set,
            ]
        );
    }
}
