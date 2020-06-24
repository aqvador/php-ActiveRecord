<?php
/**
 * Project: php-ActiveRecord
 * User: achelnokov
 * Date: 25.06.2020г.
 * Time: 2:09
 */

namespace aqvador\examples;


use aqvador\ActiveRecord\ActiveRecord;


/**
 * This is the model class for table "prepared_statistic".
 *
 * @property int    $id
 * @property string $name
 * @property string $login
 * @property string $email
 * @property string $passwordHash
 * @property string $authToken
 *
 */
class User extends ActiveRecord
{
    protected static $table = 'users';
    protected $primaryKey = 'id';
}