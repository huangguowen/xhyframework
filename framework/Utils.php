<?php

namespace xhyadminframework;

use think\facade\Db;
use think\helper\Str;

class Utils
{


    /**
     * 获取列表数据
     * @param $param
     * @param int $page
     * @param int $limit
     * @param $total
     * @return mixed
     * @author: lampzww
     * @Date: 10:19  2020/6/9
     */
    public static function select($db, $param = [], $page = 0, $limit = 0)
    {
        $field  =   "*";
        $model  =   Db::name($db);
        isset($param['map'])    &&  $model->where($param['map']);
        isset($param['alias'])  &&  $model->alias($param['alias']);
        isset($param['group'])  &&  $model->group($param['group']);
        isset($param['field'])  &&  $field  =   $param['field'];
        isset($param['order'])  &&  $model->order($param['order']);

        if(isset($param['join'])){
            foreach ($param['join'] as $item){
                $model->join($item[0],$item[1],isset($item['type']) ? $item['type'] : 'INNER');
            }
        }

        #TODO 模型操作


        if($page   &&  $limit){
            $total  =   $model->field($field)->count();
            $model->page($page, $limit);
            return  ['list'=>$model->field($field)->select()->toArray(),'total'=>$total];
        }else{
            return  $model->field($field)->select()->toArray();
        }
    }

    /**
     * 获取单条数据
     * @param $id
     * @param string $field
     * @param array $param
     * @return mixed
     * @author: lampzww
     * @Date: 10:28  2020/6/9
     */
    public static function read($db, $id, $field = '*',$param = [])
    {
        $map    =   [];
        $model  =   Db::name($db);
        (is_numeric($id)    ||  is_string($id)) &&  $map[]  =   [$model->getPk(), '=', $id];
        is_string($id)  &&  !is_numeric($id)  &&  strpos($id, 'and') !== false  &&    $map    =   $id;
        is_array($id)   &&  $map    =   $id;
        isset($param['alias'])  &&  $model->alias($param['alias']);
        if(isset($param['join'])){
            foreach ($param['join'] as $item){
                $model->join($item[0],$item[1],isset($item['type']) ? $item['type'] : 'INNER');
            }
        }


        #TODO 模型操作
        return  $model->where($map)->field($field)->find();
    }

    /**
     * 删除数据
     * @param $db
     * @param $id
     * @return int
     * @throws \think\db\exception\DbException
     * @author: lampzww
     * @Date: 12:00  2020/6/12
     */
    public static function delete($db,$id)
    {
        $map    =   [];
        $model  =   Db::name($db);
        (is_numeric($id)    ||  is_string($id)) &&  $map[]  =   [$model->getPk(), '=', $id];
        is_string($id)  &&  !is_numeric($id)  &&  strpos($id, 'and') !== false  &&    $map    =   $id;
        is_array($id)   &&  $map    =   $id;

        return  $model->where($map)->delete();
    }

    /**
     * 保存数据
     * @param $db
     * @param $data
     * @param string $id
     * @return int|string
     * @throws \think\db\exception\DbException
     * @author: lampzww
     * @Date: 14:19  2020/6/12
     */
    public static function save($db, $data, $id = '')
    {
        $map    =   [];
        $model  =   Db::name($db);
        (is_numeric($id)    ||  is_string($id)) &&  $map[]  =   [$model->getPk(), '=', $id];
        is_string($id)  &&  !is_numeric($id)  &&  strpos($id, 'and') !== false  &&    $map    =   $id;
        is_array($id)   &&  $map    =   $id;
        if ($id){
            return  $model->where($map)->replace()->update($data);
        }else{
            $data[$model->getPk()]  =   self::guid();
            return  $model->replace()->insert($data);
        }

    }

    /**
     * 获取最大排序值
     * @param $db
     * @param string $parent_field
     * @param string $parent_id
     * @param string $field
     * @return int
     * @author: lampzww
     * @Date: 14:31  2020/6/12
     */
    public static function getSortNumb($db,$parent_field = '',$parent_id = '',$field = 'sort_number')
    {
        $model  =   Db::table($db);
        $parent_field   &&  $model->where($parent_field, $parent_id);
        $max = $model->max($field);
        return ((int)$max + 1);
    }


    /**
     * 通过当前的父分类id获取树节点ID
     * @param $db
     * @param $parent_field
     * @param $parent_id
     * @param $treefield
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: lampzww
     * @Date: 11:20  2020/6/9
     */
    public static function getTreeId($db,$parent_field, $parent_id, $treefield)
    {
        $model = Db::table($db);
        $tree = '00001';
        //  获取当前最大的树节点id
        $query = "select max($parent_field) as max_treeid from $db where $parent_field='" . $parent_id . "'";
        $max = Db::query($query);
        $maxTreeId = $max[0]['max_treeid'];

        // 如果当前分类的父分类id为0  即当前分类是顶级分类 新增时用
        if ($parent_id == "0") {
            // 顶级分类 的最大值存在 则新的树id 为 最大值+1
            $tree = (str_pad($maxTreeId + 1, strlen($maxTreeId), "0", STR_PAD_LEFT));

        } else {
            // 如果不是顶级分类
            // 如果有最大值  最大值+1
            if (strlen($maxTreeId) > 0) {
                $tree = (str_pad($maxTreeId + 1, strlen($maxTreeId), "0", STR_PAD_LEFT));
            } else {
                // 如果没有 需要获取父节点的treeid
                $info = $model->where($model->getPk(), $parent_id)->find();

                $tree = $info[$treefield] . $tree;
            }

        }
        return $tree;
    }


    /**
     * 字符串转换成数组
     *
     * @time 2019年12月25日
     * @param string $string
     * @param string $dep
     * @return array
     */
    public static function stringToArrayBy(string  $string, $dep = ','): array
    {
        if (Str::contains($string, $dep)) {
            return explode($dep, trim($string, $dep));
        }

        return [$string];
    }

    /**
     * 搜索参数
     *
     * @time 2020年01月13日
     * @param array $params
     * @param array $range
     * @return array
     */
    public static function filterSearchParams(array $params, array $range = []): array
    {
        $search = [];

        // $range = array_merge(['created_at' => ['start_at', 'end_at']], $range);

        if (!empty($range)) {
            foreach ($range as $field => $rangeField) {
                if (count($rangeField) === 1) {
                    $search[$field] = [$params[$rangeField[0]]];
                    unset($params[$rangeField[0]]);
                } else {
                    $search[$field] = [$params[$rangeField[0]], $params[$rangeField[1]]];
                    unset($params[$rangeField[0]], $params[$rangeField[1]]);
                }
            }
        }

        return array_merge($search, $params);
    }

    /**
     * 导入树形数据
     *
     * @time 2020年04月29日
     * @param $data
     * @param $table
     * @param string $pid
     * @param string $primaryKey
     * @return void
     */
    public static function importTreeData($data, $table, $pid = 'parent_id', $primaryKey = 'id')
    {
        $table = \config('database.connections.mysql.prefix') . $table;

        foreach ($data as $value) {
            if (isset($value[$primaryKey])) {
                unset($value[$primaryKey]);
            }

            $children = $value['children'] ?? false;
            if ($children) {
                unset($value['children']);
            }

            $id = Db::name($table)->insertGetId($value);

            if ($children) {
                foreach ($children as &$v) {
                    $v[$pid] = $id;
                    $v['level'] = !$value[$pid] ? $id : $value['level'] . '-' . $id;
                }
                self::importTreeData($children, $table, $pid);
            }
        }
    }

    public static function getString($condition, $value)
    {
        if ($value != "") {
            $value = str_replace("'", "\'", $value);
            return " " . str_replace("{0}", $value, $condition) . " ";
        } else {
            return "";
        }
    }

    public  static function getNumber($condition, $value)
    {
        if ($value != "" && is_numeric($value)) {
            return " " . str_replace("{0}", $value, $condition) . " ";
        } else {
            return "";
        }
    }


    /**
     * 获以日期
     */
    public  static function getDate($condition, $value)
    {
        if ($value != "" && is_numeric($value)) {
            return " " . str_replace("{0}", $value, $condition) . " ";
        } else {
            return "";
        }
    }

    public  static function getPagingSql($sql, $pageIndex, $pageSize)
    {
        $curRowIndex = ($pageIndex - 1) * $pageSize;
        $wrapSql = "
        select * from 
        (
            $sql
        ) t1 LIMIT $curRowIndex, $pageSize
        ";

        return $wrapSql;
    }

    public  static function getOrderby($orderbyField, $orderbyDirection, $defaultOrderbyField = "")
    {
        if ($orderbyField != "") {

            $orderbyDirectionValue = " desc";
            if ($orderbyDirection == "" || $orderbyDirection == "asc" || $orderbyDirection == "ascend") {
                $orderbyDirectionValue = " asc";
            }
            return " order by " . $orderbyField . " " . $orderbyDirectionValue;
        } else if ($defaultOrderbyField != "") {
            return " order by " . $defaultOrderbyField;
        } else {
            return "";
        }
    }

    public  static function now($format = "")
    {
        return date('Y-m-d H:i:s');
    }

    public static function guid()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $uuid =
            substr($charid, 0, 8)
            . substr($charid, 8, 4)
            . substr($charid, 12, 4)
            . substr($charid, 16, 4)
            . substr($charid, 20, 12);
        return $uuid;
    }

    /**
     * SQL参数安全转义
     */
    public static function safeValue($value)
    {
        return str_replace("'", "\'", $value);
    }


    static public function get_client_ip($type = 0,$adv=false) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($adv){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos    =   array_search('unknown',$arr);
                if(false !== $pos) unset($arr[$pos]);
                $ip     =   trim($arr[0]);
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip     =   $_SERVER['HTTP_CLIENT_IP'];
            }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip     =   $_SERVER['REMOTE_ADDR'];
            }
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }

    /**
     * 写入后台日志
     *
     */
    public static function writeLog($logType, $request, $userID, $userName, $logTitle, $logDetail)
    {
        $route = $request->rule()->getName();
        $logTable = 's_system_log_data';
        if (!empty(\think\facade\Env::get('appconfig.APPTABLE'))) {
            $logTable = \think\facade\Env::get('appconfig.APPTABLE');
        }
        $params =  urldecode(json_encode($request->param(), JSON_UNESCAPED_UNICODE));
        $userAgent = $request->header['user-agent'];
        // $ip = $request->server['HTTP_ORIGIN'];
        $ip = self::get_client_ip();

        if ($logType == "info") {
            $detail = "msg=$logDetail;params=$params;route=$route;user-agent=$userAgent";
        } else if ($logType == "error" && $logDetail != "") {

            $message = $logDetail->getMessage();
            $line = $logDetail->getLine();
            $detail = "error=$message;line=$line;route=$route;params=$params;;user-agent=$userAgent";
        }

        $entity = [
            "system_log_data_id" => self::guid(),
            "category_identity" => "default",
            "log_type" => $logType,
            "log_title" => $logTitle,
            "log_detail" => $detail,
            "log_ip" => $ip,
            "log_datetime" => self::now(),
            "user_id" => $userID,
            "user_name" => $userName,
            "server_node" => "",
            "terminal_name" => ""
        ];
        Db::table($logTable)->insert($entity);
    }






    /**
     *
     * @time 2019年12月12日
     * @param $agent
     * @return string
     */
    private function getOs($agent): string
    {
        if (false !== stripos($agent, 'win') && preg_match('/nt 6.1/i', $agent)) {
            return 'Windows 7';
        }
        if (false !== stripos($agent, 'win') && preg_match('/nt 6.2/i', $agent)) {
            return 'Windows 8';
        }
        if (false !== stripos($agent, 'win') && preg_match('/nt 10.0/i', $agent)) {
            return 'Windows 10'; #添加win10判断
        }
        if (false !== stripos($agent, 'win') && preg_match('/nt 5.1/i', $agent)) {
            return 'Windows XP';
        }
        if (false !== stripos($agent, 'linux')) {
            return 'Linux';
        }
        if (false !== stripos($agent, 'mac')) {
            return 'mac';
        }

        return '未知';
    }

    /**
     *
     * @time 2019年12月12日
     * @param $agent
     * @return string
     */
    private function getBrowser($agent): string
    {
        if (false !== stripos($agent, "MSIE")) {
            return 'MSIE';
        }
        if (false !== stripos($agent, "Firefox")) {
            return 'Firefox';
        }
        if (false !== stripos($agent, "Chrome")) {
            return 'Chrome';
        }
        if (false !== stripos($agent, "Safari")) {
            return 'Safari';
        }
        if (false !== stripos($agent, "Opera")) {
            return 'Opera';
        }

        return '未知';
    }


}
