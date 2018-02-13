# Flashcards

Fiszki (eng. flashcards) is a web-based application for vocabulary learning, 
where users can create and manage their own sets of flashcards (private or public) as well as revise them. 
Admin module is also provided.

![Gif containing screenshots of app demo](http://cis.wzks.uj.edu.pl/~15_lucarz/flashcards_demo.gif)

## Demo

http://cis.wzks.uj.edu.pl/~15_lucarz/fiszki/

_Currently only Polish translation in UI is developed._

## Built With

* [Silex](https://silex.symfony.com/) - The PHP micro-framework based on the Symfony Components
* [Composer](https://getcomposer.org/) - Dependency Manager
* [Bootstrap v.3.3.7 ](https://getbootstrap.com/docs/3.3/) - HTML, CSS, and JS framework

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes.

### Installing

A step by step series of examples that tell you have to get a development env running

1# Create app directory

```
mkdir flashcards_app
```
2# Go to the directory 
```
cd flashcards_app
```

3# Install Composer in /flashcards_app

```
curl -sS https://getcomposer.org/installer | php
```
4# Clone or download this repository to the /flashcards_app

e.g.
```
git clone https://github.com/pannaW/flashcards.git
```

5# Update dependencies with Composer

e.g.
```
php ./composer.phar -vvv update
```

6# (optional) Use tests/local.sql database dump for testing purposes.

6.1# Import tests/local.sql to your MySQL e.g. [Workbench](https://www.mysql.com/products/workbench/)

6.2# Modify src/app.php 

Fill lines with your DB data.

```
'db.options' => array(
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'dbname' => 'databasename',
            'user' => 'user', 
            'password' => 'password',
            'charset' => 'utf8', ),
```


_ Login and password to admin account. You should change them after first login._

        login: Admin
        password: jTE7cm666Xk6

7# Running app

```
cd app
php -S localhost:8000 -t web
```

Open project in: http://localhost:8000/
