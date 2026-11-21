-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Nov 21, 2026 at 05:51 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cafe_krunal`
--

-- --------------------------------------------------------

--
-- Table structure for table `feature_section`
--

CREATE TABLE `feature_section` (
  `id` int(10) UNSIGNED NOT NULL,
  `eyebrow` varchar(120) NOT NULL DEFAULT '',
  `title_line1` varchar(160) NOT NULL DEFAULT '',
  `title_line2` varchar(160) NOT NULL DEFAULT '',
  `title_line2_color` varchar(20) NOT NULL DEFAULT '#d8b38d',
  `description` text DEFAULT NULL,
  `cta_text` varchar(120) NOT NULL DEFAULT '',
  `cta_link` varchar(255) NOT NULL DEFAULT '#menu',
  `bg_image_path` varchar(255) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feature_section`
--

INSERT INTO `feature_section` (`id`, `eyebrow`, `title_line1`, `title_line2`, `title_line2_color`, `description`, `cta_text`, `cta_link`, `bg_image_path`, `is_active`, `updated_at`) VALUES
(1, 'The art of a good cup.........', 'Good things take', 'a little time.', '#f0a966', 'Carefully selected beans. Thoughtful preparation. A cup made just the way it should be — never rushed, always worth it.', 'Find your favourite', '#menu', 'uploads/feature/feature-1795166382-714e031e.jpg', 1, '2026-11-20 09:19:42');

-- --------------------------------------------------------

--
-- Table structure for table `gallery_images`
--

CREATE TABLE `gallery_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL DEFAULT '',
  `full_image_path` varchar(255) NOT NULL DEFAULT '',
  `alt_text` varchar(255) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery_images`
--

INSERT INTO `gallery_images` (`id`, `image_path`, `full_image_path`, `alt_text`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&w=900&q=85', 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&w=1400&q=90', 'Warm and inviting café interior', 1, 1, '2026-11-20 06:44:49', '2026-11-20 06:44:49'),
(2, 'https://images.unsplash.com/photo-1493857671505-72967e2e2760?auto=format&fit=crop&w=700&q=85', 'https://images.unsplash.com/photo-1493857671505-72967e2e2760?auto=format&fit=crop&w=1400&q=90', 'Coffee and café table', 1, 2, '2026-11-20 06:44:49', '2026-11-20 06:44:49'),
(3, 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=700&q=85', 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=1400&q=90', 'Café seating and atmosphere', 1, 3, '2026-11-20 06:44:49', '2026-11-20 06:44:49'),
(4, 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1100&q=85', 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1600&q=90', 'Barista preparing fresh coffee', 1, 4, '2026-11-20 06:44:49', '2026-11-20 06:44:49');

-- --------------------------------------------------------

--
-- Table structure for table `gallery_section`
--

CREATE TABLE `gallery_section` (
  `id` int(10) UNSIGNED NOT NULL,
  `eyebrow` varchar(120) NOT NULL DEFAULT '',
  `title_line1` varchar(160) NOT NULL DEFAULT '',
  `title_line2` varchar(160) NOT NULL DEFAULT '',
  `section_copy` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery_section`
--

INSERT INTO `gallery_section` (`id`, `eyebrow`, `title_line1`, `title_line2`, `section_copy`, `is_active`, `updated_at`) VALUES
(1, 'A little look inside', 'Come for the coffee.', 'Stay for the feeling.', 'A cosy corner, your favourite people, the smell of freshly brewed coffee. There\'s always room for one more moment.', 1, '2026-11-20 06:48:31');

-- --------------------------------------------------------

--
-- Table structure for table `hero_section`
--

CREATE TABLE `hero_section` (
  `id` int(10) UNSIGNED NOT NULL,
  `title_line1` varchar(160) NOT NULL DEFAULT 'Good coffee.',
  `title_line2` varchar(160) NOT NULL DEFAULT 'Better moments.',
  `subtitle` text NOT NULL,
  `image_path` varchar(500) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hero_section`
--

INSERT INTO `hero_section` (`id`, `title_line1`, `title_line2`, `subtitle`, `image_path`, `is_active`, `updated_at`) VALUES
(1, 'Good coffee. Good Mood', 'Better moments..', 'A little pause in your everyday. Enjoy freshly brewed coffee and beautiful moments at Café Krunal..', 'uploads/hero/hero-1795160345-37d033de.jpg', 1, '2026-11-20 13:11:52');

-- --------------------------------------------------------

--
-- Table structure for table `marquee_section`
--

CREATE TABLE `marquee_section` (
  `id` int(10) UNSIGNED NOT NULL,
  `items` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `marquee_section`
--

INSERT INTO `marquee_section` (`id`, `items`, `is_active`, `updated_at`) VALUES
(1, 'Freshly brewed|Thoughtfully made|Always welcoming|Stay for another cup|krunal mandaviya|visual vibe', 1, '2026-11-20 13:17:40');

-- --------------------------------------------------------

--
-- Table structure for table `menu_categories`
--

CREATE TABLE `menu_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(60) NOT NULL,
  `label` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_categories`
--

INSERT INTO `menu_categories` (`id`, `slug`, `label`, `sort_order`, `created_at`) VALUES
(1, 'coffee', 'Coffee', 1, '2026-11-20 06:36:35'),
(2, 'food', 'Food', 2, '2026-11-20 06:36:35'),
(3, 'sweet', 'Something sweet', 3, '2026-11-20 06:36:35');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `image_path` varchar(255) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `category_id`, `name`, `description`, `price`, `image_path`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 3, 'coffee ice', 'Double espresso, chilled milk, a hint of vanilla.', 9999.82, 'uploads/menu/menu-1795161123-c63f5827.jpg', 1, 1, '2026-11-20 06:36:35', '2026-11-20 07:52:37'),
(2, 1, 'Classic Cappuccino', 'Rich espresso with silky steamed milk foam.', 180.00, 'https://images.unsplash.com/photo-1570968915860-54d5c301fa9f?auto=format&fit=crop&w=350&q=80', 1, 2, '2026-11-20 06:36:35', '2026-11-20 06:36:35'),
(3, 1, 'Slow Pour', 'Single-origin coffee, brewed one cup at a time.', 240.00, 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&w=350&q=80', 1, 3, '2026-11-20 06:36:35', '2026-11-20 06:36:35'),
(4, 2, 'Garden Toast', 'Sourdough, smashed avocado, herbs and chilli.', 290.00, 'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=350&q=80', 1, 4, '2026-11-20 06:36:35', '2026-11-20 06:36:35'),
(5, 2, 'Roasted Veg Sandwich', 'Seasonal vegetables, house spread, toasted bread.', 260.00, 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=350&q=80', 1, 5, '2026-11-20 06:36:35', '2026-11-20 06:36:35'),
(6, 3, 'Chocolate Ganache Cake', 'Soft chocolate sponge with a glossy ganache.', 210.00, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=350&q=80', 1, 6, '2026-11-20 06:36:35', '2026-11-20 06:36:35'),
(7, 3, 'Butter Croissant', 'Golden, flaky and baked fresh every morning.', 160.00, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=350&q=80', 1, 7, '2026-11-20 06:36:35', '2026-11-20 06:36:35'),
(8, 3, 'Berry Cream Bowl', 'Seasonal berries, cream and a little crunch.', 230.00, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=350&q=80', 1, 8, '2026-11-20 06:36:35', '2026-11-20 06:36:35');

-- --------------------------------------------------------

--
-- Table structure for table `menu_section`
--

CREATE TABLE `menu_section` (
  `id` int(10) UNSIGNED NOT NULL,
  `eyebrow` varchar(120) NOT NULL DEFAULT '',
  `title_line1` varchar(160) NOT NULL DEFAULT '',
  `title_line2` varchar(160) NOT NULL DEFAULT '',
  `section_copy` text DEFAULT NULL,
  `footnote` text DEFAULT NULL,
  `cta_text` varchar(120) NOT NULL DEFAULT '',
  `cta_link` varchar(255) NOT NULL DEFAULT '#visit',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_section`
--

INSERT INTO `menu_section` (`id`, `eyebrow`, `title_line1`, `title_line2`, `section_copy`, `footnote`, `cta_text`, `cta_link`, `is_active`, `updated_at`) VALUES
(1, 'cofee', 'A menu worth', 'slowing down for.', 'From your everyday favourites to something new to fall in love with. Explore a few of our café classics.', 'A sample menu for website preview. Update dishes, ingredients and prices to match your café.', 'Ask about today\'s specials', '#visit', 1, '2026-11-20 07:51:12');

-- --------------------------------------------------------

--
-- Table structure for table `quote_section`
--

CREATE TABLE `quote_section` (
  `id` int(10) UNSIGNED NOT NULL,
  `quote_text` text NOT NULL DEFAULT '',
  `author` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quote_section`
--

INSERT INTO `quote_section` (`id`, `quote_text`, `author`, `is_active`, `updated_at`) VALUES
(1, 'Sometimes all you need is a good cup of coffee and a place that feels like yours.', 'Your table is waiting at Café Krunal', 1, '2026-11-20 06:58:45');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(160) DEFAULT NULL,
  `res_date` date NOT NULL,
  `res_time` varchar(20) NOT NULL,
  `guests` tinyint(3) UNSIGNED NOT NULL,
  `message` text DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `name`, `phone`, `email`, `res_date`, `res_time`, `guests`, `message`, `user_id`, `status`, `ip_address`, `created_at`, `updated_at`) VALUES
(3, 'MANDAVIYA KRUNAL', '6353074507', 'krunalmandaviya111@gmail.com', '2026-11-20', '12:00 PM', 2, 'qwertyuiopasdfghjklzxcvbnm', 1, 'pending', '::1', '2026-11-20 09:21:20', '2026-11-20 09:21:20');

-- --------------------------------------------------------

--
-- Table structure for table `story_section`
--

CREATE TABLE `story_section` (
  `id` int(11) NOT NULL,
  `eyebrow` varchar(150) NOT NULL,
  `title_line1` varchar(150) NOT NULL,
  `title_line2` varchar(150) NOT NULL,
  `paragraph1` text NOT NULL,
  `paragraph2` text NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `stamp_text` varchar(200) NOT NULL,
  `button_text` varchar(100) NOT NULL,
  `button_link` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `story_section`
--

INSERT INTO `story_section` (`id`, `eyebrow`, `title_line1`, `title_line2`, `paragraph1`, `paragraph2`, `image_path`, `stamp_text`, `button_text`, `button_link`, `is_active`, `updated_at`) VALUES
(1, 'krunal  mandaviya', 'Not just a café.', 'A feeling.', 'We believe the best moments are often the simplest: the first sip of a really good coffee, a favourite pastry shared, or a familiar face across the table. Café Krunal brings those little moments together in one warm, welcoming space.', 'Thoughtfully sourced beans, fresh ingredients and genuine hospitality are at the heart of everything we do.', 'uploads/story/story-1795160701-f3b70006.jpg', 'Made with care & good coffee', 'Come say hello', '#visit', 1, '2026-11-20 07:45:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `created_at`, `updated_at`) VALUES
(1, 'MANDAVIYA KRUNAL', 'krunalmandaviya111@gmail.com', '6353074507', '$2y$10$0Mka1.sd1hi9q5drO//VBOLqyLOIKdyawtloeDnxDwH9ysFZ5tpVq', '2026-11-20 10:22:21', '2026-11-20 10:22:21');

-- --------------------------------------------------------

--
-- Table structure for table `values_cards`
--

CREATE TABLE `values_cards` (
  `id` int(10) UNSIGNED NOT NULL,
  `icon_class` varchar(80) NOT NULL DEFAULT 'bi-cup-hot',
  `title` varchar(160) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `values_cards`
--

INSERT INTO `values_cards` (`id`, `icon_class`, `title`, `description`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'bi-cup-hot', 'Thoughtful coffee', 'Good beans, careful brewing and a little love in every cup.', 1, 1, '2026-11-20 06:49:36', '2026-11-20 06:49:36'),
(2, 'bi-flower1', 'Fresh by nature', 'Freshly prepared plates and ingredients chosen with care.', 1, 2, '2026-11-20 06:49:36', '2026-11-20 06:49:36'),
(3, 'bi-heart', 'Warm hospitality', 'A welcoming space where everyone has a seat at the table.', 1, 3, '2026-11-20 06:49:36', '2026-11-20 06:49:36');

-- --------------------------------------------------------

--
-- Table structure for table `values_section`
--

CREATE TABLE `values_section` (
  `id` int(10) UNSIGNED NOT NULL,
  `eyebrow` varchar(120) NOT NULL DEFAULT '',
  `title` varchar(160) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `values_section`
--

INSERT INTO `values_section` (`id`, `eyebrow`, `title`, `is_active`, `updated_at`) VALUES
(1, 'The little things matter', 'Made with intention.', 1, '2026-11-20 06:53:11');

-- --------------------------------------------------------

--
-- Table structure for table `visit_form_options`
--

CREATE TABLE `visit_form_options` (
  `id` int(10) UNSIGNED NOT NULL,
  `field` enum('time','guests') NOT NULL,
  `label` varchar(60) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visit_section`
--

CREATE TABLE `visit_section` (
  `id` int(10) UNSIGNED NOT NULL,
  `eyebrow` varchar(120) NOT NULL,
  `title_line1` varchar(160) NOT NULL,
  `title_line2` varchar(160) NOT NULL,
  `section_copy` text DEFAULT NULL,
  `findus_heading` varchar(120) NOT NULL DEFAULT 'Find us',
  `findus_line1` varchar(255) NOT NULL,
  `findus_line2` varchar(255) NOT NULL,
  `hours_heading` varchar(120) NOT NULL DEFAULT 'Opening hours',
  `hours_days` varchar(120) NOT NULL,
  `hours_time` varchar(120) NOT NULL,
  `hours_note` varchar(120) NOT NULL DEFAULT '',
  `contact_heading` varchar(120) NOT NULL DEFAULT 'Say hello',
  `contact_phone` varchar(60) NOT NULL,
  `contact_email` varchar(160) NOT NULL,
  `form_title` varchar(160) NOT NULL,
  `form_note` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visit_section`
--

INSERT INTO `visit_section` (`id`, `eyebrow`, `title_line1`, `title_line2`, `section_copy`, `findus_heading`, `findus_line1`, `findus_line2`, `hours_heading`, `hours_days`, `hours_time`, `hours_note`, `contact_heading`, `contact_phone`, `contact_email`, `form_title`, `form_note`, `is_active`, `updated_at`) VALUES
(1, 'Your next favourite corner', 'Let\'s make', 'some time.', 'Drop by for your daily coffee, bring a friend for brunch, or make a reservation for a slow afternoon.', 'Find us', 'Your café address goes here', 'Surat, Gujarat, India', 'Opening hours', 'Monday – Sunday', '9:00 AM – 10:00 PM', '(sample timings)', 'Say hello', '+91 00000 00000', 'hello@cafekrunal.com', 'Save yourself a seat.', 'Demo form: connect WhatsApp, email or a booking backend to receive real reservations.', 1, '2026-11-20 09:06:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `feature_section`
--
ALTER TABLE `feature_section`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gallery_images_active` (`is_active`);

--
-- Indexes for table `gallery_section`
--
ALTER TABLE `gallery_section`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hero_section`
--
ALTER TABLE `hero_section`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marquee_section`
--
ALTER TABLE `marquee_section`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu_categories`
--
ALTER TABLE `menu_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_menu_items_category` (`category_id`),
  ADD KEY `idx_menu_items_active` (`is_active`);

--
-- Indexes for table `menu_section`
--
ALTER TABLE `menu_section`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quote_section`
--
ALTER TABLE `quote_section`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`res_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `story_section`
--
ALTER TABLE `story_section`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- Indexes for table `values_cards`
--
ALTER TABLE `values_cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_values_cards_active` (`is_active`);

--
-- Indexes for table `values_section`
--
ALTER TABLE `values_section`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `visit_form_options`
--
ALTER TABLE `visit_form_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_field` (`field`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `visit_section`
--
ALTER TABLE `visit_section`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `feature_section`
--
ALTER TABLE `feature_section`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `gallery_images`
--
ALTER TABLE `gallery_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gallery_section`
--
ALTER TABLE `gallery_section`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hero_section`
--
ALTER TABLE `hero_section`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `marquee_section`
--
ALTER TABLE `marquee_section`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `menu_categories`
--
ALTER TABLE `menu_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `menu_section`
--
ALTER TABLE `menu_section`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `quote_section`
--
ALTER TABLE `quote_section`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `story_section`
--
ALTER TABLE `story_section`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `values_cards`
--
ALTER TABLE `values_cards`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `values_section`
--
ALTER TABLE `values_section`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `visit_form_options`
--
ALTER TABLE `visit_form_options`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `visit_section`
--
ALTER TABLE `visit_section`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `fk_menu_items_category` FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_res_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
