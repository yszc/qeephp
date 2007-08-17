<?php

/**
 * Generator_Abstract 是所有代码生成器的基础类，提供了一些有用的工具
 *
 * @package CodeGenerator
 * @author 廖宇雷 dualface@gmail.com
 * @version 1.0
 */
abstract class Generator_Abstract
{
    /**
     * 继承类必须覆盖此方法
     *
     * @param string $name
     * @param string $opts
     */
    abstract public function run($name, $opts);

    /**
     * 将以“_”下划线分割的字符串转换成骆驼表示法（除第一个单词外，每个单词的第一个字母大写）
     *
     * @param string $name
     *
     * @return string
     */
    protected function _camelName($name)
    {
        $name = strtolower($name);
        while (($pos = strpos($name, '_')) !== false) {
            $name = substr($name, 0, $pos) . ucfirst(substr($name, $pos + 1));
        }
        return $name;
    }
}
