<?php
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 QeePHP 项目的一部分
//
// Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
//
// 要查看完整的版权信息和许可信息，请查看源代码中附带的 COPYRIGHT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 FLEA_Db_Driver 类和 FLEA_Db_Driver_Handle 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Database
 * @version $Id$
 */

// {{{ includes
require_once 'FLEA/Db/Transaction.php';
// }}}

/**
 * FLEA_Db_Driver 是所有数据库驱动的基础类，并且提供创建特定类型数据库驱动对象的工厂方法
 *
 * @package Database
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.2
 */
abstract class FLEA_Db_Driver
{
    /**
     * 可用的查询参数占位符形式
     *
     * example:
     * <code>
     * // PARAM_QM 形式
     * $sql = "SELECT * FROM posts WHERE post_id = ?
     * $dbo->execute($sql, array($post_id));
     *
     * // PARAM_CL_NAMED 形式
     * $sql = "SELECT * FROM posts WHERE post_id = :post_id
     * $dbo->execute($sql, array('post_id' => $post_id));
     *
     * // PARAM_DL_SEQUENCE 形式
     * $sql = "SELECT * FROM posts WHERE post_id = $1
     * $dbo->execute($sql, array($post_id));
     *
     * // PARAM_AT_NAMED 形式
     * $sql = "SELECT * FROM posts WHERE post_id = @post_id
     * $dbo->execute($sql, array('post_id' => $post_id));
     * </code>
     *
     * 默认情况下，不同的数据库驱动会在其 $paramStyle 属性中指定特定的占位符样式。
     * 例如 MySQL 驱动使用 PARAM_QM，而 Oracle 则使用 PARAM_CL_NAMED。
     * 这种区别，是源自 PHP 数据库扩展的实现决定的。
     *
     * 不过为了简化应用程序在不同数据库之间的迁移，以及简化访问异种数据库时的代码。
     * QeePHP 允许开发者为数据库驱动强制指定占位符样式。
     * 此时数据库驱动会根据情况模拟指定的占位符样式。
     */
    const PARAM_QM          = 1; // 问号作为参数占位符
    const PARAM_CL_NAMED    = 2; // 冒号开始的命名参数
    const PARAM_DL_SEQUENCE = 3; // $符号开始的序列
    const PARAM_AT_NAMED    = 4; // @开始的命名参数

    /**
     * 可用的查询结果集返回形式
     */
    const FETCH_MODE_ARRAY  = 1; // 返回的每一个记录就是一个索引数组
    const FETCH_MODE_ASSOC  = 2; // 返回的每一个记录就是一个以字段名作为键名的数组

    /**
     * 指示查询参数的样式，继承类必须重载该成员变量
     *
     * @var const
     */
    public $paramStyle = self::PARAM_QM;

    /**
     * 指示返回结果集的形式
     *
     * @var const
     */
    public $fetchMode = self::FETCH_MODE_ASSOC;

    /**
     * 数据库连接信息
     *
     * @var array
     */
    protected $_dsn = null;

    /**
     * 数据库连接句柄
     *
     * @var resource
     */
    protected $_conn = null;

    /**
     * 最近一次插入操作或者 nextId() 操作返回的 ID 值
     *
     * @var mixed
     */
    protected $_insertId = null;

    /**
     * 保存和还原 $fetchMode 的堆栈
     *
     * @var array
     */
    protected $_fetchModeStack = array();

    /**
     * 用于描绘 true、false 和 null 的数据库值
     */
    protected $_TRUE_VALUE  = 1;
    protected $_FALSE_VALUE = 0;
    protected $_NULL_VALUE  = 'NULL';

    /**
     * unix timestamp 转换为日期时使用的格式
     */
    protected $_TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    /**
     * 指示事务执行期间是否发生了错误
     *
     * @var boolean
     */
    protected $_hasFailedQuery = true;

    /**
     * 获取一个数据库访问对象实例
     *
     * DSN 是 Database Source Name 的缩写，可以理解为数据源名。
     *
     * 在 QeePHP 中，DSN 是一个数组或字符串，包含了连接数据库需要的各种信息，例如主机、用户名、密码等。
     *
     * DSN 的正确写法：
     * example:
     * <code>
     * $dsn = array(
     *      'driver'   => 'mysql',
     *      'host'     => 'localhost',
     *      'login'    => 'username',
     *      'password' => 'password',
     *      'database' => 'test_db',
     * );
     *
     * // 上述连接信息对应的字符串形式：
     * $dsn = 'mysql://username:password@localhost/test_db';
     * </code>
     *
     * @param array|string $dsn
     *
     * @return FLEA_Db_Driver
     */
    static public function getDBO($dsn = null)
    {
        static $qee = false;
        if (!$qee) { $qee = class_exists('FLEA'); }

        if (empty($dsn) && $qee) {
            if (FLEA::isRegister('QeePHP.DBO_default')) {
                return FLEA::registry('QeePHP.DBO_default');
            }
            $dsn = FLEA::getAppInf('dbDSN');
            $default = true;
        } else {
            $default = false;
        }

        $dsn = self::parseDSN($dsn);
        if ($dsn == false) {
            require_once 'FLEA/Db/Exception/InvalidDSN.php';
            throw new FLEA_Db_Exception_InvalidDSN($dsn);
        }

        $dsnid = 'QeePHP.DBO_#' . $dsn['id'];
        if ($qee && FLEA::isRegistered($dsnid)) {
            return FLEA::registry($dsnid);
        }

        $driver = ucfirst(strtolower($dsn['driver']));
        $class = 'FLEA_Db_Driver_' . $driver;
        require_once "FLEA/Db/Driver/{$driver}.php";
        $dbo = new $class($dsn);

        if ($qee) {
            FLEA::register($dbo, $dsnid);
            if ($default) {
                FLEA::register($dbo, 'QeePHP.DBO_default');
            }
        }
        return $dbo;
    }

    /**
     * 分析 DSN 字符串或数组，返回包含 DSN 连接信息的数组，失败返回 false
     *
     * @param array|string $dsn
     *
     * @return array
     */
    static function parseDSN($dsn)
    {
        if (is_array($dsn)) {
            $dsn['host']        = isset($dsn['host']) ? $dsn['host'] : '';
            $dsn['port']        = isset($dsn['port']) ? $dsn['port'] : '';
            $dsn['login']       = isset($dsn['login']) ? $dsn['login'] : '';
            $dsn['password']    = isset($dsn['password']) ? $dsn['password'] : '';
            $dsn['database']    = isset($dsn['database']) ? $dsn['database'] : '';
            $dsn['options']     = isset($dsn['options']) ? $dsn['options'] : '';
            $dsn['tablePrefix'] = isset($dsn['tablePrefix']) ? $dsn['tablePrefix'] : '';
            $dsn['schema']      = isset($dsn['schema']) ? $dsn['schema'] : '';
        } else {
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
            if (isset($parse['query'])) {
                $dsn['options'] = array();
                parse_str($parse['query'], $dsn['options']);
            } else {
                $dsn['options'] = null;
            }
        }

        if (!isset($dsn['driver'])) {
            return false;
        }

        $dsnid = "{$dsn['driver']}://{$dsn['login']}@{$dsn['host']}_{$dsn['tablePrefix']}/{$dsn['database']}/";
        $dsnid .= serialize($dsn['options']);
        $dsn['id'] = $dsnid;
        return $dsn;
    }

    /**
     * 构造函数
     *
     * @param array|string $dsn
     */
    protected function __construct($dsn)
    {
        $this->_dsn = $dsn;
    }

    /**
     * 连接数据库，失败时抛出异常
     *
     * 如果已经连接到了数据库，再次连接不会造成任何影响。
     */
    abstract public function connect();

    /**
     * 关闭数据库连接，失败时抛出异常
     *
     * 如果当前没有连接到数据库，尝试关闭连接会抛出一个异常。
     */
    abstract public function close();

    /**
     * 返回连接数据库的句柄
     *
     * @return resource
     */
    public function handle()
    {
        return $this->_conn;
    }

    /**
     * 选择要操作的数据库
     *
     * @param string $database
     */
    abstract public function selectDB($database);

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
     * $title = isset($_POST['title']) ? $_POST['title'] : null;
     * $body = isset($_POST['body']) ? $_POST['body'] : null;
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
    abstract public function qstr($value);

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
     * @param string $tableName
     * @param string $schema
     *
     * @return string
     */
    abstract public function qtable($tableName, $schema = null);

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
     * @param string $tableName
     * @param string $schema
     *
     * @return string
     */
    abstract public function qfield($fieldName, $tableName = null, $schema = null);

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
     * @param string $tableName
     * @param string $schema
     * @param boolean $returnArray
     *
     * @return string
     */
    abstract public function qfields($fields, $tableName = null, $schema = null, $returnArray = false);

    /**
     * 为数据表产生下一个序列值，失败时抛出异常
     *
     * 调用 nextId() 方法，将获得指定名称序列的下一个值。
     * 此处所指的序列，是指一个不断增大的数字。
     *
     * 假设本次调用 nextId() 返回 3，那么下一次调用 nextId() 就会返回一个比 3 更大的值。
     * nextId() 返回的序列值，可以作为记录的主键字段值，以便确保插入记录时总是使用不同的主键值。
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
    abstract public function nextId($seqName = 'sdboseq', $startValue = 1);

    /**
     * 创建一个新的序列，失败时抛出异常
     *
     * 调用 nextId() 时，如果指定的序列不存在，则会自动调用 createSeq() 创建。
     * 开发者也可以自行调用 createSeq() 创建一个新序列。
     *
     * @param string $seqName
     * @param int $startValue
     */
    abstract public function createSeq($seqName = 'sdboseq', $startValue = 1);

    /**
     * 删除一个序列，失败时抛出异常
     *
     * @param string $seqName
     */
    abstract public function dropSeq($seqName = 'sdboseq');

    /**
     * 获取自增字段的最后一个值或者 nextId() 方法产生的最后一个值
     *
     * 某些数据库（例如 MySQL）可以将一个字段设置为自增。
     * 也就是每次往数据表插入一条记录，该字段的都会自动填充一个更大的新值。
     *
     * insertId() 方法可以获得最后一次插入记录时产生的自增字段值，或者最后一次调用 nextId() 返回的值。
     *
     * 如果在一次操作中，插入了多条记录，那么 insertId() 有可能返回的是第一条记录的自增值。
     * 这个问题是由数据库本身的实现决定的。
     *
     * @return int
     */
    abstract public function insertId();

    /**
     * 返回最近一次数据库操作受到影响的记录数
     *
     * 这些操作通常是插入记录、更新记录以及删除记录。
     * 不同的数据库对于其他操作，也可能影响到 affectedRows() 返回的值。
     *
     * @return int
     */
    abstract public function affectedRows();

    /**
     * 执行一个查询，返回一个查询对象或者 boolean 值，出错时抛出异常
     *
     * $sql 是要执行的 SQL 语句字符串，而 $inputarr 则是提供给 SQL 语句中参数占位符需要的值。
     *
     * 如果执行的查询是诸如 INSERT、DELETE、UPDATE 等不会返回结果集的操作，
     * 则 execute() 执行成功后会返回 true，失败时将抛出异常。
     *
     * 如果执行的查询是 SELECT 等会返回结果集的操作，
     * 则 execute() 执行成功后会返回一个 FLEA_Db_Driver_Handle 对象，失败时将抛出异常。
     *
     * FLEA_Db_Driver_Handle 对象封装了查询结果句柄，而不是结果集。
     * 因此要获得查询的数据，需要调用 FLEA_Db_Driver_Handle 的 fetchAll() 等方法。
     *
     * 如果希望执行 SQL 后直接获得结果集，可以使用驱动的 getAll()、getRow() 等方法。
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
     * $rowset = $handle->fetchAll();
     * $handle->free();
     * </code>
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return FLEA_Db_Driver_Handle
     */
    abstract public function execute($sql, $inputarr = null);

    /*
     * 进行限定范围的查询，并且返回 FLEA_Db_Driver_Handle 对象，出错时抛出异常
     *
     * 使用 selectLimit()，可以限定 SELECT 查询返回的结果集的大小。
     * $length 参数指定结果集最多包含多少条记录。而 $offset 参数则指定在查询结果中，从什么位置开始提取记录。
     *
     * 假设 SELECT * FROM posts ORDER BY post_id ASC 的查询结果一共有 500 条记录。
     * 现在通过指定 $length 为 20，则可以限定只提取其中的 20 条记录作为结果集。
     * 进一步指定 $offset 参数为 59，则可以从查询结果的第 60 条记录开始提取 20 条作为结果集。
     *
     * 注意：$offset 参数是从 0 开始计算的。因此 $offset 为 59 时，实际上是从第 60 条记录开始提取。
     *
     * selectLimit() 并不直接返回结果集，而是返回 FLEA_Db_Driver_Handle 对象。
     * 因此需要调用 FLEA_Db_Driver_Handle 对象的 fetchAll() 等方法来获得数据。
     *
     * example:
     * <code>
     * $sql = "SELECT * FROM posts WHERE created > ? ORDER BY post_id DESC";
     * $length = 20;
     * $offset = 0;
     * $current = time() - 60 * 60 * 24 * 15; // 查询创建时间在 15 天内的记录
     * $handle = $dbo->selectLimit($sql, $length, $offset, array($current));
     * $rowset = $handle->fetchAll();
     * $handle->free();
     * </code>
     *
     * @param string $sql
     * @param int $length
     * @param int $offset
     * @param array $inputarr
     *
     * @return FLEA_Db_Driver_Handle
     */
    abstract public function selectLimit($sql, $length = null, $offset = null, array $inputarr = null);

    /**
     * 执行一个查询并返回记录集，失败时抛出异常
     *
     * getAll() 等同于执行下面的代码：
     *
     * <code>
     * $rowset = $dbo->execute($sql, $inputarr)->fetchAll();
     * </code>
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return array
     */
    public function & getAll($sql, array $inputarr = null)
    {
        $rowset = $this->execute($sql, $inputarr)->fetchAll();
        return $rowset;
    }

    /**
     * 执行查询，返回第一条记录
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return mixed
     */
    public function & getRow($sql, array $inputarr = null)
    {
        $row = $this->selectLimit($sql, 1, null, $inputarr)->fetchRow();
        return $row;
    }

    /**
     * 执行查询，返回第一条记录的第一个字段
     *
     * example:
     * <code>
     * $sql = "SELECT COUNT(*) FROM posts";
     * $count = $dbo->getOne($sql);
     * </code>
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return mixed
     */
    public function getOne($sql, array $inputarr = null)
    {
        return $this->selectLimit($sql, 1, null, $inputarr)->fetchOne();
    }

    /**
     * 执行查询，返回结果集的指定列
     *
     * example:
     * <code>
     * $sql = "SELECT post_id FROM posts";
     * $cols = $dbo->getCol($sql);
     * </code>
     *
     * @param string|resource $sql
     * @param int $col 要返回的列，0 为第一列
     * @param array $inputarr
     *
     * @return mixed
     */
    public function & getCol($sql, $col = 0, array $inputarr = null)
    {
        return $this->execute($sql, $inputarr)->fetchCol($col);
    }

    /**
     * 将 unix timestamp 转换为数据库可以接受的日期格式
     *
     * example:
     * <code>
     * // 假设 created 是 DATETIME 类型的字段
     * $sql = "INSERT INTO posts (title, body, created) VALUES (?, ?, ?)";
     * $created = $dbo->dbTimeStamp(time());
     * $dbo->execute($sql, array($title, $body, $created);
     * </code>
     *
     * @param int $timestamp
     */
    public function dbTimeStamp($timestamp)
    {
        return date($this->_TIMESTAMP_FORMAT, $timestamp);
    }

    /**
     * 开始一个事务，并且返回一个 FLEA_Db_Transaction 对象
     *
     * 使用 beginTrans() 的好处是在 FLEA_Db_Transaction 对象销毁时，会自动提交事务或回滚事务。
     *
     * 传统的事务处理代码：
     * <code>
     * function doSometing()
     * {
     *      ...
     *
     *      $dbo->startTrans();
     *      try {
     *          $dbo->execute($sql);
     *      } catch (Exception $ex) {
     *          // 回滚事务
     *          $dbo->completeTrans(false);
     *          // 重新抛出异常
     *          throw $ex;
     *      }
     *      // 提交事务
     *      $dbo->completeTrans();
     * }
     * </code>
     *
     * 如果使用 beginTrans()，代码可以简化为：
     * <code>
     * function doSometing()
     * {
     *      ...
     *
     *      $tran = $dbo->beginTrans();
     *      $dbo->execute($sql);
     * }
     * </code>
     *
     * 如果在 $dbo->execute($sql) 抛出了异常，那么 $tran 对象在销毁前会自动回滚事务。
     * 正常执行到 doSometing() 函数结束时，$tran 对象会在销毁前自动提交事务。
     *
     * <strong>
     * 使用 beginTrans() 最重要的一点就是必须用一个变量存储 beginTrans() 返回的 FLEA_Db_Transaction 对象。
     * </strong>
     *
     * FLEA_Db_Transaction 对象也提供了明确的事务提交和回滚操作。具体信息请参考 FLEA_Db_Transaction 类的描述。
     *
     * @return FLEA_Db_Transaction
     */
    public function beginTrans()
    {
        return new FLEA_Db_Transaction($this);
    }

    /**
     * 开始一个事务
     *
     * 调用 startTrans() 开始一个事务后，应该在关闭数据库连接前调用 completeTrans() 提交或回滚事务。
     */
    abstract public function startTrans();

    /**
     * 完成事务，根据事务期间的查询是否出错决定是提交还是回滚事务
     *
     * 如果 $commitOnNoErrors 参数为 true，当事务期间所有查询都成功完成时，则提交事务，否则回滚事务；
     * 如果 $commitOnNoErrors 参数为 false，则强制回滚事务。
     *
     * @param $commitOnNoErrors
     */
    abstract public function completeTrans($commitOnNoErrors = true);

    /**
     * 指示在调用 completeTrans() 时回滚事务
     */
    public function failTrans()
    {
        $this->_hasFailedQuery = true;
    }

    /**
     * 指示在调用 complateTrans() 时提交事务（如果 $commitOnNoErrors 参数为 true）
     */
    public function successTrans()
    {
        $this->_hasFailedQuery = false;
    }

    /**
     * 检查事务过程中是否出现失败的查询
     */
    public function hasFailedQuery()
    {
        return $this->_hasFailedQuery;
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
     * - primaryKey:      是否是主键
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
     * @param string $table
     * @param string $schema
     *
     * @return array
     */
    abstract public function & metaColumns($table, $schema = null);

    /**
     * 保存驱动当前的 $fetchMode 属性，以便稍后用 popFetchMode() 方法还原
     *
     * pushFetchMode() 和 popFetchMode() 应该成对出现。
     *
     * example:
     * <code>
     * $dbo->pushFetchMode();
     * // 临时改变 $fetchMode
     * $dbo->fetchMode = $dbo->FETCH_MODE_ARRAY;
     *
     * ...
     *
     * // 还原改变之前的 $fetchMode
     * $dbo->popFetchMode();
     * </code>
     *
     * pushFetchMode() 可以调用多次。
     */
    public function pushFetchMode()
    {
       array_push($this->_fetchModeStack, $this->fetchMode);
    }

    /**
     * 还原最近一次保存的 $fetchMode 属性
     */
    public function popFetchMode()
    {
        $this->fetchMode = array_pop($this->_fetchModeStack);
    }
}



/**
 * FLEA_Db_Driver_Handle 是封装查询句柄的基础类
 *
 * @package Database
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
abstract class FLEA_Db_Driver_Handle
{
    /**
     * 返回记录的形式
     * @var const
     */
    public $fetchMode = FLEA_Db_Driver::FETCH_MODE_ASSOC;

    /**
     * 查询句柄
     *
     * @var resource
     */
    protected $_handle = null;

    /**
     * 保存和还原 $fetchMode 的堆栈
     *
     * @var array
     */
    protected $_fetchModeStack = array();

    /**
     * 构造函数
     *
     * @param resource $handle
     * @param const $fetchMode
     */
    public function __construct($handle, $fetchMode)
    {
        if (is_resource($handle) || is_object($handle)) {
            $this->_handle = $handle;
        }
        $this->fetchMode = $fetchMode;
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->free();
    }

    /**
     * 返回句柄
     *
     * @return resource
     */
    public function handle()
    {
        return $this->_handle;
    }

    /**
     * 指示句柄是否有效
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->_handle != null;
    }

    /**
     * 释放句柄
     */
    abstract public function free();

    /**
     * 从查询句柄提取一条记录
     *
     * @return array
     */
    abstract public function fetchRow();

    /**
     * 从查询句柄中提取记录集
     *
     * @return array
     */
    public function & fetchAll()
    {
        $rowset = array();
        while ($row = $this->fetchRow()) {
            $rowset[] = $row;
        }
        return $rowset;
    }

    /**
     * 从查询句柄提取一条记录，并返回该记录的第一个字段
     *
     * @return mixed
     */
    public function fetchOne()
    {
        $row = $this->fetchRow();
        return reset($row);
    }

    /**
     * 从查询句柄提取记录集，并返回包含每行指定列数据的数组，如果 $col 为 0，则返回第一列
     *
     * @param int $col
     *
     * @return array
     */
    public function & fetchCol($col = 0)
    {
        $this->pushFetchMode();
        $this->fetchMode = FLEA_Db_Driver::FETCH_MODE_ARRAY;
        $cols = array();
        while ($row = $this->fetchRow()) {
            $cols[] = $row[$col];
        }
        $this->popFetchMode();
        return $cols;
    }

    /**
     * 保存当前的 $fetchMode 属性，以便稍后用 popFetchMode() 方法还原
     */
    public function pushFetchMode()
    {
       array_push($this->_fetchModeStack, $this->fetchMode);
    }

    /**
     * 还原最近一次保存的 $fetchMode 属性
     */
    public function popFetchMode()
    {
        $this->fetchMode = array_pop($this->_fetchModeStack);
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
     * $rowset = $handle->fetchAllWithFieldRefs('post_id', $fieldValues, $reference);
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
    public function & fetchAllWithFieldRefs($field, array & $fieldValues, array & $reference)
    {
        $fieldValues = array();
        $reference = array();
        $offset = 0;
        $data = array();

        while ($row = $this->fetchRow()) {
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
     * 该方法主要用于 FLEA_Db_TableDataGateway 组装来自两个数据表的数据。
     *
     * @param FLEA_Db_Driver_Handle $handle
     * @param array $assocRowset
     * @param string $mappingName
     * @param boolean $oneToOne
     * @param string $refKeyName
     */
    public function assemble(FLEA_Db_Driver_Handle $handle, array & $assocRowset, $mappingName, $oneToOne, $refKeyName)
    {
        if ($oneToOne) {
            // 一对一组装数据
            while ($row = $handle->fetchRow()) {
                $rkv = $row[$refKeyName];
                unset($row[$refKeyName]);
                $assocRowset[$rkv][$mappingName] = $row;
            }
        } else {
            // 一对多组装数据
            while ($row = $handle->fetchRow()) {
                $rkv = $row[$refKeyName];
                unset($row[$refKeyName]);
                $assocRowset[$rkv][$mappingName][] = $row;
            }
        }
    }
}
