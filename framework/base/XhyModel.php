<?php
namespace xhyadminframework\base;

use think\model\concern\SoftDelete;

/**
 *
 * Class CatchModel
 * @package xhyadminframework\base
 */
abstract class XhyModel extends \think\Model
{

    protected $defaultSoftDelete = 0;

    protected $autoWriteTimestamp = true;

    public const LIMIT = 12;

    // 开启
    public const ENABLE = 1;
    // 禁用
    public const DISABLE = 2;
}
