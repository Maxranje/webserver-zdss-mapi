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
  `sop_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学管',
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
  `identify` varchar(50) NOT NULL DEFAULT '' COMMENT '科目标识',
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
  `state` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 1:正常,2:下线, 3:休眠, 4:完结',
  `type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '类型, 9超管, 10观察者, 11管理员, 12学生, 13老师',
  `phone` varchar(50) NOT NULL DEFAULT '' COMMENT '手机号',
  `avatar` varchar(100) NOT NULL DEFAULT '' COMMENT '头像',
  `school` varchar(100) NOT NULL DEFAULT '' COMMENT '学校',
  `graduate` varchar(100) NOT NULL DEFAULT '' COMMENT '班级',
  `bpid` int(11) NOT NULL DEFAULT '0' COMMENT '生源地id',
  `sop_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学管',
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
INSERT INTO `tblUser` VALUES (101002,'caoj','曹俊斌','chris,900531', 1,9,'15718883189','','','',0,'M',0,0,0,'');


CREATE TABLE `tblOrder` (
  `order_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'order_id',
  `subject_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'subject_id',
  `student_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学员uid',
  `bpid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '生源地id',
  `cid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '班型id',
  `balance` int(11) NOT NULL DEFAULT '0' COMMENT '实际存额',
  `price` int(11) NOT NULL DEFAULT '0' COMMENT '实际价格',  
  `isfree` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '是否免费',  
  `discount_z` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '优惠信息',
  `discount_j` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '优惠信息',
  `operator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作员UID',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`order_id`),
  KEY `balance` (`balance`),
  KEY `subject_id` (`subject_id`, `create_time`),
  KEY `student_uid` (`student_uid`, `create_time`),
  KEY `bpid` (`bpid`, `create_time`),
  KEY `cid` (`cid`, `create_time`),
  KEY `create_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=1010001001 DEFAULT CHARSET=utf8 COMMENT='订单表';

CREATE TABLE `tblOrderChange` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'order_id',
  `student_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'student_uid',
  `type` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '充值类型, 1充值,2结转',
  `balance` int(11) NOT NULL DEFAULT '0' COMMENT '金额',
  `duration` VARCHAR(11) NOT NULL DEFAULT '0' COMMENT '课程数',
  `order_info` VARCHAR(2000) NOT NULL DEFAULT '0' COMMENT '订单信息',
  `operator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作员UID',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `student_uid` (`student_uid`),
  KEY `u_t` (`update_time`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='充值记录';

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
  `price` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '1个人价格',
  `muilt_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '阈值人数',
  `muilt_price` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '阈值人数',
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
  `identify` varchar(200) NOT NULL DEFAULT '' COMMENT '班号',
  `subject_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '科目id',
  `cid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '班型id',
  `descs` varchar(200) NOT NULL DEFAULT '' COMMENT '描述',
  `state` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态, 1正常, 2关闭',
  `area_operator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '助教',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `n` (`name`),
  KEY `i` (`identify`),
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

CREATE TABLE `tblClasze` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '名称',
  `identify` varchar(200) NOT NULL DEFAULT '' COMMENT '标记',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='班型';

CREATE TABLE `tblClaszemap` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `cid` int(11) unsigned NOT NULL COMMENT 'claszeid',
  `bpid` int(11) unsigned NOT NULL COMMENT '生源地id',
  `subject_id` int(11) unsigned NOT NULL COMMENT '科目ID',
  `price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '价格',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `b_s` (`bpid`, `subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='班型映射';

CREATE TABLE `tblCapital` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `uid` int(11) unsigned NOT NULL  COMMENT 'uid',
  `type` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '类型, 1用户充值, 2.退费',
  `operator` int(11) unsigned NOT NULL  COMMENT 'uid',
  `capital` int(11) NOT NULL COMMENT 'capital',
  `plan_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'plan_id',
  `rop_uid` int(11) NOT NULL DEFAULT '0' COMMENT '审核员uid',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` VARCHAR(2000) NOT NULL DEFAULT '' COMMENT "冗余",
  PRIMARY KEY (`id`),
  KEY `uid_type` (`uid`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='学生金额变更表';

-- 该能力下线
-- CREATE TABLE `tblPlan` (
--   `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
--   `name` varchar(200) NOT NULL DEFAULT '' COMMENT '名称',
--   `price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '价格',
--   `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
--   `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
--   `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
--   PRIMARY KEY (`id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='计划';

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
  KEY `schedule_id` (`schedule_id`),
  KEY `u_s_t` (`update_time`,`state`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='消费记录';


-- alter table tblCapital add column `plan_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'plan_id';
-- alter table tblUser add column `sop_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学管';
-- alter table tblCurriculum add column `sop_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学管';

-- v2.1.3
-- alter table tblRecords add index `u_s_t` (`update_time`, `state`, `type`);
-- alter table tblOrderChange add index `u_t` (`update_time`, `type`);

-- v2.1.4
CREATE TABLE `tblReview` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `type` TINYINT UNSIGNED NOT NULL DEFAULT '1' COMMENT '审核类型: 1: 充值, 2: 退款',
  `state` TINYINT UNSIGNED NOT NULL DEFAULT '3' COMMENT '1正常2拒接3待定',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户uid',
  `rop_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核员uid',
  `sop_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '送审人员uid',
  `work_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '目标业务id',
  `remark` varchar(2000) NOT NULL DEFAULT '' COMMENT '备注',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `type` (`type`),
  KEY `work_id` (`work_id`),
  KEY `state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='审核';
-- alter table tblCapital add column `rop_uid` int(11) NOT NULL DEFAULT '0' COMMENT '审核员uid';
-- alter table tblCapital add column `state` TINYINT UNSIGNED NOT NULL DEFAULT '1' COMMENT '1正常2拒接3待定';


-- v2.1.9 留学计划相关业务更新
-- 留学计划
CREATE TABLE `tblAbroadPlan` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '名称',
  `price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '计划价格',
  `duration` VARCHAR(11) NOT NULL DEFAULT '0' COMMENT '计划时长',
  `operator` int(10) unsigned NOT NULL COMMENT '创建人员',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1000 COMMENT='留学计划';

CREATE TABLE `tblApackageConfirm` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `abroadplan_id` int(10) unsigned NOT NULL COMMENT '计划id',
  `apackage_id` int(10) unsigned NOT NULL COMMENT '服务id',
  `content` TEXT COMMENT '配置',
  `operator` int(10) unsigned NOT NULL COMMENT '创建人员',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='留学服务双向检查项';

CREATE TABLE `tblAbroadplanConfirm` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `abroadplan_id` int(10) unsigned NOT NULL COMMENT '计划id',
  `content` TEXT COMMENT '配置',
  `operator` int(10) unsigned NOT NULL COMMENT '创建人员',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='留学计划双向检查项';

-- 留学计划订单和班型绑定
CREATE TABLE `tblAporderpackage` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学员uid',
  `abroadplan_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '计划id',
  `schedule_nums` VARCHAR(11) NOT NULL DEFAULT '0' COMMENT '计划时长',
  `price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '价格',
  `state` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态',
  `operator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作员UID',
  `remark` VARCHAR(2000) NOT NULL DEFAULT '0' COMMENT '备注',
  `confirm` TEXT COMMENT '服务check记录',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=301000  DEFAULT CHARSET=utf8 COMMENT='计划与订单映射表';

alter table tblOrder add column `abroadplan_id` int(11) NOT NULL DEFAULT '0' COMMENT '计划ID';
alter table tblOrder add column `apackage_id` int(11) NOT NULL DEFAULT '0' COMMENT '包ID';
alter table tblOrder add column `type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '类型. 1常规, 2计划';
alter table tblOrder add index `t_a_a` (`type`, `apackage_id`);

alter table tblOrderChange drop index `u_t`;
alter table tblOrderChange add index `t_u` (`type`, `update_time`);

alter table tblCapital change column `plan_id` `abroadplan_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'abroadplan_id';

-- 这个不写入表里
insert into tblAbroadPlan (`name`, `price`, `duration`, `operator`, `update_time`, `create_time`) select name, price, 20, 101001, update_time, create_time from tblPlan;

-- 操作日志表
CREATE TABLE `tblOperationLog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作员uid',
  `point` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '场景, 42:编辑排课',
  `work_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '外键id',
  `original_data` TEXT COMMENT '原内容',
  `current_data` TEXT COMMENT '新内容',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `w_i` (`work_id`),
  KEY `u_c` (`uid`,`create_time`),
  KEY `t_c` (`point`,`create_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='操作日志';