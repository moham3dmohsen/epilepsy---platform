-- تشغيل مرة واحدة لدعم الإيموجي والمرفقات في الرسائل
ALTER TABLE `messages`
  ADD COLUMN `msg_type` ENUM('text','image','file') NOT NULL DEFAULT 'text' AFTER `content`,
  ADD COLUMN `file_path` VARCHAR(500) NULL DEFAULT NULL AFTER `msg_type`,
  ADD COLUMN `file_name` VARCHAR(255) NULL DEFAULT NULL AFTER `file_path`;
