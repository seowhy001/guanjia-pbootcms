<?php


namespace app\api\controller;

use core\basic\Controller;
use app\admin\model\IndexModel;
use app\admin\model\content\ContentModel;
use core\basic\Model;

class SeowhyController extends Controller
{
    private $model;

    //获取分类
    public function categoryLists(){
        $model_hash = $_REQUEST['model_hash']?$_REQUEST['model_hash']:2;
        $model =  new Model();
        $list = $model->table('ay_content_sort')->where("mcode='$model_hash'")->where('status=1')->field('scode as id,name as title')->select();
        $tmpArrs = array();
        foreach ($list as $k=>&$v){
            $tmp = [];
            $tmp['id'] = intval($v->id);
            $tmp['title'] = $v->title;
            array_push($tmpArrs,$tmp);
        }
        return $this->seowhy_successRsp($tmpArrs);
    }

    // 把文章插入到数据库
    public function articleAdd()
    {
        $this->model = new ContentModel();
        if (isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on") {
            $http = "https://";
        } else {
            $http =  "http://";
        }
        $domain = $http.str_replace('\\', '/', $_SERVER['HTTP_HOST']);

        //检查标题
        $title  = isset($_REQUEST['title']) ? $_REQUEST['title'] : '';//标题
        if (empty($title)) {
            $this->seowhy_failRsp(1404, "title is empty", "标题不能为空");
        }


        //检查内容
        $content  = isset($_REQUEST['content']) ? $_REQUEST['content'] : '';
        if (empty($content)) {
            $this->seowhy_failRsp(1404, "content is empty", "内容不能为空");
        }
        //对标题与内容等字符串中的 英文单引号进行转义
        $title=str_replace("'","&#039;",$title) ;
        $content=str_replace("'","&#039;",$content) ;
        //检查栏目
        $scode  = isset($_REQUEST['category_id']) ? $_REQUEST['category_id'] : '';
        if (empty($scode)) {
            $this->seowhy_failRsp(1404, "scode is empty", "内容栏目编码不能为空");
        }


        // 地址类型
        $url_rule_type = $this->config('url_rule_type') ?: 3;
        // 获取内容栏目详情
        $contentSort = $this->getSort($scode,$acode);
        $scodename = $contentSort[0];

        //标题重复判断
//                if(self::titleUnique){
//                    $maxId = $this->getMaxid($title);
//                    $existId = $maxId[0];
//                    if($existId>0){
//                        if ($url_rule_type==1){
//                            // 模型名称
//                            $docFinalUrl=$domain.'/index.php/'.$scodename.'/' .$existId.'.html';
//                        }
//                        elseif($url_rule_type==2){
//                            $docFinalUrl=$domain.'/'.$scodename.'/' .$existId.'.html';
//                        }
//                        else{
//                            $docFinalUrl=$domain.'/?'.$scodename.'/' .$existId.'.html';
//                        }
//                        return $this->seowhy_successRsp(array("url" => $docFinalUrl),'标题已存在');
//                    }
//                }

        // 取得信息
        $acode  = isset($_REQUEST['acode']) ? $_REQUEST['acode'] : 'cn';
        $subscode = $_REQUEST['subscode'];
        $titlecolor = $_REQUEST['titlecolor'];
        $subtitle = str_replace("'","&#039;",$_REQUEST['subtitle']);
        $filename = $_REQUEST['filename'];//URL名称
        $author = str_replace("'","&#039;",$_REQUEST['author']);
        $source = str_replace("'","&#039;",$_REQUEST['source']);
        $outlink = $_REQUEST['outlink'];//跳转外链接

        if(empty($_REQUEST['date'])){
            $date =  date('Y-m-d H:i:s');
        }else{
            //date("Y-m-d H:i:s",$time);//把数字型时间按格式转换成时间格
            $date =  date('Y-m-d H:i:s',$_REQUEST['date']);
        }


        $ico = $_REQUEST['ico'];//缩略图
        $pics = $_REQUEST['pics'];//轮转图
        // $content = $_REQUEST['content'];
        $tags = str_replace("'","&#039;",$_REQUEST['tags']);
        $keywords = str_replace("'","&#039;",$_REQUEST['keywords']);
        $description = str_replace("'","&#039;",$_REQUEST['description']);
        $status = empty($_REQUEST['status']) ? 1 : $_REQUEST['status'];//状态
        if($_REQUEST['status']===0){
            $status = 0;
        }
        $istop = empty($_REQUEST['istop']) ? 0 : $_REQUEST['istop'];//置顶,0,1,默认0
        $isrecommend = empty($_REQUEST['isrecommend']) ? 0 : $_REQUEST['isrecommend'];//推荐 ,0,1,默认0
        $isheadline = empty($_REQUEST['isheadline']) ? 0 : $_REQUEST['isheadline'];//头条,0,1,默认0
        $gid = empty($_REQUEST['gid']) ? 0 : $_REQUEST['gid'];//浏览权限 ,0不限制,1初级会员,2中级会员,3高级会员 ,默认0
        $gtype = empty($_REQUEST['gtype']) ? 4 : $_REQUEST['gtype'];//权限类型 ,1小于,2小于等于,3等于,4大于等于,5大于 ,默认4
        $gnote = $_REQUEST['gnote'];//权限不足提示


        // 构建数据
        $data = array(
            'acode' => $acode,
            'scode' => $scode,
            'subscode' => $subscode,
            'title' => $title,
            'titlecolor' => $titlecolor,
            'subtitle' => $subtitle,
            'filename' => $filename,
            'author' => $author,
            'source' => $source,
            'outlink' => $outlink,
            'date' => $date,
            'ico' => $ico,
            'pics' => $pics,
            'content' => $content,
            'tags' => $tags,
            'enclosure' => $enclosure,
            'keywords' => $keywords,
            'description' => $description,
            'sorting' => 255,
            'status' => $status,
            'istop' => $istop,
            'isrecommend' => $isrecommend,
            'isheadline' => $isheadline,
            'visits' => 0,
            'likes' => 0,
            'oppose' => 0,
            'create_user' => 'admin',
            'update_user' => 'admin',
            'gid' => $gid,
            'gtype' => $gtype,
            'gnote' => $gnote
        );



        //增加内容
        $id = $this->model->addContent($data);
        if ($id) {
            if ($url_rule_type==1){
                $docFinalUrl=$domain.'/index.php/'.$scodename.'/' .$id.'.html';
            }
            elseif($url_rule_type==2){
                $docFinalUrl=$domain.'/'.$scodename.'/' .$id.'.html';
            }
            else{
                $docFinalUrl=$domain.'/?'.$scodename.'/' .$id.'.html';
            }
            $this->downloadImages($_REQUEST);
            $this->seowhy_successRsp(array("url" => $docFinalUrl));

        } else {
            $this->seowhy_failRsp(1403, "insert ay_content error", "文章发布错误");
        }

    }
    /**
     * 获取文件完整路径
     * @return string
     */
    private function getFilePath(){
        $rootUrl=dirname(dirname(dirname(dirname(__FILE__))));
        return $rootUrl.'/static/upload/image';
    }

    /**
     * 查找文件夹，如不存在就创建并授权
     * @return string
     */
    private function createFolders($dir){
        //return is_dir($dir) or ($this->createFolders(dirname($dir)) and mkdir($dir, 0777));
        $isDir =  is_dir($dir);
        if($isDir == true){
            return true;
        }else{
            mkdir($dir, 0777,true);
            return true;
        }
    }

    private function  downloadImages($post){
        try{

            $downloadFlag = isset($post['__guanjia_download_imgs_flag']) ? $post['__guanjia_download_imgs_flag'] : '';
            if (!empty($downloadFlag) && $downloadFlag== "true") {
                $docImgsStr = isset($post['__guanjia_docImgs']) ? $post['__guanjia_docImgs'] : '';
                if (!empty($docImgsStr)) {
                    $docImgs = explode(',',$docImgsStr);
                    if (is_array($docImgs)) {
                        $uploadDir = $this->getFilePath();
                        foreach ($docImgs as $imgUrl) {
                            $urlItemArr = explode('/',$imgUrl);
                            $itemLen=count($urlItemArr);
                            if($itemLen>=3){

                                $fileRelaPath=$urlItemArr[$itemLen-3].'/'.$urlItemArr[$itemLen-2];
                                $imgName=$urlItemArr[$itemLen-1];
                                $finalPath=$uploadDir. '/'.$fileRelaPath;
                                if ($this->createFolders($finalPath)) {
                                    $file = $finalPath . '/' . $imgName;
                                    if(!file_exists($file)){
                                        $doc_image_data = file_get_contents($imgUrl);
                                        file_put_contents($file, $doc_image_data);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $ex) {

        }
    }


    // 获取内容栏目详情
    public function getSort($scode,$acode)
    {
        $model =  new Model();
        return $model->table('ay_content_sort')->where("scode='$scode'")->column('filename');
    }
    // 按标题查找最大的那条文章记录
    public function getMaxid($title)
    {
        $model =  new Model();
        return $model->table('ay_content')->where("title='$title'")->column('IFNULL(max(id ),0)  maxid');
    }


    private function seowhy_successRsp($data = "", $msg = "") {
        $this->seowhy_rsp(1, $data, $msg);
    }
    private function seowhy_failRsp($code = 0, $data = "", $msg = "") {
        $this->seowhy_rsp($code, $data, $msg);
    }

    private function seowhy_rsp($code = 0, $data = "", $msg = "") {
        die(json_encode(array("code" => $code, "data" => $data, "msg" => urlencode($msg))));
    }


}



?>