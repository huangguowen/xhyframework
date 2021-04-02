<?php

namespace xhyadminframework;

use think\Paginator;
use think\Response;
use think\response\Json;

class XhyResponse
{
  /**
   * 成功的响应
   *
   * @time 2019年12月02日
   * @param array $data
   * @param $msg
   * @param int $code
   * @return Json
   */
  public static function success($data = [], $msg = '成功', $code = Code::SUCCESS): Json
  {
    return json([
      'code'    => $code,
      'message' => $msg,
      'data'    => $data,
    ]);
  }

  public static function success2($data = [], $extnedData, $msg = '成功', $code = Code::SUCCESS): Json
  {
    return json(
      array_merge([
      'code'    => $code,
      'message' => $msg,
      'data'    => $data
      ],$extnedData));
  }

  public static function listData($data = [], $totalCount,$pageIndex,$pageSize, $msg = '成功'): Json
  {
    return json(
      [
      'code'    => Code::SUCCESS,
      'message' => $msg,
      'data'    => $data,
      'count'    => $totalCount,
      'current'    => $pageIndex,
      'limit'    => $pageSize,
      ]);
  }
 

  /**
   * 分页
   *
   * @time 2019年12月06日
   * @param Paginator $list
   * @return
   */
  public static function paginate(Paginator $list)
  {
    return json([
      'code'    => Code::SUCCESS,
      'message' => '成功',
      'count'   => $list->total(),
      'current' => $list->currentPage(),
      'limit'   => $list->listRows(),
      'data'    => $list->getCollection(),
    ]);
  }

  /**
   * 错误的响应
   *
   * @time 2019年12月02日
   * @param string $msg
   * @param int $code
   * @return Json
   */
  public static function fail($msg = '', $code = Code::FAILED): Json
  {
    return json([
        'code' => $code,
        'message'  => $msg,
    ]);
  }
}
