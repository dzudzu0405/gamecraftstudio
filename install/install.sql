-- =====================================================================
-- GameCraft Studio 1.0.0 - schema and content library
-- Generated 24 Aug 2026 08:07
--
-- HOW TO USE THIS ON cPANEL:
--   1. In cPanel > MySQL Databases, create a database and user, grant ALL PRIVILEGES.
--   2. Open phpMyAdmin and select that database.
--   3. Import tab > choose this file > Go.
--   4. Open the site; the installer will only ask for an admin account.
--
-- This file contains NO user accounts and NO projects.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. Table structure
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `plan` VARCHAR(20) NOT NULL DEFAULT 'starter',
  `role` VARCHAR(20) NOT NULL DEFAULT 'creator',
  `avatar_seed` VARCHAR(60) NULL,
  `locale` VARCHAR(10) NOT NULL DEFAULT 'en',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `plan_started_at` DATETIME NULL,
  `last_login_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  UNIQUE (`email`),
  KEY `idx_users_plan` (`plan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `kind` VARCHAR(20) NOT NULL,
  `tier` VARCHAR(20) NOT NULL,
  `code` VARCHAR(60) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `theme` VARCHAR(30) NULL,
  `cells` INT NULL,
  `poses` INT NULL,
  `card_count` INT NULL,
  `art_seed` VARCHAR(80) NOT NULL,
  `image_path` VARCHAR(255) NULL,
  `meta` TEXT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  UNIQUE (`kind`, `code`),
  KEY `idx_library_items_kind_tier` (`kind`, `tier`),
  KEY `idx_library_items_kind_cells` (`kind`, `cells`),
  KEY `idx_library_items_theme` (`theme`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mission_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(60) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `subject` VARCHAR(40) NOT NULL,
  `level` VARCHAR(20) NOT NULL,
  `tier` VARCHAR(20) NOT NULL,
  `sticker` VARCHAR(30) NOT NULL,
  `pattern` TEXT NOT NULL,
  `answer` TEXT NOT NULL,
  `variables` TEXT NULL,
  `hint` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  UNIQUE (`code`),
  KEY `idx_mission_templates_subject_level` (`subject`, `level`),
  KEY `idx_mission_templates_tier` (`tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `difficulty` VARCHAR(20) NOT NULL DEFAULT 'standard',
  `theme` VARCHAR(30) NOT NULL DEFAULT 'forest',
  `subjects` VARCHAR(255) NULL,
  `question_count` INT NOT NULL DEFAULT 90,
  `cells` INT NOT NULL DEFAULT 18,
  `players_min` INT NOT NULL DEFAULT 2,
  `players_max` INT NOT NULL DEFAULT 4,
  `age_min` INT NOT NULL DEFAULT 6,
  `age_max` INT NOT NULL DEFAULT 9,
  `map_item_id` INT NULL,
  `character_item_id` INT NULL,
  `move_item_id` INT NULL,
  `reward_item_id` INT NULL,
  `background_id` INT NULL,
  `cover_seed` VARCHAR(80) NULL,
  `story` TEXT NULL,
  `how_to_play` TEXT NULL,
  `hero_name` VARCHAR(120) NULL,
  `wizard_step` INT NOT NULL DEFAULT 1,
  `settings` TEXT NULL,
  `published_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  KEY `idx_projects_user_id_status` (`user_id`, `status`),
  KEY `idx_projects_user_id_updated_at` (`user_id`, `updated_at`),
  KEY `idx_projects_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_missions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT UNSIGNED NOT NULL,
  `cell_no` INT NOT NULL,
  `slot_no` INT NOT NULL,
  `source` VARCHAR(20) NOT NULL DEFAULT 'library',
  `template_id` INT NULL,
  `subject` VARCHAR(40) NULL,
  `question` TEXT NOT NULL,
  `answer` TEXT NULL,
  `sticker` VARCHAR(30) NOT NULL DEFAULT 'star',
  `created_at` DATETIME NOT NULL,
  KEY `idx_project_missions_project_id_cell_no_slot_no` (`project_id`, `cell_no`, `slot_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_players` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(60) NOT NULL,
  `color` VARCHAR(20) NOT NULL DEFAULT 'red',
  `sort_order` INT NOT NULL DEFAULT 0,
  KEY `idx_project_players_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_assets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `project_id` INT NULL,
  `kind` VARCHAR(20) NOT NULL DEFAULT 'background',
  `original_name` VARCHAR(190) NULL,
  `path` VARCHAR(255) NOT NULL,
  `thumb_path` VARCHAR(255) NULL,
  `mime` VARCHAR(60) NULL,
  `width` INT NULL,
  `height` INT NULL,
  `size_bytes` INT NULL,
  `created_at` DATETIME NOT NULL,
  KEY `idx_user_assets_user_id_kind` (`user_id`, `kind`),
  KEY `idx_user_assets_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exports` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `project_id` INT NULL,
  `format` VARCHAR(20) NOT NULL DEFAULT 'print',
  `status` VARCHAR(20) NOT NULL DEFAULT 'ready',
  `page_count` INT NULL,
  `file_path` VARCHAR(255) NULL,
  `note` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  KEY `idx_exports_user_id_created_at` (`user_id`, `created_at`),
  KEY `idx_exports_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `game_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(60) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `description` VARCHAR(400) NULL,
  `theme` VARCHAR(30) NOT NULL,
  `difficulty` VARCHAR(20) NOT NULL,
  `subjects` VARCHAR(255) NULL,
  `tier` VARCHAR(20) NOT NULL,
  `age_min` INT NOT NULL DEFAULT 6,
  `age_max` INT NOT NULL DEFAULT 9,
  `players_min` INT NOT NULL DEFAULT 2,
  `players_max` INT NOT NULL DEFAULT 4,
  `art_seed` VARCHAR(80) NOT NULL,
  `image_path` VARCHAR(255) NULL,
  `uses_count` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  UNIQUE (`code`),
  KEY `idx_game_templates_theme` (`theme`),
  KEY `idx_game_templates_difficulty` (`difficulty`),
  KEY `idx_game_templates_tier` (`tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `community_posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `project_id` INT NULL,
  `author_name` VARCHAR(120) NOT NULL,
  `title` VARCHAR(180) NOT NULL,
  `caption` VARCHAR(400) NULL,
  `theme` VARCHAR(30) NOT NULL,
  `difficulty` VARCHAR(20) NOT NULL,
  `art_seed` VARCHAR(80) NOT NULL,
  `image_path` VARCHAR(255) NULL,
  `likes` INT NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  KEY `idx_community_posts_is_featured` (`is_featured`),
  KEY `idx_community_posts_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `marketplace_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `seller_name` VARCHAR(120) NOT NULL,
  `title` VARCHAR(180) NOT NULL,
  `description` VARCHAR(400) NULL,
  `kind` VARCHAR(20) NOT NULL DEFAULT 'template',
  `theme` VARCHAR(30) NOT NULL,
  `price_cents` INT NOT NULL DEFAULT 0,
  `art_seed` VARCHAR(80) NOT NULL,
  `image_path` VARCHAR(255) NULL,
  `rating` INT NOT NULL DEFAULT 50,
  `sales` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  KEY `idx_marketplace_items_kind` (`kind`),
  KEY `idx_marketplace_items_theme` (`theme`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `listings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `project_id` INT NULL,
  `channel` VARCHAR(20) NOT NULL DEFAULT 'etsy',
  `title` VARCHAR(200) NOT NULL,
  `bullet_points` TEXT NULL,
  `description` TEXT NULL,
  `tags` VARCHAR(400) NULL,
  `price_cents` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  KEY `idx_listings_user_id` (`user_id`),
  KEY `idx_listings_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Content library data
-- ---------------------------------------------------------------------

-- library_items (116 rows)
DELETE FROM `library_items`;
INSERT INTO `library_items` (`id`, `kind`, `tier`, `code`, `name`, `theme`, `cells`, `poses`, `card_count`, `art_seed`, `image_path`, `meta`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'map', 'starter', 'map-12-01', 'Woodland Trail - 12 spaces', 'forest', 12, NULL, NULL, 'map-12-01', NULL, '{"layout":"serpentine"}', 0, 1, '2026-08-24 08:06:45'),
(2, 'map', 'starter', 'map-12-02', 'Dinosaur Valley - 12 spaces', 'dino', 12, NULL, NULL, 'map-12-02', NULL, '{"layout":"serpentine"}', 1, 1, '2026-08-24 08:06:45'),
(3, 'map', 'starter', 'map-12-03', 'Deep Space Run - 12 spaces', 'space', 12, NULL, NULL, 'map-12-03', NULL, '{"layout":"serpentine"}', 2, 1, '2026-08-24 08:06:45'),
(4, 'map', 'starter', 'map-12-04', 'Coral Reef - 12 spaces', 'ocean', 12, NULL, NULL, 'map-12-04', NULL, '{"layout":"serpentine"}', 3, 1, '2026-08-24 08:06:45'),
(5, 'map', 'starter', 'map-12-05', 'Pirate Waters - 12 spaces', 'pirate', 12, NULL, NULL, 'map-12-05', NULL, '{"layout":"serpentine"}', 4, 1, '2026-08-24 08:06:45'),
(6, 'map', 'starter', 'map-12-06', 'Wizard Realm - 12 spaces', 'magic', 12, NULL, NULL, 'map-12-06', NULL, '{"layout":"serpentine"}', 5, 1, '2026-08-24 08:06:45'),
(7, 'map', 'pro', 'map-12-07', 'Storybook Kingdom - 12 spaces', 'castle', 12, NULL, NULL, 'map-12-07', NULL, '{"layout":"serpentine"}', 6, 1, '2026-08-24 08:06:45'),
(8, 'map', 'pro', 'map-12-08', 'Desert Canyon - 12 spaces', 'desert', 12, NULL, NULL, 'map-12-08', NULL, '{"layout":"serpentine"}', 7, 1, '2026-08-24 08:06:45'),
(9, 'map', 'pro', 'map-12-09', 'Frozen North - 12 spaces', 'arctic', 12, NULL, NULL, 'map-12-09', NULL, '{"layout":"serpentine"}', 8, 1, '2026-08-24 08:06:45'),
(10, 'map', 'publisher', 'map-12-10', 'Candy Land - 12 spaces', 'candy', 12, NULL, NULL, 'map-12-10', NULL, '{"layout":"serpentine"}', 9, 1, '2026-08-24 08:06:45'),
(11, 'map', 'publisher', 'map-12-11', 'Robot City - 12 spaces', 'robot', 12, NULL, NULL, 'map-12-11', NULL, '{"layout":"serpentine"}', 10, 1, '2026-08-24 08:06:45'),
(12, 'map', 'publisher', 'map-12-12', 'Happy Farm - 12 spaces', 'farm', 12, NULL, NULL, 'map-12-12', NULL, '{"layout":"serpentine"}', 11, 1, '2026-08-24 08:06:45'),
(13, 'map', 'starter', 'map-18-01', 'Woodland Trail - 18 spaces', 'forest', 18, NULL, NULL, 'map-18-01', NULL, '{"layout":"serpentine"}', 0, 1, '2026-08-24 08:06:45'),
(14, 'map', 'starter', 'map-18-02', 'Dinosaur Valley - 18 spaces', 'dino', 18, NULL, NULL, 'map-18-02', NULL, '{"layout":"serpentine"}', 1, 1, '2026-08-24 08:06:45'),
(15, 'map', 'starter', 'map-18-03', 'Deep Space Run - 18 spaces', 'space', 18, NULL, NULL, 'map-18-03', NULL, '{"layout":"serpentine"}', 2, 1, '2026-08-24 08:06:45'),
(16, 'map', 'starter', 'map-18-04', 'Coral Reef - 18 spaces', 'ocean', 18, NULL, NULL, 'map-18-04', NULL, '{"layout":"serpentine"}', 3, 1, '2026-08-24 08:06:45'),
(17, 'map', 'starter', 'map-18-05', 'Pirate Waters - 18 spaces', 'pirate', 18, NULL, NULL, 'map-18-05', NULL, '{"layout":"serpentine"}', 4, 1, '2026-08-24 08:06:45'),
(18, 'map', 'starter', 'map-18-06', 'Wizard Realm - 18 spaces', 'magic', 18, NULL, NULL, 'map-18-06', NULL, '{"layout":"serpentine"}', 5, 1, '2026-08-24 08:06:45'),
(19, 'map', 'pro', 'map-18-07', 'Storybook Kingdom - 18 spaces', 'castle', 18, NULL, NULL, 'map-18-07', NULL, '{"layout":"serpentine"}', 6, 1, '2026-08-24 08:06:45'),
(20, 'map', 'pro', 'map-18-08', 'Desert Canyon - 18 spaces', 'desert', 18, NULL, NULL, 'map-18-08', NULL, '{"layout":"serpentine"}', 7, 1, '2026-08-24 08:06:45'),
(21, 'map', 'pro', 'map-18-09', 'Frozen North - 18 spaces', 'arctic', 18, NULL, NULL, 'map-18-09', NULL, '{"layout":"serpentine"}', 8, 1, '2026-08-24 08:06:45'),
(22, 'map', 'publisher', 'map-18-10', 'Candy Land - 18 spaces', 'candy', 18, NULL, NULL, 'map-18-10', NULL, '{"layout":"serpentine"}', 9, 1, '2026-08-24 08:06:45'),
(23, 'map', 'publisher', 'map-18-11', 'Robot City - 18 spaces', 'robot', 18, NULL, NULL, 'map-18-11', NULL, '{"layout":"serpentine"}', 10, 1, '2026-08-24 08:06:45'),
(24, 'map', 'publisher', 'map-18-12', 'Happy Farm - 18 spaces', 'farm', 18, NULL, NULL, 'map-18-12', NULL, '{"layout":"serpentine"}', 11, 1, '2026-08-24 08:06:45'),
(25, 'map', 'pro', 'map-24-01', 'Woodland Trail - 24 spaces', 'forest', 24, NULL, NULL, 'map-24-01', NULL, '{"layout":"serpentine"}', 0, 1, '2026-08-24 08:06:45'),
(26, 'map', 'pro', 'map-24-02', 'Dinosaur Valley - 24 spaces', 'dino', 24, NULL, NULL, 'map-24-02', NULL, '{"layout":"serpentine"}', 1, 1, '2026-08-24 08:06:45'),
(27, 'map', 'pro', 'map-24-03', 'Deep Space Run - 24 spaces', 'space', 24, NULL, NULL, 'map-24-03', NULL, '{"layout":"serpentine"}', 2, 1, '2026-08-24 08:06:45'),
(28, 'map', 'pro', 'map-24-04', 'Coral Reef - 24 spaces', 'ocean', 24, NULL, NULL, 'map-24-04', NULL, '{"layout":"serpentine"}', 3, 1, '2026-08-24 08:06:45'),
(29, 'map', 'pro', 'map-24-05', 'Pirate Waters - 24 spaces', 'pirate', 24, NULL, NULL, 'map-24-05', NULL, '{"layout":"serpentine"}', 4, 1, '2026-08-24 08:06:45'),
(30, 'map', 'pro', 'map-24-06', 'Wizard Realm - 24 spaces', 'magic', 24, NULL, NULL, 'map-24-06', NULL, '{"layout":"serpentine"}', 5, 1, '2026-08-24 08:06:45'),
(31, 'map', 'publisher', 'map-24-07', 'Storybook Kingdom - 24 spaces', 'castle', 24, NULL, NULL, 'map-24-07', NULL, '{"layout":"serpentine"}', 6, 1, '2026-08-24 08:06:45'),
(32, 'map', 'publisher', 'map-24-08', 'Desert Canyon - 24 spaces', 'desert', 24, NULL, NULL, 'map-24-08', NULL, '{"layout":"serpentine"}', 7, 1, '2026-08-24 08:06:45'),
(33, 'map', 'publisher', 'map-24-09', 'Frozen North - 24 spaces', 'arctic', 24, NULL, NULL, 'map-24-09', NULL, '{"layout":"serpentine"}', 8, 1, '2026-08-24 08:06:45'),
(34, 'map', 'publisher', 'map-24-10', 'Candy Land - 24 spaces', 'candy', 24, NULL, NULL, 'map-24-10', NULL, '{"layout":"serpentine"}', 9, 1, '2026-08-24 08:06:45'),
(35, 'map', 'publisher', 'map-24-11', 'Robot City - 24 spaces', 'robot', 24, NULL, NULL, 'map-24-11', NULL, '{"layout":"serpentine"}', 10, 1, '2026-08-24 08:06:45'),
(36, 'map', 'publisher', 'map-24-12', 'Happy Farm - 24 spaces', 'farm', 24, NULL, NULL, 'map-24-12', NULL, '{"layout":"serpentine"}', 11, 1, '2026-08-24 08:06:45'),
(37, 'character', 'starter', 'char-01', 'Maya the Explorer', 'forest', NULL, 3, NULL, 'char-01', NULL, '{"poses":3}', 0, 1, '2026-08-24 08:06:45'),
(38, 'character', 'starter', 'char-02', 'Brave Ben', 'dino', NULL, 3, NULL, 'char-02', NULL, '{"poses":3}', 1, 1, '2026-08-24 08:06:45'),
(39, 'character', 'starter', 'char-03', 'Clever Chloe', 'space', NULL, 3, NULL, 'char-03', NULL, '{"poses":3}', 2, 1, '2026-08-24 08:06:45'),
(40, 'character', 'starter', 'char-04', 'Quick Kai', 'ocean', NULL, 3, NULL, 'char-04', NULL, '{"poses":3}', 3, 1, '2026-08-24 08:06:45'),
(41, 'character', 'starter', 'char-05', 'Curious Cora', 'pirate', NULL, 3, NULL, 'char-05', NULL, '{"poses":3}', 4, 1, '2026-08-24 08:06:45'),
(42, 'character', 'starter', 'char-06', 'Cheerful Theo', 'magic', NULL, 3, NULL, 'char-06', NULL, '{"poses":3}', 5, 1, '2026-08-24 08:06:45'),
(43, 'character', 'starter', 'char-07', 'Steady Sana', 'castle', NULL, 3, NULL, 'char-07', NULL, '{"poses":3}', 6, 1, '2026-08-24 08:06:45'),
(44, 'character', 'starter', 'char-08', 'Mighty Milo', 'desert', NULL, 3, NULL, 'char-08', NULL, '{"poses":3}', 7, 1, '2026-08-24 08:06:45'),
(45, 'character', 'starter', 'char-09', 'Bright Bella', 'arctic', NULL, 3, NULL, 'char-09', NULL, '{"poses":3}', 8, 1, '2026-08-24 08:06:45'),
(46, 'character', 'starter', 'char-10', 'Patient Pedro', 'candy', NULL, 3, NULL, 'char-10', NULL, '{"poses":3}', 9, 1, '2026-08-24 08:06:45'),
(47, 'character', 'pro', 'char-11', 'Gentle Gia', 'robot', NULL, 5, NULL, 'char-11', NULL, '{"poses":5}', 10, 1, '2026-08-24 08:06:45'),
(48, 'character', 'pro', 'char-12', 'Playful Pax', 'farm', NULL, 5, NULL, 'char-12', NULL, '{"poses":5}', 11, 1, '2026-08-24 08:06:45'),
(49, 'character', 'pro', 'char-13', 'Creative Cleo', 'forest', NULL, 5, NULL, 'char-13', NULL, '{"poses":5}', 12, 1, '2026-08-24 08:06:45'),
(50, 'character', 'pro', 'char-14', 'Fearless Finn', 'dino', NULL, 5, NULL, 'char-14', NULL, '{"poses":5}', 13, 1, '2026-08-24 08:06:45');
INSERT INTO `library_items` (`id`, `kind`, `tier`, `code`, `name`, `theme`, `cells`, `poses`, `card_count`, `art_seed`, `image_path`, `meta`, `sort_order`, `is_active`, `created_at`) VALUES
(51, 'character', 'pro', 'char-15', 'Graceful Grace', 'space', NULL, 5, NULL, 'char-15', NULL, '{"poses":5}', 14, 1, '2026-08-24 08:06:45'),
(52, 'character', 'pro', 'char-16', 'Solid Sam', 'ocean', NULL, 5, NULL, 'char-16', NULL, '{"poses":5}', 15, 1, '2026-08-24 08:06:45'),
(53, 'character', 'pro', 'char-17', 'Diligent Dara', 'pirate', NULL, 5, NULL, 'char-17', NULL, '{"poses":5}', 16, 1, '2026-08-24 08:06:45'),
(54, 'character', 'pro', 'char-18', 'Noble Nico', 'magic', NULL, 5, NULL, 'char-18', NULL, '{"poses":5}', 17, 1, '2026-08-24 08:06:45'),
(55, 'character', 'pro', 'char-19', 'Thoughtful Thea', 'castle', NULL, 5, NULL, 'char-19', NULL, '{"poses":5}', 18, 1, '2026-08-24 08:06:45'),
(56, 'character', 'pro', 'char-20', 'Free-spirited Fox', 'desert', NULL, 5, NULL, 'char-20', NULL, '{"poses":5}', 19, 1, '2026-08-24 08:06:45'),
(57, 'character', 'publisher', 'char-21', 'Sharp Sky', 'arctic', NULL, 8, NULL, 'char-21', NULL, '{"poses":8}', 20, 1, '2026-08-24 08:06:45'),
(58, 'character', 'publisher', 'char-22', 'Sturdy Stone', 'candy', NULL, 8, NULL, 'char-22', NULL, '{"poses":8}', 21, 1, '2026-08-24 08:06:45'),
(59, 'character', 'publisher', 'char-23', 'Wise Willow', 'robot', NULL, 8, NULL, 'char-23', NULL, '{"poses":8}', 22, 1, '2026-08-24 08:06:45'),
(60, 'character', 'publisher', 'char-24', 'Certain Cedar', 'farm', NULL, 8, NULL, 'char-24', NULL, '{"poses":8}', 23, 1, '2026-08-24 08:06:45'),
(61, 'character', 'publisher', 'char-25', 'Kind Kira', 'forest', NULL, 8, NULL, 'char-25', NULL, '{"poses":8}', 24, 1, '2026-08-24 08:06:45'),
(62, 'character', 'publisher', 'char-26', 'Decisive Dex', 'dino', NULL, 8, NULL, 'char-26', NULL, '{"poses":8}', 25, 1, '2026-08-24 08:06:45'),
(63, 'character', 'publisher', 'char-27', 'Dreamy Delia', 'space', NULL, 8, NULL, 'char-27', NULL, '{"poses":8}', 26, 1, '2026-08-24 08:06:45'),
(64, 'character', 'publisher', 'char-28', 'Roaming Rex', 'ocean', NULL, 8, NULL, 'char-28', NULL, '{"poses":8}', 27, 1, '2026-08-24 08:06:45'),
(65, 'character', 'publisher', 'char-29', 'Precious Pearl', 'pirate', NULL, 8, NULL, 'char-29', NULL, '{"poses":8}', 28, 1, '2026-08-24 08:06:45'),
(66, 'character', 'publisher', 'char-30', 'Talented Tessa', 'magic', NULL, 8, NULL, 'char-30', NULL, '{"poses":8}', 29, 1, '2026-08-24 08:06:45'),
(67, 'move', 'starter', 'move-01', 'Forest move cards', 'forest', NULL, NULL, 8, 'move-01', NULL, '{"cards":8}', 0, 1, '2026-08-24 08:06:45'),
(68, 'move', 'starter', 'move-02', 'Dinosaurs move cards', 'dino', NULL, NULL, 8, 'move-02', NULL, '{"cards":8}', 1, 1, '2026-08-24 08:06:45'),
(69, 'move', 'starter', 'move-03', 'Outer space move cards', 'space', NULL, NULL, 8, 'move-03', NULL, '{"cards":8}', 2, 1, '2026-08-24 08:06:45'),
(70, 'move', 'starter', 'move-04', 'Ocean move cards', 'ocean', NULL, NULL, 8, 'move-04', NULL, '{"cards":8}', 3, 1, '2026-08-24 08:06:45'),
(71, 'move', 'starter', 'move-05', 'Pirates move cards', 'pirate', NULL, NULL, 8, 'move-05', NULL, '{"cards":8}', 4, 1, '2026-08-24 08:06:45'),
(72, 'move', 'pro', 'move-06', 'Magic move cards', 'magic', NULL, NULL, 8, 'move-06', NULL, '{"cards":8}', 5, 1, '2026-08-24 08:06:45'),
(73, 'move', 'pro', 'move-07', 'Castle move cards', 'castle', NULL, NULL, 8, 'move-07', NULL, '{"cards":8}', 6, 1, '2026-08-24 08:06:45'),
(74, 'move', 'pro', 'move-08', 'Desert move cards', 'desert', NULL, NULL, 8, 'move-08', NULL, '{"cards":8}', 7, 1, '2026-08-24 08:06:45'),
(75, 'move', 'pro', 'move-09', 'Arctic move cards', 'arctic', NULL, NULL, 8, 'move-09', NULL, '{"cards":8}', 8, 1, '2026-08-24 08:06:45'),
(76, 'move', 'pro', 'move-10', 'Candy land move cards', 'candy', NULL, NULL, 8, 'move-10', NULL, '{"cards":8}', 9, 1, '2026-08-24 08:06:45'),
(77, 'move', 'publisher', 'move-11', 'Robots move cards', 'robot', NULL, NULL, 8, 'move-11', NULL, '{"cards":8}', 10, 1, '2026-08-24 08:06:45'),
(78, 'move', 'publisher', 'move-12', 'Farm move cards', 'farm', NULL, NULL, 8, 'move-12', NULL, '{"cards":8}', 11, 1, '2026-08-24 08:06:45'),
(79, 'move', 'publisher', 'move-13', 'Forest move cards', 'forest', NULL, NULL, 8, 'move-13', NULL, '{"cards":8}', 12, 1, '2026-08-24 08:06:45'),
(80, 'move', 'publisher', 'move-14', 'Dinosaurs move cards', 'dino', NULL, NULL, 8, 'move-14', NULL, '{"cards":8}', 13, 1, '2026-08-24 08:06:45'),
(81, 'move', 'publisher', 'move-15', 'Outer space move cards', 'space', NULL, NULL, 8, 'move-15', NULL, '{"cards":8}', 14, 1, '2026-08-24 08:06:45'),
(82, 'move', 'publisher', 'move-16', 'Ocean move cards', 'ocean', NULL, NULL, 8, 'move-16', NULL, '{"cards":8}', 15, 1, '2026-08-24 08:06:45'),
(83, 'move', 'publisher', 'move-17', 'Pirates move cards', 'pirate', NULL, NULL, 8, 'move-17', NULL, '{"cards":8}', 16, 1, '2026-08-24 08:06:45'),
(84, 'move', 'publisher', 'move-18', 'Magic move cards', 'magic', NULL, NULL, 8, 'move-18', NULL, '{"cards":8}', 17, 1, '2026-08-24 08:06:45'),
(85, 'move', 'publisher', 'move-19', 'Castle move cards', 'castle', NULL, NULL, 8, 'move-19', NULL, '{"cards":8}', 18, 1, '2026-08-24 08:06:45'),
(86, 'move', 'publisher', 'move-20', 'Desert move cards', 'desert', NULL, NULL, 8, 'move-20', NULL, '{"cards":8}', 19, 1, '2026-08-24 08:06:45'),
(87, 'reward', 'starter', 'reward-01', 'Explorer Badge - Forest', 'forest', NULL, NULL, 1, 'reward-01', NULL, NULL, 0, 1, '2026-08-24 08:06:45'),
(88, 'reward', 'starter', 'reward-02', 'Gold Courage Cup - Dinosaurs', 'dino', NULL, NULL, 1, 'reward-02', NULL, NULL, 1, 1, '2026-08-24 08:06:45'),
(89, 'reward', 'starter', 'reward-03', 'Bright Mind Award - Outer space', 'space', NULL, NULL, 1, 'reward-03', NULL, NULL, 2, 1, '2026-08-24 08:06:45'),
(90, 'reward', 'starter', 'reward-04', 'Persistence Medal - Ocean', 'ocean', NULL, NULL, 1, 'reward-04', NULL, NULL, 3, 1, '2026-08-24 08:06:45'),
(91, 'reward', 'starter', 'reward-05', 'Creativity Star - Pirates', 'pirate', NULL, NULL, 1, 'reward-05', NULL, NULL, 4, 1, '2026-08-24 08:06:45'),
(92, 'reward', 'starter', 'reward-06', 'Golden Key - Magic', 'magic', NULL, NULL, 1, 'reward-06', NULL, NULL, 5, 1, '2026-08-24 08:06:45'),
(93, 'reward', 'starter', 'reward-07', 'Wisdom Gem - Castle', 'castle', NULL, NULL, 1, 'reward-07', NULL, NULL, 6, 1, '2026-08-24 08:06:45'),
(94, 'reward', 'starter', 'reward-08', 'Guardian Shield - Desert', 'desert', NULL, NULL, 1, 'reward-08', NULL, NULL, 7, 1, '2026-08-24 08:06:45'),
(95, 'reward', 'starter', 'reward-09', 'Little Crown - Arctic', 'arctic', NULL, NULL, 1, 'reward-09', NULL, NULL, 8, 1, '2026-08-24 08:06:45'),
(96, 'reward', 'starter', 'reward-10', 'Treasure Map - Candy land', 'candy', NULL, NULL, 1, 'reward-10', NULL, NULL, 9, 1, '2026-08-24 08:06:45'),
(97, 'reward', 'pro', 'reward-11', 'Victory Flag - Robots', 'robot', NULL, NULL, 1, 'reward-11', NULL, NULL, 10, 1, '2026-08-24 08:06:45'),
(98, 'reward', 'pro', 'reward-12', 'Kindness Heart - Farm', 'farm', NULL, NULL, 1, 'reward-12', NULL, NULL, 11, 1, '2026-08-24 08:06:45'),
(99, 'reward', 'pro', 'reward-13', 'Explorer Badge - Forest', 'forest', NULL, NULL, 1, 'reward-13', NULL, NULL, 12, 1, '2026-08-24 08:06:45'),
(100, 'reward', 'pro', 'reward-14', 'Gold Courage Cup - Dinosaurs', 'dino', NULL, NULL, 1, 'reward-14', NULL, NULL, 13, 1, '2026-08-24 08:06:45');
INSERT INTO `library_items` (`id`, `kind`, `tier`, `code`, `name`, `theme`, `cells`, `poses`, `card_count`, `art_seed`, `image_path`, `meta`, `sort_order`, `is_active`, `created_at`) VALUES
(101, 'reward', 'pro', 'reward-15', 'Bright Mind Award - Outer space', 'space', NULL, NULL, 1, 'reward-15', NULL, NULL, 14, 1, '2026-08-24 08:06:45'),
(102, 'reward', 'pro', 'reward-16', 'Persistence Medal - Ocean', 'ocean', NULL, NULL, 1, 'reward-16', NULL, NULL, 15, 1, '2026-08-24 08:06:45'),
(103, 'reward', 'pro', 'reward-17', 'Creativity Star - Pirates', 'pirate', NULL, NULL, 1, 'reward-17', NULL, NULL, 16, 1, '2026-08-24 08:06:45'),
(104, 'reward', 'pro', 'reward-18', 'Golden Key - Magic', 'magic', NULL, NULL, 1, 'reward-18', NULL, NULL, 17, 1, '2026-08-24 08:06:45'),
(105, 'reward', 'pro', 'reward-19', 'Wisdom Gem - Castle', 'castle', NULL, NULL, 1, 'reward-19', NULL, NULL, 18, 1, '2026-08-24 08:06:45'),
(106, 'reward', 'pro', 'reward-20', 'Guardian Shield - Desert', 'desert', NULL, NULL, 1, 'reward-20', NULL, NULL, 19, 1, '2026-08-24 08:06:45'),
(107, 'reward', 'publisher', 'reward-21', 'Little Crown - Arctic', 'arctic', NULL, NULL, 1, 'reward-21', NULL, NULL, 20, 1, '2026-08-24 08:06:45'),
(108, 'reward', 'publisher', 'reward-22', 'Treasure Map - Candy land', 'candy', NULL, NULL, 1, 'reward-22', NULL, NULL, 21, 1, '2026-08-24 08:06:45'),
(109, 'reward', 'publisher', 'reward-23', 'Victory Flag - Robots', 'robot', NULL, NULL, 1, 'reward-23', NULL, NULL, 22, 1, '2026-08-24 08:06:45'),
(110, 'reward', 'publisher', 'reward-24', 'Kindness Heart - Farm', 'farm', NULL, NULL, 1, 'reward-24', NULL, NULL, 23, 1, '2026-08-24 08:06:45'),
(111, 'reward', 'publisher', 'reward-25', 'Explorer Badge - Forest', 'forest', NULL, NULL, 1, 'reward-25', NULL, NULL, 24, 1, '2026-08-24 08:06:45'),
(112, 'reward', 'publisher', 'reward-26', 'Gold Courage Cup - Dinosaurs', 'dino', NULL, NULL, 1, 'reward-26', NULL, NULL, 25, 1, '2026-08-24 08:06:45'),
(113, 'reward', 'publisher', 'reward-27', 'Bright Mind Award - Outer space', 'space', NULL, NULL, 1, 'reward-27', NULL, NULL, 26, 1, '2026-08-24 08:06:45'),
(114, 'reward', 'publisher', 'reward-28', 'Persistence Medal - Ocean', 'ocean', NULL, NULL, 1, 'reward-28', NULL, NULL, 27, 1, '2026-08-24 08:06:45'),
(115, 'reward', 'publisher', 'reward-29', 'Creativity Star - Pirates', 'pirate', NULL, NULL, 1, 'reward-29', NULL, NULL, 28, 1, '2026-08-24 08:06:45'),
(116, 'reward', 'publisher', 'reward-30', 'Golden Key - Magic', 'magic', NULL, NULL, 1, 'reward-30', NULL, NULL, 29, 1, '2026-08-24 08:06:45');

-- mission_templates (15 rows)
DELETE FROM `mission_templates`;
INSERT INTO `mission_templates` (`id`, `code`, `name`, `subject`, `level`, `tier`, `sticker`, `pattern`, `answer`, `variables`, `hint`, `is_active`, `created_at`) VALUES
(1, 'math-add', 'Simple addition', 'math', 'beginner', 'starter', 'star', 'There are {a} rabbits in the meadow, and {b} more hop over to join them. How many rabbits are there now?', '{a+b} rabbits', '{"a":{"min":2,"max":12},"b":{"min":1,"max":8}}', 'Count them all together.', 1, '2026-08-24 08:06:45'),
(2, 'math-sub', 'Simple subtraction', 'math', 'beginner', 'starter', 'leaf', 'There are {a} apples on the tree and {b} of them fall to the ground. How many are still on the tree?', '{a-b} apples', '{"a":{"min":6,"max":20},"b":{"min":1,"max":5}}', 'Take the fallen ones away from the total.', 1, '2026-08-24 08:06:45'),
(3, 'nature-animal', 'Animal sounds', 'nature', 'beginner', 'starter', 'heart', 'What sound does a {animal} make? Do your best impression!', 'Any good impression counts', '{"animal":{"list":["cat","dog","cow","rooster","duck","sheep","frog","pig","horse","goat"]}}', 'If everyone laughs, you win.', 1, '2026-08-24 08:06:45'),
(4, 'life-action', 'Silly challenge', 'life', 'beginner', 'starter', 'sun', 'Try to {action} for {n} seconds. Manage it and you carry on!', 'Complete the action', '{"action":{"list":["hop on one foot","stand on one leg","clap as fast as you can","spin around","laugh out loud","pretend to be a cat"]},"n":{"min":5,"max":15,"step":5}}', 'Everyone else counts out loud.', 1, '2026-08-24 08:06:45'),
(5, 'lit-letter', 'Words that start with', 'literacy', 'beginner', 'starter', 'book', 'Name {n} words that begin with the letter "{letter}".', 'Any {n} valid words', '{"n":{"min":2,"max":4},"letter":{"list":["B","C","H","M","N","T","L","S","D","G"]}}', 'Look around the room for ideas.', 1, '2026-08-24 08:06:45'),
(6, 'math-mul', 'Multiplication', 'math', 'standard', 'pro', 'gem', 'There are {a} baskets and each one holds {b} eggs. How many eggs are there altogether?', '{a*b} eggs', '{"a":{"min":2,"max":9},"b":{"min":2,"max":9}}', 'Multiply the baskets by the eggs in each.', 1, '2026-08-24 08:06:45'),
(7, 'en-word', 'Vocabulary', 'english', 'standard', 'pro', 'bulb', 'What does the word "{word}" mean? Explain it in your own words.', 'A sensible explanation counts', '{"word":{"list":["forest","river","mountain","treasure","bridge","castle","dragon","compass","island","journey","courage","friendship"]}}', 'Think about where you have heard it before.', 1, '2026-08-24 08:06:45'),
(8, 'science-why', 'Why does it happen', 'science', 'standard', 'pro', 'drop', 'Why {phenomenon}?', 'Any reasonable explanation counts', '{"phenomenon":{"list":["does it rain","do rainbows appear after rain","does ice melt when you leave it out","do leaves change colour in autumn","can we see stars at night","do balloons float when filled with helium","are shadows longer in the afternoon"]}}', 'No textbook needed - just say what you think.', 1, '2026-08-24 08:06:45'),
(9, 'logic-seq', 'Number sequence', 'logic', 'standard', 'pro', 'key', 'What number comes next in this sequence: {a}, {a+d}, {a+d+d}, ... ?', '{a+d+d+d}', '{"a":{"min":1,"max":10},"d":{"min":2,"max":6}}', 'Work out how much each number grows by.', 1, '2026-08-24 08:06:45'),
(10, 'lit-opposite', 'Opposites', 'literacy', 'standard', 'pro', 'music', 'What is the opposite of "{word}"?', 'The correct opposite', '{"word":{"list":["tall","fast","hot","bright","happy","big","thick","new","near","heavy","clean","dry"]}}', 'Think of the other end of the scale.', 1, '2026-08-24 08:06:45'),
(11, 'math-div', 'Sharing equally', 'math', 'advanced', 'publisher', 'shield', 'Share {a} biscuits equally between {b} friends. How many does each friend get?', '{a/b} biscuits each', '{"a":{"min":12,"max":48,"step":6},"b":{"min":2,"max":6}}', 'Everyone gets exactly the same.', 1, '2026-08-24 08:06:45'),
(12, 'math-word2', 'Two-step word problem', 'math', 'advanced', 'publisher', 'trophy', 'You have {a} coins. You buy {b} items that cost {c} coins each. How many coins are left?', '{a-b*c} coins', '{"a":{"min":50,"max":100,"step":10},"b":{"min":2,"max":4},"c":{"min":5,"max":12}}', 'Work out the cost first, then subtract.', 1, '2026-08-24 08:06:45'),
(13, 'geo-continent', 'Places on the map', 'geography', 'advanced', 'publisher', 'flag', 'Which continent is "{place}" in?', 'The correct continent', '{"place":{"list":["Egypt","Brazil","Japan","Italy","Kenya","Canada","India","Norway","Peru","Vietnam"]}}', 'Picture a world map in your head.', 1, '2026-08-24 08:06:45'),
(14, 'logic-riddle', 'Days puzzle', 'logic', 'advanced', 'publisher', 'moon', 'If today is day {a} of the week, what day will it be in {b} days?', 'Count {b} days on from day {a}', '{"a":{"min":2,"max":7},"b":{"min":2,"max":9}}', 'Count on your fingers.', 1, '2026-08-24 08:06:45'),
(15, 'life-team', 'Working together', 'life', 'advanced', 'publisher', 'rocket', 'What would you do if {situation}? Tell everyone one way to handle it.', 'Any thoughtful answer counts', '{"situation":{"list":["a friend fell over in the playground","your group could not agree on anything","you spilled water all over the table","you saw someone sitting on their own","you forgot to bring your homework","you found something that belongs to someone else"]}}', 'There is no single right answer - just be kind.', 1, '2026-08-24 08:06:45');

-- game_templates (54 rows)
DELETE FROM `game_templates`;
INSERT INTO `game_templates` (`id`, `code`, `name`, `description`, `theme`, `difficulty`, `subjects`, `tier`, `age_min`, `age_max`, `players_min`, `players_max`, `art_seed`, `image_path`, `uses_count`, `is_active`, `created_at`) VALUES
(1, 'tpl-forest-beginner', 'Secret of the Woods', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'forest', 'beginner', 'math,logic', 'starter', 4, 6, 2, 4, 'tpl-forest-beginner', NULL, 67, 1, '2026-08-24 08:06:45'),
(2, 'tpl-forest-standard', 'Secret of the Woods', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'forest', 'standard', 'literacy,life', 'starter', 6, 9, 2, 4, 'tpl-forest-standard', NULL, 23, 1, '2026-08-24 08:06:45'),
(3, 'tpl-forest-advanced', 'Secret of the Woods', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'forest', 'advanced', 'english,nature', 'pro', 8, 12, 3, 6, 'tpl-forest-advanced', NULL, 22, 1, '2026-08-24 08:06:45'),
(4, 'tpl-dino-beginner', 'Dinosaur Rescue Mission', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'dino', 'beginner', 'science,nature', 'starter', 4, 6, 2, 4, 'tpl-dino-beginner', NULL, 38, 1, '2026-08-24 08:06:45'),
(5, 'tpl-dino-standard', 'Dinosaur Rescue Mission', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'dino', 'standard', 'math,science', 'starter', 6, 9, 2, 4, 'tpl-dino-standard', NULL, 175, 1, '2026-08-24 08:06:45'),
(6, 'tpl-dino-advanced', 'Dinosaur Rescue Mission', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'dino', 'advanced', 'logic,life', 'pro', 8, 12, 3, 6, 'tpl-dino-advanced', NULL, 119, 1, '2026-08-24 08:06:45'),
(7, 'tpl-space-beginner', 'Space Explorer Quest', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'space', 'beginner', 'literacy,english', 'starter', 4, 6, 2, 4, 'tpl-space-beginner', NULL, 120, 1, '2026-08-24 08:06:45'),
(8, 'tpl-space-standard', 'Space Explorer Quest', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'space', 'standard', 'geography,nature', 'starter', 6, 9, 2, 4, 'tpl-space-standard', NULL, 229, 1, '2026-08-24 08:06:45'),
(9, 'tpl-space-advanced', 'Space Explorer Quest', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'space', 'advanced', 'math,logic', 'pro', 8, 12, 3, 6, 'tpl-space-advanced', NULL, 103, 1, '2026-08-24 08:06:45'),
(10, 'tpl-ocean-beginner', 'Treasure Beneath the Waves', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'ocean', 'beginner', 'literacy,life', 'starter', 4, 6, 2, 4, 'tpl-ocean-beginner', NULL, 81, 1, '2026-08-24 08:06:45'),
(11, 'tpl-ocean-standard', 'Treasure Beneath the Waves', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'ocean', 'standard', 'english,nature', 'starter', 6, 9, 2, 4, 'tpl-ocean-standard', NULL, 39, 1, '2026-08-24 08:06:45'),
(12, 'tpl-ocean-advanced', 'Treasure Beneath the Waves', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'ocean', 'advanced', 'science,nature', 'pro', 8, 12, 3, 6, 'tpl-ocean-advanced', NULL, 78, 1, '2026-08-24 08:06:45'),
(13, 'tpl-pirate-beginner', 'Pirate Voyage', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'pirate', 'beginner', 'math,science', 'starter', 4, 6, 2, 4, 'tpl-pirate-beginner', NULL, 205, 1, '2026-08-24 08:06:45'),
(14, 'tpl-pirate-standard', 'Pirate Voyage', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'pirate', 'standard', 'logic,life', 'starter', 6, 9, 2, 4, 'tpl-pirate-standard', NULL, 113, 1, '2026-08-24 08:06:45'),
(15, 'tpl-pirate-advanced', 'Pirate Voyage', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'pirate', 'advanced', 'literacy,english', 'pro', 8, 12, 3, 6, 'tpl-pirate-advanced', NULL, 103, 1, '2026-08-24 08:06:45'),
(16, 'tpl-magic-beginner', 'Magic Academy Challenge', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'magic', 'beginner', 'geography,nature', 'starter', 4, 6, 2, 4, 'tpl-magic-beginner', NULL, 217, 1, '2026-08-24 08:06:45'),
(17, 'tpl-magic-standard', 'Magic Academy Challenge', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'magic', 'standard', 'math,logic', 'starter', 6, 9, 2, 4, 'tpl-magic-standard', NULL, 122, 1, '2026-08-24 08:06:45'),
(18, 'tpl-magic-advanced', 'Magic Academy Challenge', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'magic', 'advanced', 'literacy,life', 'pro', 8, 12, 3, 6, 'tpl-magic-advanced', NULL, 220, 1, '2026-08-24 08:06:45'),
(19, 'tpl-castle-beginner', 'The Golden Bell', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'castle', 'beginner', 'english,nature', 'starter', 4, 6, 2, 4, 'tpl-castle-beginner', NULL, 234, 1, '2026-08-24 08:06:45'),
(20, 'tpl-castle-standard', 'The Golden Bell', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'castle', 'standard', 'science,nature', 'starter', 6, 9, 2, 4, 'tpl-castle-standard', NULL, 44, 1, '2026-08-24 08:06:45'),
(21, 'tpl-castle-advanced', 'The Golden Bell', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'castle', 'advanced', 'math,science', 'pro', 8, 12, 3, 6, 'tpl-castle-advanced', NULL, 18, 1, '2026-08-24 08:06:45'),
(22, 'tpl-desert-beginner', 'The Hidden Oasis', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'desert', 'beginner', 'logic,life', 'starter', 4, 6, 2, 4, 'tpl-desert-beginner', NULL, 88, 1, '2026-08-24 08:06:45'),
(23, 'tpl-desert-standard', 'The Hidden Oasis', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'desert', 'standard', 'literacy,english', 'starter', 6, 9, 2, 4, 'tpl-desert-standard', NULL, 60, 1, '2026-08-24 08:06:45'),
(24, 'tpl-desert-advanced', 'The Hidden Oasis', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'desert', 'advanced', 'geography,nature', 'pro', 8, 12, 3, 6, 'tpl-desert-advanced', NULL, 50, 1, '2026-08-24 08:06:45'),
(25, 'tpl-arctic-beginner', 'Arctic Adventure', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'arctic', 'beginner', 'math,logic', 'starter', 4, 6, 2, 4, 'tpl-arctic-beginner', NULL, 37, 1, '2026-08-24 08:06:45'),
(26, 'tpl-arctic-standard', 'Arctic Adventure', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'arctic', 'standard', 'literacy,life', 'starter', 6, 9, 2, 4, 'tpl-arctic-standard', NULL, 31, 1, '2026-08-24 08:06:45'),
(27, 'tpl-arctic-advanced', 'Arctic Adventure', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'arctic', 'advanced', 'english,nature', 'pro', 8, 12, 3, 6, 'tpl-arctic-advanced', NULL, 162, 1, '2026-08-24 08:06:45'),
(28, 'tpl-candy-beginner', 'Candy Land Quest', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'candy', 'beginner', 'science,nature', 'starter', 4, 6, 2, 4, 'tpl-candy-beginner', NULL, 203, 1, '2026-08-24 08:06:45'),
(29, 'tpl-candy-standard', 'Candy Land Quest', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'candy', 'standard', 'math,science', 'starter', 6, 9, 2, 4, 'tpl-candy-standard', NULL, 112, 1, '2026-08-24 08:06:45'),
(30, 'tpl-candy-advanced', 'Candy Land Quest', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'candy', 'advanced', 'logic,life', 'pro', 8, 12, 3, 6, 'tpl-candy-advanced', NULL, 150, 1, '2026-08-24 08:06:45'),
(31, 'tpl-robot-beginner', 'Robot Factory Rescue', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'robot', 'beginner', 'literacy,english', 'starter', 4, 6, 2, 4, 'tpl-robot-beginner', NULL, 153, 1, '2026-08-24 08:06:45'),
(32, 'tpl-robot-standard', 'Robot Factory Rescue', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'robot', 'standard', 'geography,nature', 'starter', 6, 9, 2, 4, 'tpl-robot-standard', NULL, 12, 1, '2026-08-24 08:06:45'),
(33, 'tpl-robot-advanced', 'Robot Factory Rescue', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'robot', 'advanced', 'math,logic', 'pro', 8, 12, 3, 6, 'tpl-robot-advanced', NULL, 212, 1, '2026-08-24 08:06:45'),
(34, 'tpl-farm-beginner', 'A Day on the Farm', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'farm', 'beginner', 'literacy,life', 'starter', 4, 6, 2, 4, 'tpl-farm-beginner', NULL, 208, 1, '2026-08-24 08:06:45'),
(35, 'tpl-farm-standard', 'A Day on the Farm', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'farm', 'standard', 'english,nature', 'starter', 6, 9, 2, 4, 'tpl-farm-standard', NULL, 56, 1, '2026-08-24 08:06:45'),
(36, 'tpl-farm-advanced', 'A Day on the Farm', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'farm', 'advanced', 'science,nature', 'pro', 8, 12, 3, 6, 'tpl-farm-advanced', NULL, 55, 1, '2026-08-24 08:06:45'),
(37, 'tpl-forest-standard-b', 'Secret of the Woods (extended)', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'forest', 'standard', 'logic,life', 'publisher', 6, 9, 2, 4, 'tpl-forest-standard-b', NULL, 99, 1, '2026-08-24 08:06:45'),
(38, 'tpl-dino-beginner-b', 'Dinosaur Rescue Mission (extended)', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'dino', 'beginner', 'geography,nature', 'publisher', 4, 6, 2, 4, 'tpl-dino-beginner-b', NULL, 10, 1, '2026-08-24 08:06:45'),
(39, 'tpl-dino-advanced-b', 'Dinosaur Rescue Mission (extended)', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'dino', 'advanced', 'literacy,life', 'publisher', 8, 12, 3, 6, 'tpl-dino-advanced-b', NULL, 135, 1, '2026-08-24 08:06:45'),
(40, 'tpl-space-standard-b', 'Space Explorer Quest (extended)', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'space', 'standard', 'science,nature', 'publisher', 6, 9, 2, 4, 'tpl-space-standard-b', NULL, 81, 1, '2026-08-24 08:06:45'),
(41, 'tpl-ocean-beginner-b', 'Treasure Beneath the Waves (extended)', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'ocean', 'beginner', 'logic,life', 'publisher', 4, 6, 2, 4, 'tpl-ocean-beginner-b', NULL, 136, 1, '2026-08-24 08:06:45'),
(42, 'tpl-ocean-advanced-b', 'Treasure Beneath the Waves (extended)', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'ocean', 'advanced', 'geography,nature', 'publisher', 8, 12, 3, 6, 'tpl-ocean-advanced-b', NULL, 204, 1, '2026-08-24 08:06:45'),
(43, 'tpl-pirate-standard-b', 'Pirate Voyage (extended)', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'pirate', 'standard', 'literacy,life', 'publisher', 6, 9, 2, 4, 'tpl-pirate-standard-b', NULL, 184, 1, '2026-08-24 08:06:45'),
(44, 'tpl-magic-beginner-b', 'Magic Academy Challenge (extended)', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'magic', 'beginner', 'science,nature', 'publisher', 4, 6, 2, 4, 'tpl-magic-beginner-b', NULL, 126, 1, '2026-08-24 08:06:45'),
(45, 'tpl-magic-advanced-b', 'Magic Academy Challenge (extended)', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'magic', 'advanced', 'logic,life', 'publisher', 8, 12, 3, 6, 'tpl-magic-advanced-b', NULL, 173, 1, '2026-08-24 08:06:45'),
(46, 'tpl-castle-standard-b', 'The Golden Bell (extended)', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'castle', 'standard', 'geography,nature', 'publisher', 6, 9, 2, 4, 'tpl-castle-standard-b', NULL, 219, 1, '2026-08-24 08:06:45'),
(47, 'tpl-desert-beginner-b', 'The Hidden Oasis (extended)', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'desert', 'beginner', 'literacy,life', 'publisher', 4, 6, 2, 4, 'tpl-desert-beginner-b', NULL, 48, 1, '2026-08-24 08:06:45'),
(48, 'tpl-desert-advanced-b', 'The Hidden Oasis (extended)', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'desert', 'advanced', 'science,nature', 'publisher', 8, 12, 3, 6, 'tpl-desert-advanced-b', NULL, 78, 1, '2026-08-24 08:06:45'),
(49, 'tpl-arctic-standard-b', 'Arctic Adventure (extended)', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'arctic', 'standard', 'logic,life', 'publisher', 6, 9, 2, 4, 'tpl-arctic-standard-b', NULL, 191, 1, '2026-08-24 08:06:45'),
(50, 'tpl-candy-beginner-b', 'Candy Land Quest (extended)', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'candy', 'beginner', 'geography,nature', 'publisher', 4, 6, 2, 4, 'tpl-candy-beginner-b', NULL, 68, 1, '2026-08-24 08:06:45');
INSERT INTO `game_templates` (`id`, `code`, `name`, `description`, `theme`, `difficulty`, `subjects`, `tier`, `age_min`, `age_max`, `players_min`, `players_max`, `art_seed`, `image_path`, `uses_count`, `is_active`, `created_at`) VALUES
(51, 'tpl-candy-advanced-b', 'Candy Land Quest (extended)', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'candy', 'advanced', 'literacy,life', 'publisher', 8, 12, 3, 6, 'tpl-candy-advanced-b', NULL, 174, 1, '2026-08-24 08:06:45'),
(52, 'tpl-robot-standard-b', 'Robot Factory Rescue (extended)', 'A 18-space map with 90 mission cards, suited to ages 6-9.', 'robot', 'standard', 'science,nature', 'publisher', 6, 9, 2, 4, 'tpl-robot-standard-b', NULL, 140, 1, '2026-08-24 08:06:45'),
(53, 'tpl-farm-beginner-b', 'A Day on the Farm (extended)', 'A 12-space map with 60 mission cards, suited to ages 4-6.', 'farm', 'beginner', 'logic,life', 'publisher', 4, 6, 2, 4, 'tpl-farm-beginner-b', NULL, 47, 1, '2026-08-24 08:06:45'),
(54, 'tpl-farm-advanced-b', 'A Day on the Farm (extended)', 'A 24-space map with 120 mission cards, suited to ages 8-12.', 'farm', 'advanced', 'geography,nature', 'publisher', 8, 12, 3, 6, 'tpl-farm-advanced-b', NULL, 220, 1, '2026-08-24 08:06:45');

-- community_posts (12 rows)
DELETE FROM `community_posts`;
INSERT INTO `community_posts` (`id`, `user_id`, `project_id`, `author_name`, `title`, `caption`, `theme`, `difficulty`, `art_seed`, `image_path`, `likes`, `is_featured`, `created_at`) VALUES
(1, NULL, NULL, 'Ms. Lane - Sunflower Preschool', 'Secret of the Woods', 'Made this for my five-year-olds. They loved swapping the mission cards.', 'forest', 'beginner', 'community-0', NULL, 218, 1, '2026-08-24 08:06:46'),
(2, NULL, NULL, 'Michael, parent', 'Space Explorer Quest', 'Printed at A3 and laminated it - a year later it is still going strong.', 'space', 'standard', 'community-1', NULL, 35, 1, '2026-08-22 08:06:46'),
(3, NULL, NULL, 'Hannah, teacher', 'Treasure Beneath the Waves', 'Added extra vocabulary questions for my Year 3 class.', 'ocean', 'standard', 'community-2', NULL, 236, 1, '2026-08-20 08:06:46'),
(4, NULL, NULL, 'Mr. Dean - Primary school', 'Dinosaur Rescue Mission', 'Used it in form time. 24 spaces runs long but they stayed hooked.', 'dino', 'advanced', 'community-3', NULL, 143, 1, '2026-08-18 08:06:46'),
(5, NULL, NULL, 'Ms. May - Learning centre', 'Magic Academy Challenge', 'Rewrote every mission card in Spanish for my group.', 'magic', 'standard', 'community-4', NULL, 59, 1, '2026-08-16 08:06:46'),
(6, NULL, NULL, 'Tara, parent', 'A Day on the Farm', 'Even my four-year-old can play if an adult reads the questions.', 'farm', 'beginner', 'community-5', NULL, 268, 1, '2026-08-14 08:06:46'),
(7, NULL, NULL, 'Nathan - print shop', 'Pirate Voyage', 'Selling well on Etsy. Customers love the winner card.', 'pirate', 'advanced', 'community-6', NULL, 168, 0, '2026-08-12 08:06:46'),
(8, NULL, NULL, 'Ms. Yen - Preschool', 'Candy Land Quest', 'The colours grab them instantly - they all want the hero card.', 'candy', 'beginner', 'community-7', NULL, 250, 0, '2026-08-10 08:06:46'),
(9, NULL, NULL, 'Mr. Long - Primary school', 'Robot Factory Rescue', 'Added science questions and my Year 5s were completely absorbed.', 'robot', 'advanced', 'community-8', NULL, 115, 0, '2026-08-08 08:06:46'),
(10, NULL, NULL, 'Nicole, parent', 'Arctic Adventure', 'Our weekend family adventure, sorted.', 'arctic', 'standard', 'community-9', NULL, 275, 0, '2026-08-06 08:06:46'),
(11, NULL, NULL, 'Ms. Vann - Teacher', 'The Golden Bell', 'Great for revising reading with Year 1.', 'castle', 'beginner', 'community-10', NULL, 167, 0, '2026-08-04 08:06:46'),
(12, NULL, NULL, 'Ben - Creator', 'The Hidden Oasis', 'The 24-space map is perfect for a group of six.', 'desert', 'advanced', 'community-11', NULL, 316, 0, '2026-08-02 08:06:46');

-- marketplace_items (10 rows)
DELETE FROM `marketplace_items`;
INSERT INTO `marketplace_items` (`id`, `seller_name`, `title`, `description`, `kind`, `theme`, `price_cents`, `art_seed`, `image_path`, `rating`, `sales`, `is_active`, `created_at`) VALUES
(1, 'GameCraft Studio', 'Six rainforest map designs', 'A digital product, downloadable straight after purchase. Works with the Starter, Pro and Publisher plans.', 'asset_pack', 'forest', 900, 'market-0', NULL, 48, 190, 1, '2026-08-24 08:06:46'),
(2, 'GameCraft Studio', 'Space character set - 8 poses', 'A digital product, downloadable straight after purchase. Works with the Starter, Pro and Publisher plans.', 'asset_pack', 'space', 1200, 'market-1', NULL, 47, 472, 1, '2026-08-21 08:06:46'),
(3, 'Blue Ocean Studio', 'Year 3 vocabulary mission card pack', 'A digital product, downloadable straight after purchase. Works with the Starter, Pro and Publisher plans.', 'bundle', 'ocean', 1500, 'market-2', NULL, 49, 165, 1, '2026-08-18 08:06:46'),
(4, 'Lane Handmade', 'Complete "Secret of the Woods" game', 'A digital product, downloadable straight after purchase. Works with the Starter, Pro and Publisher plans.', 'template', 'forest', 700, 'market-3', NULL, 46, 47, 1, '2026-08-15 08:06:46'),
(5, 'GameCraft Studio', 'Gold foil hero card designs', 'A digital product, downloadable straight after purchase. Works with the Starter, Pro and Publisher plans.', 'asset_pack', 'castle', 800, 'market-4', NULL, 50, 119, 1, '2026-08-12 08:06:46'),
(6, 'Rainbow Studio', 'Three 24-space desert maps', 'A digital product, downloadable straight after purchase. Works with the Starter, Pro and Publisher plans.', 'asset_pack', 'desert', 1400, 'market-5', NULL, 44, 340, 1, '2026-08-09 08:06:46'),
(7, 'Nathan Print', 'Pirate game - commercial licence', 'A digital product, downloadable straight after purchase. Works with the Starter, Pro and Publisher plans.', 'template', 'pirate', 1900, 'market-6', NULL, 48, 332, 1, '2026-08-06 08:06:46'),
(8, 'GameCraft Studio', 'Sticker icon set for mission cards', 'A digital product, downloadable straight after purchase. Works with the Starter, Pro and Publisher plans.', 'asset_pack', 'magic', 500, 'market-7', NULL, 47, 151, 1, '2026-08-03 08:06:46'),
(9, 'Blue Ocean Studio', 'Farmyard character pack', 'A digital product, downloadable straight after purchase. Works with the Starter, Pro and Publisher plans.', 'asset_pack', 'farm', 1100, 'market-8', NULL, 45, 397, 1, '2026-07-31 08:06:46'),
(10, 'Rainbow Studio', 'Three preschool games in one bundle', 'A digital product, downloadable straight after purchase. Works with the Starter, Pro and Publisher plans.', 'bundle', 'candy', 2400, 'market-9', NULL, 49, 34, 1, '2026-07-28 08:06:46');

SET FOREIGN_KEY_CHECKS = 1;

-- Done: 207 library rows.
