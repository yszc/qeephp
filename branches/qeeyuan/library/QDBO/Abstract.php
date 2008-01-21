<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.txt 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QDBO_Abstract 类
 *
 * @copyright Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
 * @author 起源科技 (www.qeeyuan.com)
 * @package core
 * @version $Id$
 */

/**
 * QDBO_Abstract 是所有数据库驱动的抽象基础类
 *
 * @package core
 * @author 起源科技 (www.qeeyuan.com)
 * @version 1.1
 */
abstract class QDBO_Abstract
{
    /**
     * 所有 SQL 查询的日志
     *
     * @var array
     */
    public $log = array();

    /**
     * 执行的查询计数
     *
     * @var int
     */
    public $query_count = 0;

    /**
     * 参数占位符类型
     */
    const param_qm          = '?'; // 问号作为参数占位符
    const param_cl_named    = ':'; // 冒号开始的命名参数
    const param_dl_sequence = '$'; // $符号开始的序列
    const param_at_named    = '@'; // @开始的命名参数

    /**
     * 可用的查询结果集返回形式
     */
    const fetch_mode_array      = 1; // 返回的每一个记录就是一个索引数组
    const fetch_mode_assoc      = 2; // 返回的每一个记录就是一个以字段名作为键名的数组

    /**
     * 数据库连接信息
     *
     * @var mixed
     */
    protected $dsn;

    /**
     * 数据库访问对象 ID
     *
     * @var string
     */
    protected $id;

    /**
     * 默认的 schema
     *
     * @var string
     */
    protected $schema = '';

    /**
     * 默认的数据表前缀
     *
     * @var string
     */
    protected $table_prefix = '';

    /**
     * 指示返回结果集的形式
     *
     * @var const
     */
    protected $fetch_mode = self::fetch_mode_assoc;

    /**
     * 数据库连接句柄
     *
     * @var resource
     */
    protected $conn;

    /**
     * 最后一次数据库操作的错误信息
     *
     * @var mixed
     */
    protected $last_err;

    /**
     * 最后一次数据库操作的错误代码
     *
     * @var mixed
     */
    protected $last_errcode;

    /**
     * 最近一次插入操作或者 next_id() 操作返回的插入 ID
     *
     * @var mixed
     */
    protected $insert_id;

    /**
     * 指示事务启动次数
     *
     * @var int
     */
    protected $trans_count = 0;

    /**
     * 指示事务执行期间是否发生了错误
     *
     * @var boolean
     */
    protected $has_failed_query = false;

    /**
     * SAVEPOINT 堆栈
     *
     * @var array
     */
    protected $savepoints_stack = array();

    /**
     * 用于描绘 true、false 和 null 的数据库值
     */
    protected $TRUE_VALUE       = 1;
    protected $FALSE_VALUE      = 0;
    protected $NULL_VALUE       = 'NULL';

    /**
     * 数据库接受的日期格式
     */
    protected $TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    /**
     * 指示驱动是否支持原生的参数绑定
     *
     * @var boolean
     */
    protected $BIND_ENABLED = true;
    /**
     * 指示使用何种样式的参数占位符
     *
     * @var string
     */
    protected $PARAM_STYLE  = self::param_qm;

    /**
     * 指示数据库是否有自增字段功能
     *
     * @var boolean
     */
    protected $HAS_INSERT_ID = true;

    /**
     * 指示数据库是否能获得更新、删除操作影响的记录行数量
     *
     * @var boolean
     */
    protected $AFFECTED_ROWS_ENABLED = true;

    /**
     * 指示数据库是否支持事务
     *
     * @var boolean
     */
    protected $TRANSACTION_ENABLED = true;

    /**
     * 指示数据库是否支持事务中的 SAVEPOINT 功能
     *
     * @var boolean
     */
    protected $SAVEPOINT_ENABLED = false;

    /**
     * 指示是否将查询语句放入 log 数组
     *
     * @var boolean
     */
    protected $LOG_QUERY = false;

    /**
     * 指示是否将查询结果中的字段名转换为全小写
     *
     * @var boolean
     */
    protected $RESULT_FIELD_NAME_LOWER = false;

    /**
     * 返回数据库访问对象实例
     *
     * @param mixed $dsn
     *
     * @return QDBO_Abstract
     */
    static function get_dbo($dsn = null)
    {
        $default = is_null($dsn);
        if ($default && Q::is_reg('dbo_default')) {
            return Q::get_reg('dbo_default');
        }

        if (is_null($dsn)) {
            $dsn = Q::getIni('dsn');
        }
        $dbtype = $dsn['driver'];
        $obj_id = "dbo_{$dbtype}_" .  md5(serialize($dsn));
        if (Q::is_reg($obj_id)) {
            return Q::get_reg($obj_id);
        }

        $class_name = 'QDBO_' . ucfirst($dbtype);
        $dbo = new $class_name($dsn, $obj_id);
        Q::reg($dbo, $obj_id);
        if ($default) {
            Q::reg($dbo, 'dbo_default');
        }
        return $dbo;
    }

    /**
     * 构造函数
     *
     * @param mixed $dsn
     * @param string $id
     */
    function __construct($dsn, $id)
    {
        $this->dsn = $dsn;
        $this->id = $id;
        if (defined('DEPLOY_MODE') && !DEPLOY_MODE) {
            $this->LOG_QUERY = true;
        }
    }

    /**
     * 返回数据库访问对象使用的 DSN
     *
     * @return mixed
     */
    function get_dsn()
    {
        return $this->dsn;
    }

    /**
     * 返回数据库访问对象的 ID
     *
     * @return string
     */
    function get_id()
    {
        return $this->id;
    }

    /**
     * 返回数据库对象对应的 schema
     *
     * @return string
     */
    function get_schema()
    {
        return $this->schema;
    }

    /**
     * 返回数据库对象对应的表前缀
     *
     * @return string
     */
    function get_table_prefix()
    {
        return isset($this->dsn['prefix']) ? $this->dsn['prefix'] : '';
    }

    /**
     * 连接数据库，失败时抛出异常
     *
     * 如果已经连接到了数据库，再次连接不会造成任何影响。
     */
    abstract function connect();

    /**
     * 创建一个持久连接，失败时抛出异常
     *
     * 如果已经连接到了数据库，再次连接不会造成任何影响。
     */
    abstract function pconnect();

    /**
     * 强制创建一个新连接，失败时抛出异常
     *
     * 如果已经连接到了数据库，再次连接不会造成任何影响。
     */
    abstract function nconnect();

    /**
     * 确认是否已经连接到数据库
     *
     * @return boolean
     */
    abstract function is_connected();

    /**
     * 关闭数据库连接
     */
    function close()
    {
        $this->conn = null;
        $this->last_err = null;
        $this->last_errcode = null;
        $this->insert_id = null;
        $this->trans_count = 0;
    }

    /**
     * 返回连接数据库的句柄
     *
     * @return resource
     */
    function handle()
    {
        return $this->conn;
    }

    /**
     * 选择要操作的数据库
     *
     * @param string $database
     *
     * @return boolean
     */
    abstract function select_db($database);

    /**
     * 转义值
     *
     * 为了能够在 SQL 语句中安全的插入数据，应该用 qstr() 方法将数据中的特殊字符转义。
     *
     * example:
     * <code>
     * $param = "It's live";
     * $param = $dbo->qstr($param);
     * $sql = "INSERT INTO posts (title) VALUES ({$param})";
     * $dbo->execute($sql);
     * </code>
     *
     * 但更有效，而且更简单的方式是使用参数占位符：
     *
     * example:
     * <code>
     * $param = "It's live";
     * $sql = "INSERT INTO posts (title) VALUES (?)";
     * $dbo->execute($sql, array($param));
     * </code>
     *
     * 而且对于 Oracle 等数据库，由于限制每条 SQL 语句不能超过 4000 字节，
     * 因此在插入包含大量数据的记录时，必须使用参数占位符的形式。
     *
     * example:
     * <code>
     * $title = isset($POST['title']) ? $POST['title'] : null;
     * $body = isset($POST['body']) ? $POST['body'] : null;
     *
     * ... 检查 $title、$body 是否为空 ...
     *
     * $sql = "INSERT INTO posts (title, body) VALUES (:title, :body)";
     * // 使用引用来构造数组，避免 $title、$body 的数据占用双倍内存
     * $dbo->execute($sql, array('title' => & $title, 'body' => & $body));
     * </code>
     *
     * @param mixed $value
     *
     * @return string
     */
    abstract function qstr($value);

    /**
     * 将 SQL 语句中的参数占位符替换为相应的参数值
     *
     * @param string $sql
     * @param array $params
     * @param enum $param_style
     *
     * @return string
     */
    function qinto($sql, array $params = null, $param_style = null)
    {
        if (is_null($param_style)) {
            $param_style = $this->PARAM_STYLE;
        }

        $callback = array($this, 'qstr');
        switch ($param_style) {
        case self::param_qm:
        case self::param_dl_sequence:
            if ($param_style == self::param_qm) {
                $parts = explode('?', $sql);
            } else {
                $parts = preg_split('/\$[0-9]+/', $sql);
            }
            if (count($params) != count($parts) - 1) {
                throw new QTable_Exception(__('Invalid parameters for "%s"', $sql));
            }

            $str = $parts[0];
            $offset = 1;
            foreach ($params as $arg_value) {
                if (is_array($arg_value)) {
                    $arg_value = array_map($callback, $arg_value);
                    $str .= implode(',', $arg_value) . $parts[$offset];
                } else {
                    $str .= $this->qstr($arg_value) . $parts[$offset];
                }
                $offset++;
            }
            return $str;

        case self::param_cl_named:
        case self::param_at_named:
            $split = ($param_style == self::param_cl_named) ? ':' : '@';
            $parts = preg_split('/(' . $split . '[a-z0-9_\-]+)/i', $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
            $max = count($parts);
            if (count($params) * 2 + 1 != $max) {
                throw new QDBO_Exception(__('Invalid parameters for "%s"', $sql));
            }
            $str = $parts[0];

            for ($offset = 1; $offset < $max; $offset += 2) {
                $arg_name = substr($parts[$offset], 1);
                if (!isset($params[$arg_name])) {
                    throw new QDBO_Exception(__('Invalid parameter "%s" for "%s"', $arg_name, $sql));
                }
                if (is_array($params[$arg_name])) {
                    $arg_value = array_map($callback, $params[$arg_name]);
                    $str .= implode(',', $arg_value) . $parts[$offset + 1];
                } else {
                    $str .= $this->qstr($params[$arg_name]) . $parts[$offset + 1];
                }
            }
            return $str;
        }
    }

    /**
     * 返回数据表名称的完全限定名
     *
     * 如果数据表的名字是 SQL 中的关键字，或者是要通过 schema 来确定数据表。
     * 那么就应该用 qtable() 方法将数据表名称转换为完全限定名。
     *
     * example:
     * <code>
     * $tablename = 'posts';
     * $schema = 'test_db';
     *
     * $identifier = $dbo->qtable($tablename, $schema);
     * // 如果是 MySQL 驱动，则 $identifier 的值为 `test_db`.`posts`
     * </code>
     *
     * 注意：不同数据库驱动，产生的完全限定名是不同的。
     *
     * @param string $table_name
     * @param string $schema
     *
     * @return string
     */
    abstract function qtable($table_name, $schema = null);

    /**
     * 返回字段名称的完全限定名
     *
     * 与 qtable() 类似，qfield() 产生包括 schema、数据库名、字段名的完全限定名。
     *
     * example:
     * <code>
     * $field = 'post_id';
     * $tablename = 'posts';
     * $schema = 'test_db';
     * $identifier = $dbo->qfield($field, $tablename, $schema);
     * // 如果是 MySQL 驱动，则 $identifier 的值为 `test_db`.`posts`.`post_id`
     * </code>
     *
     * 注意：不同数据库驱动，产生的完全限定名是不同的。
     *
     * @param string $fieldName
     * @param string $table_name
     * @param string $schema
     *
     * @return string
     */
    abstract function qfield($fieldName, $table_name = null, $schema = null);

    /**
     * 返回多个字段名称的完全限定名
     *
     * 转换的结果是以“,”连接的、包含所有字段完全限定名的字符串，
     * 或者一个包含所有字段的完全限定名的数组。
     * 默认返回字符串，如果要返回数组，将 $returnArray 参数指定为 true。
     *
     * example:
     * <code>
     * $fields = 'post_id, title, body';
     * $tablename = 'posts';
     * $identifiers = $dbo->qfields($fields, $tablename);
     * // 如果是 MySQL 驱动，则 $identifiers 的值为 `posts`.`post_id`, `posts`.`title`, `posts`.`body`
     *
     * $fields = array('post_id', 'title', 'body');
     * $identifiers = $dbo->qfields($fields, null, null, true);
     * // 如果是 MySQL 驱动，则 $identifiers 的值为 array("`post_id`", "`title`", "`body`")
     * </code>
     *
     * @param string|array $fields
     * @param string $table_name
     * @param string $schema
     * @param boolean $returnArray
     *
     * @return string
     */
    function qfields($fields, $table_name = null, $schema = null, $returnArray = false)
    {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
            $fields = array_map('trim', $fields);
        }
        $return = array();
        foreach ($fields as $fieldName) {
            $return[] = $this->qfield($fieldName, $table_name, $schema);
        }
        return $returnArray ? $return : implode(', ', $return);
    }

    /**
     * 为数据表产生下一个序列值，失败时抛出异常
     *
     * 调用 next_id() 方法，将获得指定名称序列的下一个值。
     * 此处所指的序列，是指一个不断增大的数字。
     *
     * 假设本次调用 next_id() 返回 3，那么下一次调用 next_id() 就会返回一个比 3 更大的值。
     * next_id() 返回的序列值，可以作为记录的主键字段值，以便确保插入记录时总是使用不同的主键值。
     *
     * 可以使用多个序列，只需要指定不同的 $seqName 参数即可。
     *
     * 在不同的数据库中，序列的产生方式各有不同。
     * PostgreSQL、Oracle 等数据库中，会使用数据库自带的序列功能来实现。
     * 其他部分数据库会创建一个后缀为 _seq 表来存放序列值。
     *
     * 例如 $seqName 为 posts，则存放该序列的表名称为 posts_seq。
     *
     * @param string $seqName
     * @param string $startValue
     *
     * @return int
     */
    abstract function next_id($seqName = 'dboseq', $startValue = 1);

    /**
     * 创建一个新的序列，失败时抛出异常
     *
     * 调用 next_id() 时，如果指定的序列不存在，则会自动调用 create_seq() 创建。
     * 开发者也可以自行调用 create_seq() 创建一个新序列。
     *
     * @param string $seqName
     * @param int $startValue
     */
    abstract function create_seq($seqName = 'dboseq', $startValue = 1);

    /**
     * 删除一个序列，失败时抛出异常
     *
     * @param string $seqName
     */
    abstract function drop_seq($seqName = 'dboseq');

    /**
     * 获取自增字段的最后一个值或者 next_id() 方法产生的最后一个值
     *
     * 某些数据库（例如 MySQL）可以将一个字段设置为自增。
     * 也就是每次往数据表插入一条记录，该字段的都会自动填充一个更大的新值。
     *
     * insert_id() 方法可以获得最后一次插入记录时产生的自增字段值，或者最后一次调用 next_id() 返回的值。
     *
     * 如果在一次操作中，插入了多条记录，那么 insert_id() 有可能返回的是第一条记录的自增值。
     * 这个问题是由数据库本身的实现决定的。
     *
     * @return int
     */
    abstract function insert_id();

    /**
     * 返回最近一次数据库操作受到影响的记录数
     *
     * 这些操作通常是插入记录、更新记录以及删除记录。
     * 不同的数据库对于其他操作，也可能影响到 affected_rows() 返回的值。
     *
     * @return int
     */
    abstract function affected_rows();

    /**
     * 执行一个查询，返回一个查询对象或者 boolean 值，出错时抛出异常
     *
     * $sql 是要执行的 SQL 语句字符串，而 $inputarr 则是提供给 SQL 语句中参数占位符需要的值。
     *
     * 如果执行的查询是诸如 INSERT、DELETE、UPDATE 等不会返回结果集的操作，
     * 则 execute() 执行成功后会返回 true，失败时将抛出异常。
     *
     * 如果执行的查询是 SELECT 等会返回结果集的操作，
     * 则 execute() 执行成功后会返回一个 DBO_Result 对象，失败时将抛出异常。
     *
     * DBO_Result 对象封装了查询结果句柄，而不是结果集。
     * 因此要获得查询的数据，需要调用 DBO_Result 的 fetch_all() 等方法。
     *
     * 如果希望执行 SQL 后直接获得结果集，可以使用驱动的 get_all()、get_row() 等方法。
     *
     * example:
     * <code>
     * $sql = "INSERT INTO posts (title, body) VALUES (?, ?)";
     * $dbo->execute($sql, array($title, $body));
     * </code>
     *
     * example:
     * <code>
     * $sql = "SELECT * FROM posts WHERE post_id < 12";
     * $handle = $dbo->execute($sql);
     * $rowset = $handle->fetch_all();
     * $handle->free();
     * </code>
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return DBO_Result
     */
    abstract function execute($sql, $inputarr = null);

    /*
     * 进行限定范围的查询，并且返回 DBO_Result 对象，出错时抛出异常
     *
     * 使用 select_limit()，可以限定 SELECT 查询返回的结果集的大小。
     * $length 参数指定结果集最多包含多少条记录。而 $offset 参数则指定在查询结果中，从什么位置开始提取记录。
     *
     * 假设 SELECT * FROM posts ORDER BY post_id ASC 的查询结果一共有 500 条记录。
     * 现在通过指定 $length 为 20，则可以限定只提取其中的 20 条记录作为结果集。
     * 进一步指定 $offset 参数为 59，则可以从查询结果的第 60 条记录开始提取 20 条作为结果集。
     *
     * 注意：$offset 参数是从 0 开始计算的。因此 $offset 为 59 时，实际上是从第 60 条记录开始提取。
     *
     * select_limit() 并不直接返回结果集，而是返回 DBO_Result 对象。
     * 因此需要调用 DBO_Result 对象的 fetch_all() 等方法来获得数据。
     *
     * example:
     * <code>
     * $sql = "SELECT * FROM posts WHERE created > ? ORDER BY post_id DESC";
     * $length = 20;
     * $offset = 0;
     * $current = time() - 60 * 60 * 24 * 15; // 查询创建时间在 15 天内的记录
     * $handle = $dbo->select_limit($sql, $length, $offset, array($current));
     * $rowset = $handle->fetch_all();
     * $handle->free();
     * </code>
     *
     * @param string $sql
     * @param int $length
     * @param int $offset
     * @param array $inputarr
     *
     * @return DBO_Result
     */
    abstract function select_limit($sql, $length = null, $offset = null, array $inputarr = null);

    /**
     * 执行一个查询并返回记录集，失败时抛出异常
     *
     * get_all() 等同于执行下面的代码：
     *
     * <code>
     * $rowset = $dbo->execute($sql, $inputarr)->fetch_all();
     * </code>
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return array
     */
    function get_all($sql, array $inputarr = null)
    {
        return $this->execute($sql, $inputarr)->fetch_all();
    }


    /**
     * 执行查询，返回第一条记录
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return mixed
     */
    function get_row($sql, array $inputarr = null)
    {
        return $this->select_limit($sql, 1, null, $inputarr)->fetch_row();
    }

    /**
     * 执行查询，返回第一条记录的第一个字段
     *
     * example:
     * <code>
     * $sql = "SELECT COUNT(*) FROM posts";
     * $count = $dbo->get_one($sql);
     * </code>
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return mixed
     */
    function get_one($sql, array $inputarr = null)
    {
        return $this->select_limit($sql, 1, null, $inputarr)->fetch_one();
    }

    /**
     * 执行查询，返回结果集的指定列
     *
     * example:
     * <code>
     * $sql = "SELECT post_id FROM posts";
     * $cols = $dbo->get_col($sql);
     * </code>
     *
     * @param string|resource $sql
     * @param int $col 要返回的列，0 为第一列
     * @param array $inputarr
     *
     * @return mixed
     */
    function get_col($sql, $col = 0, array $inputarr = null)
    {
        return $this->execute($sql, $inputarr)->fetch_col($col);
    }

    /**
     * 将 unix timestamp 转换为数据库可以接受的日期格式
     *
     * example:
     * <code>
     * // 假设 created 是 DATETIME 类型的字段
     * $sql = "INSERT INTO posts (title, body, created) VALUES (?, ?, ?)";
     * $created = $dbo->db_timestamp(time());
     * $dbo->execute($sql, array($title, $body, $created));
     * </code>
     *
     * @param int $timestamp
     *
     * @return string
     */
    function db_timestamp($timestamp)
    {
        return date($this->TIMESTAMP_FORMAT, $timestamp);
    }

    /**
     * 开始一个事务，并且返回一个 QDBO_Transaction 对象
     *
     * 使用 begin_trans() 的好处是在 QDBO_Transaction 对象销毁时，会自动提交事务或回滚事务。
     *
     * 传统的事务处理代码：
     * <code>
     * function doSometing()
     * {
     *      ...
     *
     *      $dbo->start_trans();
     *      try {
     *          $dbo->execute($sql);
     *      } catch (Exception $ex) {
     *          // 回滚事务
     *          $dbo->complete_trans(false);
     *          // 重新抛出异常
     *          throw $ex;
     *      }
     *      // 提交事务
     *      $dbo->complete_trans();
     * }
     * </code>
     *
     * 如果使用 begin_trans()，代码可以简化为：
     * <code>
     * function doSometing()
     * {
     *      $tran = $dbo->begin_trans();
     *      ...
     *      $dbo->execute($sql);
     *      ...
     *      // 退出函数时会自动提交事务
     * }
     * </code>
     *
     * 如果在 $dbo->execute($sql) 抛出了异常，那么 $tran 对象在销毁前会自动回滚事务。
     * 正常执行到 doSometing() 函数结束时，$tran 对象会在销毁前自动提交事务。
     *
     * <strong>
     * 使用 begin_trans() 最重要的一点就是必须用一个变量存储 begin_trans() 返回的 QDBO_Transaction 对象。
     * </strong>
     *
     * QDBO_Transaction 对象也提供了明确的事务提交和回滚操作。具体信息请参考 DBO_Transaction 类的描述。
     *
     * @return QDBO_Transaction
     */
    function begin_trans()
    {
        return new QDBO_Transaction($this);
    }

    /**
     * 开始一个事务
     *
     * 调用 start_trans() 开始一个事务后，应该在关闭数据库连接前调用 complete_trans() 提交或回滚事务。
     */
    abstract function start_trans();

    /**
     * 完成事务，根据事务期间的查询是否出错决定是提交还是回滚事务
     *
     * 如果 $commit_on_no_errors 参数为 true，当事务期间所有查询都成功完成时，则提交事务，否则回滚事务；
     * 如果 $commit_on_no_errors 参数为 false，则强制回滚事务。
     *
     * @param $commit_on_no_errors
     */
    abstract function complete_trans($commit_on_no_errors = true);

    /**
     * 指示在调用 complete_trans() 时回滚事务
     */
    function fail_trans()
    {
        $this->has_failed_query = true;
    }

    /**
     * 指示在调用 complateTrans() 时提交事务（如果 $commit_on_no_errors 参数为 true）
     */
    function success_trans()
    {
        $this->has_failed_query = false;
    }

    /**
     * 检查事务过程中是否出现失败的查询
     */
    function has_failed_query()
    {
        return $this->has_failed_query;
    }

    /**
     * 返回指定数据表（或者视图）的元数据
     *
     * 返回的结果是一个二维数组，每一项为一个字段的元数据。
     * 每个字段包含下列属性：
     *
     * - name:            字段名
     * - scale:           小数位数
     * - type:            字段类型
     * - simpleType:      简单字段类型（与数据库无关）
     * - maxLength:       最大长度
     * - notNull:         是否不允许保存 NULL 值
     * - pk:      是否是主键
     * - autoIncrement:   是否是自动增量字段
     * - binary:          是否是二进制数据
     * - unsigned:        是否是无符号数值
     * - hasDefault:      是否有默认值
     * - defaultValue:    默认值
     * - description:     字段描述
     *
     * simpleType 是下列值之一：
     *
     * - C char/varchar 等类型
     * - X text 等类型
     * - B 二进制数据
     * - N 数值或者浮点数
     * - D 日期
     * - T TimeStamp
     * - L 逻辑布尔值
     * - I 整数
     * - R 自动增量
     * - P 非自增的主键字段
     *
     * @param string $table_name
     * @param string $schema
     * @param boolean $quoteTablename
     *
     * @return array
     */
    abstract function meta_columns($table_name, $schema = null, $quoteTablename = true);

    /**
     * 获得所有数据表的名称
     *
     * @param string $pattern
     * @param string $schema
     *
     * @return array
     */
    abstract function meta_tables($pattern = null, $schema = null);

    /**
     * 根据包含记录内容的数组返回一条有效的 SQL 插入记录语句
     *
     * @param array $row
     * @param string $table 要插入的数据表
     * @param string $schema
     *
     * @return string
     */
    function get_insert_sql(array & $row, $table, $schema = null)
    {
        list($holders, $values) = $this->get_placeholder($row);
        $holders = implode(',', $holders);
        $fields = $this->qfields(array_keys($values));
        $table = $this->qtable($table, $schema);
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$holders})";
        return $sql;
    }

    /**
     * 根据包含记录内容的数组返回一条有效的 SQL 更新记录语句
     *
     * @param array $row
     * @param string $table 要插入的数据表
     * @param string $schema
     *
     * @return string
     */
    function get_update_sql(array & $row, $pk, $table, $schema = null)
    {
        $pkv = $row[$pk];
        unset($row[$pk]);
        list($pairs, ) = $this->get_placeholder_pairs($row);
        $row[$pk] = $pkv;
        $pairs = implode(',', $pairs);
        $table = $this->qtable($table, $schema);
        $pk = $this->qfield($pk);
        $sql = "UPDATE {$table} SET {$pairs} WHERE {$pk} = " . $this->qstr($pkv);
        return $sql;
    }

    /**
     * 确定驱动是否支持参数绑定
     *
     * @return boolean
     */
    function bind_enabled()
    {
        return $this->BIND_ENABLED;
    }

    /**
     * 返回驱动使用的参数占位符样式
     *
     * @return string
     */
    function param_style()
    {
        return $this->PARAM_STYLE;
    }

    /**
     * 根据驱动的参数占位符样式，返回包含参数占位符及有效数据的数组
     *
     * @param array $inputarr
     * @param array $fields
     *
     * @return array
     */
    function get_placeholder(array & $inputarr, array $fields = null)
    {
        $holders = array();
        $values = array();
        if (is_array($fields)) {
            $fields = array_change_key_case(array_flip($fields), CASE_LOWER);
            foreach (array_keys($inputarr) as $key) {
                if (!isset($fields[strtolower($key)])) { continue; }
                if ($this->PARAM_STYLE == self::param_qm) {
                    $holders[] = $this->PARAM_STYLE;
                } else {
                    $holders[] = $this->PARAM_STYLE . $key;
                }
                $values[$key] =& $inputarr[$key];
            }
        } else {
            foreach (array_keys($inputarr) as $key) {
                if ($this->PARAM_STYLE == self::param_qm) {
                    $holders[] = $this->PARAM_STYLE;
                } else {
                    $holders[] = $this->PARAM_STYLE . $key;
                }
                $values[$key] =& $inputarr[$key];
            }
        }
        return array($holders, $values);
    }

    /**
     * 根据驱动的参数占位符样式，返回包含参数及占位符字符串对、有效数据的数组
     *
     * @param array $inputarr
     * @param array $fields
     *
     * @return array
     */
    function get_placeholder_pairs(array & $inputarr, array $fields = null)
    {
        $pairs = array();
        $values = array();
        if (is_array($fields)) {
            $fields = array_change_key_case(array_flip($fields), CASE_LOWER);
            foreach (array_keys($inputarr) as $key) {
                if (!isset($fields[strtolower($key)])) { continue; }
                $qkey = $this->qfield($key);
                if ($this->PARAM_STYLE == self::param_qm) {
                    $pairs[] = "{$qkey}={$this->PARAM_STYLE}";
                } else {
                    $pairs[] = "{$qkey}={$this->PARAM_STYLE}{$key}";
                }
                $values[$key] =& $inputarr[$key];
            }
        } else {
            foreach (array_keys($inputarr) as $key) {
                $qkey = $this->qfield($key);
                if ($this->PARAM_STYLE == self::param_qm) {
                    $pairs[] = "{$qkey}={$this->PARAM_STYLE}";
                } else {
                    $pairs[] = "{$qkey}={$this->PARAM_STYLE}{$key}";
                }
                $values[$key] =& $inputarr[$key];
            }
        }
        return array($pairs, $values);
    }

    /**
     * 将字符串形式的 DSN 转换为数组
     *
     * @param string $dsn
     *
     * @return array
     */
    static function parse_dsn($dsn)
    {
        $dsn = str_replace('@/', '@localhost/', $dsn);
        $parse = parse_url($dsn);
        if (empty($parse['scheme'])) { return false; }

        $dsn = array();
        $dsn['host']     = isset($parse['host']) ? $parse['host'] : 'localhost';
        $dsn['port']     = isset($parse['port']) ? $parse['port'] : '';
        $dsn['login']    = isset($parse['user']) ? $parse['user'] : '';
        $dsn['password'] = isset($parse['pass']) ? $parse['pass'] : '';
        $dsn['driver']   = isset($parse['scheme']) ? strtolower($parse['scheme']) : '';
        $dsn['database'] = isset($parse['path']) ? substr($parse['path'], 1) : '';

        return $dsn;
    }

}

/**
 * DBO_Result 是封装查询句柄的基础类
 *
 * @package Database
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
abstract class QDBO_Result
{
    /**
     * 指示返回结果集的形式
     *
     * @var const
     */
    public $fetch_mode;

    /**
     * 查询句柄
     *
     * @var resource
     */
    protected $handle = null;

    /**
     * 构造函数
     *
     * @param resource $handle
     * @param const $fetch_mode
     */
    function __construct($handle, $fetch_mode)
    {
        if (is_resource($handle) || is_object($handle)) {
            $this->handle = $handle;
        }
        $this->fetch_mode = $fetch_mode;
    }

    /**
     * 析构函数
     */
    function __destruct()
    {
        $this->free();
    }

    /**
     * 返回句柄
     *
     * @return resource
     */
    function handle()
    {
        return $this->handle;
    }

    /**
     * 指示句柄是否有效
     *
     * @return boolean
     */
    function valid()
    {
        return $this->handle != null;
    }

    /**
     * 释放句柄
     */
    abstract function free();

    /**
     * 从查询句柄提取一条记录
     *
     * @return array
     */
    abstract function fetch_row();

    /**
     * 从查询句柄中提取记录集
     *
     * @return array
     */
    function fetch_all()
    {
        $rowset = array();
        while (($row = $this->fetch_row())) {
            $rowset[] = $row;
        }
        return $rowset;
    }

    /**
     * 从查询句柄提取一条记录，并返回该记录的第一个字段
     *
     * @return mixed
     */
    function fetch_one()
    {
        $row = $this->fetch_row();
        return $row ? reset($row) : null;
    }

    /**
     * 从查询句柄提取记录集，并返回包含每行指定列数据的数组，如果 $col 为 0，则返回第一列
     *
     * @param int $col
     *
     * @return array
     */
    function fetch_col($col = 0)
    {
        $mode = $this->fetch_mode;
        $this->fetch_mode = QDBO_Abstract::fetch_mode_array;
        $cols = array();
        while (($row = $this->fetch_row())) {
            $cols[] = $row[$col];
        }
        $this->fetch_mode = $mode;
        return $cols;
    }

    /**
     * 返回记录集和指定字段的值集合，以及以该字段值作为索引的结果集
     *
     * 假设数据表 posts 有字段 post_id 和 title，并且包含下列数据：
     *
     * <code>
     * +---------+-----------------------+
     * | post_id | title                 |
     * +---------+-----------------------+
     * |       1 | It's live             |
     * +---------+-----------------------+
     * |       2 | QeePHP Recipes        |
     * +---------+-----------------------+
     * |       7 | QeePHP User manual    |
     * +---------+-----------------------+
     * |      15 | QeePHP Quickstart     |
     * +---------+-----------------------+
     * </code>
     *
     * 现在我们查询 posts 表的数据，并以 post_id 的值为结果集的索引值：
     *
     * example:
     * <code>
     * $sql = "SELECT * FROM posts";
     * $handle = $dbo->execute($sql);
     *
     * $fieldValues = array();
     * $reference = array();
     * $rowset = $handle->fetch_all_refby('post_id', $fieldValues, $reference);
     * </code>
     *
     * 上述代码执行后，$rowset 包含 posts 表中的全部 4 条记录。
     * 而 $fieldValues 则是一个包含 4 条记录 post_id 字段值的一维数组 array(1, 2, 7, 15)。
     * 最后，$reference 是如下形式的数组：
     *
     * <code>
     * $reference = array(
     *      1 => & array(...),
     *      2 => & array(...),
     *      7 => & array(...),
     *     15 => & array(...)
     * );
     * </code>
     *
     * $reference 用 post_id 字段值作为索引值，并且指向 $rowset 中 post_id 值相同的记录。
     * 由于是以引用方式构造的 $reference 数组，因此并不会占用双倍内存。
     *
     * @param string $field
     * @param array $fieldValues
     * @param array $reference
     *
     * @return array
     */
    function fetch_all_refby($field, array & $fieldValues, array & $reference)
    {
        $fieldValues = array();
        $reference = array();
        $offset = 0;
        $data = array();

        while (($row = $this->fetch_row())) {
            $fieldValue = $row[$field];
            $data[$offset] = $row;
            $fieldValues[$offset] = $fieldValue;
            $reference[$fieldValue] =& $data[$offset];
            $offset++;
        }

        return $data;
    }

    /**
     * 将两个数据集按照指定字段的值进行组装
     *
     * 表数据入口使用该方法组装来自两个数据表的数据。
     *
     * @param QDBO_Result $handle
     * @param array $assocRowset
     * @param string $mappingName
     * @param boolean $oneToOne
     * @param string $refKeyName
     */
    function assemble(QDBO_Result $handle, array & $assocRowset, $mappingName, $oneToOne, $refKeyName)
    {
        if ($oneToOne) {
            // 一对一组装数据
            while (($row = $handle->fetch_row())) {
                $rkv = $row[$refKeyName];
                unset($row[$refKeyName]);
                $assocRowset[$rkv][$mappingName] = $row;
            }
        } else {
            // 一对多组装数据
            while (($row = $handle->fetch_row())) {
                $rkv = $row[$refKeyName];
                unset($row[$refKeyName]);
                $assocRowset[$rkv][$mappingName][] = $row;
            }
        }
    }
}

/**
 * QDBO_Transaction 类封装了一个事务操作
 *
 * @package Database
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class QDBO_Transaction
{
    /**
     * 数据库访问对象
     *
     * @var QDBO_Abstract
     */
    protected $dbo;

    /**
     * 指示当前是否在事务中
     *
     * @var boolean
     */
    protected $inTran;

    /**
     * 构造函数
     *
     * @param QDBO_Abstract $dbo
     */
    function __construct(QDBO_Abstract $dbo)
    {
        $this->dbo = $dbo;
        $this->dbo->start_trans();
        $this->inTran = true;
    }

    /**
     * 析构函数
     */
    function __destruct()
    {
        if ($this->inTran) {
            $this->dbo->complete_trans();
        }
    }

    /**
     * 完成事务，根据事务期间的查询是否出错决定是提交还是回滚事务
     *
     * 如果 $commit_on_no_errors 参数为 true，当事务期间所有查询都成功完成时，则提交事务，否则回滚事务；
     * 如果 $commit_on_no_errors 参数为 false，则强制回滚事务。
     *
     * @param $commit_on_no_errors
     */
    function commit($commit_on_no_errors = true)
    {
        $this->dbo->complete_trans($commit_on_no_errors);
        $this->inTran = false;
    }

    /**
     * 回滚事务
     */
    function rollback()
    {
        $this->dbo->complete_trans(false);
        $this->inTran = false;
    }

    /**
     * 指示在调用 complete_trans() 时回滚事务
     */
    function fail_trans()
    {
        $this->dbo->fail_trans();
    }

    /**
     * 指示在调用 complateTrans() 时提交事务（如果 $commit_on_no_errors 参数为 true）
     */
    function success_trans()
    {
        $this->dbo->success_trans();
    }

    /**
     * 检查事务过程中是否出现失败的查询
     */
    function has_failed_query()
    {
        return $this->dbo->has_failed_query();
    }
}
