<?php

class Zy_Core_Actions {

    // get / post 请求
    protected $_request     = array();

    // server 数据
    protected $_public      = array();

    // 输出数据结构
    protected $_output      = array();

    // 用户信息
    protected $_userInfo    = array();

    // 是否登录
    protected $_isLogin     = false;

    protected $_userid      = 0;

    protected $_data        = array();

    // ------------------------------

    // 初始化Action所需要内容
    public function _init () {

        // 构造请求参数
        $_GET   = !empty($_GET)  && is_array($_GET)  ? $_GET  : array();
        $_POST  = !empty($_POST) && is_array($_POST) ? $_POST : array();
        $_Json  = json_decode(file_get_contents('php://input'), true);
        $_Json  = !empty($_Json) && is_array($_Json) ? $_Json : array();

        $this->_request = array_merge ($_GET, $_POST, $_Json) ;

        $this->_public = empty($_SERVER) ? array() : $_SERVER ;

        $this->_output  = [
            'status'        => 0,
            'msg'           => 'success',
            'data'          => array(),
        ];

        // session中有用户信息,  获取用户信息
        $this->_userInfo = Zy_Core_Session::getInstance()->getSessionUserInfo();
        if (!empty($this->_userInfo['userid'])) {
            $this->_userid = $this->_userInfo['userid'] ;
            $this->_isLogin = true;
            $this->_userInfo["is_reviewer"] = $this->isReviewer();
            define("OPERATOR", intval($this->_userid));
        } 
        try
        {
            Zy_Helper_Benchmark::start('ts_all');
            $res = $this->execute();
            $this->_output['data'] = empty($res) ? array() : (is_array($res) ? $res : array($res));
            Zy_Helper_Benchmark::stop('ts_all');
        }
        catch (Zy_Core_Exception $exception)
        {
            $this->_output['status'] = $exception->getCode ();
            $this->_output['msg'] = $exception->getMessage ();
        }

        $this->displayJson();
    }

    public function isLogin () {
        return !empty ($this->_userInfo);
    }

    public function isReviewer () {
        return !empty ($this->_userInfo["modes"]) && 
            is_array($this->_userInfo["modes"]) && 
            in_array(Service_Data_Roles::ROLE_MODE_REVIEW_HANDLE, $this->_userInfo["modes"]) ? 1 : 0;
    }

    public function displayJson () {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache');

        Zy_Helper_Log::addnotice("time: [" . Zy_Helper_Benchmark::elapsed_all() . "] request complete" );
        if (!empty($_REQUEST['cros_callback'])) {
            header('Content-Type: text/javascript');
            echo $_REQUEST['cros_callback'] . '(' . json_encode($this->_output) . ');';
            exit;
        }
		echo json_encode($this->_output);
		exit;
    }

    public function displayTemplate ($tpl) {
        header('Content-Type: text/html; charset=utf-8');
        header("Cache-Control: no-cache, must-revalidate");
        
        $loader = new Twig_Loader_Filesystem(VIEWPATH);
        // 配置环境
        $twig = new Twig_Environment($loader);
        
        $this->_output['static_path'] = VIEWPATH;
        // 将参数传入指定模板，渲染输出结果
        echo $twig->render($tpl . ".twig", $this->_output);
        exit;
    }

    public function redirectLogin () {
        header('HTTP/1.1 301 Moved Permanently');
        header(sprintf("Location: http://%s/login", $_SERVER['HTTP_HOST']));
        exit;
    }

    public function redirect404 () {
        header('HTTP/1.1 301 Moved Permanently');
        header(sprintf("Location: http://%s/mapi/sign/err", $_SERVER['HTTP_HOST']));
        exit;
    }    

    public function redirect ($path) {
        header('HTTP/1.1 301 Moved Permanently');
        header(sprintf("Location: http://%s/%s", $_SERVER['HTTP_HOST'], $path));
        exit;
    }

    public function error($ec = 405, $em = '', $data = []) {
        $this->_output['status'] = $ec;
        $this->_output['msg'] = $em;
        $this->_output['data'] = $data;
        $this->displayJson();
    }

    public function execute(){}

}