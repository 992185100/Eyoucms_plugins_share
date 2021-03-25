/*
Navicat MySQL Data Transfer

Source Server         : localhost_3306
Source Server Version : 50553
Source Host           : localhost:3306
Source Database       : eyoucms_develop

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2018-07-14 16:08:17
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for #@__weapp_wx_config
-- ----------------------------
DROP TABLE IF EXISTS `#@__weapp_wx_config`;
CREATE TABLE `#@__weapp_wx_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '表id',
  `wxname` varchar(60) DEFAULT '' COMMENT '公众号名称',
  `apiurl` varchar(500) DEFAULT '' COMMENT '服务器地址(URL)',
  `appid` varchar(50) DEFAULT '' COMMENT 'appid',
  `appsecret` varchar(50) DEFAULT '' COMMENT 'appsecret',
  `wxid` varchar(64) DEFAULT '' COMMENT '公众号原始ID',
  `weixin` char(64) DEFAULT '' COMMENT '微信号',
  `headerpic` char(255) DEFAULT '' COMMENT '头像地址',
  `token` int(255) DEFAULT '0' COMMENT 'token',
  `w_token` varchar(150) DEFAULT '' COMMENT '微信对接token',
  `type` tinyint(1) DEFAULT '0' COMMENT '微信号类型',
  `web_access_token` varchar(200) DEFAULT '' COMMENT ' 网页授权token',
  `web_refresh_token` varchar(200) DEFAULT '' COMMENT 'web_refresh_token',
  `web_expires_in` int(11) DEFAULT '0' COMMENT 'access_token有效期(秒)',
  `web_expires` int(11) DEFAULT '0' COMMENT '过期时间',
  `qr` varchar(200) DEFAULT '' COMMENT '二维码',
  `wait_access` tinyint(1) DEFAULT '1' COMMENT '微信接入状态,0待接入1已接入',
  `add_time` int(11) DEFAULT '0' COMMENT 'create_time',
  `update_time` int(11) DEFAULT '0' COMMENT 'updatetime',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='微信公众账号表';

-- ----------------------------
-- Table structure for #@__weapp_wx_img
-- ----------------------------
DROP TABLE IF EXISTS `#@__weapp_wx_img`;
CREATE TABLE `#@__weapp_wx_img` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '表id',
  `keyword` varchar(100) NOT NULL COMMENT '关键词',
  `intro` text COMMENT '简介',
  `litpic` varchar(255) DEFAULT '' COMMENT '封面图片',
  `media_id` varchar(255) DEFAULT '' COMMENT '素材ID',
  `wx_img_url` varchar(500) DEFAULT '' COMMENT '官方返回的素材url(图片只能在腾讯系域名内使用，否则图片将被屏蔽)',
  `thumb_media_id` varchar(255) DEFAULT '' COMMENT '缩略图的media_id',
  `thumb_wx_img_url` varchar(500) DEFAULT '' COMMENT '官方返回的缩略图素材url(图片只能在腾讯系域名内使用，否则图片将被屏蔽)',
  `url` varchar(255) DEFAULT '' COMMENT '图文外链地址',
  `title` varchar(100) DEFAULT '' COMMENT '标题',
  `aid` int(11) DEFAULT '0' COMMENT '站内文档ID',
  `token` int(11) NOT NULL DEFAULT '0' COMMENT '公众号唯一标识',
  `add_time` int(11) DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `token` (`token`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='微信单图文回复表';

-- ----------------------------
-- Table structure for #@__weapp_wx_keyword
-- ----------------------------
DROP TABLE IF EXISTS `#@__weapp_wx_keyword`;
CREATE TABLE `#@__weapp_wx_keyword` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '表id',
  `keyword` varchar(100) DEFAULT '' COMMENT '关键字',
  `pid` int(11) DEFAULT NULL COMMENT '对应表ID',
  `type` varchar(30) DEFAULT 'TEXT' COMMENT '关键词操作类型',
  `token` int(11) NOT NULL DEFAULT '0' COMMENT '公众号唯一标识',
  `add_time` int(11) DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`) USING BTREE,
  KEY `token` (`token`,`type`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='微信回复关键字主表';

-- ----------------------------
-- Table structure for #@__weapp_wx_menu
-- ----------------------------
DROP TABLE IF EXISTS `#@__weapp_wx_menu`;
CREATE TABLE `#@__weapp_wx_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `level` tinyint(1) DEFAULT '1' COMMENT '菜单级别',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT 'name',
  `sort_order` int(5) DEFAULT '0' COMMENT '排序',
  `type` varchar(20) DEFAULT '' COMMENT '0 view 1 click',
  `value` varchar(255) DEFAULT NULL COMMENT 'value',
  `token` int(11) NOT NULL DEFAULT '0' COMMENT '公众号唯一标识',
  `pid` int(11) DEFAULT '0' COMMENT '上级菜单',
  `add_time` int(11) DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `token` (`token`,`pid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='微信自定义菜单表';

-- ----------------------------
-- Table structure for #@__weapp_wx_news
-- ----------------------------
DROP TABLE IF EXISTS `#@__weapp_wx_news`;
CREATE TABLE `#@__weapp_wx_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '表id',
  `keyword` varchar(100) NOT NULL DEFAULT '' COMMENT '关键字',
  `img_id` varchar(50) DEFAULT '' COMMENT '图文组合id',
  `msg_id` varchar(50) DEFAULT '' COMMENT '群发的消息ID',
  `errcode` varchar(20) DEFAULT '' COMMENT '错误码',
  `is_del` tinyint(1) DEFAULT '0' COMMENT '撤销：1=是，0=否',
  `token` int(11) NOT NULL DEFAULT '0' COMMENT '公众号唯一标识',
  `add_time` int(11) DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `token` (`token`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='微信组合图文回复表';

-- ----------------------------
-- Table structure for #@__weapp_wx_pic
-- ----------------------------
DROP TABLE IF EXISTS `#@__weapp_wx_pic`;
CREATE TABLE `#@__weapp_wx_pic` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '表id',
  `keyword` varchar(100) DEFAULT '' COMMENT '关键词',
  `title` varchar(255) DEFAULT '',
  `media_id` varchar(255) DEFAULT '' COMMENT '素材ID',
  `litpic` varchar(255) DEFAULT '' COMMENT '上传图片',
  `wx_img_url` varchar(500) DEFAULT '' COMMENT '官方返回的素材url(图片只能在腾讯系域名内使用，否则图片将被屏蔽)',
  `token` int(11) NOT NULL DEFAULT '0' COMMENT '公众号唯一标识',
  `add_time` int(11) DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `token` (`token`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='微信图片回复表';

-- ----------------------------
-- Table structure for #@__weapp_wx_subscribe
-- ----------------------------
DROP TABLE IF EXISTS `#@__weapp_wx_subscribe`;
CREATE TABLE `#@__weapp_wx_subscribe` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '表id',
  `media_id` varchar(255) DEFAULT '' COMMENT '素材ID',
  `litpic` varchar(255) DEFAULT '' COMMENT '上传图片',
  `wx_img_url` varchar(500) DEFAULT '' COMMENT '官方返回的素材url(图片只能在腾讯系域名内使用，否则图片将被屏蔽)',
  `text` text COMMENT '回复内容',
  `type` varchar(30) DEFAULT 'TEXT' COMMENT '关键词操作类型',
  `token` int(11) NOT NULL DEFAULT '0' COMMENT '公众号唯一标识',
  `add_time` int(11) DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `token` (`token`,`type`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='微信被关注回复表';

-- ----------------------------
-- Table structure for #@__weapp_wx_text
-- ----------------------------
DROP TABLE IF EXISTS `#@__weapp_wx_text`;
CREATE TABLE `#@__weapp_wx_text` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '表id',
  `keyword` varchar(100) DEFAULT '' COMMENT '关键字',
  `text` text COMMENT 'text',
  `token` int(11) NOT NULL DEFAULT '0' COMMENT '公众号唯一标识',
  `add_time` int(11) DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `token` (`token`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='微信文本回复表';
