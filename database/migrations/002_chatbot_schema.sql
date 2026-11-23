CREATE DATABASE IF NOT EXISTS thdh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE thdh;

CREATE TABLE IF NOT EXISTS chatbot_settings (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  bot_name VARCHAR(80) NOT NULL DEFAULT 'THDH Support',
  greeting VARCHAR(500) NOT NULL,
  fallback_message VARCHAR(500) NOT NULL,
  support_path VARCHAR(255) NOT NULL DEFAULT '/contact',
  updated_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chatbot_knowledge (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  stable_key CHAR(64) NOT NULL UNIQUE,
  question VARCHAR(255) NOT NULL,
  answer MEDIUMTEXT NOT NULL,
  category VARCHAR(80) NOT NULL DEFAULT 'general',
  keywords TEXT NULL,
  link_label VARCHAR(100) NULL,
  link_path VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  sort_order INT NOT NULL DEFAULT 0,
  source_type VARCHAR(20) NOT NULL DEFAULT 'manual',
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  INDEX idx_chatbot_knowledge_status_category (status, category),
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
