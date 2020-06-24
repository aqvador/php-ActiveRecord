<?php
/**
 * Project: aqvador\activeRecord
 * User: achelnokov
 * Date: 24.06.2020г.
 * Time: 18:46
 */

namespace aqvador\ActiveRecord;

use aqvador\ActiveRecord\traites\ArrayAssess;
use PDO;
use Exception;
use ArrayAccess;

/**
 * Class ActiveRecord
 *
 * @package app\ActiveRecord
 *
 *          operators
 * @method isNull(string $field)
 * @method isNotNull(string $field)
 * @method in(string $field, $param)
 * @method eq(string $field, $param)
 * @method ne(string $field, $param)
 * @method gt(string $field, $param)
 * @method lt(string $field, $param)
 * @method ge(string $field, $param)
 * @method le(string $field, $param)
 * @method like(string $field, $param)
 * @method notIn(string $field, $param)
 * @method regexp(string $field, $param)
 * @method between(string $field, $param)
 *
 *          sqlParts
 * @method select(string $select)
 * @method from(string $table)
 * @method set(string $set)
 * @method where(string $where)
 * @method group(string $group)
 * @method groupBy(string $groupBy)
 * @method having(string $having)
 * @method order(string $order)
 * @method orderBy(string $orderBy)
 * @method limit(int $limit)
 * @method offset(int $offset)
 */
abstract class ActiveRecord extends Base implements ArrayAccess
{
    use ArrayAssess;

    /**
     * @var PDO static property to connect database.
     */
    public static $db;
    /**
     * @var array maping the function name and the operator, to build Expressions in WHERE condition.
     * <pre>user can call it like this:
     *      $user->isnotnull()->eq('id', 1);
     * will create Expressions can explain to SQL:
     *      WHERE user.id IS NOT NULL AND user.id = :ph1</pre>
     */
    public static $operators
        = [
            'eq'        => '=',
            'ne'        => '<>',
            'gt'        => '>',
            'lt'        => '<',
            'ge'        => '>=',
            'le'        => '<=',
            'between'   => 'BETWEEN',
            'like'      => 'LIKE',
            'in'        => 'IN',
            'notIn'     => 'NOT IN',
            'isNull'    => 'IS NULL',
            'isNotNull' => 'IS NOT NULL',
        ];
    /**
     * @var array Part of SQL, maping the function name and the operator to build SQL Part.
     * <pre>call function like this:
     *      $user->order('id desc', 'name asc')->limit(2,1);
     *  can explain to SQL:
     *      ORDER BY id desc, name asc limit 2,1</pre>
     */
    public static $sqlParts
        = [
            'select'  => 'SELECT',
            'from'    => 'FROM',
            'set'     => 'SET',
            'where'   => 'WHERE',
            'group'   => 'GROUP BY',
            'groupBy' => 'GROUP BY',
            'having'  => 'HAVING',
            'order'   => 'ORDER BY',
            'orderBy' => 'ORDER BY',
            'limit'   => 'LIMIT',
            'offset'  => 'OFFSET',
            'top'     => 'TOP',
        ];
    /**
     * @var array Static property to stored the default Sql Expressions values.
     */
    public static $defaultSqlExpressions
        = [
            'expressions' => [],
            'wrap'        => false,
            'select'      => null,
            'insert'      => null,
            'update'      => null,
            'set'         => null,
            'delete'      => 'DELETE ',
            'join'        => null,
            'from'        => null,
            'values'      => null,
            'where'       => null,
            'having'      => null,
            'limit'       => null,
            'order'       => null,
            'group'       => null,
        ];
    /**
     * @var array Stored the Expressions of the SQL.
     */
    protected $sqlExpressions = [];
    /**
     * @var string  The table name in database.
     */
    protected static $table;
    /**
     * @var string  The primary key of this ActiveRecord, just suport single primary key.
     */
    protected $primaryKey = 'id';

    protected $cache = true;
    protected $cacheExpire = 600;
    /**
     * @var array Stored the params will bind to SQL when call PDOStatement::execute(),
     */
    protected $params = [];
    const BELONGS_TO = 'belongs_to';
    const HAS_MANY   = 'has_many';
    const HAS_ONE    = 'has_one';
    /**
     * @var array Stored the configure of the relation, or target of the relation.
     */
    protected $relations = [];
    /**
     * @var int The count of bind params, using this count and const "PREFIX" (:ph) to generate place holder in SQL.
     */
    public static $count = 0;
    const PREFIX = ':ph';

    protected static $schema;

    protected $_oldAttributes = [];

    public function __construct($config = [])
    {
        $this->getTableSchema();
        parent::__construct($config);
    }


    /**
     * function to reset the $params and $sqlExpressions.
     *
     * @return ActiveRecord return $this, can using chain method calls.
     */
    public function reset()
    {
        $this->params = [];
        $this->sqlExpressions = [];
        $this->setOldAttributes();
        return $this;
    }

    /**
     * set the DB connection.
     *
     * @param  PDO  $db
     */
    public static function setDb($db)
    {
        self::$db = $db;
    }

    /**
     * function to find one record and assign in to current object.
     *
     * @param  int  $id  If call this function using this param, will find record by using this id. If not set, just find the first record in database.
     *
     * @return bool|ActiveRecord if find record, assign in to current object and return it, other wise return "false".
     */
    public function find($id = null)
    {
        if ($id) {
            $this->reset()->eq($this->primaryKey, $id);
        }
        return self::_query($this->limit(1)->_buildSql([
            'select',
            'from',
            'join',
            'where',
            'group',
            'having',
            'order',
            'limit',
        ]), $this->params, $this->reset(), true);
    }

    /**
     * function to find all records in database.
     *
     * @return static[] an array of ActiveRecord instances, or an empty array if nothing matches.
     */
    public function findAll()
    {
        return self::_query($this->_buildSql(['select', 'from', 'join', 'where', 'group', 'having', 'order', 'limit']),
            $this->params, $this->reset());
    }

    /**
     * function to delete current record in database.
     *
     * @return bool
     */
    public function delete()
    {
        return self::execute($this->eq($this->primaryKey, $this->{$this->primaryKey})->_buildSql([
            'delete',
            'from',
            'where',
        ]), $this->params);
    }

    /**
     * function to build update SQL, and update current record in database, just write the dirty data into database.
     *
     * @return bool|ActiveRecord if update success return current object, other wise return false.
     */
    public function update()
    {
        $attributes = $this->getRealParams();
        print_r($attributes);
        if (count($attributes) == 0)
            return true;
        foreach ($attributes as $field => $value) {
            $this->addCondition($field, '=', $value, ',', 'set');
        }
        if (self::execute($this->eq($this->primaryKey, $this->{$this->primaryKey})->_buildSql([
            'update',
            'set',
            'where',
        ]), $this->params)
        )
            return $this->reset();
        return false;
    }

    /**
     * function to build insert SQL, and insert current record into database.
     *
     * @return bool|ActiveRecord if insert success return current object, other wise return false.
     */
    public function insert()
    {
        $attributes = $this->getRealParams();
        if (count($attributes) == 0)
            return true;
        $value = $this->_filterParam($attributes);
        $this->insert = new Expressions([
            'operator' => 'INSERT INTO '.static::$table,
            'target'   => new WrapExpressions(['target' => array_keys($attributes)]),
        ]);
        $this->values = new Expressions([
            'operator' => 'VALUES',
            'target'   => new WrapExpressions(['target' => $value]),
        ]);
        if (self::execute($this->_buildSql(['insert', 'values']), $this->params)) {
            $this->{$this->primaryKey} = self::$db->lastInsertId();
            return $this->reset();
        }
        return false;
    }

    protected function getRealParams()
    {
        $attributes = [];
        foreach (self::$schema as $prop => $param) {
            if (isset($this->_oldAttributes[$prop])) {
                if (property_exists($this, $prop) && $this->_oldAttributes[$prop] !== $this->$prop) {
                    $attributes[$prop] = $this->$prop;
                }
            } elseif (isset($this->$prop)) {
                $attributes[$prop] = $this->$prop;
            }
        }
        return $attributes;
    }

    /**
     * helper function to exec sql.
     *
     * @param  string  $sql    The SQL need to be execute.
     * @param  array   $param  The param will be bind to PDOStatement.
     *
     * @return bool
     */
    public static function execute($sql, $param = [])
    {
        $res = (($sth = self::$db->prepare($sql)) && $sth->execute($param));
        if (!$res)
            throw new Exception($sth->errorInfo()[2]);
        return $res;
    }

    /**
     * helper function to query one record by sql and params.
     *
     * @param  string        $sql     The SQL to find record.
     * @param  array         $param   The param will be bind to PDOStatement.
     * @param  ActiveRecord  $obj     The object, if find record in database, will assign the attributes in to this object.
     * @param  bool          $single  if set to true, will find record and fetch in current object, otherwise will find all records.
     *
     * @return bool|ActiveRecord|array
     */
    public static function _query($sql, $param = [], $obj = null, $single = false)
    {
        if ($sth = self::$db->prepare($sql)) {
            $sth->setFetchMode(PDO::FETCH_CLASS, get_called_class());
            $sth->execute($param);
            for (; ($obj = $sth->fetch()) && !$single;) {
                $result[] = $obj->setOldAttributes();
            }
            return $single ? $obj->setOldAttributes() : $result;
        }
        return false;
    }

    protected function setOldAttributes()
    {
        foreach ($this as $k => $v) {
            if (isset(self::$schema[$k])) {
                $this->_oldAttributes[$k] = $v;
            }
        }
        return $this;
    }

    /**
     * helper function to get relation of this object.
     * There was three types of relations: {BELONGS_TO, HAS_ONE, HAS_MANY}
     *
     * @param  string  $name  The name of the relation, the array key when defind the relation.
     *
     * @return mixed
     */
    protected function & getRelation($name)
    {
        $relation = $this->relations[$name];
        if ($relation instanceof self || (is_array($relation) && $relation[0] instanceof self))
            return $relation;
        $this->relations[$name] = $obj = new $relation[1];
        if (isset($relation[3]) && is_array($relation[3]))
            foreach ((array)$relation[3] as $func => $args) {
                call_user_func_array([$obj, $func], (array)$args);
            }
        $backref = isset($relation[4]) ? $relation[4] : '';
        if ((!$relation instanceof self) && self::HAS_ONE == $relation[0])
            $obj->eq($relation[2], $this->{$this->primaryKey})->find() && $backref
            && $obj->__set($backref, $this); elseif (is_array($relation) && self::HAS_MANY == $relation[0]) {
            $this->relations[$name] = $obj->eq($relation[2], $this->{$this->primaryKey})->findAll();
            if ($backref)
                foreach ($this->relations[$name] as $o) {
                    $o->__set($backref, $this);
                }
        } elseif ((!$relation instanceof self) && self::BELONGS_TO == $relation[0])
            $obj->eq($obj->primaryKey, $this->{$relation[2]})->find() && $backref && $obj->__set($backref, $this);
        else throw new Exception("Relation $name not found.");
        return $this->relations[$name];
    }

    /**
     * helper function to build SQL with sql parts.
     *
     * @param  string        $n  The SQL part will be build.
     * @param  int           $i  The index of $n in $sqls array.
     * @param  ActiveRecord  $o  The refrence to $this
     *
     * @return string
     */
    private function _buildSqlCallback(&$n, $i, $o)
    {
        if ('select' === $n && null == $o->$n)
            $n = strtoupper($n).' '.$o::$table.'.*'; elseif (('update' === $n || 'from' === $n) && null == $o->$n)
            $n = strtoupper($n).' '.$o::$table;
        elseif ('delete' === $n)
            $n = strtoupper($n).' ';
        else $n = (null !== $o->$n) ? $o->$n.' ' : '';
    }

    /**
     * helper function to build SQL with sql parts.
     *
     * @param  array  $sqls  The SQL part will be build.
     *
     * @return string
     */
    protected function _buildSql($sqls = [])
    {
        array_walk($sqls, [$this, '_buildSqlCallback'], $this);
        //this code to debug info.
        //echo 'SQL: ', implode(' ', $sqls), "\n", "PARAMS: ", implode(', ', $this->params), "\n";
        return implode(' ', $sqls);
    }

    /**
     * magic function to make calls witch in function mapping stored in $operators and $sqlPart.
     * also can call function of PDO object.
     *
     * @param  string  $name  function name
     * @param  array   $args  The arguments of the function.
     *
     * @return mixed Return the result of callback or the current object to make chain method calls.
     */
    public function __call($name, $args)
    {
        if (is_callable($callback = [self::$db, $name]))
            return call_user_func_array($callback, $args);
        if (in_array($name = strtolower($name), array_keys(self::$operators)))
            $this->addCondition($args[0], self::$operators[$name], isset($args[1]) ? $args[1] : null,
                (is_string(end($args)) && 'or' === strtolower(end($args))) ? 'OR' : 'AND'); elseif (in_array(
            $name = str_replace('by', '', $name), array_keys(self::$sqlParts))
        )
            $this->$name = new Expressions([
                'operator' => self::$sqlParts[$name],
                'target'   => implode(', ', $args),
            ]);
        else throw new Exception("Method $name not exist.");
        return $this;
    }

    /**
     * make wrap when build the SQL expressions of WHWRE.
     *
     * @param  string  $op  If give this param will build one WrapExpressions include the stored expressions add into WHWRE. otherwise wil stored the expressions into array.
     *
     * @return ActiveRecord return $this, can using chain method calls.
     */
    public function wrap($op = null)
    {
        if (1 === func_num_args()) {
            $this->wrap = false;
            if (is_array($this->expressions) && count($this->expressions) > 0)
                $this->_addCondition(new WrapExpressions(['delimiter' => ' ', 'target' => $this->expressions]),
                    'or' === strtolower($op) ? 'OR' : 'AND');
            $this->expressions = [];
        } else $this->wrap = true;
        return $this;
    }

    /**
     * helper function to build place holder when make SQL expressions.
     *
     * @param  mixed  $value  the value will bind to SQL, just store it in $this->params.
     *
     * @return mixed $value
     */
    protected function _filterParam($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $this->params[$value[$key] = self::PREFIX.++self::$count] = $val;
            }
        } elseif (is_string($value)) {
            $this->params[$ph = self::PREFIX.++self::$count] = $value;
            $value = $ph;
        }
        return $value;
    }

    /**
     * helper function to add condition into WHERE.
     * create the SQL Expressions.
     *
     * @param  string  $field  The field name, the source of Expressions
     * @param  string  $operator
     * @param  mixed   $value  the target of the Expressions
     * @param  string  $op     the operator to concat this Expressions into WHERE or SET statment.
     * @param  string  $name   The Expression will contact to.
     */
    public function addCondition($field, $operator, $value, $op = 'AND', $name = 'where')
    {
        $value = $this->_filterParam($value);
        if ($exp = new Expressions([
            'source'   => ('where' == $name ? static::$table.'.' : '').$field,
            'operator' => $operator,
            'target'   => (is_array($value) ? new WrapExpressions('between' === strtolower($operator)
                ? ['target' => $value, 'start' => ' ', 'end' => ' ', 'delimiter' => ' AND '] : ['target' => $value])
                : $value),
        ])
        ) {
            !$this->wrap ? $this->_addCondition($exp, $op, $name) : $this->_addExpression($exp, $op);
        }
    }

    /**
     * helper function to add condition into JOIN.
     * create the SQL Expressions.
     *
     * @param  string  $table  The join table name
     * @param  string  $on     The condition of ON
     * @param  string  $type   The join type, like "LEFT", "INNER", "OUTER"
     */
    public function join($table, $on, $type = 'LEFT')
    {
        $this->join = new Expressions([
            'source'   => $this->join ?: '',
            'operator' => $type.' JOIN',
            'target'   => new Expressions(['source' => $table, 'operator' => 'ON', 'target' => $on]),
        ]);
        return $this;
    }

    /**
     * helper function to make wrapper. Stored the expression in to array.
     *
     * @param  Expressions  $exp       The expression will be stored.
     * @param  string       $operator  The operator to concat this Expressions into WHERE statment.
     */
    protected function _addExpression($exp, $operator)
    {
        if (!is_array($this->expressions) || count($this->expressions) == 0) {
            $this->expressions = [$exp];
        } else {
            $this->expressions[] = new Expressions(['operator' => $operator, 'target' => $exp]);
        }
    }

    /**
     * helper function to add condition into WHERE.
     *
     * @param  Expressions  $exp       The expression will be concat into WHERE or SET statment.
     * @param  string       $operator  the operator to concat this Expressions into WHERE or SET statment.
     * @param  string       $name      The Expression will contact to.
     */
    protected function _addCondition($exp, $operator, $name = 'where')
    {
        if (!$this->$name)
            $this->$name = new Expressions(['operator' => strtoupper($name), 'target' => $exp]); else
            $this->$name->target = new Expressions([
                'source'   => $this->$name->target,
                'operator' => $operator,
                'target'   => $exp,
            ]);
    }

    /**
     * magic function to SET values of the current object.
     */
    public function __set($name, $val)
    {
        if (array_key_exists($name, $this->sqlExpressions) || array_key_exists($name, self::$defaultSqlExpressions)) {
            $this->sqlExpressions[$name] = $val;
        } elseif (array_key_exists($name, $this->relations) && $val instanceof self) {
            $this->relations[$name] = $val;
        } elseif (isset(self::$schema[$name])) {
            $this->$name = $val;
        }
    }

    /**
     * magic function to UNSET values of the current object.
     */
    public function __unset($name)
    {
        if (isset($this->sqlExpressions[$name])) {
            unset($this->sqlExpressions[$name]);
        }
    }

    /**
     * magic function to GET the values of current object.
     */
    public function & __get($name)
    {
        if (isset($this->sqlExpressions[$name])) {
            return $this->sqlExpressions[$name];
        } elseif (isset($this->relations[$name])) {
            return $this->getRelation($name);
        }
        return parent::__get($name);
    }

    public function getTableSchema()
    {
        if (is_null(self::$schema)) {
            self::$schema = (new Scheme(static::$table, $this->cache, $this->cacheExpire))->getScheme(static::$db);
        }
    }

    public static function call_find()
    {
        $class = get_called_class();
        return new $class;
    }
}