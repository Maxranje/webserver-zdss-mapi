<?php
/**
 * session 基础类
 *
 * @author wangxuewen <maxranje@aliyun.com>
 */

class Zy_Core_Session  {

    private static $instance    = null;

    private function __construct() {}

    public static function getInstance () {
        if (self::$instance === NULL) {
            if (array_key_exists('zyuuid', $_COOKIE)) {
                $session_id = $_COOKIE['zyuuid'];
                session_id($session_id);
                session_start();
            }
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getSessionUserInfo () {
        if (session_status() == PHP_SESSION_NONE) {
            return [];
        }

        $userid = $this->getSessionUserId ();
        $name = $this->getSessionUserName ();
        $passport = $this->getSessionUserPassport();
        $phone = $this->getSessionUserPhone ();
        $type = $this->getSessionUserType ();
        $pages = $this->getSessionUserPages();

        if (empty($userid) || empty($name) || empty($phone) || empty($type) || empty($passport)) {
            return [];
        }

        return [
            'userid'    => $userid,
            'name'      => $name,
            'phone'     => $phone,
            'type'      => $type,
            'pages'     => $pages,
            'passport'  => $passport,
        ];
    }

    public function setSessionUserInfo ($userid, $name, $passport, $phone, $type, $pages = array(), $avatar = "") {
        if (empty($userid) || empty($name) || empty($phone) || empty($type) || empty($passport)) {
            return false;
        }

        if (session_status() != PHP_SESSION_ACTIVE) {
            session_name('zyuuid');
            session_start();
            $session_id = session_id();
            $expire = time()+864000;
            setcookie('zyuuid', $session_id, $expire , "/");
        }

        $this->setSessionUserId($userid);
        $this->setSessionUserName($name);
        $this->setSessionUserPhone($phone);
        $this->setSessionUserType($type);
        $this->setSessionUserPages($pages);
        $this->setSessionUserAvatar($avatar);
        $this->setSessionUserPassport($passport);
        
        return true;
    }

    public function delSessionAndCookie () {
        if (session_status() == PHP_SESSION_NONE) {
            return true;
        }
        setcookie('zyuuid', "", 0, "/");
        setcookie('PHPSESSID', "", 0, "/");
        session_destroy();
        return true;
    }

    public function getSessionUserId () {
        return isset($_SESSION['userid']) ? $_SESSION['userid'] : '';
    }

    public function getSessionUserPassport () {
        return isset($_SESSION['passport']) ? $_SESSION['passport'] : '';
    }

    public function getSessionUserPhone () {
        return isset($_SESSION['phone']) ? $_SESSION['phone'] : '';
    }

    public function getSessionUserName () {
        return isset($_SESSION['name']) ? $_SESSION['name'] : '';
    }

    public function getSessionUserAvatar () {
        return isset($_SESSION['avatar']) ? $_SESSION['avatar'] : '';
    }

    public function getSessionUserType () {
        return isset($_SESSION['type']) ? $_SESSION['type'] : '';
    }

    public function getSessionUserPages () {
        return isset($_SESSION['pages']) && is_array($_SESSION['pages']) ? $_SESSION['pages'] : array();
    }

    public function setSessionUserName ($name) {
        $_SESSION['name'] = $name;
    }

    public function setSessionUserId ($userid) {
        $_SESSION['userid'] = $userid;
    }

    public function setSessionUserPhone ($phone) {
        $_SESSION['phone'] = $phone;
    }

    public function setSessionUserPassport ($passport) {
        $_SESSION['passport'] = $passport;
    }

    public function setSessionUserAvatar ($avatar) {
        $_SESSION['avatar'] = $avatar;
    }

    public function setSessionUserType ($type) {
        $_SESSION['type'] = $type;
    }

    public function setSessionUserPages ($pages) {
        $_SESSION['pages'] = $pages;
    }

    public function getSessionUserVerify () {
        return isset($_SESSION['verify']) ? $_SESSION['verify'] : [];
    }

    public function setSessionVerify ($verify) {
        $_SESSION['verify'] = $verify;
    }
}
