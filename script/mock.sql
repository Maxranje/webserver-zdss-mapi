use zy_mapiv2;

CREATE TABLE `tblPaper` (
    `pid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'pid',
    `title` VARCHAR(200) not null DEFAULT '' COMMENT '试卷标题',
    `descs` VARCHAR(500) not null DEFAULT '' COMMENT '说明',
    `subject_id` (11) NOT NULL DEFAULT '0' COMMENT '科目',
    `source` (11) NOT NULL DEFAULT '0' COMMENT '来源',
    `frequency` int(11) NOT NULL DEFAULT '0' COMMENT '次数',
    `level` TINYINT(2) NOT NULL DEFAULT '1' COMMENT '难度',
    `question_cnt` int(11) NOT NULL DEFAULT '0' COMMENT '试题数量',
    `question_score` int(11) NOT NULL DEFAULT '0' COMMENT '试题总分数',
    `operator` int(11) NOT NULL DEFAULT '0' COMMENT '操作员id',
    `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',  
    `ext` VARCHAR(2000) NOT NULL DEFAULT '' COMMENT "冗余",
    PRIMARY KEY (`pid`)
) ENGINE=InnoDB AUTO_INCREMENT=1000 DEFAULT CHARSET=utf8 COMMENT='试卷表';

CREATE TABLE `tblQuestion` (
    `qid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'qid',
    `content` TEXT COMMENT '试题主内容',
    `subject_id` (11) NOT NULL DEFAULT '0' COMMENT '科目id',
    `type` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '类型',
    `is_multiple` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '是否分组',
    `pre_meta_id` int(11) NOT NULL DEFAULT '0' COMMENT '前置材料id',
    `highlight_id` int(11) NOT NULL DEFAULT '0' COMMENT '高亮位置id',
    `level` TINYINT(2) NOT NULL DEFAULT '1' COMMENT '试题难度',
    `score` int(11) NOT NULL DEFAULT '0' COMMENT '分数',
    `frequency` int(11) NOT NULL DEFAULT '0' COMMENT '次数',
    `operator` int(11) NOT NULL DEFAULT '0' COMMENT '操作员id',
    `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',  
    `ext` VARCHAR(2000) NOT NULL DEFAULT '' COMMENT "冗余",
    PRIMARY KEY (`qid`),
    KEY `type` (`type`),
    KEY `level` (`level`)
) ENGINE=InnoDB AUTO_INCREMENT=1000200 DEFAULT CHARSET=utf8 COMMENT='试题表';

CREATE TABLE `tblPaperQuesMap` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '试卷id',
  `qid` int(11) NOT NULL DEFAULT '0' COMMENT '试题id',
  `operator` int(11) NOT NULL DEFAULT '0' COMMENT '操作员id',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',  
  `ext` VARCHAR(2000) NOT NULL DEFAULT '' COMMENT "冗余",
  PRIMARY KEY (`qid`),
  KEY `pid` (`pid`),
  KEY `qid` (`qid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='试卷试题映射表';

CREATE TABLE `tblAnswer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `qid` int(11) NOT NULL DEFAULT '0' COMMENT '前置材料id',
  `content` VARCHAR(2000) not null DEFAULT '' COMMENT '答案内容',
  `type` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '试题类型',
  `is_correct` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '正确答案id',
  `score` int(11) NOT NULL DEFAULT '0' COMMENT '分数',
  `operator` int(11) NOT NULL DEFAULT '0' COMMENT '操作员id',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',  
  `ext` VARCHAR(2000) NOT NULL DEFAULT '' COMMENT "冗余",
  PRIMARY KEY (`id`),
  KEY `qid` (`qid`),
  KEY `type` (`type`)  
) ENGINE=InnoDB AUTO_INCREMENT=2000100 DEFAULT CHARSET=utf8 COMMENT='答案表';

CREATE TABLE `tblQuestAnsMap` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '试卷id',
  `qid` int(11) NOT NULL DEFAULT '0' COMMENT '试题id',
  `operator` int(11) NOT NULL DEFAULT '0' COMMENT '操作员id',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',  
  `ext` VARCHAR(2000) NOT NULL DEFAULT '' COMMENT "冗余",
  PRIMARY KEY (`qid`),
  KEY `pid` (`pid`),
  KEY `qid` (`qid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='试卷试题映射表';

CREATE TABLE `tblMeta` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `content` text COMMENT '分组的材料id',
  `type` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '试题类型',
  `is_multiple` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '',
  `operator` int(11) NOT NULL DEFAULT '0' COMMENT '操作员id',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',  
  `ext` VARCHAR(2000) NOT NULL DEFAULT '' COMMENT "冗余",
  PRIMARY KEY (`id`),
  KEY `type` (`type`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='材料';

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

CREATE TABLE `tblPaperSource` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `paper_id` int(11) NOT NULL DEFAULT '0' COMMENT 'paper id',
  `source_id` int(11) NOT NULL DEFAULT '0' COMMENT 'source id',
  PRIMARY KEY (`id`),
  KEY `source_id` (`source_id`),
  KEY `paper_id` (`paper_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='来源试卷映射表';

CREATE TABLE `tblPaperSubject` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `paper_id` int(11) NOT NULL DEFAULT '0' COMMENT 'paper id',
  `subject_id` int(11) NOT NULL DEFAULT '0' COMMENT 'subject id',
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `paper_id` (`paper_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='科目试卷映射表';
