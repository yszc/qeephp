<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.TXT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QValidate_Validator 类
 *
 * @package core
 * @version $Id$
 */

/**
 * QValidate_Validator 为验证特定数据提供了多种方法
 *
 * @package core
 */
class QValidate_Validator
{
    /**
     * 验证器的id
     *
     * @var string
     */
    public $id;

    /**
     * 要验证的数据
     *
     * @var mixed
     */
    protected $value;

    /**
     * 验证结果
     *
     * @var boolean
     */
    protected $result;

    /**
     * 所有没有验证通过的检查
     *
     * @var array
     */
    protected $failed;

    /**
     * 指示是否跳过余下的验证
     *
     * @var boolean
     */
    protected $skip;

    /**
     * 构造函数
     *
     * @param string $id
     * @param mixed $value
     */
    function __construct($id, $value = null)
    {
        $this->id = $id;
        $this->setData($value);
    }

    /**
     * 设置要验证的数据
     *
     * @param mixed $value
     */
    function setData($value)
    {
        $this->value = $value;
        $this->result = true;
        $this->failed = array();
        $this->skip = false;
    }

    /**
     * 返回验证结果
     *
     * @return boolean
     */
    function isPassed()
    {
        return (bool)$this->result;
    }

    /**
     * 返回所有失败的验证
     *
     * @param boolean $only_first_msg 指示是否只返回第一个错误信息
     *
     * @return array
     */
    function getFailed($only_first_msg = false)
    {
        return $only_first_msg ? reset($this->failed) : $this->failed;
    }

    /**
     * 返回要验证的数据
     *
     * @return mixed
     */
    function getData()
    {
        return $this->value;
    }

    /**
     * 运行一个验证
     *
     * @param array $rule
     */
    function runRule(array $rule)
    {
        $check = array_shift($rule);
        $func = str_replace('_', '', $check);
        call_user_func_array(array($this, $func), $rule);
    }

    /**
     * 对数据进行一系列验证
     *
     * @param array $rules
     */
    function runRules(array $rules)
    {
        foreach ($rules as $rule) {
            $this->runRule($rule);
        }
        return ($this->result) ? null : $this->failed;
    }

    /**
     * 如果为空（空字符串或者 null），则跳过余下的验证
     *
     * @return QValidate_Validator
     */
    function skipEmpty()
    {
        if (strlen($this->value) == 0) {
            $this->skip = true;
        }
        return $this;
    }

    /**
     * 如果为 NULL，则跳过余下的验证
     *
     * @return QValidate_Validator
     */
    function skipNull()
    {
        if (is_null($this->value)) {
            $this->skip = true;
        }
        return $this;
    }

    /**
     * 是否等于指定值
     *
     * @param mixed $test
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function equal($test, $msg = '')
    {
        $this->setResult($this->value == $test, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 不等于指定值
     *
     * @param mixed $test
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function notEqual($test, $msg = '')
    {
        $this->setResult($this->value != $test, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否与指定值完全一致
     *
     * @param mixed $test
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function same($test, $msg = '')
    {
        $this->setResult($this->value === $test, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 最小长度
     *
     * @param int $len
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function minLength($len, $msg = '')
    {
        $this->setResult(strlen($this->value) >= $len, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 最大长度
     *
     * @param int $len
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function maxLength($len, $msg = '')
    {
        $this->setResult(strlen($this->value) <= $len, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 最小值
     *
     * @param int|float $min
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function min($min, $msg = '')
    {
        $this->setResult($this->value >= $min, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 最大值
     *
     * @param int|float $max
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function max($max, $msg = '')
    {
        $this->setResult($this->value <= $max, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 在两个值之间
     *
     * @param int|float $min
     * @param int|float $max
     * @param boolean $inclusive 是否包含 min/max 在内
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function between($min, $max, $inclusive = true, $msg = '')
    {
        if ($inclusive) {
            $this->setResult($this->value >= $min && $this->value <= $max, __FUNCTION__, $msg);
        } else {
            $this->setResult($this->value > $min && $this->value < $max, __FUNCTION__, $msg);
        }
        return $this;
    }

    /**
     * 大于指定值
     *
     * @param int|float $test
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function greaterThan($test, $msg = '')
    {
        $this->setResult($this->value > $test, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 大于等于指定值
     *
     * @param int|float $test
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function greaterOrEqual($test, $msg = '')
    {
        $this->setResult($this->value >= $test, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 小于指定值
     *
     * @param int|float $test
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function lessThan($test, $msg = '')
    {
        $this->setResult($this->value < $test, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 小于登录指定值
     *
     * @param int|float $test
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function lessOrEqual($test, $msg = '')
    {
        $this->setResult($this->value <= $test, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 不为 null
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function notNull($msg = '')
    {
        $this->setResult(!is_null($this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 不为空
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function notEmpty($msg = '')
    {
        $this->setResult(!empty($this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是特定类型
     *
     * @param string $type
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isType($type, $msg = '')
    {
        $this->setResult(gettype($this->value) == $type, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是字母加数字
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isAlphaNumber($msg = '')
    {
        $this->setResult(ctype_alnum($this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是字母
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isAlpha($msg = '')
    {
        $this->setResult(ctype_alpha($this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是控制字符
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isControlChar($msg = '')
    {
        $this->setResult(ctype_cntrl($this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是数字
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isNumerical($msg = '')
    {
        $this->setResult(ctype_digit($this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是可见的字符
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isGraph($msg = '')
    {
        $this->setResult(ctype_graph($this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是全小写
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isLower($msg = '')
    {
        $this->setResult(ctype_lower($this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是可打印的字符
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isPrintable($msg = '')
    {
        $this->setResult(ctype_lower($this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是标点符号
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isPunctuation($msg = '')
    {
        $this->setResult(ctype_punct($this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是空白字符
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isWhitespace($msg = '')
    {
        $this->setResult(ctype_space($this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是全大写
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isUpper($msg = '')
    {
        $this->setResult(ctype_upper($this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是十六进制数
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isHex($msg = '')
    {
        $this->setResult(ctype_xdigit($this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是 ASCII 字符
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isAscii($msg = '')
    {
        $this->setResult(preg_match('/[^\x20-\x7f]/', $this->value) == 0, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是电子邮件地址
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isEmail($msg = '')
    {
        $reg = '/^[a-z0-9]+([._\-\+]*[a-z0-9]+)*@([a-z0-9]+[-a-z0-9]*[a-z0-9]+\.)+[a-z0-9]+$/i';
        $this->setResult(preg_match($reg, $this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是日期（yyyy/mm/dd、yyyy-mm-dd）
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isDate($msg = '')
    {
        if (strpos($this->value, '-') !== false) {
            $p = '-';
        } elseif (strpos($this->value, '/') !== false) {
            $p = '\/';
        } else {
            $this->setResult(false, __FUNCTION__, $msg);
            return $this;
        }

        if (preg_match('/^\d{4}' . $p . '\d{2}' . $p . '\d{2}$/', $this->value)) {
            $year = substr($this->value, 0, 4);
            $month = substr($this->value, 5, 2);
            $day = substr($this->value, 8, 2);
            $this->setResult(checkdate($month, $day, $year), __FUNCTION__, $msg);
        } else {
            $this->setResult(false, __FUNCTION__, $msg);
        }
        return $this;
    }

    /**
     * 是否是一个 time() 函数返回的整数
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isPHPTimeStamp($msg = '')
    {
        $int = intval($this->value);
        if ($int != $this->value || $int <= 0) {
            $this->setResult(false, __FUNCTION__, $msg);
        }
        return $this;
    }

    /**
     * 是否是时间（hh:mm:ss）
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isTime($msg = '')
    {
        $parts = explode(':', $this->value);
        $count = count($parts);
        do {
            if ($count != 2 || $count != 3) { break; }
            if ($count == 2) { $parts[2] = '00'; }
            $test = @strtotime($parts[0] . ':' . $parts[1] . ':' . $parts[2]);
            if ($test === -1 || $test === false || date('H:i:s') != $this->value) {
                break;
            }
            $this->setResult(true, __FUNCTION__, $msg);
            return $this;
        } while (false);
        $this->setResult(false, __FUNCTION__, $msg);

        return $this;
    }

    /**
     * 是否是整数
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isInt($msg = '')
    {
        $test = intval($this->value) . '' == $this->value;
        $this->setResult($test !== -1 && $test !== false, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是浮点数
     *
     * @param string $msg
     */
    function isFloat($msg = '')
    {
        $value = floatval($this->value);
        if ($value == 0) {
            if ($value === 0 && $this->value === '0') {
                $test = true;
            } else {
                $test = false;
            }
        } else {
            $test = true;
        }
        $this->setResult($test !== -1 && $test !== false, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是 IPv4 地址（格式为 a.b.c.h）
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isIpv4($msg = '')
    {
        $test = @ip2long($this->value);
        $this->setResult($test !== -1 && $test !== false, __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是八进制数值
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isOctal($msg = '')
    {
        $this->setResult(preg_match('/0[0-7]+/', $this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是二进制数值
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isBinary($msg = '')
    {
        $this->setResult(preg_match('/[01]+/', $this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 是否是 Internet 域名
     *
     * @param string $msg
     *
     * @return QValidate_Validator
     */
    function isDomain($msg = '')
    {
        $this->setResult(preg_match('/[a-z0-9\.]+/i', $this->value), __FUNCTION__, $msg);
        return $this;
    }

    /**
     * 设置检查结果
     *
     * @param boolean $result
     * @param string $check
     * @param string $msg
     */
    protected function setResult($result, $check, $msg = '')
    {
        if ($this->skip) { return; }
        $this->result = $this->result && (boolean)$result;
        if (!$result) {
            $this->failed[$check] = $msg;
        }
    }
}
