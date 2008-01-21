<?php

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
    protected $_validators = array();

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
        $this->_validators[$id] = $obj;
        return $obj;
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
    function isFailed($only_first_msg = false)
    {
        $failed = array();
        foreach ($this->validators as $validator) {
            /* @var $validator QValidate_Validator */
            $f = $validator->isFailed($only_first_msg);
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
