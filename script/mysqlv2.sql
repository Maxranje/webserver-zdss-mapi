CREATE DATABASE `zy_mapiv2` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `zy_mapiv2`;

CREATE TABLE `tblRole` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '权限名称',
  `descs` varchar(200) NOT NULL DEFAULT '' COMMENT '权限描述',
  `page_ids` varchar(200) NOT NULL DEFAULT '' COMMENT '能操作的页面',
  `mode_ids` varchar(200) NOT NULL DEFAULT '' COMMENT '能操作的功能',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='权限表';

CREATE TABLE `tblRoleMap` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `role_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '权限id',
  `descs` varchar(200) NOT NULL DEFAULT '' COMMENT '权限描述',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户uid',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='权限ID关联表';

CREATE TABLE `tblSchedule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `column_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '专科',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '班级',
  `subject_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '科目ID',
  `teacher_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '老师UID',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `state` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:待开始, 2:结束',
  `operator` int(10) unsigned NOT NULL COMMENT '操作员',
  `area_id` int(11) NOT NULL DEFAULT '0' COMMENT '校区id',
  `room_id` int(11) NOT NULL DEFAULT '0' COMMENT '房间id',
  `area_operator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '校区管理者',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`, `start_time`),
  KEY `teacher_uid` (`teacher_uid`, `start_time`),
  KEY `area_id` (`area_id`, `start_time`),
  KEY `room_id` (`room_id`, `start_time`),
  KEY `area_operator` (`area_operator`),
  KEY `start_time` (`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='上课记录表';

CREATE TABLE `tblCurriculum` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `schedule_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排课id',
  `student_uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '学生uid',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `column_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '专科id',
  `subject_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '科目ID',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '班级',
  `teacher_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '老师UID',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `state` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:待开始, 2:结束',
  `area_id` int(11) NOT NULL DEFAULT '0' COMMENT '校区id',
  `room_id` int(11) NOT NULL DEFAULT '0' COMMENT '房间id',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `schedule_id` (`schedule_id`, `start_time`),
  KEY `student_uid` (`student_uid`, `start_time`),
  KEY `order_id` (`order_id`, `start_time`),
  KEY `start_time` (`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='学生上课记录表';


CREATE TABLE `tblSubject` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '科目名称',
  `price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '科目价格',
  `descs` varchar(200) NOT NULL DEFAULT '' COMMENT '描述',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父id',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='科目分类表';


CREATE TABLE `tblUser` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '登录名',
  `nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '昵称',
  `passport` varchar(50) NOT NULL DEFAULT '' COMMENT '密码',
  `state` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 1:正常,2:下线',
  `type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '类型, 9超管, 10观察者, 11管理员, 12学生, 13老师',
  `phone` varchar(50) NOT NULL DEFAULT '' COMMENT '手机号',
  `avatar` varchar(100) NOT NULL DEFAULT '' COMMENT '头像',
  `school` varchar(100) NOT NULL DEFAULT '' COMMENT '学校',
  `graduate` varchar(100) NOT NULL DEFAULT '' COMMENT '班级',
  `bpid` int(11) NOT NULL DEFAULT '0' COMMENT '生源地id',
  `sex` char(1) NOT NULL DEFAULT 'M' COMMENT '性别: M男生, F:女生',
  `balance` int(11) NOT NULL DEFAULT '0' COMMENT '余额',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`uid`),
  KEY `nick` (`nickname`),
  KEY `n_p1` (`name`,`phone`),
  KEY `n_p` (`name`,`passport`),
  KEY `bpid` (`bpid`)
) ENGINE=InnoDB AUTO_INCREMENT=101000 DEFAULT CHARSET=utf8 COMMENT='用户表';


INSERT INTO `tblUser` VALUES (101001,'maxranje','maxranje','3192161', 1,9,'3192161','','','',0,'M',0,0,0,'');


CREATE TABLE `tblOrder` (
  `order_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'order_id',
  `subject_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'subject_id',
  `student_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学员uid',
  `balance` int(11) NOT NULL DEFAULT '0' COMMENT '实际存额',
  `price` int(11) NOT NULL DEFAULT '0' COMMENT '实际价格',  
  `discount_z` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '优惠信息',
  `discount_j` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '优惠信息',
  `operator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作员UID',
  `transfer_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '结转源id',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`order_id`),
  KEY `balance` (`balance`),
  KEY `transfer_id` (`transfer_id`),
  KEY `subject_id` (`subject_id`, `create_time`),
  KEY `student_uid` (`student_uid`, `create_time`),
  KEY `create_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=1010001001 DEFAULT CHARSET=utf8 COMMENT='订单表';

CREATE TABLE `tblTransfer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'order_id',
  `student_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'student_uid',
  `transfer_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'transfer_id',
  `balance` int(11) NOT NULL DEFAULT '0' COMMENT '结转金额',
  `schedule_nums` VARCHAR(100) NOT NULL DEFAULT '0' COMMENT '课时数',
  `operator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作员UID',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `student_uid` (`student_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='结转记录';

CREATE TABLE `tblRefund` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'order_id',
  `student_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'student_uid',
  `balance` int(11) NOT NULL DEFAULT '0' COMMENT '退款金额',
  `schedule_nums` VARCHAR(100) NOT NULL DEFAULT '0' COMMENT '课时',
  `operator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作员UID',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `student_uid` (`student_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='退款记录';

CREATE TABLE `tblRecharge` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'order_id',
  `student_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'student_uid',
  `type` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '充值类型, 1充值,2结转',
  `balance` int(11) NOT NULL DEFAULT '0' COMMENT '金额',
  `schedule_nums` VARCHAR(11) NOT NULL DEFAULT '0' COMMENT '课程数',
  `operator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作员UID',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `student_uid` (`student_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='充值记录';

CREATE TABLE `tblRecords` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `uid` int(10) unsigned NOT NULL COMMENT 'uid',
  `state` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1正常2回退3待定',
  `type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '类型, 9超管, 11管理员, 12学生, 13老师',
  `group_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '班级id',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `subject_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '科目id',
  `teacher_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '教师id',
  `schedule_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排课id',
  `category` tinyint(4) NOT NULL DEFAULT '0' COMMENT '类型, 1学员消耗, 2教师收入, 3教师多人收入',
  `operator` int(10) unsigned NOT NULL COMMENT 'uid',
  `money` int(11) NOT NULL DEFAULT '0' COMMENT 'money',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `state` (`state`),
  KEY `schedule_id` (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='消费记录';

CREATE TABLE `tblArea` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '名称',
  `is_online` tinyint(2) NOT NULL DEFAULT '2' COMMENT '是否在线, 1是, 2不是',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='校区表';


CREATE TABLE `tblRoom` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '教室名称',
  `area_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '校区id',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `area_id` (`area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='教室表';

CREATE TABLE `tblColumn` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `subject_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '学科id',
  `teacher_uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '老师id',
  `price` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '价格列表',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `s` (`subject_id`),
  KEY `t` (`teacher_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='老师专科表';


CREATE TABLE `tblGroup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '名称',
  `descs` varchar(200) NOT NULL DEFAULT '' COMMENT '描述',
  `state` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态, 1正常, 2关闭',
  `area_operator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '区域管理者',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `n` (`name`),
  KEY `s_u` (`state`, `update_time`),
  KEY `area_operator` (`area_operator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='班级表';

CREATE TABLE `tblLock` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '类型, 9超管, 11管理员, 12学生, 13老师',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '教师id',
  `start_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `end_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '截止时间',
  `operator` int(11) unsigned NOT NULL COMMENT 'uid',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `t_s` (`uid`,`start_time`),
  KEY `s` (`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='锁教师';

CREATE TABLE `tblBirthplace` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '名称',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='生源地';
