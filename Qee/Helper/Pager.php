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
 * 定义 Qee_Helper_Pager 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
 * @author 起源科技(www.qeeyuan.com)
 * @package Core
 * @version $Id$
 */

/**
 * Qee_Helper_Pager 类提供数据查询分页功能
 *
 * Qee_Helper_Pager 使用很简单，只需要构造时传入 Qee_Db_TableDataGateway 实例以及查询条件即可。
 *
 * @package Core
 * @author 起源科技(www.qeeyuan.com)
 * @version 1.0
 */
class Qee_Helper_Pager
{
    /**
     * 如果 $this->source 是一个 Qee_Db_TableDataGateway 对象，则调用
     * $this->source->findAll() 来获取记录集。
     *
     * 否则通过 $this->dbo->selectLimit() 来获取记录集。
     *
     * @var Qee_Db_TableDataGateway|string
     */
    public $source;

    /**
     * 数据库访问对象，当 $this->source 参数为 SQL 语句时，必须调用
     * $this->setDBO() 设置查询时要使用的数据库访问对象。
     *
     * @var SDBO
     */
    public $dbo;

    /**
     * 查询条件
     *
     * @var mixed
     */
    protected $_conditions;

    /**
     * 排序
     *
     * @var string
     */
    protected $_sortby;

    /**
     * 计算实际页码时的基数
     *
     * @var int
     */
    public $basePageIndex = 0;

    /**
     * 每页记录数
     *
     * @var int
     */
    public $pageSize = -1;

    /**
     * 总记录数
     *
     * @var int
     */
    public $totalCount = -1;

    /**
     * 符合条件的记录总数
     *
     * @var int
     */
    public $count = -1;

    /**
     * 符合条件的记录页数
     *
     * @var int
     */
    public $pageCount = -1;

    /**
     * 第一页的索引，从 0 开始
     *
     * @var int
     */
    public $firstPage = -1;

    /**
     * 第一页的页码
     *
     * @var int
     */
    public $firstPageNumber = -1;

    /**
     * 最后一页的索引，从 0 开始
     *
     * @var int
     */
    public $lastPage = -1;

    /**
     * 最后一页的页码
     *
     * @var int
     */
    public $lastPageNumber = -1;

    /**
     * 上一页的索引
     *
     * @var int
     */
    public $prevPage = -1;

    /**
     * 上一页的页码
     *
     * @var int
     */
    public $prevPageNumber = -1;

    /**
     * 下一页的索引
     *
     * @var int
     */
    public $nextPage = -1;

    /**
     * 下一页的页码
     *
     * @var int
     */
    public $nextPageNumber = -1;

    /**
     * 当前页的索引
     *
     * @var int
     */
    public $currentPage = -1;

    /**
     * 当前页的页码
     *
     * @var int
     */
    public $currentPageNumber = -1;

    /**
     * 构造函数
     *
     * 如果 $source 参数是一个 TableDataGateway 对象，则 Qee_Helper_Pager 会调用
     * 该 TDG 对象的 findCount() 和 findAll() 来确定记录总数并返回记录集。
     *
     * 如果 $source 参数是一个字符串，则假定为 SQL 语句。这时，Qee_Helper_Pager
     * 不会自动调用计算各项分页参数。必须通过 setCount() 方法来设置作为分页计算
     * 基础的记录总数。
     *
     * 同时，如果 $source 参数为一个字符串，则不需要 $conditions 和 $sortby 参数。
     * 而且可以通过 setDBO() 方法设置要使用的数据库访问对象。否则 Qee_Helper_Pager
     * 将尝试获取一个默认的数据库访问对象。
     *
     * @param TableDataGateway|string $source
     * @param int $currentPage
     * @param int $pageSize
     * @param mixed $conditions
     * @param string $sortby
     *
     * @return Qee_Helper_Pager
     */
    public function __construct($source, $currentPage, $pageSize = 20, $conditions = null, $sortby = null)
    {
        $this->currentPage = $currentPage;
        $this->pageSize = $pageSize;

        if (is_object($source)) {
            $this->source = $source;
            $this->_conditions = $conditions;
            $this->_sortby = $sortby;
            $this->count = $this->source->findCount($conditions);
            if ($conditions == null) {
                $this->totalCount = $this->count;
            } else {
                $this->totalCount = $this->source->findCount();
            }
            $this->_computingPage();
        } else {
            $this->source = $source;
        }
    }

    /**
     * 设置记录总数，从而更新分页参数
     *
     * @param int $count
     */
    public function setCount($count)
    {
        $this->count = $count;
        $this->_computingPage();
    }

    /**
     * 设置数据库访问对象
     *
     * @param SDBO $dbo
     */
    public function setDBO($dbo)
    {
        $this->dbo = $dbo;
    }

    /**
     * 返回当前页对应的记录集
     *
     * @param string $fields
     *
     * @return array
     */
    public function findAll($fields = '*')
    {
        if ($this->count == -1) {
            $this->count = 20;
        }

        $offset = ($this->currentPage - $this->basePageIndex) * $this->pageSize;
        if (is_object($this->source)) {
            $limit = array($this->pageSize, $offset);
            $rowset = $this->source->findAll($this->_conditions, $this->_sortby, $limit, $fields);
        } else {
            if (is_null($this->dbo)) {
                $this->dbo = Qee::getDBO(0);
            }
            $rs = $this->dbo->selectLimit($this->source, $this->pageSize, $offset);
            $rowset = $this->dbo->getAll($rs);
        }
        return $rowset;
    }

    /**
     * 返回分页信息，方便在模版中使用
     *
     * @return array
     */
    public function getPagerData()
    {
        $data = array(
            'pageSize' => $this->pageSize,
            'totalCount' => $this->totalCount,
            'count' => $this->count,
            'pageCount' => $this->pageCount,
            'firstPage' => $this->firstPage,
            'firstPageNumber' => $this->firstPageNumber,
            'lastPage' => $this->lastPage,
            'lastPageNumber' => $this->lastPageNumber,
            'prevPage' => $this->prevPage,
            'prevPageNumber' => $this->prevPageNumber,
            'nextPage' => $this->nextPage,
            'nextPageNumber' => $this->nextPageNumber,
            'currentPage' => $this->currentPage,
            'currentPageNumber' => $this->currentPageNumber,
        );

        $data['pagesNumber'] = array();
        for ($i = 0; $i < $this->pageCount; $i++) {
            $data['pagesNumber'][$i] = $i + 1;
        }

        return $data;
    }

    /**
     * 产生指定范围内的页面索引和页号
     *
     * @param int $currentPage
     * @param int $navbarLen
     *
     * @return array
     */
    public function getNavbarIndexs($currentPage = 0, $navbarLen = 8)
    {
        $mid = intval($navbarLen / 2);
        if ($currentPage < $this->firstPage) {
            $currentPage = $this->firstPage;
        }
        if ($currentPage > $this->lastPage) {
            $currentPage = $this->lastPage;
        }

        $begin = $currentPage - $mid;
        if ($begin < $this->firstPage) { $begin = $this->firstPage; }
        $end = $begin + $navbarLen - 1;
        if ($end >= $this->lastPage) {
            $end = $this->lastPage;
            $begin = $end - $navbarLen + 1;
            if ($begin < $this->firstPage) { $begin = $this->firstPage; }
        }

        $data = array();
        for ($i = $begin; $i <= $end; $i++) {
            $data[] = array('index' => $i, 'number' => ($i + 1 - $this->basePageIndex));
        }
        return $data;
    }

    /**
     * 计算各项分页参数
     */
    protected function _computingPage()
    {
        $this->pageCount = ceil($this->count / $this->pageSize);
        $this->firstPage = $this->basePageIndex;
        $this->lastPage = $this->pageCount + $this->basePageIndex - 1;

        if ($this->lastPage < $this->basePageIndex) {
            $this->lastPage = $this->basePageIndex;
        }

        if ($this->currentPage >= $this->pageCount + $this->basePageIndex) {
            $this->currentPage = $this->lastPage;
        }

        if ($this->currentPage < $this->basePageIndex) {
            $this->currentPage = $this->firstPage;
        }

        if ($this->currentPage < $this->lastPage - 1) {
            $this->nextPage = $this->currentPage + 1;
        } else {
            $this->nextPage = $this->lastPage;
        }

        if ($this->currentPage > $this->basePageIndex) {
            $this->prevPage = $this->currentPage - 1;
        } else {
            $this->prevPage = $this->basePageIndex;
        }

        $this->firstPageNumber = $this->firstPage + 1 - $this->basePageIndex;
        $this->lastPageNumber = $this->lastPage + 1 - $this->basePageIndex;
        $this->nextPageNumber = $this->nextPage + 1 - $this->basePageIndex;
        $this->prevPageNumber = $this->prevPage + 1 - $this->basePageIndex;
        $this->currentPageNumber = $this->currentPage + 1 - $this->basePageIndex;
    }
}
