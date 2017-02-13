<?php

namespace DongPHP\System;

use DongPHP\System\Libraries\Database\Builder;
use DongPHP\System\Libraries\DB;

abstract class Model extends Data
{

    protected $table;
    /**
     * @var Builder
     */
    protected $builder;

    public function __construct()
    {

    }

    /**
     * @return Builder
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @param $where
     * @param Builder|null $builder
     * @return mixed|static
     */
    public function select($where)
    {
        return $this->builder->where($where)->first();
    }

    /**
     * @param $where
     * @param Builder|null $builder
     * @return mixed|static
     */
    public function selectAll($where)
    {
        return $this->builder->where($where)->get();
    }

    /**
     * @param $values
     * @param $where
     * @param Builder|null $builder
     * @return int
     */
    public function update($values, $where)
    {
        return $this->builder->where($where)->update($values);
    }

    /**
     * @param $values
     * @return bool
     */
    public function insert($values)
    {
        return $this->builder->insert($values);
    }

    /**
     * @param $where
     * @return int
     */
    public function delete($where)
    {
        return $this->builder->where($where)->delete();
    }


    public function page($where, $page, $size, $orderBy = [])
    {
        $config = ['like', '<=', '>=', '<', '>'];
        foreach ($where as $k => $v) {
            $tmp = explode(' ', $k);
            if (count($tmp) == 2 && in_array($tmp[1], $config)) {
                $this->builder->where($tmp[0], $tmp[1], $v);
            } else {
                if (count($tmp) == 2 && $tmp[1] = 'in') {
                    $this->builder->whereIn($tmp[0], $v);
                } else {
                    $this->builder->where($k, '=', $v);
                }
            }
        }
        if ($orderBy) {
            $this->builder->orderBy($orderBy[0], $orderBy[1]);
        }
        return $this->builder->pageInfo($page, $size);
    }
}
