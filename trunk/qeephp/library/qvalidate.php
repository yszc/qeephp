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
 * 定义 QValidate 类
 *
 * @package core
 * @version $Id$
 */

/**
 * QValidate 提供数据验证服务
 *
 * @package core
 */
class QValidate
{
    /**
     * 所有的验证器对象
     *
     * @var array
     */
    protected $validators = array();

    /**
     * 检查一项数据
     *
     * @param string $id
     * @param mixed $data
     *
     * @return QValidate_Validator
     */
    function check($id, $data)
    {
        if (is_array($data)) {
            if (array_key_exists($id, $data)) {
                $obj = new QValidate_Validator($id, $data[$id]);
            } else {
                $obj = new QValidate_Validator($id, null);
            }
        } else {
            $obj = new QValidate_Validator($id, $data);
        }
        $this->validators[$id] = $obj;
        return $obj;
    }

    /**
     * 对一组数据进行验证
     *
     * @param array $data
     * @param array $rules_group
     * @param array|string $fields
     *
     * @return array
     */
    function groupCheck(array $data, array $rules_group, $fields = null)
    {
        $result = array();
        $v = new QValidate_Validator(null);
        if (is_null($fields)) {
            $fields = array_keys($rules_group);
        } else {
            $fields = Q::normalize($fields);
        }
        $fields = array_flip($fields);

        foreach ($rules_group as $field => $rules) {
            if (!isset($fields[$field])) { continue; }
            $v->setData(isset($data[$field]) ? $data[$field]: null);
            $v->id = $field;
            $r = $v->runRules($rules);
            if (!empty($r)) {
                $result[$field] = $r;
            }
        }
        return $result;
    }

    /**
     * 返回所有检查总的验证结果
     *
     * @return boolean
     */
    function isPassed()
    {
        $result = true;
        foreach ($this->validators as $validator) {
            /* @var $validator QValidate_Validator */
            $result = $result && $validator->isPassed();
        }
        return $result;
    }

    /**
     * 返回所有失败的验证
     *
     * @param boolean $only_first_msg 指示对于每一项数据都只返回第一个错误信息
     *
     * @return array
     */
    function getFailed($only_first_msg = false)
    {
        $failed = array();
        foreach ($this->validators as $validator) {
            /* @var $validator QValidate_Validator */
            $f = $validator->getFailed($only_first_msg);
            if (empty($f)) { continue; }
            $failed[$validator->id] = $f;
        }
        return $failed;
    }

    /**
     * 获得所有有效的数据
     *
     * @return array
     */
    function getValidData()
    {
        $data = array();
        foreach ($this->validators as $validator) {
            /* @var $validator QValidate_Validator */
            if ($validator->isPassed()) {
                $data[$validator->id] = $validator->getData();
            }
        }
        return $data;
    }
}
