<?php

class RedisPage
{
    /**
     * @var Redis
     */
    protected $redis;
    protected $zKey;
    protected $scoreKey;
    protected $valKey;
    protected $key;

    protected $pageSize = 50;

    protected $getHashFunc;
    protected $autoDelNotFound = true;
    const HMAP_EXPIRE = 2592000;//30天

    /**
     * RedisPage constructor.
     * @param $redis  redis对象
     * @param $zKey  有许集合的key
     * @param $key  hashmap的key
     * @param $scoreKey 数组中用来当做score的key
     * @param $valKey  数组中用来当做值的key
     * @param bool $autodel 是否自动删除找不到的key
     */
    public function __construct($redis, $zKey, $key, $scoreKey, $valKey,$autodel=true) {
        $this->redis = $redis;
        $this->zKey = $zKey;
        $this->key = $key;
        $this->scoreKey = $scoreKey;
        $this->valKey = $valKey;
        $this->autoDelNotFound = $autodel;
    }

    /**返回key
     * @function getKey
     * @author 半山
     * @version 1.0
     * @date
     * @param $type
     * @return mixed
     */
    public function getKey($type) {
        return $this->$type;
    }

    /**设置一页的数目
     * @function setPageSize
     * @author 半山
     * @version 1.0
     * @date
     * @param $size
     */
    public function setPageSize($size) {
        $this->pageSize = $size;
    }

    /**当hashmap里没有对应的值时 自定义取值函数 注意这函数需要返回数组
     * @function setGetHashFunc
     * @author 半山
     * @version 1.0
     * @date
     * @param $func
     */
    public function setGetHashFunc($func) {
        $this->getHashFunc = $func;
    }

    /**设置总体的过期时间
     * @function expireAll
     * @author 半山
     * @version 1.0
     * @date
     * @param $time
     */
    public function expireAll($time) {
        $this->redis->expire($this->zKey, $time);
        $this->redis->expire($this->key, $time);
    }

    public function expireZsort($time) {
        $this->redis->expire($this->zKey,$time);
    }

    public function expireHmap($time){
        $this->redis->expire($this->key,$time);
    }

    /**添加一个元素
     * @function add
     * @author 半山
     * @version 1.0
     * @date
     * @param $val
     */
    public function add($val) {
        $this->redis->zAdd($this->zKey, $val[$this->scoreKey], $val[$this->valKey]);
        $this->redis->hSet($this->key, $val[$this->valKey], json_encode($val));
    }

    /**添加一个数组
     * @function addAll
     * @author 半山
     * @version 1.0
     * @date
     * @param $vals
     */
    public function addAll($vals) {
        foreach ($vals as $val) {
            $this->add($val);
        }
    }

    /**仅添加一个集合元素
     * @function addSet
     * @author 半山
     * @version 1.0
     * @date
     * @param $val
     */
    public function addSet($val) {
        $this->redis->zAdd($this->zKey, $val[$this->scoreKey], $val[$this->valKey]);
    }

    /**仅添加集合数组
     * @function addAllSet
     * @author 半山
     * @version 1.0
     * @date
     * @param $sets
     */
    public function addAllSet($sets) {
        foreach ($sets as $val) {
            $this->redis->zAdd($this->zKey, $val[$this->scoreKey], $val[$this->valKey]);
        }
    }


    /**删除一个元素
     * @function del
     * @author 半山
     * @version 1.0
     * @date
     * @param $val
     */
    public function del($val) {
        $this->redis->zRem($this->zKey, $val[$this->scoreKey]);
        $this->redis->hDel($this->key, $val[$this->valKey]);
    }

    /**更新一个元素
     * @function update
     * @author 半山
     * @version 1.0
     * @date
     * @param $val
     */
    public function update($val) {
        $this->add($val);
    }

    /** 获取从start到end的元素
     * @function getRange
     * @author 半山
     * @version 1.0
     * @date
     * @param $start
     * @param $end
     * @return array
     */
    public function getRange($start, $end) {
        $tmp = $this->redis->zRange($this->zKey, $start, $end);
        if (!$tmp) {
            return [];
        }
        $res = [];
        foreach ($tmp as $key) {
            $val = $this->redis->hGet($this->key, $key);
            if (!$val && $this->getHashFunc) {
                $getHashFunc = $this->getHashFunc;
                $val = $getHashFunc($key);
                if( (!$val || empty($val)) && $this->autoDelNotFound){
                    $this->redis->zRem($this->zKey,$key);
                }else{
                    $res[] = $val;
                    $this->redis->hSet($this->key, $val[$this->valKey], json_encode($val));
                }


            } else {
                $res[] = json_decode($val, true);
            }
        }

        return $res;
    }

    /**获取某一页的元素，注意页码从1开始
     * @function getPage
     * @author 半山
     * @version 1.0
     * @date
     * @param $page
     * @return array|bool
     */
    public function getPage($page) {
        if (empty($page)) {
            return false;
        }

        $start = ($page - 1) * $this->pageSize;
        $end = $page * $this->pageSize - 1;
        return $this->getRange($start, $end);
    }

    /** 获取总数
     * @function count
     * @author 半山
     * @version 1.0
     * @date
     * @return int
     */
    public function count() {
        return $this->redis->zCard($this->zKey);
    }


    /**获取一页的大小
     * @function getPageSize
     * @author 半山
     * @version 1.0
     * @date
     * @return int
     */
    public function getPageSize() {
        return $this->pageSize;
    }

    /**删除所有
     * @function deleteAll
     * @author 半山
     * @version 1.0
     * @date
     */
    public function deleteAll() {
        $this->redis->delete($this->key, $this->zKey);
    }

}
