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
     * @param Application $app
     * @return mixed
     */
    public function indexAction(Application $app)
    {
        $flashcardRepository = new FlashcardRepository($app['db']);

        return $app['twig']->render(
            'flashcard/index.html.twig',
            ['flashcard' => $flashcardRepository->findAll()]
        );
    }


    /**
     * @param Application $app
     * @param Request     $request
     * @return mixed
     */
    public function addAction(Application $app, Request $request)
    {
        $flashcard = [];
        $form = $app['form.factory']
            ->createBuilder(FlashcardType::class, $flashcard, ['flashcard_repository' => new FlashcardRepository($app['db'])])
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

            return $app->redirect($app['url_generator']->generate('flashcard_index'), 301);
        }

        return $app['twig']->render(
            'flashcard/add.html.twig',
            [
                'flashcard' => $flashcard,
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
        $flashcardRepository = new FlashcardRepository($app['db']);

        return $app['twig']->render(
            'flashcard/view.html.twig',
            [
                'flashcard' => $flashcardRepository->findOneById($id),
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
        $flashcardRepository = new FlashcardRepository($app['db']);
        $flashcard = $flashcardRepository->findOneById($id);

        if (!$flashcard) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('flashcard_index'));
        }

        $form = $app['form.factory']
            ->createBuilder(FlashcardType::class, $flashcard, ['flashcard_repository' => new FlashcardRepository($app['db'])])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $flashcardRepository->save($form->getData());
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('flashcard_index'), 301);
        }

        return $app['twig']->render(
            'flashcard/edit.html.twig',
            [
                'flashcard' => $flashcard,
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
        $flashcardRepository = new FlashcardRepository($app['db']);
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
            ]
        );
    }
}
