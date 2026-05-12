-- ============================================================
-- mixice.cn 数据库结构
-- 共 5 张表：photography / standpoint / page / comment / settings
-- ============================================================

CREATE DATABASE IF NOT EXISTS `mixice` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `mixice`;

-- -----------------------------------------------------------
-- 摄影作品
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `photography`;
CREATE TABLE `photography` (
  `id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title`       VARCHAR(200) NOT NULL DEFAULT '' COMMENT '作品标题',
  `cover`       VARCHAR(500) DEFAULT NULL COMMENT '封面图URL',
  `images`      LONGTEXT COMMENT '相册图片JSON',
  `comment_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '允许评论: 1是 0否',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='摄影作品';

-- -----------------------------------------------------------
-- 观点文章
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `standpoint`;
CREATE TABLE `standpoint` (
  `id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title`       VARCHAR(200) NOT NULL DEFAULT '' COMMENT '文章标题',
  `cover`       VARCHAR(500) DEFAULT NULL COMMENT '封面图URL',
  `content`     LONGTEXT COMMENT '富文本正文',
  `comment_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '允许评论: 1是 0否',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='观点文章';

-- -----------------------------------------------------------
-- 静态页面（如 gear）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `page`;
CREATE TABLE `page` (
  `id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title`       VARCHAR(200) NOT NULL DEFAULT '' COMMENT '页面标题',
  `slug`        VARCHAR(100) NOT NULL COMMENT 'URL标识 如gear',
  `content`     LONGTEXT COMMENT '富文本正文',
  `comment_enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '允许评论: 1是 0否',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='静态页面';

-- -----------------------------------------------------------
-- 评论（多态：挂载到 photography / standpoint / page）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `comment`;
CREATE TABLE `comment` (
  `id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `target_type` ENUM('photography','standpoint','page') NOT NULL COMMENT '关联类型',
  `target_id`   INT(10) UNSIGNED NOT NULL COMMENT '关联ID',
  `name`        VARCHAR(50) NOT NULL DEFAULT '' COMMENT '评论者昵称',
  `email`       VARCHAR(100) DEFAULT NULL COMMENT '评论者邮箱',
  `content`     TEXT NOT NULL COMMENT '评论内容',
  `status`      TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0隐藏 1显示',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '评论时间',
  PRIMARY KEY (`id`),
  KEY `idx_target` (`target_type`, `target_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评论';

-- -----------------------------------------------------------
-- 站点设置（固定列模式）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id`                 INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title`              VARCHAR(200) NOT NULL DEFAULT '' COMMENT '站点标题',
  `description`        VARCHAR(500) DEFAULT '' COMMENT '网站描述',
  `account`            VARCHAR(50) NOT NULL DEFAULT '' COMMENT '管理员账号',
  `password`           VARCHAR(255) NOT NULL DEFAULT '' COMMENT '管理员密码',
  `home_ticket`        TEXT COMMENT '首页标语',
  `photography_ticket` TEXT COMMENT '摄影页标语',
  `standpoint_ticket`  TEXT COMMENT '观点页标语',
  `comment_ticket`     TEXT COMMENT '评论页标语',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='站点设置';
