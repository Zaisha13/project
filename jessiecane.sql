-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 02, 2025 at 11:42 AM
-- Server version: 8.0.44
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jessiecane`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `branch_id` int NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `branch_code` varchar(20) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_name`, `branch_code`, `address`, `phone`, `is_active`, `date_created`) VALUES
(1, 'SM Fairview', 'fairview', 'SM City Fairview, Quirino Highway, Quezon City', '02-1234-5678', 1, '2025-11-24 22:03:51'),
(2, 'SM San Jose del Monte', 'sjdm', 'SM City San Jose del Monte, Bulacan', '044-123-4567', 1, '2025-11-24 22:03:51');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int NOT NULL,
  `user_id` int NOT NULL,
  `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int NOT NULL,
  `cart_id` int NOT NULL,
  `product_id` int NOT NULL,
  `size` enum('small','medium','large') DEFAULT 'medium',
  `quantity` int DEFAULT '1',
  `sugar_level` varchar(20) DEFAULT '100%',
  `ice_level` varchar(20) DEFAULT 'Regular',
  `add_ons` varchar(255) DEFAULT NULL,
  `special_instructions` text,
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cashiers`
--

CREATE TABLE `cashiers` (
  `cashier_id` int NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cashiers`
--

INSERT INTO `cashiers` (`cashier_id`, `email`, `password`, `firstname`, `lastname`, `branch_id`, `is_active`, `date_created`) VALUES
(1, 'cashierfairview@gmail.com', 'cashierfairview', 'Cashier', 'Fairview', 1, 1, '2025-11-24 22:03:51'),
(2, 'cashiersjdm@gmail.com', 'cashiersjdm', 'Cashier', 'SJDM', 2, 1, '2025-11-24 22:03:51');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `inquiry_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('pending','read','replied','closed') DEFAULT 'pending',
  `admin_reply` text,
  `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_replied` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `user_id` int DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_username` varchar(100) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `customer_notes` text,
  `branch` varchar(100) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `amount_received` decimal(10,2) NOT NULL DEFAULT '0.00',
  `change_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `order_type` varchar(20) DEFAULT 'Digital',
  `is_guest` tinyint(1) DEFAULT '1',
  `timestamp` bigint DEFAULT NULL,
  `order_date` varchar(20) DEFAULT NULL,
  `order_time` varchar(20) DEFAULT NULL,
  `payment_method` varchar(20) DEFAULT 'GCash',
  `payment_status` varchar(20) DEFAULT 'Pending',
  `order_status` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_id`, `user_id`, `customer_name`, `customer_username`, `customer_email`, `customer_phone`, `customer_notes`, `branch`, `subtotal`, `tax`, `total`, `amount_received`, `change_amount`, `order_type`, `is_guest`, `timestamp`, `order_date`, `order_time`, `payment_method`, `payment_status`, `order_status`, `created_at`) VALUES
(1, 'ORD-8036', NULL, 'awaw', '', 'N/A', 'N/A', '', 'SM Fairview', 129.00, 0.00, 129.00, 0.00, 0.00, 'Walk-in', 1, 1764598036, '12/01/2025', '3:07 PM', 'gcash', 'Pending', 'Pending', '2025-12-01 14:07:16'),
(2, 'ORD-9526', NULL, 'aw11', '', 'N/A', 'N/A', '', 'SM Fairview', 158.00, 0.00, 158.00, 0.00, 0.00, 'Digital', 1, 1764599526, '12/01/2025', '3:32 PM', 'gcash', 'Pending', 'Pending', '2025-12-01 14:32:06'),
(3, 'ORD-0105', 1, 'maniptest1', '', 'cashierfairview@gmail.com', 'N/A', '', 'SM Fairview', 238.00, 0.00, 238.00, 0.00, 0.00, 'Digital', 0, 1764600105, '12/01/2025', '3:41 PM', 'gcash', 'Pending', 'Pending', '2025-12-01 14:41:45'),
(4, 'ORD-0136', NULL, 'maniptest2', '', 'N/A', 'N/A', '', 'SM Fairview', 238.00, 0.00, 238.00, 0.00, 0.00, 'Digital', 1, 1764600136, '12/01/2025', '3:42 PM', 'gcash', 'Pending', 'Approved', '2025-12-01 14:42:16'),
(5, 'ORD-0285', 2, 'maniptestotherbranch', '', 'cashiersjdm@gmail.com', 'N/A', '', 'SM San Jose del Monte', 218.00, 0.00, 218.00, 0.00, 0.00, 'Digital', 0, 1764600285, '12/01/2025', '3:44 PM', 'gcash', 'Pending', 'Cancelled', '2025-12-01 14:44:45'),
(6, 'ORD-0515', NULL, 'changetest1', '', 'N/A', 'N/A', '', 'SM San Jose del Monte', 109.00, 0.00, 109.00, 0.00, 0.00, 'Walk-in', 1, 1764600515, '12/01/2025', '3:48 PM', 'cash', 'Paid', 'Pending', '2025-12-01 14:48:35'),
(7, 'ORD-1138', NULL, 'changetsst11', '', 'N/A', 'N/A', '', 'SM San Jose del Monte', 139.00, 0.00, 139.00, 150.00, 11.00, 'Walk-in', 1, 1764601138, '12/01/2025', '3:58 PM', 'cash', 'Paid', 'Pending', '2025-12-01 14:58:58'),
(8, 'ORD-1275', NULL, 'changetst2', '', 'N/A', 'N/A', '', 'SM San Jose del Monte', 79.00, 0.00, 79.00, 100.00, 21.00, 'Walk-in', 1, 1764601275, '12/01/2025', '4:01 PM', 'cash', 'Paid', 'Pending', '2025-12-01 15:01:15'),
(9, 'ORD-2717', NULL, 'testest111', '', 'N/A', 'N/A', '', 'SM Fairview', 89.00, 0.00, 89.00, 0.00, 0.00, 'Digital', 1, 1764602717, '12/01/2025', '4:25 PM', 'gcash', 'Pending', 'Pending', '2025-12-01 15:25:17');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `size` varchar(20) DEFAULT 'Regular',
  `special` varchar(100) DEFAULT NULL,
  `notes` text,
  `quantity` int NOT NULL DEFAULT '1',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_name`, `size`, `special`, `notes`, `quantity`, `price`, `created_at`) VALUES
(1, 1, 'Pure Sugarcane', 'Tall', 'No Ice', '', 1, 129.00, '2025-12-01 14:07:16'),
(2, 2, 'Pure Sugarcane', 'Regular', 'None', '', 2, 79.00, '2025-12-01 14:32:06'),
(3, 3, 'Strawberry Yogurt Cane', 'Regular', 'None', '', 2, 119.00, '2025-12-01 14:41:45'),
(4, 4, 'Dragon Fruit Cane', 'Regular', 'None', '', 2, 119.00, '2025-12-01 14:42:16'),
(5, 5, 'Passion Fruit Cane', 'Regular', 'None', '', 2, 109.00, '2025-12-01 14:44:45'),
(6, 6, 'Calamansi Cane', 'Regular', 'No Ice', '', 1, 109.00, '2025-12-01 14:48:35'),
(7, 7, 'Orange Cane', 'Tall', 'None', '', 1, 139.00, '2025-12-01 14:58:58'),
(8, 8, 'Pure Sugarcane', 'Regular', 'None', '', 1, 79.00, '2025-12-01 15:01:15'),
(9, 9, 'Lemon Cane', 'Regular', 'None', '', 1, 89.00, '2025-12-01 15:25:17');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT 'Sugarcane Drinks',
  `description` text,
  `price_regular` decimal(10,2) NOT NULL,
  `price_tall` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `category`, `description`, `price_regular`, `price_tall`, `image_url`, `is_available`, `date_created`) VALUES
(1, 'Pure Sugarcane', 'Sugarcane Drinks', 'Freshly pressed sugarcane juice in its purest form — naturally sweet, refreshing, and energizing.', 79.00, 109.00, 'pure-sugarcane.png', 1, '2025-12-01 13:45:20'),
(2, 'Calamansi Cane', 'Sugarcane Drinks', 'Zesty calamansi blended with sweet sugarcane juice.', 89.00, 119.00, 'calamansi-cane.png', 1, '2025-12-01 13:45:20'),
(3, 'Lemon Cane', 'Sugarcane Drinks', 'Fresh lemon combined with pure sugarcane juice.', 89.00, 119.00, 'lemon-cane.png', 1, '2025-12-01 13:45:20'),
(4, 'Yakult Cane', 'Sugarcane Drinks', 'Sugarcane juice mixed with creamy Yakult.', 89.00, 119.00, 'yakult-cane.png', 1, '2025-12-01 13:45:20'),
(5, 'Calamansi Yakult Cane', 'Sugarcane Drinks', 'Calamansi, Yakult, and sugarcane in one refreshing drink.', 99.00, 129.00, 'calamansi-yakult-cane.png', 1, '2025-12-01 13:45:20'),
(6, 'Lemon Yakult Cane', 'Sugarcane Drinks', 'Lemon, Yakult, and sugarcane for a tangy-creamy mix.', 99.00, 129.00, 'lemon-yakult-cane.png', 1, '2025-12-01 13:45:20'),
(7, 'Lychee Cane', 'Sugarcane Drinks', 'Exotic lychee blended with sugarcane juice.', 99.00, 129.00, 'lychee-cane.png', 1, '2025-12-01 13:45:20'),
(8, 'Orange Cane', 'Sugarcane Drinks', 'Fresh orange juice with sweet sugarcane.', 109.00, 139.00, 'orange-cane.png', 1, '2025-12-01 13:45:20'),
(9, 'Passion Fruit Cane', 'Sugarcane Drinks', 'Tropical passion fruit with sugarcane juice.', 109.00, 139.00, 'passion-fruit-cane.png', 1, '2025-12-01 13:45:20'),
(10, 'Watermelon Cane', 'Sugarcane Drinks', 'Hydrating watermelon blended with sugarcane.', 109.00, 139.00, 'watermelon-cane.png', 1, '2025-12-01 13:45:20'),
(11, 'Dragon Fruit Cane', 'Sugarcane Drinks', 'Vibrant dragon fruit and sugarcane juice.', 119.00, 149.00, 'dragon-fruit-cane.png', 1, '2025-12-01 13:45:20'),
(12, 'Strawberry Yogurt Cane', 'Sugarcane Drinks', 'Strawberry yogurt with sweet sugarcane.', 119.00, 149.00, 'strawberry-yogurt-cane.png', 1, '2025-12-01 13:45:20'),
(13, 'Brown Sugar Boba', 'Special Drinks', 'Brown sugar pearls with fresh milk and sugarcane.', 99.00, 119.00, NULL, 1, '2025-12-01 13:45:20'),
(14, 'Cheese Foam Milk Tea', 'Special Drinks', 'Milk tea topped with creamy cheese foam.', 109.00, 129.00, NULL, 1, '2025-12-01 13:45:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('customer','admin','cashier') DEFAULT 'customer',
  `is_active` tinyint(1) DEFAULT '1',
  `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `firstname`, `lastname`, `username`, `email`, `phone`, `address`, `password`, `role`, `is_active`, `date_created`) VALUES
(1, '', '', '', '', '', '', '$2y$10$79IVjjuTIHoO9icsaoAueOMAmsAkzDGxZSwPiLJDR92B.AbpyQHjS', 'customer', 1, '2025-11-03 15:28:52'),
(2, 'Felipe', 'Condez', 'felixshin', 'felixtaisuki@gmail.com', '09298280214', 'Camarin Street', '$2y$10$XjPyxDn16.CFcqaOf3UWQOGhXr2V3xlvjOjyfPh1f2266tl7e9dIy', 'customer', 1, '2025-11-03 15:29:03'),
(3, 'basty', 'eugenio', 'ytsab', 'baste@gmail.com', '09298280214', 'Camarin Street', '$2y$10$umOefrSP0jLeudhMDQ6Xjeehvee0.hAwsDJ24y3FJm7lEYvqWtH.W', 'customer', 1, '2025-11-03 15:38:51'),
(4, 'Felipe', 'Condez', 'aaa', 'felipecondez123@gmail.com', '09298280214', 'Camarin Street', '$2y$10$J0l4rmyDtUfEuoSE35itdehXQVxi/rYuPVWBPRGqfjeFAdGEFBRFy', 'customer', 1, '2025-12-01 13:40:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`branch_id`),
  ADD UNIQUE KEY `branch_code` (`branch_code`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `fk_cart_user` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `fk_cart_items_cart` (`cart_id`),
  ADD KEY `fk_cart_items_product` (`product_id`);

--
-- Indexes for table `cashiers`
--
ALTER TABLE `cashiers`
  ADD PRIMARY KEY (`cashier_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_cashiers_branch` (`branch_id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`inquiry_id`),
  ADD KEY `fk_inquiries_user` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_order_code` (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_items_order` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `branch_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cashiers`
--
ALTER TABLE `cashiers`
  MODIFY `cashier_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `inquiry_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cashiers`
--
ALTER TABLE `cashiers`
  ADD CONSTRAINT `fk_cashiers_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD CONSTRAINT `fk_inquiries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
