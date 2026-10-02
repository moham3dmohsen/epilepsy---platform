-- Migration: إضافة حقول التحقق من الكارنيه الطبي
-- شغّل هذا الملف مرة واحدة على قاعدة البيانات الموجودة

ALTER TABLE `users`
  ADD COLUMN `syndicate_card` varchar(255) DEFAULT NULL AFTER `status`,
  ADD COLUMN `doctor_status` enum('pending','approved','rejected') DEFAULT NULL AFTER `syndicate_card`,
  ADD COLUMN `profile_picture` varchar(255) DEFAULT NULL AFTER `doctor_status`;
