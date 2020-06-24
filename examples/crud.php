<?php
/**
 * Project: php-ActiveRecord
 * User: achelnokov
 * Date: 25.06.2020г.
 * Time: 2:03
 */

use \aqvador\ActiveRecord\ActiveRecord;
use \aqvador\examples\User;

/**
 * @var  $dsn  string
 * @var  $conf array
 */

$pdo = new PDO($dsn, $conf['user'], $conf['password']);

ActiveRecord::setDb($pdo);


/** INSERT */
$user = new User();
$user->name = 'Vasya';
$user->login = 'vasya-petya';
$user->email = 'vasya@petya.ru';
$user->insert();

/** SEARCH */
$users = new User();
/** @var User $user */
$user = $users->eq('email', 'vasya@petya.ru')->find(); // findAll()

/** UPDATE */
$user->name = 'Fedya';
$user->update();

/** DELETE */
$user->delete();
