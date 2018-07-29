<?php

namespace app\base\model;

use think\Loader;

abstract class Base extends \think\Model
{
    /**
     * 根据条件修改信息
     * #Date:
     * @ param $data
     * @ return false|int|string
     * @ throws
     */
    public static function editMapData($where, $data)
    {
        if (empty($where) || empty($data)) return false;

        if (static::where($where)->update($data) === false) return false;
        return true;

    }

    /**
     * 通过主键获取单条数据
     * @ param int $id 主键ID
     * @ return $this|array|false|\PDOStatement|string|Model
     */
    public function findId($id, $field = true, $append = [], $isDel = false)
    {
        $map[$this->pk] = $id;
        return $this->findMap($map, $field, $append, $isDel);
    }

    /**
     * 通过查询条件获取单条数据对象
     * @ param array $map 查询条件
     * @ param bool|true $field 字段
     * @ param array $append 追加已经定义获取器字段
     * @ param bool|true $isDel
     * @ return  $this|array|false|string|
     */
    public static function findMap($map = [], $field = true, $append = [], $isDel = false)
    {
        if ($isDel && !isset($map['is_del'])) {
            $map['is_del'] = 1;
        }
        if (is_array($field)) {
            $object = static::where($map)->field($field[0], $field[1])->find();
        } else {
            $object = static::where($map)->field($field)->find();
        }
        if (!empty($object) && !empty($append)) {
            return $object->append($append);
        } else {
            return $object;
        }
    }

    /**
     * 通过主键ID获取多条数据对象
     * @ param array $map
     * @ param bool|true $field
     * @ param array $append 这需要在模型里增加获取器
     * @ param bool|true $is_del
     * @ return array
     */
    public function getListById($id, $field = true, $order = 'create_time desc', $group = '', $limit = 0, $append = [], $is_del = false)
    {
        $map[$this->pk] = $id;
        return $this->getListByMap($map, $field, $order, $group, $limit, $append, $is_del);
    }

    /**
     * 通过查询条件获取多条数据对象
     * @ param array $map
     * @ param bool|true $field
     * @ param array $append 这需要在模型里增加获取器
     * @ param bool|true $is_del
     * @ return array
     */
    public static function getListByMap($map = [], $field = true, $order = 'create_time desc', $group = '', $limit = 0, $append = [], $is_del = false)
    {
        if ($is_del && !isset($map['is_del'])) {
            $map['is_del'] = 1;
        }
        if (is_array($field)) {
            $object_list = static::where($map)->field($field[0], $field[1])->order($order)->group($group)->limit($limit)->select();
        } else {
            $object_list = static::where($map)->field($field)->order($order)->group($group)->limit($limit)->select();
        }
        $list = [];
        if (!empty($object_list)) {
            foreach ($object_list as $item => $value) {
                if (!empty($append)) {
                    $list[] = $value->append($append)->toArray();
                } else {
                    $list[] = $value->toArray();
                }
            }
        }
        return $list;
    }

    /**
     * 根据有主键修改信息 无Id 新增信息
     * #Date:
     * @ param $data
     * @ return false|int|string
     * @ throws
     */
    public function editData($data)
    {
        if (isset($data[$this->pk])) {
            if (is_numeric($data[$this->pk]) && $data[$this->pk] > 0) {
                $save = $this->allowField(true)->save($data, [$this->pk => $data[$this->pk]]);
            } else {
                $save = $this->allowField(true)->save($data);
            }
        } else {
            $save = $this->allowField(true)->save($data);
        }
        if ($save == 0 || $save == false) {
            $res = false;
        } else {
            $res = true;
        }
        return $res;
    }

    /**
     * 根据有主键批量修改信息 无Id 批量新增信息
     * @ param $data
     * @ return bool
     */
    public function editListDate($data)
    {
        foreach ($data as $key => $value) {
            if (isset($value[$this->pk])) {
                if (is_numeric($data[$this->pk]) && $data[$this->pk] > 0) {
                    $update[] = $value;
                } else {
                    $insert[] = $value;
                }
            } else {
                $insert[] = $value;
            }
        }
        if (isset($insert)) {
            $insert = $this->saveAll($insert);
        }
        if (isset($update)) {
            $update = $this->isUpdate(true)->saveAll($update);
        }
        if ((isset($insert) AND $insert == 0) || (isset($insert) AND $insert == false) || (isset($update) AND $update == 0) || (isset($update) AND $update == false)) {
            $res = false;
        } else {
            $res = true;
        }
        return $res;
    }
}