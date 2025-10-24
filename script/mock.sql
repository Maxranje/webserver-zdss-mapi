use zy_mapiv2;
-- mock 填充
alter table tblRole add column `mock_ids` varchar(200) NOT NULL DEFAULT '' COMMENT '能操作的mock页面';
alter table tblUser add column `is_mock`  TINYINT(2) NOT NULL DEFAULT '1' COMMENT '是否模考考生1:是,2不是';

CREATE TABLE `tblQuestion` (
    `qid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'qid',
    `type` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '类型',
    `state` TINYINT(2) NOT NULL DEFAULT '1' COMMENT '状态1有效,2失效',
    `level` TINYINT(2) NOT NULL DEFAULT '1' COMMENT '试题难度',
    `subject_id` int(11) NOT NULL DEFAULT '0' COMMENT '科目id',
    `content` TEXT COMMENT '试题主内容',
    `explan` TEXT COMMENT '解析',
    `description` VARCHAR(300) NOT NULL DEFAULT '' COMMENT "备注",
    `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '分组id',
    `is_coll` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '是否是题目组壳子',
    `pre_meta_id` int(11) NOT NULL DEFAULT '0' COMMENT '前置材料id',
    `light_id` int(11) NOT NULL DEFAULT '0' COMMENT '材料高亮id',
    `score` int(11) NOT NULL DEFAULT '0' COMMENT '分数',
    `operator` int(11) NOT NULL DEFAULT '0' COMMENT '操作员id',
    `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',  
    `ext` VARCHAR(2000) NOT NULL DEFAULT '' COMMENT "冗余",
    PRIMARY KEY (`qid`),
    KEY `type` (`type`),
    KEY `level` (`level`),
    KEY `state` (`state`), 
    KEY `coll_parent_id` (`is_coll`,`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1000200 DEFAULT CHARSET=utf8 COMMENT='试题表';

CREATE TABLE `tblAnswer` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `qid` int(11) NOT NULL DEFAULT '0' COMMENT '试题id',
    `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '题目组试题id',
    `type` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '试题类型',    
    `content` TEXT COMMENT '答案内容',
    `is_correct` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '是否正确',
    `operator` int(11) NOT NULL DEFAULT '0' COMMENT '操作员id',
    `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',  
    `ext` VARCHAR(2000) NOT NULL DEFAULT '' COMMENT "冗余",
    PRIMARY KEY (`id`),
    KEY `qid` (`qid`),
    KEY `parent_id` (`parent_id`),
    KEY `type` (`type`)  
) ENGINE=InnoDB AUTO_INCREMENT=2000100 DEFAULT CHARSET=utf8 COMMENT='答案表';

CREATE TABLE `tblMeta` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `content` text COMMENT '材料内容',
    `meta_type` TINYINT(2) NOT NULL DEFAULT '1' COMMENT '物料类型',
    `operator` int(11) NOT NULL DEFAULT '0' COMMENT '操作员id',
    `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `ext` VARCHAR(2000) NOT NULL DEFAULT '' COMMENT "冗余",
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='材料';

CREATE TABLE `tblTag` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `title` varchar(200) NOT NULL DEFAULT '' COMMENT '标签名',
    `description` varchar(1000) NOT NULL DEFAULT '' COMMENT '描述',
    `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父id',
    `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
    PRIMARY KEY (`id`),
    KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='标签表';

CREATE TABLE `tblQuestionTag` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `qid` int(11) NOT NULL DEFAULT '0' COMMENT '试题id',
    `tag_id` int(11) NOT NULL DEFAULT '0' COMMENT '标签id',
    `level` int(11) NOT NULL DEFAULT '0' COMMENT 'level',
    `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
    PRIMARY KEY (`id`),
    KEY `qid` (`qid`),
    KEY `tag_id_level` (`tag_id`, `level`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='试题标签映射表';

CREATE TABLE `tblPaper` (
    `pid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `title` VARCHAR(200) not null DEFAULT '' COMMENT '试卷标题',
    `type` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '类型',
    `frequency` int(11) NOT NULL DEFAULT '0' COMMENT '次数',
    `total_score` int(11) NOT NULL DEFAULT '0' COMMENT '总分数',
    `total_question` int(11) NOT NULL DEFAULT '0' COMMENT '考试题数',
    `remark` VARCHAR(500) not null DEFAULT '' COMMENT '说明',
    `operator` int(11) NOT NULL DEFAULT '0' COMMENT '操作员id',
    `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',  
    `ext` VARCHAR(2000) NOT NULL DEFAULT '' COMMENT "冗余",
    PRIMARY KEY (`pid`),
    KEY `type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=1000 DEFAULT CHARSET=utf8 COMMENT='试卷表';

CREATE TABLE `tblPaperQuestion` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '试卷id',
  `qid` int(11) NOT NULL DEFAULT '0' COMMENT '试题id',
  `score` int(11) NOT NULL DEFAULT '0' COMMENT '分数',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',  
  `ext` VARCHAR(2000) NOT NULL DEFAULT '' COMMENT "冗余",
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `qid` (`qid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='试卷试题映射表';


CREATE TABLE `tblSource` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '来源名称',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父id',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='来源分类表';

CREATE TABLE `tblQuestionSource` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `qid` int(11) NOT NULL DEFAULT '0' COMMENT 'question id',
    `source_id` int(11) NOT NULL DEFAULT '0' COMMENT 'source id',
    `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',  
    `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
    PRIMARY KEY (`id`),
    KEY `source_id` (`source_id`),
    KEY `qid` (`qid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='来源映射表';

-- exam
CREATE TABLE `tblExam` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `indentify` VARCHAR(200) not null DEFAULT '' COMMENT '考试标识',
    `pid` int(11) NOT NULL DEFAULT '0' COMMENT '试卷id',
    `paper_type` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '考试类型',
    `total_score` int(11) NOT NULL DEFAULT '0' COMMENT '总分',
    `total_question` int(11) NOT NULL DEFAULT '0' COMMENT '出题数',
    `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '开始时间',
    `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '结束时间',
    `expire_time` int(11) NOT NULL DEFAULT '0' COMMENT '时长',
    `remark` VARCHAR(500) not null DEFAULT '' COMMENT '说明',
    `teacher_uid` int(11) NOT NULL DEFAULT '0' COMMENT '监考老师',
    `status` TINYINT(2) not null DEFAULT '0' COMMENT '状态',
    `operator` int(11) NOT NULL DEFAULT '0' COMMENT '操作员',    
    `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',  
    `ext` VARCHAR(2000) NOT NULL DEFAULT '' COMMENT "冗余",
    PRIMARY KEY (`id`),
    KEY `pid` (`pid`),
    KEY `indentify` (`indentify`),
    KEY `type_status` (`paper_type`, `status`, `start_time`),
    KEY `teacher_uid` (`teacher_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='考试';

CREATE TABLE `tblExamStudent` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `exam_id` int(11) NOT NULL DEFAULT '0' COMMENT '考试 id',
    `pid` int(11) NOT NULL DEFAULT '0' COMMENT 'paper id',
    `student_uid` int(11) NOT NULL DEFAULT '0' COMMENT '考生id',
    `status` TINYINT(2) not null DEFAULT '0' COMMENT '状态',
    `score` int(11) not null DEFAULT '0' COMMENT '总分',
    `spend_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '花费时间(逆向)',
    `operator` int(11) NOT NULL DEFAULT '0' COMMENT '操作员',
    `start_time` int(11) not null DEFAULT '0' COMMENT '开始作答时间',
    `end_time` int(11) not null DEFAULT '0' COMMENT '交卷时间',
    `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',  
    `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
    PRIMARY KEY (`id`),
    KEY `exam_id_uid` (`exam_id`, `student_uid`),
    KEY `student_uid_s` (`student_uid`, `status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='考试与考生映射表';

CREATE TABLE `tblExamAnswer` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `exam_id` int(11) NOT NULL DEFAULT '0' COMMENT '考试 id',
    `pid` int(11) NOT NULL DEFAULT '0' COMMENT 'paper id',
    `student_uid` int(11) NOT NULL DEFAULT '0' COMMENT '考生id',
    `type` int(11) NOT NULL DEFAULT '0' COMMENT 'type',
    `qid` int(11) NOT NULL DEFAULT '0' COMMENT 'qid',
    `answer_id` int(11) NOT NULL DEFAULT '0' COMMENT 'answer id',   
    `is_correct` TINYINT(2) not null DEFAULT '0' COMMENT '是否正确',
    `score` int(11) not null DEFAULT '0' COMMENT '得分',
    `answer_content` text  COMMENT '客观题答案',
    `review_content` TEXT COMMENT '解析',
    `spend_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '作答时间',
    `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',  
    `ext` varchar(2000) NOT NULL DEFAULT '' COMMENT '冗余',
    PRIMARY KEY (`id`),
    KEY `pid` (`pid`),
    KEY `e_u_q` (`exam_id`, `student_uid`, `qid`),
    KEY `student_uid_time` (`student_uid`, `update_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='考生考试作答记录表';