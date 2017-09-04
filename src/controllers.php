<?php
use Controller\SetController;
use Controller\TagController;
use Controller\FlashcardController;
use Controller\UserController;
use Controller\AuthController;
use Controller\RegisterController;

/**
 * Routing and controllers.
 *
 * @var $app \Silex\Application
 */
$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array());
})->bind('homepage');


$app->mount('/set', new SetController());
$app->mount('/tag', new TagController());
$app->mount('/flashcard', new FlashcardController());
$app->mount('/user', new UserController());
$app->mount('/auth', new AuthController());
$app->mount('/registration', new RegisterController());
