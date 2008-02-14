<?php

class Behavior_Fakeuuid implements QActiveRecord_Behavior_Interface
{
    /**
     * 种子的编码
     *
     * @var array
     */
    protected $seed;

    /**
     * 进制
     *
     * @var int
     */
    protected $base;


    /**
     * 构造函数
     */
    protected function __construct()
    {
        $seed = 'tlzypjwamdsgcuxqkhiboernfv';
        $this->base = strlen($seed);
        for ($i = 0; $i < $this->base; $i++) {
            $this->seed[$i] = substr($seed, $i, 1);
        }
    }

    static function get_callbacks()
    {
        $obj = new Behavior_Fakeuuid();
        return array(
            array(self::before_create, array($obj, 'before_create')),
        );
    }

    function before_create(QActiveRecord_Abstract $obj, array & $props)
    {
        $id = $obj->get_table()->next_id();
        $props['member_id'] = $this->encode_id($id);
    }

    function encode_id($number, $len = 8)
    {
        $number = intval($number);
        $offset = 0;
        $encode = '';
        $first = $number % $this->base;
        while ($len) {
            $pos = $number % $this->base;
            $pos = ($pos + $first + $offset) % $this->base;
            $encode .= $this->seed[$pos];
            $number = intval($number / $this->base);
            $offset++;
            $len--;
        }
        $encode .= $this->seed[$first];
        return $encode;
    }
}

