-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 20, 2026 at 06:29 AM
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
-- Database: `clothingbrand_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('main_admin','sub_admin') DEFAULT 'sub_admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@almas.com', 'main_admin', '2026-02-12 07:53:52'),
(2, 'Faizan', '$2y$10$sg3BwrqDf2z9Y50RRc3p/euJ0SVZTQo3wWlZCntQGVwmMJ1t5aYmG', 'fazimarketers@gmail.com', 'sub_admin', '2026-02-17 07:48:25'),
(3, 'Ehtisham Hussain', '$2y$10$P35jL7bYxHlYHYXUJqn0TeLkCj489DyYM0y5ushFxFg2kPZF9w0Eq', 'faizanhussain9274@gmail.com', 'sub_admin', '2026-02-17 07:49:06');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`) VALUES
(1, 'Party Wear', '2026-02-12 07:53:52'),
(2, 'Wedding Wear', '2026-02-12 07:53:52'),
(3, 'Formal Wear', '2026-02-12 07:53:52'),
(4, 'Eastren Wear', '2026-02-12 07:53:52');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `product_id`, `user_name`, `email`, `rating`, `message`, `created_at`, `status`) VALUES
(2, 21, 'FAIZAN HUSSAIN', 'faizanhussain9274@gmail.com', 4, 'Great sharwani for barat event', '2026-02-18 14:33:30', 'pending'),
(3, 21, 'Ariyan Shah', 'fazimarketers@gmail.com', 5, 'Fantastic shirwani for  barat event', '2026-02-18 14:34:07', 'pending'),
(4, 20, 'Aslam Shah', 'ehtashamh161@gmail.com', 5, 'best Shirwani for barat event and  very comfortable', '2026-02-18 14:34:47', 'disapproved'),
(5, 20, 'Saim Shah', 'fazimarketers@gmail.com', 4, 'best shirwani for barat', '2026-02-18 14:35:09', 'approved'),
(6, 19, 'Azhar Hussain', 'Azharhussain9274@gmail.com', 5, 'Very comfortable shirwani for suit for walima event', '2026-02-18 14:36:06', 'approved'),
(7, 19, 'Irfan Malik', 'ehtashamh161@gmail.com', 4, 'Satisfy for their quality', '2026-02-18 14:37:06', 'approved'),
(8, 11, 'Sib Tul Hasnain', 'Hasnain194@gmail.com', 4, 'Great Dress for event used as well.', '2026-02-18 16:38:49', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_email` varchar(30) NOT NULL,
  `customer_name` text NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','shipped','delivered') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_email`, `customer_name`, `user_id`, `total_amount`, `status`, `created_at`) VALUES
(5, 'faizanhussain9274@gmail.com', 'Ayesha FATIMA', NULL, 3198.00, 'delivered', '2026-02-18 14:25:39'),
(6, 'faizanhussain1027@gmail.com', 'FAIZAN HUSSAIN', NULL, 4499.00, 'shipped', '2026-02-18 14:29:25'),
(7, 'aslamshah121@gmail.com', 'Aslam Shah', NULL, 7196.00, 'shipped', '2026-02-18 14:30:34'),
(8, 'Saimshah077x@gmail.com', 'Saim Shah', NULL, 5999.00, 'pending', '2026-02-18 14:31:36'),
(9, 'uzair183@gmail.com', 'Malik Uzair', NULL, 1699.00, 'pending', '2026-02-19 07:13:41'),
(10, 'fazimarketers@gmail.com', 'Niyal malik', NULL, 5999.00, 'pending', '2026-02-19 07:16:42');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(5, 5, 12, 2, 1599.00),
(6, 6, 21, 1, 4499.00),
(7, 7, 16, 4, 1799.00),
(8, 8, 19, 1, 5999.00),
(9, 9, 17, 1, 1699.00),
(10, 10, 19, 1, 5999.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `product_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `description`, `category_id`, `price`, `stock`, `product_image`, `created_at`) VALUES
(11, 'Regular Fit Styling Waist Coat', 'Made with high-quality blended fabric, this regular fit waistcoat provides a polished look with a soft hand feel. The balanced grey color placed within the overall design enhances its modern and elegant appearance, making it ideal for formal dressing and festive wear.', 4, 1200.00, 23, '1771349096_eastren_wear-1.png', '2026-02-17 16:21:24'),
(12, 'Aqua Green Formal Shirt', 'Premium 100% Cotton,semi spread collar,2 button adjustable angle cuff and front pocket\r\n100% Cotton\r\nModern Fit\r\nModel Specs: The model height is 6ft 1in and wearing size is 5.5.', 3, 1599.00, 42, '1771349082_Formal-party.png', '2026-02-17 16:58:28'),
(16, 'Slim Fit Formal Chino Pant', 'Our Exclusive Cotton Chino Crafted With A Special Flexible Waist Band Level Up Your Look With Comfort,It Features Premium Cotton Stretch Fabric, Side Pocketrs, Back Welt Pockets, Button And Hook Front Enclosure And Front Crease With Slim Fit Pattern.', 3, 1799.00, 15, '1771349162_Formal_party-1.png', '2026-02-17 17:26:02'),
(17, 'Regular Fit Styling Waist', 'Top your traditional wear with an exquisite waistcoat from Almas Clothing Brand.\r\nDetails: Almas  Man\r\nSeasonality: Summer\r\nOccasion: Eastern Wear', 4, 1699.00, 20, '1771349372_Eastren_wear-2.png', '2026-02-17 17:29:32'),
(18, 'Black Sharlwar Kameez & Black  Waiscoat', 'A classic waistcoat crafted for a sharp, refined look. Perfect for formal and semi-formal occasions.', 1, 4999.00, 49, '1771350070_partyWear-1 (3).png', '2026-02-17 17:41:10'),
(19, 'Imp Navy Suit', 'The Important Suit is crafted for the modern gentleman who values style and confidence. Made from premium fabric with a perfect fit, it offers a timeless and sophisticated look, ideal for business meetings, formal events, or special occasions.', 1, 5999.00, 48, '1771350160_partyWear-1 (2).png', '2026-02-17 17:42:40'),
(20, 'Premium Satin Silk Pristine Embroidered Sherwani', 'Crafted in Pakistan as a part of the Summer 25 Collection, this Sherwani features self-on-self Beige floral and geometric embroidery all over along with Almas Clothing Brand signature embossed buttons in gold.', 2, 4599.00, 25, '1771350611_partyWear-1 (4).png', '2026-02-17 17:50:11'),
(21, 'Premium Jamawar Dark Brown Traditional Sherwani', 'Premium Jamawar Dark Brown Traditional Shirwani for men collection best for wedding wearing article in Almas Clothing Brand.', 2, 4499.00, 32, '1771350750_partyWear-1 (5).png', '2026-02-17 17:52:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
