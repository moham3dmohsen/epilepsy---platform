CREATE TABLE IF NOT EXISTS platform_settings (
  setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO platform_settings (setting_key, setting_value) VALUES
('notif_new_user', '1'),
('notif_new_report', '1'),
('notif_critical', '1'),
('notif_contact', '1'),
('notif_system', '0'),
('allow_registration', '1'),
('maintenance_mode', '0'),
('content_review', '0'),
('admin_2fa', '0'),
('session_timeout', '60'),
('maintenance_message', 'المنصة قيد الصيانة حالياً. نعود قريباً.');

ALTER TABLE articles ADD COLUMN IF NOT EXISTS status ENUM('draft','published') NOT NULL DEFAULT 'published' AFTER read_time;
ALTER TABLE videos ADD COLUMN IF NOT EXISTS status ENUM('draft','published') NOT NULL DEFAULT 'published' AFTER category;
