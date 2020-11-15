-- 重新设置表，将私聊和群聊，服务号消息统一处理，私聊消息存放两份，一份是自己发给对方的，一份是对方发给自己的
-- 从单个用户角度的,单个用户所有联系人/会话表,微信首页列表就从这里拉取,注意私聊的话会有(a,b) (b,a) 两条记录
create table sc_contact(
    id int(11) unsigned not null auto_increment,
    selfId int(11) unsigned not null comment '自己的uid',
    category int(11) unsigned not null comment '对手方的类型，具体见代码',
    otherId int(11) unsigned not null comment '对手方id,如果私聊，则为对方id，如果群聊，则为群组id，如果服务号，则为服务号id',
    header varchar(255) default '' comment '显示在微信首页或者通讯录中对手方的头像',
    nickname varchar(255) default '' comment '显示在微信首页或者通讯录中对手方的名称',
    unreadCount int(11) unsigned default 0 comment '未读消息总计',
    lastMessageId int(11) unsigned default 0 comment '最后一条消息的id',
    lastMessageTime datetime default CURRENT_TIMESTAMP comment '最后一条消息发送的时间',
    lastMessageSenderNickname varchar(255) default '' comment '最后一条消息发送者的昵称',
    lastMessageType int(11) unsigned default 0 comment '最后一条消息的类型',
    lastMessageInfo varchar(255) default '' comment '最后一条消息的具体内容，只对普通文本消息有用',
    primary key(id)
)engine=InnoDB default charset=utf8 AUTO_INCREMENT=243935 comment '联系人/会话表';

-- 用户表
CREATE TABLE `sc_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `telephone` varchar(16) DEFAULT '' COMMENT '电话号码',
  `registerTime` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
  `status` tinyint(4) unsigned DEFAULT '0' COMMENT '用户状态，具体含义见代码',
  `token` varchar(255) DEFAULT '' COMMENT '用户token',
  `nickname` varchar(16) DEFAULT '' COMMENT '用户昵称',
  `header` varchar(128) DEFAULT '' COMMENT '头像文件名称,不含后缀',
  `userType` int(11) unsigned comment '用户类型，系统普通用户，游客用户，代理，客服等，每种带_前缀不一样',
  `refCode` varchar(64) default '' comment '代理的追踪代码',
  `parentId` int(11) unsigned default 0 comment '代理的id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=32523 DEFAULT CHARSET=utf8 COMMENT='用户表';
-- 插入一些客服人员
insert into sc_user set username='s_kefu001',password='m_kefu001',nickname='客服001号',header='wechat',userType=501;
insert into sc_user set username='s_kefu002',password='m_kefu002',nickname='客服002号',header='wechat',userType=501;
insert into sc_user set username='s_kefu003',password='m_kefu003',nickname='客服003号',header='wechat',userType=501;

-- 群组表
create table sc_group(
    id int(11) unsigned not null auto_increment,
    groupName varchar(255) default '' comment '群聊名称',
    bulletin varchar(255) default '' comment '群公告',
    header varchar(255) default '' comment '群组头像',
    groupType int(11) unsigned comment '群组的类型，如文本群，普通群等等',
    ownerId int(11) unsigned default 0 comment '群组创建者，系统群可以是0，也可以是客服创建',
    memberCount int(11) unsigned default 0 comment '群人数',
    PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=32523 DEFAULT CHARSET=utf8 COMMENT='群组表';
-- 插入一个群组
insert into sc_group set groupName='普通测试群',bulletin='欢迎大家加入这个群！',header='wechat',groupType=101;

-- 群成员表
create table sc_group_member(
    id int(11) unsigned not null auto_increment,
    groupId int(11) unsigned not null comment '群组id',
    userId int(11) unsigned not null comment '用户id',
    joinTime datetime default current_timestamp comment '加入聊天的时间',
    primary key(`id`)
)ENGINE=InnoDB AUTO_INCREMENT=32523 DEFAULT CHARSET=utf8 COMMENT='群成员表';

-- 从消息发送者角度的消息表，只包含发送部分
create table sc_message(
    id int(11) unsigned not null auto_increment,
    chatId int(11) unsigned not null comment '会话的id，也即联系人的id，根据消息大类的不同，这个id已经包含了发送者，接收者的所有信息',
    senderId int(11) unsigned not null comment '冗余信息，发送者的id',
    senderHeader varchar(255) default '' comment '发送者的头像',
    sendTime datetime default current_timestamp comment '发送的时间',
    messageType int(11) unsigned not null comment '发送消息的具体类型，普通的文本消息，还是图片，红包等，具体见代码',
    info varchar(255) default '' comment '根据messageType的不同，表征的含义不同',
    primary key(`id`)
)ENGINE=InnoDB AUTO_INCREMENT=32523 DEFAULT CHARSET=utf8 COMMENT='消息表';

-- 从单个用户角度的消息历史记录，单个用户查询具体消息时要用到，显然，对于群组消息，是个messageId的写扩撒
create table sc_message_history(
    id int(11) unsigned not null auto_increment,
    selfId int(11) unsigned not null comment '用户自己的id',
    chatId int(11) unsigned not null comment '会话id，注意和sc_message.chatId不一样，因为每个人在群组中的会话id是不一样的',
    messageId int(11) unsigned not null comment '消息的id',
    primary key(`id`)
)ENGINE=InnoDB AUTO_INCREMENT=32523 DEFAULT CHARSET=utf8 COMMENT='消息历史记录';

alter table sc_user add column socketId varchar(255) default '' comment '用户的socketId';

insert into sc_user set username='s_kefu005',password='m_kefu005',nickname='客服005号',header='kefu',userType=501;

-- 用户余额
alter table sc_user add column balance int(11) unsigned default 0 comment '用户余额，单位为分';

-- 用户发送大红包的表，数据未拆分
create table sc_red_package(
    id int(11) unsigned not null auto_increment,
    userId int(11) unsigned default 0 comment '发送的用户id',
    amount int(11) unsigned default 0 comment '发送的红包总金额，分为单位',
    sendTime datetime default current_timestamp comment '发送的时间',
    packageCount int(11) unsigned default 1 comment '拆成了多少个包',
    packageType int(11) unsigned default 0 comment '红包类型',
    extraInfo varchar(1024) default null comment '附加重要信息，以json串表达',
    primary key(`id`)
)ENGINE=InnoDB AUTO_INCREMENT=32523 DEFAULT CHARSET=utf8 COMMENT='红包表';

-- 拆分后的小红包表
create table sc_split_red_package(
    id int(11) unsigned not null auto_increment,
    redPackageId int(11) unsigned not null comment '对应的红包id',
    amount int(11) unsigned default 0 comment '拆分出的金额，分为单位',
    status tinyint(4) unsigned default 1 comment '红包状态，1为已发出未领取，2为已领取，3为已过期退回',
    pickUserId int(11) unsigned default 0 comment '领取的用户id，为0时说明这个包没被人领取',
    pickTime datetime default null comment '领取的时间',
    key redPackageId(`redPackageId`) using btree,
    primary key(`id`)
)ENGINE=InnoDB AUTO_INCREMENT=32523 DEFAULT CHARSET=utf8 COMMENT='红包表';

alter table sc_message add column senderNickname varchar(16) default '' comment '发送者的昵称，冗余存储';

alter table sc_red_package add column packageLeft int(11) unsigned default 0 comment '还剩下多少个红包未领取';
alter table sc_red_package add column chatId int(11) unsigned default 0 comment '发送者在的chatId';
alter table sc_red_package add column messageId int(11) unsigned default 0 comment '红包发送的消息id';
alter table sc_user add column freezeBalance int(11) unsigned default 0 comment '冻结的余额';

-- 公司人员表
create table sc_staff(
    `id` int(11) unsigned not null AUTO_INCREMENT,
    `username` varchar(255) NOT NULL DEFAULT '' COMMENT '用户名',
    `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
    `status` tinyint(4) unsigned default 1 comment '人员状态，1为正常，2为离职',
    `registerTime` datetime default current_timestamp comment '注册时间',
    `resignTime` datetime default null comment '离职时间',
    PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 comment '公司人员表';
insert into sc_staff set username='teststaff',password=md5(concat(md5('XSH37srerd'),'pre123')),
status=1;
alter table sc_staff add column token varchar(255) default '' comment '管理员token';

alter table sc_user add column realname varchar(32) default '' comment '用户的真实姓名';
alter table sc_user add column idNumber varchar(32) default '' comment '身份证号';

alter table sc_user add column level tinyint(4) unsigned default 1 comment '用户vip级别';

-- create table sc_level_history(
--	`id` int(11) unsigned not null AUTO_INCREMENT,
--	`userId` int(11) unsigned default 0 comment '用户的id',
--	`beforeLevel` tinyint(4) unsigned comment '用户原来的vip级别',
--	`afterLevel` tinyint(4) unsigned comment '用户之后的vip级别',
--	`changeTime` datetime default current_timestamp comment '变化的时间',
--	primary key(`id`)
-- )ENGINE=InnoDB DEFAULT CHARSET=utf8 comment '用户vip级别变化表';

-- 所有交易明细，用于追踪玩家余额balance
create table sc_transaction(
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `userId` int(11) unsigned NOT NULL comment '用户id',
    `type` int(11) unsigned NOT NULL
    comment '资金变化的种类，具体见代码',
    `changeAmount` int(11) unsigned NOT NULL comment '余额变化的数目，可正可负，分为单位',
    `afterBalance` int(11) unsigned NOT NULL comment '变化后的主钱包余额',
    `orderTime` datetime not null default current_timestamp comment '这笔明细生成的时间',
    `attchId` varchar(32) default '' comment '对应的具体类型表里面的id',
    KEY userId(`userId`) using hash,
    PRIMARY KEY (`id`)
)ENGINE=InnoDB AUTO_INCREMENT=32523 DEFAULT CHARSET=utf8 comment '资金明细';

alter table sc_transaction add key orderTime(`orderTime`) using btree;

insert into sc_group set groupName='试玩群',bulletin='欢迎大家加入这个群！',header='wechat',groupType=201;
alter table sc_transaction add column remark varchar(64) default '' comment '备注';
alter table sc_transaction modify column `changeAmount` int(11) NOT NULL COMMENT '余额变化的数目，可正可负，分为单位';

--  优惠/朋友圈列表
create table sc_promo(
	`id` int(11) unsigned not null auto_increment,
	`html` varchar(4096) default '' comment '朋友圈样式文本',
	`picture` varchar(255) default '' comment '朋友圈图片',
	`senderId` int(11) unsigned not null comment '发布者的id',
	primary key(`id`)
)ENGINE=InnoDB AUTO_INCREMENT=32523 DEFAULT CHARSET=utf8 comment '朋友圈列表';

alter table sc_promo modify column `html` text comment '朋友圈样式文本';
alter table sc_promo add column addTime datetime default current_timestamp comment '增加的时间';
alter table sc_promo drop column picture;
alter table sc_promo add column status tinyint(4) unsigned default 0 comment '朋友圈的状态，具体见代码';
alter table sc_promo add column promoCode varchar(16) default '' comment '具体代码';
alter table sc_promo add unique key `promoCode`(`promoCode`);
insert into sc_promo set html="朋友圈测试样式，其实可以有图片的",senderId=32524,status=1,promoCode='xcv4';

-- 代理用户层级表
create table sc_agent_user_level(
	`id` int(11) unsigned not null auto_increment,
	`agentId` int(11) unsigned not null comment '代理的id',
	`userId` int(11) unsigned not null comment '用户的id',
	`level` int(11) unsigned default 1 comment '代理层级，直接下级为1，依此类推',
	`addTime` datetime default current_timestamp,
	primary key(`id`)
)ENGINE=InnoDB AUTO_INCREMENT=32523 DEFAULT CHARSET=utf8 comment '代理用户层级表';

-- 用户银行卡列表
create table sc_user_bankcard(
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `userId` int(11) unsigned NOT NULL comment '用户id',
    `typeCode` varchar(16) NOT NULL comment '账号类型，支付方式简写或银行代码简写，如 ALIPAY,WECHAT,ICBC,ABC等',
    `accountNumber` varchar(32) not null comment '账号',
    `realName` varchar(16) not null comment '帐户持有人真实姓名',
    `city` varchar(16) default '' comment '开户城市，仅银行卡需要',
    `branchName` varchar(16) default '' comment '支行名称',
    PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 comment '用户银行卡列表';

alter table sc_user add column withdrawPassword varchar(255) default '' comment '玩家提款密码，没有密码时为空字符串';

-- 提现表
create table sc_withdraw(
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `userId` int(11) unsigned NOT NULL comment '用户ID',
    `userBankcardId` int(11) unsigned NOT NULL comment '用户收款账号id',
    `amount` int(11) unsigned NOT NULL comment '提现金额，分为单位',
    `status` tinyint(4) unsigned NOT NULL default 1 comment '提现状态：1为提交，2为确认已出，3为确认拒绝',
    `orderTime` datetime NOT NULL default current_timestamp comment '前台提交时间',
    `confirmTime` datetime default NULL comment '后台确认时间',
    `staffId` int(11) unsigned NOT NULL default 0 comment '后台处理人员',
    PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 comment '提现表';
alter table sc_withdraw modify column     `status` tinyint(4) unsigned NOT NULL default 1 comment '提现状态：1为提交，2为确认已出，3为确认拒绝，4为处理中';

-- 群组是否禁言
alter table sc_group add column disableTalk tinyint(2) default 0 comment '是否禁言';

alter table sc_user_bankcard add column status tinyint(4) unsigned not null default 1 comment '银行卡状态：1为在使用，2为已经被删除';

-- 三方线上充值表
create table sc_third_pay_order(
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    userId int(11) unsigned NOT NULL comment '用户id',
    amount int(11) unsigned NOT NULL comment '充值金额，分为单位',
    realAmount int(11) unsigned default NULL comment '实际的充值金额，分为单位',
    status tinyint(4) unsigned NOT NULL default 1 comment '充值状态：1为发起支付，2为回调成功',
    orderTime datetime NOT NULL default current_timestamp comment '订单发起时间',
    notifyTime datetime default NULL comment '订单回调时间',
    orderId varchar(64) NOT NULL comment '我方平台订单号',
    thirdOrderId varchar(64) default '' comment '第三方平台订单号',
    UNIQUE KEY orderId(orderId),
    PRIMARY KEY (id)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 comment '三方线上充值表';

insert into sc_staff set username='mystaff',password=md5(concat(md5('Rexuw1345'),'pre123')),
status=1;

alter table sc_transaction modify column `attchId` varchar(64) default '' comment '对应的具体类型表或相关的id';
alter table sc_message modify column info text comment '根据messageType的不同，表征的含义不同';
alter table sc_contact modify column lastMessageInfo text comment '最后一条消息的具体内容，只对普通文本消息有用';

