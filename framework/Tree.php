<?php

namespace xhyadminframework;

class Tree
{
    public static function done(array $items, $pid = 0, $pidField = 'parent_id', $children = 'children', $pk = 'id')
    {
        $tree = [];
        foreach ($items as $key => &$item) {
            if ($item[$pidField] == $pid) {
                $child = self::done($items, $item[$pk], $pidField, $children, $pk);
                if (count($child)) {
                    $item[$children] = $child;
                }
                $tree[] = $item;
            }
        }

        return $tree;
    }

    public static function resetTree($treeList, $menuId = '')
    {
        foreach ($treeList as $k => &$v) {
            if (@$v['children']) {
                if ($menuId == '') {
                    $v['level'] = $v['menu_id'];
                } else {
                    $v['level'] = $v['menu_id'].'-'.$menuId;
                }
                $child = self::resetTree($v['children'], $v['level']);
                $v['children'] = $child;
            } else {
                if ($menuId == '') {
                    $v['level'] = $v['menu_id'];
                } else {
                    $v['level'] = $v['menu_id']. '-'. $menuId;
                }
            }
        }
        return $treeList;
    }

    public static function generateTree($list, $pk = 'menu_id', $pid = 'menu_parent_id', $child = '_child', $root = 0)
    {
        $tree     = array();
        $packData = array();
        foreach ($list as $data) {
            $packData[$data[$pk]] = $data;
        }
        foreach ($packData as $key => $val) {
            if ($val[$pid] == $root) {
                $tree[] = &$packData[$key];
            } else {
                $packData[$val[$pid]][$child][] = &$packData[$key];
            }
        }
        return $tree;
    }
}
