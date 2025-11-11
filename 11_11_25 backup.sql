-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 11, 2025 at 10:47 AM
-- Server version: 10.5.26-MariaDB
-- PHP Version: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stclogistics_tms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `contact_number_2` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `gst_no` varchar(50) DEFAULT NULL,
  `food_license_no` varchar(100) DEFAULT NULL,
  `trade_license_path` varchar(255) DEFAULT NULL,
  `bank_ac_name` varchar(255) DEFAULT NULL,
  `bank_ac_no` varchar(100) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_ifsc` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `address`, `city`, `state`, `country`, `contact_number`, `contact_number_2`, `email`, `website`, `gst_no`, `food_license_no`, `trade_license_path`, `bank_ac_name`, `bank_ac_no`, `bank_name`, `bank_ifsc`, `is_active`) VALUES
(1, 'Head Office', 'STC Logistics Building, City Center', 'Durgapur', 'West Bengal', 'India', '9876543210', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(2, 'Guwahati', 'Agarwal Complex, Beharbari, Guwahati-781040', 'Guwahati', 'Assam', 'India', '8707024051', '', 'guwahati@stclogistics.in', 'www.stclogistics.in', '19BKAPC0667G1ZF', '', NULL, 'STC LOGISTICS', '735005000008', 'ICICI Bank', 'ICIC0007350', 1),
(3, 'Durgapur', 'Samrat Building, Bhiringee', 'Durgapur', 'West Bengal', 'India', '9932700246', '', 'durgapur@stclogistics.in', 'www.stclogistics.in', '19BKAPC0667G1ZF', '', NULL, NULL, NULL, NULL, NULL, 1),
(4, 'Silchar', 'Ramnagar, SIlchar', 'Silchar', 'Assam', 'India', '8707024051', '', 'silchar@stclogistics.in', 'www.stclogistics.in', '19BKAPC0667G1ZF', '', NULL, NULL, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `brokers`
--

CREATE TABLE `brokers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `gst_no` varchar(50) DEFAULT NULL,
  `pan_no` varchar(20) DEFAULT NULL,
  `aadhaar_no` varchar(20) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `bank_ifsc_code` varchar(20) DEFAULT NULL,
  `pan_doc_path` varchar(255) DEFAULT NULL,
  `gst_doc_path` varchar(255) DEFAULT NULL,
  `bank_doc_path` varchar(255) DEFAULT NULL,
  `aadhaar_doc_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `visibility_type` enum('global','local') NOT NULL DEFAULT 'global',
  `branch_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brokers`
--

INSERT INTO `brokers` (`id`, `name`, `address`, `city`, `state`, `contact_person`, `contact_number`, `gst_no`, `pan_no`, `aadhaar_no`, `bank_account_no`, `bank_ifsc_code`, `pan_doc_path`, `gst_doc_path`, `bank_doc_path`, `aadhaar_doc_path`, `is_active`, `visibility_type`, `branch_id`, `created_at`) VALUES
(1, 'Guddu Khan', 'Beltola', 'Guwahati', 'Assam', 'Guddu', '8638951508', '', '', '', '', '', '', '', '', '', 1, 'global', NULL, '2025-10-04 09:13:56'),
(2, 'Tufazel Khan', 'Garchuk', 'Guwahati', 'Assam', 'Tufazel', '7002962303', '', '', '', '', '', '', '', '', '', 1, 'global', NULL, '2025-10-04 09:15:28'),
(3, 'STC LOGISTICS', 'Beltola', 'Guwahati', 'Assam', 'Shubham Chaubey', '8707024051', '', '', '', '', '', '', '', '', '', 1, 'global', NULL, '2025-10-04 09:16:42'),
(4, 'Golden Transport ', '', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 2, '2025-10-07 05:52:42'),
(5, 'Ariful ', '', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 2, '2025-10-08 13:03:49'),
(6, 'Badal da', '', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 2, '2025-10-08 13:09:06'),
(7, 'Sohid ali', '', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 2, '2025-10-13 12:55:40'),
(8, 'Arvind da', '', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 2, '2025-10-14 11:36:20'),
(9, 'Harida', '', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 2, '2025-10-25 11:55:16'),
(10, 'DASHMESH TRANSPORT CO.', 'DURGAPUR-713213', NULL, NULL, NULL, '9999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-04 12:13:45'),
(11, 'NEW AWSAR TRANSPORT.', 'PCBL MORE,DURGAPUR', NULL, NULL, NULL, '99999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-04 12:32:27'),
(12, 'KANNEDY TRAILER SERVICE.', 'NAIM NAGAR,DURGAPUR-713213', NULL, NULL, NULL, '99999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-04 12:35:00'),
(13, 'SHRI SANWARIYA TRANSPORT CO.', 'AJMER', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-04 12:35:35'),
(14, 'KOLKATA HARYANA ROADWAYS.', 'DANKUNI,KOLKATA', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-04 13:41:15'),
(15, 'SHYAM FREIGHT CARRIER.', 'DURGAPUR', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-04 13:49:16'),
(16, 'ANSHIKA RODWAYAS.VIKASH', 'DURGAPUR-713213', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-04 13:53:05'),
(17, 'NATIONAL TRAILER SERVICE.NAJ', 'DURGAPUR', '', '', 'NAJ', '000000000000', '', '', '', '', '', NULL, NULL, NULL, NULL, 1, 'global', NULL, '2025-11-05 15:36:40'),
(18, 'NATINOL TRAILER SERVICE.NAJ', 'DURGAPUR', NULL, NULL, NULL, '000000000000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-05 15:36:40'),
(19, 'NATINOL TRAILER SERVICE.NAJ', 'DURGAPUR', NULL, NULL, NULL, '000000000000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-05 15:36:40'),
(20, 'MMK TRAILOR SERVICE', '', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-06 12:57:13'),
(21, 'NATIONAL TRAILOR MOVERCE ', 'DURGAPUR', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-07 11:20:05'),
(22, 'NATIONAL TRAILOR MOVERCE ', 'DURGAPUR', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-07 11:20:05'),
(23, 'NATIONAL TRAILOR MOVERCE ', 'DURGAPUR', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-07 11:20:05'),
(24, 'AK TRANSPORT RASID', 'DURGAPUR', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-07 12:05:31'),
(25, 'Narendra singh punia ', '', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 2, '2025-11-08 11:50:53'),
(26, 'NEW ANKIT ROAD CARRIER', 'DURGAPUR', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'global', 3, '2025-11-10 13:58:12');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `state_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `name`, `state_id`) VALUES
(1, 'Durgapur', 1),
(2, 'Kolkata', 1),
(3, 'Raipur', 2),
(4, 'Gurgaon', 3),
(5, 'Bangalore', 4),
(6, 'Jamshedpur', 5),
(7, 'Baddi', 6),
(8, 'Patna', 7),
(9, 'Delhi', 8),
(10, 'Mumbai', 9),
(11, 'Guwahati', 10),
(12, 'Silchar', 10),
(13, 'Goalpara', 10),
(14, 'Silapathar', 10),
(15, 'Tezpur', 10),
(16, 'Agartala', 11),
(17, 'Sabroom', 11),
(18, 'Udaipur', 11),
(19, 'Dharmanagar', 11),
(20, 'Kumarghat', 11),
(21, 'Asansol', 1),
(22, 'Murshidabad', 1),
(23, 'Azara', 10),
(24, 'Chayagaon', 10),
(25, 'Dhubri', 10),
(26, 'Sibsagar', 10),
(27, 'Tinsukia', 10),
(28, 'Jagiroad', 10),
(29, 'Raha', 10),
(30, 'Moranhat', 10),
(31, 'Jonai', 10),
(32, 'Sonitpur', 10),
(33, 'Deohati', 10),
(34, 'Hajo', 10),
(35, 'Mendipathar', 10),
(36, 'Lakhipur', 10),
(37, 'Katigorah', 10),
(38, 'Badarpur', 10),
(39, 'Panchgram', 10),
(40, 'Patharkandi', 10),
(41, 'Sonai', 10),
(42, 'Katakhal', 10),
(43, 'Phuentsholing', 15),
(44, 'Gaya', 7),
(45, 'Bhagalpur', 7),
(46, 'Chandigarh', 18),
(47, 'Amritsar', 18),
(48, 'Ludhiana', 18),
(49, 'Jalandhar', 18),
(50, 'Dehradun', 19),
(51, 'Haridwar', 19),
(52, 'Rishikesh', 19),
(53, 'Nainital', 19),
(54, 'Srinagar', 20),
(55, 'Jammu', 20),
(56, 'Anantnag', 20),
(57, 'Bhopal', 21),
(58, 'Indore', 21),
(59, 'Gwalior', 21),
(60, 'Jabalpur', 21),
(61, 'Thiruvananthapuram', 22),
(62, 'Kochi', 22),
(63, 'Kozhikode', 22),
(64, 'Thrissur', 22),
(65, 'Visakhapatnam', 23),
(66, 'Vijayawada', 23),
(67, 'Tirupati', 23),
(68, 'Guntur', 23),
(69, 'Bhubaneswar', 24),
(70, 'Cuttack', 24),
(71, 'Puri', 24),
(72, 'Rourkela', 24),
(73, 'Shillong', 25),
(74, 'Tura', 25),
(75, 'Baghmara', 25),
(76, 'Barengapara', 25),
(77, 'Mankachar', 25),
(78, 'Phulbari', 25),
(79, 'Hatsingimari', 25),
(80, 'Rongram', 25),
(81, 'Williamnagar', 25),
(82, 'Tikrikilla', 25),
(83, 'Khowai', 11),
(84, 'Ambasa', 11),
(85, 'Kakraban', 11),
(86, 'Garobadha', 25),
(87, 'Choutaki', 10),
(88, 'Bongaigaon', 10),
(89, 'Mankachar', 10),
(90, 'Mahendraganj', 25),
(91, 'Mendipathar', 25),
(92, 'Lunglei', 12),
(93, 'Aizawl', 12),
(94, 'Saiha', 12),
(96, 'Vairengte', 12),
(97, 'Kolasib', 12),
(98, 'Belonia', 11),
(99, 'Santirbazar', 11),
(100, 'Katigorah', 10),
(101, 'Teliamura', 11),
(102, 'Karimganj', 10),
(103, 'Udarbond', 10),
(104, 'Dibrugarh', 10),
(105, 'Sonari', 10),
(106, 'Golaghat', 10),
(107, 'Hojai', 10),
(108, 'Kamalabari', 10),
(109, 'North Lakhimpur', 10),
(110, 'Bilasipara', 10),
(112, 'Biswanath Charali', 10),
(113, 'Rangvamual ', 10),
(114, 'Bawngkawn ', 10),
(115, 'Gauripur , Bharmaputra Industrial area, ', 10),
(116, 'Gauripur ', 10),
(117, 'Srirampur ', 10),
(118, 'Ladrymbai ', 10),
(119, 'Phuentsholing ', 15),
(120, 'Thimphu town ', 15),
(121, 'Sapatgaram ', 10),
(122, 'Dudhnoi', 10),
(123, 'Siliguri ', 10),
(124, 'Singimari ', 25),
(125, 'Krishnai ', 10),
(126, 'Howly ', 10),
(127, 'Champaknagar ', 25),
(128, 'Lakhipur ', 10),
(129, 'Dharmanagar ', 11),
(130, 'Bhaga Bazar ', 10),
(131, 'Kalachhara ', 11),
(132, 'Sivsagar ', 10),
(133, 'Gandacherra ', 11),
(134, 'Abdullapur ', 10),
(135, 'Tulamura ', 11),
(136, 'Hailakandi ', 10),
(137, 'Gohpur ', 10),
(138, 'Nalchar ', 11),
(139, 'Lawngtlai ', 12),
(140, 'Thumapui ', 12),
(141, 'Kokrajhar ', 10),
(142, 'Barpeta town ', 10),
(143, 'Siliguri', 1),
(144, 'Champaknagar', 11),
(145, 'Lad Rymbai', 25),
(146, 'JAMURIA', 1),
(147, 'GHAZIABAD', 13),
(148, 'BURNPUR', 1),
(149, 'BANGALORE', 4),
(150, 'KASHIPUR', 19),
(151, 'FARIDABAD', 3),
(152, 'BARJORA', 1),
(153, 'JAMURIA', 1),
(154, 'JAMURIA', 1),
(155, 'BHIWADI', 3),
(156, 'BHIWADI', 3),
(157, 'BHIWADI', 26),
(158, 'BHIWADI', 26),
(159, 'BARJORA', 1),
(160, 'Ladrymbai ', 25);

-- --------------------------------------------------------

--
-- Table structure for table `company_details`
--

CREATE TABLE `company_details` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gst_no` varchar(50) DEFAULT NULL,
  `fssai_no` varchar(100) DEFAULT NULL,
  `pan_no` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `contact_number_1` varchar(20) DEFAULT NULL,
  `contact_number_2` varchar(20) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_details`
--

INSERT INTO `company_details` (`id`, `name`, `slogan`, `address`, `gst_no`, `fssai_no`, `pan_no`, `email`, `website`, `contact_number_1`, `contact_number_2`, `logo_path`) VALUES
(1, 'STC LOGISTICS', 'Delivering Excellence On Time Every Time', '288/N/1/S, Ambagan, Bhiringee, Durgapur, West Bengal', '19BKAPC0667G1ZF', '', 'BKAPC0667G', 'info@stclogistics.in', 'www.stclogistics.in', '8707024051', '9932700246', 'uploads/company/logo.png');

-- --------------------------------------------------------

--
-- Table structure for table `consignment_descriptions`
--

CREATE TABLE `consignment_descriptions` (
  `id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consignment_descriptions`
--

INSERT INTO `consignment_descriptions` (`id`, `description`, `is_active`) VALUES
(1, 'Amul product only', 1),
(2, 'Biscuit/bisk/cake', 1),
(3, 'BISCUITS/RUSK/CAKES', 1),
(4, 'BISK FARM BISCUITS ', 1),
(5, 'ANGAL/CHANNEL', 1),
(6, 'NPB 400', 1),
(7, 'NPB 450', 1),
(8, 'JOIS', 1),
(9, 'CHANNEL/JOIST', 1),
(10, 'NPB', 1),
(13, 'TMT', 1);

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `name`) VALUES
(1, 'India'),
(2, 'Bhutan');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `license_number` varchar(100) NOT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `aadhaar_no` varchar(20) DEFAULT NULL,
  `pan_no` varchar(20) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `license_doc_path` varchar(255) DEFAULT NULL,
  `aadhaar_doc_path` varchar(255) DEFAULT NULL,
  `bank_doc_path` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `name`, `address`, `contact_number`, `license_number`, `license_expiry_date`, `aadhaar_no`, `pan_no`, `photo_path`, `license_doc_path`, `aadhaar_doc_path`, `bank_doc_path`, `branch_id`, `employee_id`, `is_active`, `created_at`) VALUES
(1, 'Mrinal Banikya', 'Bhalukdubi, Balijana, Goalpara, Assam-783101', '9864182519', 'AS18 20130008426', '2025-07-07', '', '', '', 'uploads/drivers/driver_license_doc_path_1759567172.jpg', '', '', 2, 6, 1, '2025-10-04 07:46:39'),
(2, 'Binoy Ch Rabha', 'Rabhapara, Abhayapuri, Bongaigaon,- 783384', '8248835865', 'AS14 20040034921', '2027-11-14', '', '', '', 'uploads/drivers/driver_license_doc_path_1759566350.jpg', '', '', 2, 7, 1, '2025-10-04 07:48:15'),
(3, 'Jadav Ray', 'Kalyanpur, Balijana, Goalpara-783101', '9954919136', 'AS18 200150016617', '2027-07-16', '', '', '', 'uploads/drivers/driver_license_doc_path_1759566916.jpg', '', '', 2, 8, 1, '2025-10-04 07:49:38'),
(4, 'Kushal Ch Ray', 'Kochpara, Agia, Balijana, Goalpara-783101', '8761813884', 'AS18 20140006404', '2026-08-15', '', '', '', 'uploads/drivers/driver_license_doc_path_1759565217.jpg', '', '', 2, 9, 1, '2025-10-04 07:51:16'),
(5, 'Beikudar Saikia', 'Melamora, Golaghat- 785621', '9365128502', 'AS18 20100006696', '2030-03-03', '', '', '', 'uploads/drivers/driver_license_doc_path_1759565997.jpg', '', '', 2, 11, 1, '2025-10-04 07:52:50'),
(6, 'Narabinda Rabha', 'Kasumari, Resubelpara, Krishnai, Goalpara-783120', '9707995470', 'ML13 20220001029', '2028-12-19', '', '', '', 'uploads/drivers/driver_license_doc_path_1759567372.jpg', '', '', 2, 12, 1, '2025-10-04 07:54:51'),
(7, 'Bidhu Namashudra', 'Khelma Part-VII, Gumra Bazar, Katigorah, Cachar, Assam-788815', '9394704898', 'AS11 20210003199', '2027-07-15', '', '', '', 'uploads/drivers/driver_license_doc_path_1759566183.jpg', '', '', 2, 13, 1, '2025-10-04 07:57:03'),
(8, 'Arabinda Rabha', 'Vill- Pubgathiapara, Dhanubhanga, Rangjuli, Goalpara- 783123', '8135005557', 'AS14 19980003140', '2024-10-09', '', '', '', 'uploads/drivers/driver_license_doc_path_1759564993.jpg', '', '', 2, 14, 1, '2025-10-04 08:00:12'),
(9, 'Dwipjyoti Hazowary', 'Gathiapara, Dhanubhanga, Rangjuli, Goalpara, Assam-783130', '8099779746', 'AS18 20100018686', '2027-04-24', '', '', '', 'uploads/drivers/driver_license_doc_path_1759566572.jpg', '', '', 2, 16, 1, '2025-10-04 08:09:39'),
(10, 'Tileswar Rabha', 'Dekapara, Bhatipara, Boko, Kamrup-781123', '6900380997', 'AS25 20200005655', '2028-02-01', '', '', '', 'uploads/drivers/driver_license_doc_path_1759567871.jpg', '', '', 2, 18, 1, '2025-10-04 08:15:40'),
(11, 'Ganga Rabha', NULL, NULL, 'AS180000000000', '2025-10-04', NULL, NULL, NULL, NULL, NULL, NULL, 2, 19, 1, '2025-10-04 09:07:05'),
(12, 'FAIJUL HASAN', NULL, NULL, 'UP1420150020518', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, 1, '2025-11-04 12:16:34');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `employee_code` varchar(50) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `date_of_joining` date DEFAULT NULL,
  `pan_no` varchar(20) DEFAULT NULL,
  `aadhaar_no` varchar(20) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `bank_ifsc_code` varchar(20) DEFAULT NULL,
  `status` enum('Active','Resigned','Terminated') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `branch_id`, `full_name`, `employee_code`, `designation`, `department`, `date_of_joining`, `pan_no`, `aadhaar_no`, `bank_account_no`, `bank_ifsc_code`, `status`) VALUES
(3, 3, 2, 'Abhinash Kumar', 'STC202501', 'Fleet Manager', 'Logistics', '2025-10-01', '', '', '', '', 'Active'),
(5, 5, 2, 'Shubham Chaubey', 'STC202511', 'Manager', 'Logistics', '2025-10-01', '', '', '', '', 'Active'),
(6, NULL, 2, 'Mrinal Banikya', 'STC202551', 'Driver', 'Logistics', '2025-10-01', '', '', '', '', 'Active'),
(7, NULL, 2, 'Binoy Ch Rabha', 'STC202552', 'Driver', 'Logistics', '2025-10-01', '', '', '', '', 'Active'),
(8, NULL, 2, 'Jadav Ray', 'STC202553', 'Driver', 'Logistics', '2025-10-01', '', '', '', '', 'Active'),
(9, NULL, 2, 'Kushal Ch Ray', 'STC202554', 'Driver', 'Logistics', '2025-10-01', '', '', '', '', 'Active'),
(11, NULL, 2, 'Beikudar Saikia', 'STC202555', 'Driver', 'Logistics', '2025-10-01', '', '', '', '', 'Active'),
(12, NULL, 2, 'Narabinda Rabha', 'STC202556', 'Driver', 'Logistics', '2025-10-01', '', '', '', '', 'Active'),
(13, NULL, 2, 'Bidhu Namashudra', 'STC202557', 'Driver', 'Logistics', '2025-10-01', '', '', '', '', 'Active'),
(14, NULL, 2, 'Arabinda Rabha', 'STC202558', 'Driver', 'Logistics', '2025-10-01', '', '', '', '', 'Active'),
(16, NULL, 2, 'Dwipjyoti Hazowary', 'STC202559', 'Driver', 'Logistics', '2025-10-01', '', '', '', '', 'Active'),
(18, NULL, 2, 'Tileswar Rabha', 'STC202560', 'Driver', 'Logistics', '2025-10-01', '', '', '', '', 'Active'),
(19, NULL, 2, 'Ganga Rabha', 'STC202561', 'Driver', 'Logistics', '2025-10-01', '', '', '', '', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_to` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `shipment_id` int(11) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `branch_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_date`, `category`, `amount`, `paid_to`, `description`, `shipment_id`, `vehicle_id`, `employee_id`, `branch_id`, `created_by`, `created_at`) VALUES
(1, '2025-10-31', 'Salary', 10000.00, '', '', NULL, NULL, 3, 2, 5, '2025-10-31 11:48:27');

-- --------------------------------------------------------

--
-- Table structure for table `fuel_logs`
--

CREATE TABLE `fuel_logs` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `odometer_reading` int(11) NOT NULL,
  `fuel_quantity` decimal(8,2) NOT NULL,
  `fuel_rate` decimal(8,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `fuel_station` varchar(255) DEFAULT NULL,
  `filled_by_driver_id` int(11) DEFAULT NULL,
  `branch_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(100) NOT NULL,
  `invoice_date` date NOT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `consignor_id` int(11) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Generated',
  `created_by_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_no`, `invoice_date`, `from_date`, `to_date`, `consignor_id`, `total_amount`, `status`, `created_by_id`, `branch_id`, `created_at`) VALUES
(8, 'STC/GCMMF/117', '2025-11-05', '2025-10-01', '2025-10-15', 3, 330993.99, 'Generated', 5, 2, '2025-11-05 08:47:42'),
(11, 'STC/BFPL/24', '2025-10-30', '2025-10-01', '2025-10-15', 21, 381600.00, 'Partially Paid', 5, 2, '2025-11-06 12:46:30'),
(12, 'STC/BFPL/25', '2025-11-05', '2025-10-16', '2025-10-31', 21, 718500.00, 'Generated', 5, 2, '2025-11-06 12:47:14'),
(13, 'STC/BFPL/26', '2025-11-05', '2025-10-01', '2025-10-15', 21, 49700.00, 'Generated', 5, 2, '2025-11-11 10:04:58');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `shipment_id`) VALUES
(74, 8, 1),
(75, 8, 2),
(76, 8, 3),
(77, 8, 4),
(78, 8, 11),
(79, 8, 12),
(80, 8, 13),
(81, 8, 17),
(82, 8, 18),
(83, 8, 19),
(84, 8, 21),
(85, 8, 25),
(86, 8, 26),
(87, 8, 27),
(88, 8, 32),
(89, 8, 33),
(90, 8, 36),
(91, 8, 37),
(92, 8, 39),
(93, 8, 42),
(94, 8, 43),
(95, 8, 44),
(96, 8, 47),
(97, 8, 48),
(98, 8, 52),
(99, 8, 53),
(100, 8, 54),
(101, 8, 57),
(102, 8, 58),
(103, 8, 59),
(118, 11, 5),
(120, 11, 7),
(121, 11, 8),
(122, 11, 10),
(123, 11, 30),
(124, 11, 34),
(125, 11, 51),
(126, 11, 60),
(127, 12, 65),
(128, 12, 66),
(129, 12, 67),
(130, 12, 81),
(131, 12, 82),
(132, 12, 86),
(133, 12, 87),
(134, 12, 88),
(135, 12, 96),
(136, 12, 98),
(137, 12, 106),
(138, 12, 107),
(139, 12, 108),
(140, 12, 110),
(141, 12, 114),
(142, 12, 118),
(143, 13, 6);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_payments`
--

CREATE TABLE `invoice_payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `reconciliation_voucher_id` int(11) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount_received` decimal(12,2) NOT NULL,
  `tds_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_mode` varchar(50) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `received_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_payments`
--

INSERT INTO `invoice_payments` (`id`, `invoice_id`, `reconciliation_voucher_id`, `payment_date`, `amount_received`, `tds_amount`, `payment_mode`, `reference_no`, `remarks`, `received_by`, `created_at`) VALUES
(1, 11, NULL, '2025-11-07', 377784.00, 3816.00, 'Bank Transfer', 'HSBCN52025110791567160', NULL, 5, '2025-11-11 10:13:44');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_logs`
--

CREATE TABLE `maintenance_logs` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `service_date` date NOT NULL,
  `service_type` varchar(255) NOT NULL,
  `odometer_reading` int(11) DEFAULT NULL,
  `service_cost` decimal(10,2) NOT NULL,
  `vendor_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `invoice_doc_paths` text DEFAULT NULL COMMENT 'JSON array of file paths',
  `tyre_number` varchar(100) DEFAULT NULL,
  `branch_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_logs`
--

INSERT INTO `maintenance_logs` (`id`, `vehicle_id`, `service_date`, `service_type`, `odometer_reading`, `service_cost`, `vendor_name`, `description`, `next_service_date`, `invoice_doc_paths`, `tyre_number`, `branch_id`, `created_by`) VALUES
(1, 2, '2025-10-16', 'Other', 0, 3010.00, 'Rajdhani Motors', 'Water separator gasket change', NULL, NULL, NULL, 2, 5),
(2, 5, '2025-10-13', 'General Service', 0, 11130.00, 'Brahmaputra Motors', 'General Service', '2026-10-01', NULL, NULL, 2, 5),
(3, 6, '2025-10-14', 'Accident Repair', 0, 2000.00, 'Reliable', 'Container Damage Repair', NULL, NULL, NULL, 2, 5),
(4, 8, '2025-11-04', 'General Service', 0, 9885.00, 'BRAHMAPUTRA MOTOR WORKS', 'Periodic Service', '2026-11-01', '[\"uploads\\/maintenance\\/log_4_invoice_doc_1_1762252389.jpeg\",\"uploads\\/maintenance\\/log_4_invoice_doc_2_1762252389.jpeg\"]', NULL, 2, 5),
(5, 12, '2025-11-04', 'General Service', 0, 24900.00, '12 mile', 'Periodic Service\r\ni beam\r\n3 tyre hub greasing\r\nkin pin\r\n(please refer to bill)', '2026-11-01', '[\"uploads\\/maintenance\\/log_5_invoice_doc_1_1762252608.jpeg\",\"uploads\\/maintenance\\/log_5_invoice_doc_2_1762252608.jpeg\",\"uploads\\/maintenance\\/log_5_invoice_doc_3_1762252608.jpeg\",\"uploads\\/maintenance\\/log_5_invoice_doc_4_1762252608.jpeg\"]', NULL, 2, 5);

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_service_types`
--

CREATE TABLE `maintenance_service_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_service_types`
--

INSERT INTO `maintenance_service_types` (`id`, `name`, `is_active`) VALUES
(1, 'Accident Repair', 1),
(2, 'Brake Repair', 1),
(3, 'Engine Work', 1),
(4, 'General Service', 1),
(5, 'Oil Change', 1),
(6, 'Other', 1),
(7, 'Tyre Replacement', 1);

-- --------------------------------------------------------

--
-- Table structure for table `parties`
--

CREATE TABLE `parties` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `party_type` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `gst_no` varchar(50) DEFAULT NULL,
  `pan_no` varchar(20) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `map_location` text DEFAULT NULL,
  `gst_doc_path` varchar(255) DEFAULT NULL,
  `pan_doc_path` varchar(255) DEFAULT NULL,
  `credit_limit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `branch_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parties`
--

INSERT INTO `parties` (`id`, `name`, `party_type`, `address`, `city`, `state`, `country`, `gst_no`, `pan_no`, `contact_number`, `contact_person`, `map_location`, `gst_doc_path`, `pan_doc_path`, `credit_limit`, `branch_id`, `is_active`, `created_at`) VALUES
(1, 'Anmol Industries Ltd', 'Consignor', 'Plot No- 15, Brahmaputra Industrial Area, Gauripur, North Guwahati', 'Guwahati', 'Assam', 'India', '18AADCB9169P1ZU', NULL, '', 'Manoj Saikia', NULL, NULL, NULL, 0.00, 2, 1, '2025-09-22 15:03:28'),
(2, 'Anmol Industries Ltd', 'Consignee', 'Ramnagar, Silchar', 'Silchar', 'Assam', 'India', '', NULL, '', 'Saidul', NULL, NULL, NULL, 0.00, 2, 1, '2025-09-22 15:04:17'),
(3, 'Gujrat Co-Operative Milk Marketing Federation Limited, Bongaigaon', 'Consignor', 'C/O- Amit Harlalka Complex, Abhyapuri, Bongaigaon-783384', 'Choutaki', 'Assam', 'India', '18AAAAG5588Q2ZU', 'AAAAG5588Q', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:20:52'),
(4, 'Agarwal Agency', 'Consignee', 'Mankachar', 'Mankachar', 'Meghalaya', 'India', '18ACBPA7442D1Z3', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:22:13'),
(5, 'Rahul Prasad', 'Consignee', 'Tura Bazar', 'Tura', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:23:09'),
(6, 'Meghalaya Store', 'Consignee', 'Phulbari Bazar', 'Phulbari', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:24:11'),
(7, 'M/S Sanjeev Kumar Sah', 'Consignee', '58, Rongram Bazar', 'Rongram', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:25:19'),
(8, 'Krishna Store', 'Consignee', 'Barengapara Bazar', 'Barengapara', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:26:24'),
(9, 'M/S Saha Store', 'Consignee', 'Garobadha Bazar', 'Garobadha', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:27:32'),
(10, 'Erkin Islam', 'Consignee', 'Ward No- 2, Beparipara, Mankachar', 'Mankachar', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:30:28'),
(11, 'Sangma Sweets', 'Consignee', 'Main Bazar, Tikrikilla', 'Tikrikilla', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:34:19'),
(12, 'Hanuman Bhander', 'Consignee', 'Williamnagar Bazar, Williamnagar', 'Williamnagar', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:35:19'),
(13, 'Wool House', 'Consignee', 'Tura Bazar, Tura', 'Tura', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:36:02'),
(14, 'Bajrangbali Store', 'Consignee', 'Tura Bazar, Tura', 'Tura', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:37:02'),
(15, 'Sanjay Trade Agency', 'Consignee', 'TD Road, Tura', 'Tura', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:37:51'),
(16, 'M/S Ashok Store & Agency', 'Consignee', 'Tura Bazar, Tura', 'Tura', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:38:34'),
(17, 'Shree Narohari Bhandar', 'Consignee', 'Babupara 1, Mahendraganj Bazar', 'Mahendraganj', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:40:00'),
(18, 'Riya Store', 'Consignee', 'Mahamaya Nagar, Hatsingimari', 'Hatsingimari', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:41:38'),
(19, 'M/S S.S Agencies', 'Consignee', 'Mendipathar, Resubelpara', 'Mendipathar', 'Meghalaya', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 09:43:42'),
(21, 'Bhagwati Foods Pvt Ltd', 'Consignor', 'Pamohi, Garchuk, Kamrup Metropolitan, Assam - 781035', 'Guwahati', 'Assam', 'India', '18AABCB3680P1Z8', 'AABCB3680P', '7002821232', 'Biswajeet Hazarika', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:02:07'),
(22, 'RR Enterprise', 'Consignee', '2E/012, Ngaizel, Lunglei', 'Lunglei', 'Mizoram', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:04:01'),
(23, 'Pancha Pandova', 'Consignee', 'M B C Nagar, Chittamara, Belonia', 'Belonia', 'Tripura', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:08:27'),
(24, 'Radha Rani Enterprise', 'Consignee', '196, Paul Para, Agartala', 'Agartala', 'Tripura', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:10:20'),
(25, 'Maa Tripureswari Stores', 'Consignee', 'Ward No- 5, Kanchan Nagar, Santirbazar', 'Santirbazar', 'Tripura', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:14:04'),
(26, 'Sena Store, Lunglei', 'Consignee', 'Lunglei', 'Lunglei', 'Mizoram', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:16:10'),
(27, 'Sena Store, Aizawl', 'Consignee', 'Aizawl', 'Aizawl', 'Mizoram', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:16:45'),
(28, 'Sena Store, Saiha', 'Consignee', '01 Bazar Veng, Saiha', 'Saiha', 'Mizoram', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:18:07'),
(29, 'SATYA RANJAN PAUL', 'Consignee', 'AMBASSA, KULAI, TRIPURA', 'Ambasa', 'Tripura', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:19:34'),
(30, 'PAUL AGENCY', 'Consignee', 'KATIRIAL, KATIGORAH, CACHAR', 'Katigorah', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:21:23'),
(31, 'RAMTHAKUR VARITIES', 'Consignee', 'Belonia', 'Belonia', 'Tripura', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:21:55'),
(32, 'GG ENTERPRISE', 'Consignee', 'Lunglei', 'Lunglei', 'Mizoram', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:22:26'),
(33, 'SAHA ENTERPRISE', 'Consignee', 'DHARMANAGAR', 'Dharmanagar', 'Tripura', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:22:59'),
(34, 'SAANVI ENTERPRISE', 'Consignee', 'TELIAMURA', 'Teliamura', 'Tripura', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:24:02'),
(35, 'TANUJ ENTERPRISE', 'Consignee', 'Kakraban', 'Kakraban', 'Tripura', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:28:33'),
(36, 'SHYMANADA STORE', 'Consignee', 'Silchar', 'Silchar', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:29:21'),
(37, 'UMADUL ENTERPRISE', 'Consignee', 'SILCHAR', 'Silchar', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:30:01'),
(38, 'PRONATI STORE', 'Consignee', 'Sonai', 'Sonai', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:30:42'),
(39, 'SAI TRADERS', 'Consignee', 'SILCHAR ROAD, KARIMGANJ', 'Karimganj', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:31:54'),
(40, 'BANSHIKA STORE', 'Consignee', 'SILCHAR', 'Silchar', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:33:02'),
(41, 'IRFAN ENTERPRISE', 'Consignee', 'BAGHA BAZAR , SILCHAR', 'Bhaga Bazar ', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:34:09'),
(42, 'AB ENTERPRISE', 'Consignee', 'GROUND FLOOR CACHAR, SILCHAR', 'Silchar', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:34:43'),
(43, 'TUOLOR AGENCIES', 'Consignee', 'VAIRENGTE', 'Vairengte', 'Mizoram', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:35:12'),
(44, 'JALAN AGENCIES', 'Consignee', 'SILCHAR', 'Silchar', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:35:50'),
(45, 'LOKNATH STORE, UDARBOND', 'Consignee', 'DURGANAGAR VIP ROAD , UDARBOND', 'Udarbond', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:36:39'),
(46, 'KEDIA TRADE AND AGENCIES, DIBRUGARH', 'Consignee', 'DIBRUGARH', 'Dibrugarh', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:38:38'),
(47, 'BHOWMIK TRADERS', 'Consignee', 'BANSHBARI, BARUAH PATHAR, DIBRUGARH', 'Dibrugarh', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:40:00'),
(48, 'MAA BHAGWATI AGENCY, SIVASAGAR', 'Consignee', 'SIVASAGAR', 'Sibsagar', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:42:20'),
(49, 'DUTTA SONS', 'Consignee', 'MORANHAT COLLEGE ROAD, MORANHAT', 'Moranhat', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:43:13'),
(50, 'PC MARKETING, SONARI', 'Consignee', 'SONARI', 'Sonari', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:43:57'),
(51, 'ANJANI AGENCY HOUSE', 'Consignee', 'TAPAN NAGAR, GOLAGHAT (MB),785621', 'Golaghat', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:46:06'),
(52, 'S.R TRADING', 'Consignee', 'HOTEL ASHOK, HOJAI', 'Hojai', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:48:03'),
(55, 'TOSHNIWAL TRADING CO.', 'Consignee', 'NORTH LAKHIMPUR', 'North Lakhimpur', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:55:30'),
(56, 'SETHIA BROTHERS, BILASIPARA', 'Consignee', 'BILASIPARA', 'Bilasipara', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:57:08'),
(57, 'SWASTIK, BANKAR KHOWAI', 'Consignee', 'BANKAR KHOWAI', 'Khowai', 'Tripura', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 11:57:51'),
(58, 'TBC AGENCY', 'Consignee', 'Rangvamual', 'Aizawl', 'Mizoram', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 17:32:22'),
(59, 'RNS AGENCIES', 'Consignee', 'BAWNGKAWN', 'Aizawl', 'Mizoram', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 17:39:58'),
(61, 'Mithu Traders', 'Consignee', 'Ranibazar, Agartala, Tripura', 'Agartala', 'Tripura', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 18:15:04'),
(62, 'Anmol industries Ltd, Silchar', 'Consignor', 'ground floor, holding no.B/C/10, National Highway Bypass, Chirukandi, Ramnagar', 'Silchar', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 18:23:04'),
(63, 'Maa Kali Enterprise', 'Consignee', 'Lakhipur Bazar, Cachar Silchar', 'Silchar', 'Assam', 'India', '', '', '', '', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-04 18:24:39'),
(65, 'Saha store', 'Consignee', 'Srirampur', 'Srirampur ', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-07 05:49:46'),
(66, 'Rajesh Kumar jain', 'Consignee', 'Ladrymbai', 'Lad Rymbai', 'Meghalaya', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-07 13:21:46'),
(67, 'PLADIS INDIA PRIVATE LIMITED', 'Consignor', '(UNITED BISCUITS PVT LIMITED) RUKMINIGAON, GUWAHATI,ASSAM,781022', 'Guwahati', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-08 12:59:33'),
(68, 'Tashi Commercial Corporation', 'Consignee', 'Phuentsholing, butan', 'Phuentsholing', 'Bhutan', 'Bhutan', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-08 13:01:02'),
(69, '8 Eleven', 'Consignee', 'Thimphu thim throns village, Thimphu town', 'Thimphu town ', 'Bhutan', 'Bhutan', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-08 13:07:00'),
(70, 'DIGSHQ BSF', 'Consignee', 'Tura', 'Tura', 'Meghalaya', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-09 07:37:20'),
(71, 'Bargabh agency,', 'Consignor', 'Sapatgaram ', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 2, 1, '2025-10-09 13:43:08'),
(72, 'Bargabh agency,', 'Consignee', 'Sapatgaram ', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 2, 1, '2025-10-09 13:44:03'),
(73, 'Ramthakur Traders,', 'Consignee', 'Udaipur', 'Udaipur', 'Tripura', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-09 17:24:55'),
(74, 'The Next G,', 'Consignee', 'Kumarghat', 'Kolasib', 'Mizoram', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-09 17:28:11'),
(75, 'Karimganj Provijon Store', 'Consignee', 'Karimganj', 'Karimganj', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-10 04:44:39'),
(76, 'Adarsh Pan Bhandar', 'Consignee', 'Dhudnoi', 'Dudhnoi', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-10 14:09:25'),
(77, 'JAI GOVINDA BHANDAR', 'Consignee', 'Dhubri', 'Dhubri', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-10 14:48:32'),
(78, 'B.B ENTERPRISE', 'Consignee', 'Bongaigaon', 'Bongaigaon', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-10 14:58:56'),
(79, 'SETHIA BROTHERS', 'Consignee', 'Bilasipara', 'Bilasipara', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-10 15:20:04'),
(80, 'P.C MARKETING', 'Consignee', 'Sivsagar, Sonari', 'Sonari', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-11 06:25:55'),
(81, 'RABISANKAR DUTTA CHOUDHARY', 'Consignee', 'Karimganj', 'Karimganj', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-12 09:08:15'),
(82, 'DHRITISHREE ENTERPRISE', 'Consignee', 'Williamnagar', 'Williamnagar', 'Meghalaya', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-13 12:53:42'),
(83, 'M/S SAJ FOOD PRODUCTS PVT.LTD.', 'Consignor', 'Vill-Garal,P.O: Bhattapara,P.S: Azara, kamrup (M), Guwahati,Assam,781017', 'Azara', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-14 04:23:46'),
(84, 'CB TRADE AGENCIES', 'Consignee', 'Supr Biswanath Charali', 'Biswanath Charali', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-14 04:25:41'),
(85, 'AARYAN ENTERPRISE', 'Consignee', 'Siliguri', 'Siliguri', 'West Bengal', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-14 11:48:19'),
(86, 'Shri Krishna Store Meghalaya', 'Consignee', 'Singimari', 'Singimari ', 'Meghalaya', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-14 11:57:50'),
(87, 'BHAI BHAI STORE', 'Consignee', 'Krishnai', 'Krishnai ', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-14 16:00:00'),
(88, 'BHARGABH AGENCY ', 'Consignee', 'Sapatgaram ', '', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 2, 1, '2025-10-15 12:54:29'),
(89, 'GAUTAM BASAK', 'Consignee', 'Howly', 'Howly ', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-15 12:57:33'),
(90, 'PRAVA VARIETIES', 'Consignee', 'Champaknagar', 'Champaknagar', 'Tripura', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-15 17:26:35'),
(91, 'Ganesh store', 'Consignee', 'Lakhipur', 'Lakhipur ', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-16 12:38:43'),
(92, 'NAG STORE', 'Consignee', 'M.S Road, Dharmanagar', 'Dharmanagar ', 'Tripura', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-16 17:05:39'),
(94, 'SISTE,S BUSINESS HUB', 'Consignee', 'Kolasib', 'Kolasib', 'Mizoram', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-17 02:23:18'),
(96, 'Namita Enterprise', 'Consignee', 'C/O Nirod Bhowmik,Kalachhara.', 'Kalachhara ', 'Tripura', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-17 11:11:54'),
(97, 'SARDAMONI', 'Consignee', 'Gandacherra', 'Gandacherra ', 'Tripura', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-18 18:00:45'),
(98, 'Choudhary Store', 'Consignee', 'Abdullapur', 'Abdullapur ', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-18 18:04:00'),
(99, 'Subhash Das Brothers', 'Consignee', 'Baghmara', 'Baghmara', 'Meghalaya', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-22 14:41:17'),
(100, 'ROHAN ENTERPRISE', 'Consignee', 'Tulamura Bazar, Gomati', 'Tulamura ', 'Tripura', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-22 15:23:29'),
(101, 'UDDAN KHETRA', 'Consignee', 'Ratanpur Road, Hailakandi', 'Hailakandi ', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-22 15:50:45'),
(102, 'Das Store', 'Consignee', 'Gohpur', 'Gohpur ', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-26 03:05:49'),
(103, 'S.S AGENCIES', 'Consignee', 'Mendhipathar', 'Mendipathar', 'Meghalaya', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-27 13:25:50'),
(104, 'R.D BROTHERS', 'Consignee', 'Mankachar', 'Mankachar', 'Meghalaya', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-28 12:13:06'),
(105, 'Shimal Saha', 'Consignee', 'Nalchar', 'Nalchar ', 'Tripura', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-29 07:11:05'),
(106, 'G.G Enterprise, Mizoram', 'Consignee', 'BN 90 HN 101,Bazar Veng Lawngtlai', 'Lawngtlai ', 'Mizoram', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-29 11:05:47'),
(107, 'Jagadish Store,', 'Consignee', 'Kokrajhar', 'Kokrajhar ', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-31 03:32:32'),
(108, 'Sambhar,', 'Consignee', 'Barpeta Town', 'Barpeta town ', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-10-31 13:52:04'),
(109, 'Amrit Bhandar', 'Consignee', 'Gohpur', 'Gohpur ', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-11-01 05:48:31'),
(110, 'MJM STORE', 'Consignee', 'Bilasipara', 'Bilasipara', 'Assam', 'India', '', '', '', '0', NULL, NULL, NULL, 0.00, 2, 1, '2025-11-01 11:21:35'),
(111, 'SHYAM SEL & POWER LTD.', 'Consignor', 'BIJAYNAGAR MORE,DHASNA,BAHADURPUR,JAMURIA-713362', 'JAMURIA', 'West Bengal', 'India', '19AAECS9421J1ZZ', '', '', '0', NULL, NULL, NULL, 0.00, 3, 1, '2025-11-04 12:07:07'),
(112, 'MAA SHARDA STEELS.', 'Consignee', '301/20.LOHAMANDI,GHAZIABAD-201001', 'GHAZIABAD', 'Uttar Pradesh', 'India', '09ACBPM2186L1ZA', '', '', '0', NULL, NULL, NULL, 0.00, 3, 1, '2025-11-04 12:09:46'),
(113, 'STEEL AUTHORITY OF INDIA LTD.', 'Consignor', 'IISCO STEEL PLANT,BURNPUR-713325 (WEST BENGAL)', 'Asansol', NULL, NULL, '19AAACS7062F6Z6', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 3, 1, '2025-11-04 12:26:15'),
(114, 'SULIT METALS AND ALLOYS PVT LTD.', 'Consignee', '2ND PHASE,INDUSTRIAL AREA,KUDUMALAK,GAWRIBDANPUR,BANGALORE-561208', 'Bangalore', NULL, NULL, '29ABCCS9435P1ZG', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 3, 1, '2025-11-04 12:29:58'),
(115, 'SUPER SMELTER LTD.', 'Consignor', 'JAMURIA INDUSTRIAL ESTATE,JAMURIA-713362', 'Durgapur', NULL, NULL, '19AAFCS1116F1ZN', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 3, 1, '2025-11-04 12:39:22'),
(116, 'SURYA ALLOY INDUSTRISE LTD.(ROLLING)', 'Consignor', 'VILL GHUTGORIA,KADASOLE,BARJORA,DIST-BANKURA-722202', 'Durgapur', NULL, NULL, '19AADCS5890E1Z2', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 3, 1, '2025-11-04 12:42:42'),
(117, 'RAINBOW STEELS', 'Consignee', '120 LOHAMANDI,GHAZIABAD-201009', 'GHAZIABAD', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 3, 1, '2025-11-04 12:43:31'),
(118, 'SHRI LAXMI STEELS', 'Consignee', '593,LOHAMANDI,SECTOR-59,FARIDABAD-121004', '', 'Haryana', 'India', '06ADWFS6234M1Z9', '', '', '0', NULL, NULL, NULL, 500000.00, 3, 1, '2025-11-04 13:13:00'),
(119, 'KAMTA PRSAD BRIJMOHANLAL', 'Consignee', '76/10,HALSEY ROAD,KANPUR-208001', '', 'Uttar Pradesh', 'India', '09AAEFK2773N1ZT', '', '', '0', NULL, NULL, NULL, 500000.00, 3, 1, '2025-11-04 13:15:31'),
(120, 'MANGALDEEP STEELS.', 'Consignee', 'D BLOCK,LOHAMANDI,GHAZIABAD-201009', 'GHAZIABAD', 'Uttar Pradesh', 'India', '09AADFM7975R1Z6', '', '', '0', NULL, NULL, NULL, 500000.00, 3, 1, '2025-11-04 13:17:49'),
(121, 'KIRAN ENTERPRISES.', 'Consignee', 'SCF NO.251-252,SEC-59,LOHAMANDI,FARIDABAD-121004', 'Gurgaon', 'Haryana', 'India', '06AALFK1051E1ZQ', '', '', '0', NULL, NULL, NULL, 500000.00, 3, 1, '2025-11-04 13:20:33'),
(122, 'BEHARI LAL AMRIT LAL AND SONS.', 'Consignee', '84/20,JK IRON COMPOUND,FAZALGANJ,KANPUR-208012', '', 'Uttar Pradesh', 'India', '09ABBFB0930C1Z3', '', '', '0', NULL, NULL, NULL, 500000.00, 3, 1, '2025-11-04 13:26:26'),
(123, 'BEHARI LAL AMRIT LAL', 'Consignee', 'ARAJI NO.675,SHACHENDI,KANPUR-209304', '', 'Uttar Pradesh', 'India', '09AARFB6791L1ZJ', '', '', '0', NULL, NULL, NULL, 500000.00, 3, 1, '2025-11-04 13:28:34'),
(124, 'NAINI PAPERS LTD.', 'Consignee', '7TH KM STONE,MORADABAD ROAD,KASHIPUR-244713', 'Dehradun', NULL, NULL, '05AAACN3805D1Z2', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 3, 1, '2025-11-04 13:38:19'),
(125, 'ONKARMAL BANSHIDHAR ', 'Consignee', 'Chamber Road, Tinsukia ', 'Tinsukia', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 2, 1, '2025-11-05 13:50:30'),
(126, 'M.D ENTERPRISE ', 'Consignee', 'Kamlabari', 'Kamalabari', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 2, 1, '2025-11-06 04:14:05'),
(127, 'RA STEELS ', 'Consignee', 'SHOP NO 3A/6 GOURAV PATH ROAD UIT COLONY BHIWADI', 'BHIWADI', NULL, NULL, '08AAWFR2293N1Z7', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 3, 1, '2025-11-07 12:01:07'),
(128, 'Rajesh Kumar jain', 'Consignee', 'Ladrymbai', 'Lad Rymbai', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 2, 1, '2025-11-08 09:47:40'),
(129, 'S.S Agencies', 'Consignee', 'Mendhipathar', 'Mendipathar', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 2, 1, '2025-11-08 12:35:47'),
(130, 'MAAN STEEL & POWER LTD ', 'Consignor', 'JAMURIA INDUSTRIAL ESATE MOUZA IKRA JAMURIA WB', 'JAMURIA', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 3, 1, '2025-11-10 13:54:40'),
(131, 'MAAN STEEL & POWER LTD ', 'Consignor', 'JAMURIA INDUSTRIAL ESATE MOUZA IKRA JAMURIA WB', 'JAMURIA', NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 3, 1, '2025-11-10 13:54:40');

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE `payslips` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `month_year` varchar(7) NOT NULL,
  `payable_days` int(2) NOT NULL,
  `gross_earnings` decimal(12,2) NOT NULL,
  `total_deductions` decimal(12,2) NOT NULL,
  `net_salary` decimal(12,2) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_mode` varchar(50) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `status` enum('Generated','Paid') NOT NULL DEFAULT 'Generated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payslips`
--

INSERT INTO `payslips` (`id`, `employee_id`, `month_year`, `payable_days`, `gross_earnings`, `total_deductions`, `net_salary`, `payment_date`, `payment_mode`, `reference_no`, `status`) VALUES
(4, 3, '2025-10', 30, 10000.00, 0.00, 10000.00, '2025-10-31', 'Bank Transfer', 'UPI', 'Paid'),
(5, 6, '2025-10', 30, 5000.00, 0.00, 5000.00, NULL, NULL, NULL, 'Generated'),
(6, 7, '2025-10', 30, 5000.00, 0.00, 5000.00, NULL, NULL, NULL, 'Generated'),
(7, 8, '2025-10', 30, 5000.00, 0.00, 5000.00, NULL, NULL, NULL, 'Generated'),
(8, 9, '2025-10', 30, 5000.00, 0.00, 5000.00, NULL, NULL, NULL, 'Generated'),
(9, 11, '2025-10', 30, 5000.00, 0.00, 5000.00, NULL, NULL, NULL, 'Generated'),
(10, 12, '2025-10', 30, 5000.00, 0.00, 5000.00, NULL, NULL, NULL, 'Generated'),
(11, 14, '2025-10', 30, 5000.00, 0.00, 5000.00, NULL, NULL, NULL, 'Generated'),
(12, 16, '2025-10', 30, 5000.00, 0.00, 5000.00, NULL, NULL, NULL, 'Generated'),
(13, 19, '2025-10', 30, 5000.00, 0.00, 5000.00, NULL, NULL, NULL, 'Generated');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'e.g., booking_edit',
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`) VALUES
(1, 'dashboard_view', 'Can view the main dashboard'),
(2, 'booking_create', 'Can create new shipment bookings'),
(3, 'booking_view_all', 'Can view all bookings (for their branch or all if admin)'),
(4, 'booking_edit', 'Can edit existing bookings'),
(5, 'tracking_update', 'Can update shipment tracking status'),
(6, 'pod_manage', 'Can manage Proof of Delivery (POD)'),
(7, 'billing_manage', 'Can access the Billing & Invoicing section'),
(8, 'accounting_manage', 'Can access the Accounting section'),
(9, 'fleet_manage', 'Can access the Fleet Management section'),
(10, 'reports_view', 'Can view all reports'),
(11, 'masters_manage', 'Can manage master data (Parties, Brokers, Drivers, Vehicles, etc.)'),
(12, 'settings_manage', 'Can manage system settings (Branches, Users, Company Details)');

-- --------------------------------------------------------

--
-- Table structure for table `reconciliation_vouchers`
--

CREATE TABLE `reconciliation_vouchers` (
  `id` int(11) NOT NULL,
  `voucher_no` varchar(100) NOT NULL,
  `voucher_date` date NOT NULL,
  `party_id` int(11) NOT NULL,
  `payment_mode` varchar(50) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `reconciled_by_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'Admin', 'Has all permissions and can manage the entire system.'),
(2, 'Manager', 'Can manage bookings, fleet, and accounting for their branch.'),
(3, 'Staff', 'Can create and view bookings, and update tracking information.');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(3, 1),
(3, 2),
(3, 3),
(3, 5),
(3, 6);

-- --------------------------------------------------------

--
-- Table structure for table `salary_structures`
--

CREATE TABLE `salary_structures` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `effective_date` date NOT NULL,
  `basic_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `hra` decimal(12,2) NOT NULL DEFAULT 0.00,
  `conveyance_allowance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `special_allowance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pf_employee_contribution` decimal(12,2) NOT NULL DEFAULT 0.00,
  `esi_employee_contribution` decimal(12,2) NOT NULL DEFAULT 0.00,
  `professional_tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tds` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salary_structures`
--

INSERT INTO `salary_structures` (`id`, `employee_id`, `effective_date`, `basic_salary`, `hra`, `conveyance_allowance`, `special_allowance`, `pf_employee_contribution`, `esi_employee_contribution`, `professional_tax`, `tds`) VALUES
(3, 14, '2025-10-01', 5000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(4, 3, '2025-10-01', 10000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(5, 11, '2025-10-01', 5000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(6, 7, '2025-10-01', 5000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(7, 16, '2025-10-01', 5000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(8, 9, '2025-10-01', 5000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(9, 19, '2025-10-01', 5000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(10, 8, '2025-10-01', 5000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(11, 6, '2025-10-01', 5000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(12, 12, '2025-10-01', 5000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(13, 18, '2025-10-01', 5000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` int(11) NOT NULL,
  `consignment_no` varchar(100) NOT NULL,
  `consignment_date` date NOT NULL,
  `consignor_id` int(11) NOT NULL,
  `consignee_id` int(11) NOT NULL,
  `is_shipping_different` tinyint(1) NOT NULL DEFAULT 0,
  `shipping_name` varchar(255) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `origin` varchar(255) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `description_id` int(11) DEFAULT NULL,
  `quantity` varchar(50) DEFAULT NULL,
  `package_type` varchar(100) DEFAULT NULL,
  `net_weight` varchar(50) DEFAULT NULL,
  `net_weight_unit` varchar(20) DEFAULT 'Kg',
  `chargeable_weight` varchar(50) DEFAULT NULL,
  `chargeable_weight_unit` varchar(20) DEFAULT 'Kg',
  `billing_type` varchar(50) DEFAULT NULL,
  `broker_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Booked',
  `payment_entry_status` varchar(50) NOT NULL DEFAULT 'Pending',
  `pod_doc_path` varchar(255) DEFAULT NULL,
  `pod_remarks` text DEFAULT NULL,
  `reporting_datetime` datetime DEFAULT NULL,
  `delivery_datetime` datetime DEFAULT NULL,
  `vehicle_payment_status` varchar(50) NOT NULL DEFAULT 'Pending',
  `created_by_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipments`
--

INSERT INTO `shipments` (`id`, `consignment_no`, `consignment_date`, `consignor_id`, `consignee_id`, `is_shipping_different`, `shipping_name`, `shipping_address`, `origin`, `destination`, `description_id`, `quantity`, `package_type`, `net_weight`, `net_weight_unit`, `chargeable_weight`, `chargeable_weight_unit`, `billing_type`, `broker_id`, `driver_id`, `vehicle_id`, `status`, `payment_entry_status`, `pod_doc_path`, `pod_remarks`, `reporting_datetime`, `delivery_datetime`, `vehicle_payment_status`, `created_by_id`, `branch_id`, `created_at`) VALUES
(1, '07597', '2025-10-03', 3, 5, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '840/00', 'Cartons/Pieces', '8512.919', 'Kg', '8512.919', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-04 10:20:44'),
(2, '07598', '2025-10-03', 3, 4, 0, NULL, NULL, 'Choutaki', 'Mankachar', 1, '895/00', 'Cartons/Pieces', '8512.121', 'Kg', '8512.121', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_2_1762427369.jpeg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-04 10:31:21'),
(3, '07599', '2025-10-04', 3, 18, 0, NULL, NULL, 'Choutaki', 'Hatsingimari', 1, '904/00', 'Cartons/Pieces', '8225.193', 'Kg', '8500.000', 'Kg', 'To be Billed', 3, 10, 5, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-04 11:51:28'),
(4, '07600', '2025-10-04', 3, 13, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '831/200', 'Cartons/Pieces', '8505.708', 'Kg', '8505.708', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_4_1762426448.jpeg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-04 13:30:01'),
(5, '08227', '2025-10-04', 21, 58, 0, NULL, NULL, 'Guwahati', 'Rangvamual ', 2, '1301', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 5, 6, 'Completed', 'Done', 'uploads/pod/pod_5_1762414571.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-04 17:37:45'),
(6, '08228', '2025-10-04', 21, 59, 0, NULL, NULL, 'Guwahati', 'Bawngkawn ', 2, '1288', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 2, 1, 'Completed', 'Done', 'uploads/1761903153_17619031244801597073595342154618.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-04 17:42:58'),
(7, '08229', '2025-10-04', 21, 42, 0, NULL, NULL, 'Guwahati', 'Silchar', 2, '1028', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, NULL, 11, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-04 18:09:08'),
(8, '08230', '2025-10-04', 21, 61, 0, NULL, NULL, 'Guwahati', 'Agartala', 2, '1798', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 6, 12, 'Completed', 'Done', 'uploads/pod/pod_8_1762416029.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-04 18:16:44'),
(9, '03816', '2025-10-04', 62, 63, 0, NULL, NULL, 'Silchar', 'Silchar', 2, '1457', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 4, 4, 'Completed', 'Done', 'uploads/pod/pod_9_1762418177.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-04 18:26:49'),
(10, '08231', '2025-10-04', 21, 39, 0, NULL, NULL, 'Guwahati', 'Karimganj', 3, '1399', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 1, 7, 'Completed', 'Done', 'uploads/pod/pod_10_1762598930.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-05 06:38:30'),
(11, '07601', '2025-10-06', 3, 15, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '826/00', 'Cartons/Pieces', '8259.622', 'Kg', '8259.622', 'Kg', 'To be Billed', 3, 10, 5, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-06 11:53:43'),
(12, '07602', '2025-10-06', 3, 12, 0, NULL, NULL, 'Choutaki', 'Williamnagar', 1, '860/00', 'Cartons/Pieces', '8295.102', 'Kg', '8500.000', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-06 11:56:29'),
(13, '07603', '2025-10-06', 3, 6, 0, NULL, NULL, 'Choutaki', 'Phulbari', 1, '948/00', 'Cartons/Pieces', '8248.758', 'Kg', '8500.000', 'Kg', 'To be Billed', 3, 8, 8, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-06 12:33:20'),
(14, '03821', '2025-10-06', 1, 55, 0, NULL, NULL, 'Gauripur ', 'North Lakhimpur', 3, '1679', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, NULL, 11, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-06 12:44:08'),
(15, '03818', '2025-10-06', 62, 30, 0, NULL, NULL, 'Silchar', 'Katigorah', 3, '1388', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 4, 4, 'Completed', 'Done', 'uploads/pod/pod_15_1762414063.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-06 15:17:18'),
(16, '03819', '2025-10-06', 62, 43, 0, NULL, NULL, 'Silchar', 'Vairengte', 3, '1830', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 7, 3, 'Completed', 'Done', 'uploads/pod/pod_16_1762417759.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-06 17:37:03'),
(17, '07604', '2025-10-07', 3, 65, 0, NULL, NULL, 'Choutaki', 'Srirampur ', 1, '1185/180', 'Cartons/Pieces', '11142.2', 'Kg', '11142.2', 'Kg', 'To be Billed', 4, NULL, 14, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-07 05:53:42'),
(18, '07605', '2025-10-07', 3, 16, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '818/00', 'Cartons/Pieces', '8261.265', 'Kg', '8261.265', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_18_1762426320.jpeg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-07 11:31:03'),
(19, '07606', '2025-10-07', 3, 4, 0, NULL, NULL, 'Choutaki', 'Mankachar', 1, '884/00', 'Cartons/Pieces', '8268.01', 'Kg', '8268.01', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-07 11:57:17'),
(20, '03820', '2025-10-07', 1, 66, 0, NULL, NULL, 'Gauripur ', 'Ladrymbai ', 3, '1421', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 3, 2, 'Completed', 'Done', 'uploads/pod/pod_20_1762415359.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-07 13:24:03'),
(21, '07607', '2025-10-08', 3, 13, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '793/7', 'Cartons/Pieces', '8282.044', 'Kg', '8282.044', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-08 12:02:32'),
(22, '03824', '2025-10-08', 1, 55, 0, NULL, NULL, 'Gauripur ', 'North Lakhimpur', 3, '1623', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 11, 10, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-08 12:13:17'),
(23, 'STC/UBPL/126', '2025-10-08', 67, 68, 0, NULL, NULL, 'Guwahati', 'Phuentsholing ', 3, '835', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 5, NULL, 15, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-08 13:04:32'),
(24, 'STC/UBPL/127', '2025-10-08', 67, 69, 0, NULL, NULL, 'Guwahati', 'Thimphu town ', 3, '336', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 6, NULL, 16, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-08 13:09:31'),
(25, '07608', '2025-10-09', 3, 70, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '662/1', 'Cartons/Pieces', '8215.443', 'Kg', '8215.443', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_25_1762426177.jpeg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-09 07:39:13'),
(26, '07609', '2025-10-09', 3, 11, 0, NULL, NULL, 'Choutaki', 'Tikrikilla', 1, '900/36', 'Cartons/Pieces', '8251.006', 'Kg', '8251.006', 'Kg', 'To be Billed', 3, 10, 5, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-09 12:35:53'),
(27, '07610', '2025-10-09', 3, 72, 0, NULL, NULL, 'Choutaki', 'Sapatgaram ', 1, '1212/15', 'Cartons/Pieces', '9006.717', 'Kg', '9006.717', 'Kg', 'To be Billed', 4, NULL, 17, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-09 13:48:38'),
(28, '03822', '2025-10-09', 62, 73, 0, NULL, NULL, 'Silchar', 'Udaipur', 3, '1430', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 7, 3, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-09 17:26:46'),
(29, '03823', '2025-10-09', 62, 74, 0, NULL, NULL, 'Silchar', 'Kumarghat', 3, '1555', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 4, 4, 'Completed', 'Done', 'uploads/pod/pod_29_1762417577.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-09 17:30:07'),
(30, '08232', '2025-10-09', 21, 75, 0, NULL, NULL, 'Guwahati', 'Karimganj', 3, '1210', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 3, 2, 'Completed', 'Done', 'uploads/pod/pod_30_1762415103.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-10 04:46:14'),
(31, '03827', '2025-10-10', 62, 36, 0, NULL, NULL, 'Silchar', 'Silchar', 3, '1351', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 6, 12, 'Completed', 'Done', 'uploads/pod/pod_31_1762416002.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-10 13:42:37'),
(32, '07611', '2025-10-10', 3, 8, 0, NULL, NULL, 'Choutaki', 'Barengapara', 1, '818', 'Cartons/Pieces', '8206.476', 'Kg', '8206.476', 'Kg', 'To be Billed', 3, 10, 5, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-10 14:06:11'),
(33, '07612', '2025-10-10', 3, 76, 0, NULL, NULL, 'Choutaki', 'Dudhnoi', 1, '1027/00', 'Cartons/Pieces', '9062.82', 'Kg', '9062.82', 'Kg', 'To be Billed', 4, NULL, 23, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-10 14:11:35'),
(34, '08233', '2025-10-10', 21, 28, 0, NULL, NULL, 'Guwahati', 'Saiha', 3, '1185', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 1, NULL, 24, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-10 14:22:19'),
(35, '03826', '2025-10-10', 1, 77, 0, NULL, NULL, 'Gauripur ', 'Dhubri', 3, '1367', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 5, NULL, 25, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-10 14:50:39'),
(36, '07613', '2025-10-10', 3, 78, 0, NULL, NULL, 'Choutaki', 'Bongaigaon', 1, '898.30', 'Cartons/Pieces', '9005.745', 'Kg', '9005.745', 'Kg', 'To be Billed', 4, NULL, 14, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-10 15:00:41'),
(37, '07614', '2025-10-10', 3, 79, 0, NULL, NULL, 'Choutaki', 'Bilasipara', 1, '1030/00', 'Cartons/Pieces', '9019.381', 'Kg', '9019.381', 'Kg', 'To be Billed', 4, NULL, 26, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-10 15:21:46'),
(38, '03825', '2025-10-10', 1, 80, 0, NULL, NULL, 'Gauripur ', 'Sonari', 3, '1533', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 1, 7, 'Completed', 'Done', 'uploads/pod/pod_38_1762599593.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-11 06:27:56'),
(39, '07615', '2025-10-11', 3, 4, 0, NULL, NULL, 'Choutaki', 'Mankachar', 1, '839/12', 'Cartons/Pieces', '8265.98', 'Kg', '8265.98', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_39_1762426258.jpeg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-11 12:17:32'),
(40, '03828', '2025-10-11', 62, 37, 0, NULL, NULL, 'Silchar', 'Silchar', 3, '879', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 6, 12, 'Completed', 'Done', 'uploads/pod/pod_40_1762415858.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-11 14:57:44'),
(41, '03829', '2025-10-11', 62, 81, 0, NULL, NULL, 'Silchar', 'Karimganj', 3, '1381', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 4, 4, 'Completed', 'Done', 'uploads/pod/pod_41_1762417978.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-12 09:09:40'),
(42, '07616', '2025-10-13', 3, 17, 0, NULL, NULL, 'Choutaki', 'Mahendraganj', 1, '878/15', 'Cartons/Pieces', '8227.769', 'Kg', '8227.769', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-13 11:13:22'),
(43, '07617', '2025-10-13', 3, 6, 0, NULL, NULL, 'Choutaki', 'Phulbari', 1, '921/00', 'Cartons/Pieces', '8241.532', 'Kg', '8241.532', 'Kg', 'To be Billed', 3, 10, 5, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-13 12:05:00'),
(44, '07618', '2025-10-13', 3, 5, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '814/00', 'Cartons/Pieces', '8214.416', 'Kg', '8214.416', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_44_1762426118.jpeg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-13 12:13:48'),
(45, '03830', '2025-10-13', 1, 82, 0, NULL, NULL, 'Gauripur ', 'Williamnagar', 3, '1513', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 7, NULL, 27, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-13 12:56:00'),
(46, '010024', '2025-10-14', 83, 84, 0, NULL, NULL, 'Azara', 'Biswanath Charali', 4, '1213', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, NULL, 11, 'Delivered', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-14 04:30:02'),
(47, '07619', '2025-10-14', 3, 15, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '825/00', 'Cartons/Pieces', '8247.295', 'Kg', '8247.295', 'Kg', 'To be Billed', 3, 10, 5, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-14 09:40:10'),
(48, '07620', '2025-10-14', 3, 13, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '1051/00', 'Cartons/Pieces', '11064.791', 'Kg', '11064.791', 'Kg', 'To be Billed', 8, NULL, 28, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-14 11:36:48'),
(49, 'STC/UBPL/128', '2025-10-14', 67, 85, 0, NULL, NULL, 'Guwahati', 'Siliguri ', 3, '202', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 6, NULL, 29, 'Delivered', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-14 11:53:15'),
(50, 'STC/UBPL/129', '2025-10-14', 67, 86, 0, NULL, NULL, 'Guwahati', 'Singimari ', 3, '365', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 6, NULL, 29, 'Delivered', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-14 12:02:27'),
(51, '08234', '2025-10-14', 21, 22, 0, NULL, NULL, 'Guwahati', 'Lunglei', 3, '1285', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, NULL, 30, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-14 13:40:39'),
(52, '07621', '2025-10-14', 3, 78, 0, NULL, NULL, 'Choutaki', 'Bongaigaon', 1, '813/540', 'Cartons/Pieces', '9075.031', 'Kg', '9075.031', 'Kg', 'To be Billed', 4, NULL, 14, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-14 13:47:20'),
(53, '07622', '2025-10-14', 3, 65, 0, NULL, NULL, 'Choutaki', 'Srirampur ', 1, '1188/19', 'Cartons/Pieces', '11679.944', 'Kg', '11679.944', 'Kg', 'To be Billed', 4, NULL, 31, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-14 15:13:28'),
(54, '07623', '2025-10-14', 3, 18, 0, NULL, NULL, 'Choutaki', 'Hatsingimari', 1, '925/00', 'Cartons/Pieces', '8208.412', 'Kg', '8208.412', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-14 15:16:37'),
(55, '03833', '2025-10-14', 1, 87, 0, NULL, NULL, 'Gauripur ', 'Krishnai ', 3, '1575', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 11, 10, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-14 16:02:30'),
(56, '03831', '2025-10-14', 62, 81, 0, NULL, NULL, 'Silchar', 'Karimganj', 3, '1492', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 6, 12, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-15 03:40:12'),
(57, '07624', '2025-10-15', 3, 4, 0, NULL, NULL, 'Choutaki', 'Mankachar', 1, '910/00', 'Cartons/Pieces', '8274.85', 'Kg', '8274.85', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_57_1762425766.jpeg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-15 05:31:36'),
(58, '07625', '2025-10-15', 3, 88, 0, NULL, NULL, 'Choutaki', 'Sapatgaram ', 1, '907/00', 'Cartons/Pieces', '9053.96', 'Kg', '9053.96', 'Kg', 'To be Billed', 4, NULL, 32, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-15 12:56:13'),
(59, '07626', '2025-10-15', 3, 89, 0, NULL, NULL, 'Choutaki', 'Howly ', 1, '986/00', 'Cartons/Pieces', '9044.14', 'Kg', '9044.14', 'Kg', 'To be Billed', 4, NULL, 33, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-15 12:59:57'),
(60, '08235', '2025-10-15', 21, 73, 0, NULL, NULL, 'Guwahati', 'Udaipur', 3, '1604', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 1, 7, 'Completed', 'Done', 'uploads/pod/pod_60_1762598888.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-15 15:21:31'),
(61, '03832', '2025-10-15', 62, 90, 0, NULL, NULL, 'Silchar', 'Champaknagar ', 3, '1296', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 4, 4, 'Completed', 'Done', 'uploads/pod/pod_61_1762417945.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-15 17:28:43'),
(62, '07627', '2025-10-16', 3, 79, 0, NULL, NULL, 'Choutaki', 'Bilasipara', 1, '998/11', 'Cartons/Pieces', '9020.003', 'Kg', '9020.003', 'Kg', 'To be Billed', 4, NULL, 34, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-16 12:30:12'),
(63, '07628', '2025-10-16', 3, 91, 0, NULL, NULL, 'Choutaki', 'Lakhipur ', 1, '1026/00', 'Cartons/Pieces', '9027.335', 'Kg', '9027.335', 'Kg', 'To be Billed', 4, NULL, 23, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-16 12:41:48'),
(64, '03836', '2025-10-16', 1, 50, 0, NULL, NULL, 'Gauripur ', 'Sonari', 3, '1675', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 3, 2, 'Completed', 'Done', 'uploads/pod/pod_64_1762415312.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-16 13:03:49'),
(65, '08236', '2025-10-16', 21, 92, 0, NULL, NULL, 'Guwahati', 'Dharmanagar ', 3, '1396', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 2, 1, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-16 17:07:56'),
(66, '08237', '2025-10-16', 21, 94, 0, NULL, NULL, 'Guwahati', 'Kolasib', 3, '1344', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 5, 6, 'Completed', 'Done', 'uploads/pod/pod_66_1762414604.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-17 02:26:50'),
(67, '08238', '2025-10-16', 21, 40, 0, NULL, NULL, 'Guwahati', 'Silchar', 3, '1602', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, NULL, 11, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-17 02:29:54'),
(68, '03834', '2025-10-16', 62, 41, 0, NULL, NULL, 'Guwahati', 'Bhaga Bazar ', 3, '1192', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 6, 12, 'Completed', 'Done', 'uploads/pod/pod_68_1762415809.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-17 02:34:20'),
(69, '07629', '2025-10-17', 3, 6, 0, NULL, NULL, 'Choutaki', 'Phulbari', 1, '1002/00', 'Cartons/Pieces', '8239.878', 'Kg', '8239.878', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_69_1762426061.jpeg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-17 10:43:04'),
(70, '03835', '2025-10-16', 62, 96, 0, NULL, NULL, 'Silchar', 'Kalachhara ', 3, '1418', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 7, 3, 'Completed', 'Done', 'uploads/pod/pod_70_1762417825.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-17 11:17:46'),
(71, '03837', '2025-10-17', 1, 48, 0, NULL, NULL, 'Gauripur ', 'Sivsagar ', 3, '1752', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, NULL, 11, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-18 09:55:38'),
(72, '03841', '2025-10-18', 1, 77, 0, NULL, NULL, 'Gauripur ', 'Dhubri', 3, '1429', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 5, NULL, 35, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-18 17:59:29'),
(73, '03839', '2025-10-18', 62, 97, 0, NULL, NULL, 'Silchar', 'Gandacherra ', 3, '1367', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 6, 12, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-18 18:02:32'),
(74, '03838', '2025-10-18', 62, 98, 0, NULL, NULL, 'Silchar', 'Abdullapur ', 3, '1424', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 4, 4, 'Completed', 'Done', 'uploads/pod/pod_74_1762415834.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-18 18:05:43'),
(75, '03840', '2025-10-19', 1, 49, 0, NULL, NULL, 'Gauripur ', 'Moranhat', 2, '1097', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 11, 10, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-20 08:47:22'),
(76, '07630', '2025-10-21', 3, 16, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '797/00', 'Cartons/Pieces', '8258.438', 'Kg', '8258.438', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_76_1762420216.jpeg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-21 11:45:34'),
(77, '07631', '2025-10-21', 3, 12, 0, NULL, NULL, 'Choutaki', 'Williamnagar', 1, '778/00', 'Cartons/Pieces', '8254.946', 'Kg', '8254.946', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-21 12:37:23'),
(78, '07632', '2025-10-22', 3, 9, 0, NULL, NULL, 'Choutaki', 'Garobadha', 1, '959/00', 'Cartons/Pieces', '8202.586', 'Kg', '8202.586', 'Kg', 'To be Billed', 3, 10, 5, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-22 10:45:57'),
(79, '07633', '2025-10-22', 3, 5, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '813/00', 'Cartons/Pieces', '8201.7', 'Kg', '8201.7', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-22 12:28:49'),
(80, '03843', '2025-10-22', 1, 99, 0, NULL, NULL, 'Gauripur ', 'Baghmara', 3, '1515', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 7, NULL, 36, 'Completed', 'Done', 'uploads/pod/pod_80_1762764497.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-22 14:43:11'),
(81, '08239', '2025-10-22', 21, 100, 0, NULL, NULL, 'Guwahati', 'Tulamura ', 3, '1362', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 1, 7, 'Completed', 'Done', 'uploads/pod/pod_81_1762599274.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-22 15:25:45'),
(82, '08240', '2025-10-22', 21, 73, 0, NULL, NULL, 'Guwahati', 'Udaipur', 3, '239', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 1, 7, 'Completed', 'Done', 'uploads/pod/pod_82_1762599233.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-22 15:28:06'),
(83, '03842', '2025-10-22', 62, 101, 0, NULL, NULL, 'Silchar', 'Hailakandi ', 3, '1312', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 6, 12, 'Completed', 'Done', 'uploads/pod/pod_83_1762415773.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-22 15:52:52'),
(84, '07634', '2025-10-23', 3, 4, 0, NULL, NULL, 'Choutaki', 'Mankachar', 1, '838/00', 'Cartons/Pieces', '8255.87', 'Kg', '8255.87', 'Kg', 'To be Billed', 3, 8, 8, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-23 07:34:41'),
(85, '07635', '2025-10-23', 3, 65, 0, NULL, NULL, 'Choutaki', 'Srirampur ', 1, '1428/00', 'Cartons/Pieces', '11789.26', 'Kg', '11789.26', 'Kg', 'To be Billed', 4, NULL, 14, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-23 12:45:04'),
(86, '08242', '2025-10-23', 21, 23, 0, NULL, NULL, 'Guwahati', 'Belonia', 3, '1433', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 5, 6, 'Completed', 'Done', 'uploads/pod/pod_86_1762414497.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-23 15:16:47'),
(87, '08241', '2025-10-23', 21, 57, 0, NULL, NULL, 'Guwahati', 'Khowai', 3, '1410', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 2, 1, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-23 16:14:25'),
(88, '08243', '2025-10-23', 21, 75, 0, NULL, NULL, 'Guwahati', 'Karimganj', 3, '1180', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, NULL, 11, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-23 16:16:51'),
(89, '07636', '2025-10-24', 3, 6, 0, NULL, NULL, 'Choutaki', 'Phulbari', 1, '976/00', 'Cartons/Pieces', '8285.64', 'Kg', '8285.64', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-24 09:45:26'),
(90, '07637', '2025-10-24', 3, 15, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '826/13', 'Cartons/Pieces', '8235.973', 'Kg', '8235.973', 'Kg', 'To be Billed', 3, 10, 5, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-24 10:27:59'),
(91, '03844', '2025-10-24', 62, 33, 0, NULL, NULL, 'Silchar', 'Dharmanagar ', 3, '1240', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 6, 12, 'Completed', 'Done', 'uploads/pod/pod_91_1762415746.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-24 13:08:46'),
(92, '07638', '2025-10-24', 3, 8, 0, NULL, NULL, 'Choutaki', 'Barengapara', 1, '847/00', 'Cartons/Pieces', '8205.089', 'Kg', '8205.089', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_92_1762425972.jpeg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-24 13:42:45'),
(93, '03845', '2025-10-25', 1, 48, 0, NULL, NULL, 'Gauripur ', 'Sivsagar ', 3, '1565', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 11, 10, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-25 10:33:51'),
(94, '07639', '2025-10-25', 3, 88, 0, NULL, NULL, 'Choutaki', 'Sapatgaram ', 1, '809/20', 'Cartons/Pieces', '9011.334', 'Kg', '9011.334', 'Kg', 'To be Billed', 4, NULL, 37, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-25 11:35:03'),
(95, '07640', '2025-10-25', 3, 56, 0, NULL, NULL, 'Choutaki', 'Bilasipara', 1, '1000/30', 'Cartons/Pieces', '9012.264', 'Kg', '9012.264', 'Kg', 'To be Billed', 4, NULL, 38, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-25 11:42:15'),
(96, '08244', '2025-10-25', 21, 26, 0, NULL, NULL, 'Guwahati', 'Lunglei', 3, '1245', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 9, NULL, 39, 'Completed', 'Done', 'uploads/pod/pod_96_1762414337.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-25 11:56:17'),
(97, '07641', '2025-10-25', 3, 18, 0, NULL, NULL, 'Choutaki', 'Hatsingimari', 1, '926/00', 'Cartons/Pieces', '8246.431', 'Kg', '8246.431', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-25 12:15:21'),
(98, '08245', '2025-10-25', 21, 26, 0, NULL, NULL, 'Guwahati', 'Lunglei', 3, '1100', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 1, NULL, 24, 'Completed', 'Done', 'uploads/pod/pod_98_1762414142.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-25 13:00:20'),
(99, '03846', '2025-10-25', 1, 102, 0, NULL, NULL, 'Gauripur ', 'Gohpur ', 2, '1520', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 4, 4, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-26 03:08:15'),
(100, '03847', '2025-10-25', 1, 2, 0, NULL, NULL, 'Gauripur ', 'Silchar', 3, '2946', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 3, 2, 'Completed', 'Done', 'uploads/pod/pod_100_1762415274.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-26 03:11:17'),
(101, '07642', '2025-10-27', 3, 18, 0, NULL, NULL, 'Choutaki', 'Hatsingimari', 1, '955/000', 'Cartons/Pieces', '8250.72', 'Kg', '8250.72', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_101_1762423799.jpeg', '', NULL, NULL, 'Pending', 5, 2, '2025-10-27 11:30:03'),
(102, '07643', '2025-10-27', 3, 4, 0, NULL, NULL, 'Choutaki', 'Mankachar', 1, '856/000', 'Cartons/Pieces', '8262.75', 'Kg', '8262.75', 'Kg', 'To be Billed', 3, 10, 5, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 5, 2, '2025-10-27 11:34:03'),
(103, '07644', '2025-10-27', 3, 19, 0, NULL, NULL, 'Choutaki', 'Mendipathar', 1, '770/000', 'Cartons/Pieces', '8205.413', 'Kg', '8205.413', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 5, 2, '2025-10-27 13:19:59'),
(104, '07645', '2025-10-27', 3, 103, 0, NULL, NULL, 'Choutaki', 'Mendipathar', 1, '770/00', 'Cartons/Pieces', '8205.413', 'Kg', '8205.413', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-27 13:27:08'),
(105, '03848', '2025-10-27', 62, 35, 0, NULL, NULL, 'Silchar', 'Kakraban', 3, '1345', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 6, 12, 'Completed', 'Done', 'uploads/pod/pod_105_1762415704.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-27 14:37:57'),
(106, '08247', '2025-10-27', 21, 42, 0, NULL, NULL, 'Guwahati', 'Silchar', 3, '1150', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 4, 4, 'Completed', 'Done', 'uploads/pod/pod_106_1762413879.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-27 14:57:27'),
(107, '08246', '2025-10-27', 21, 22, 0, NULL, NULL, 'Guwahati', 'Lunglei', 3, '1400', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 1, NULL, 40, 'Completed', 'Done', 'uploads/pod/pod_107_1762585961.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-27 15:00:19'),
(108, '08250', '2025-10-28', 21, 22, 0, NULL, NULL, 'Guwahati', 'Lunglei', 3, '1175', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 1, NULL, 41, 'Completed', 'Done', 'uploads/pod/pod_108_1762413808.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-28 11:08:13'),
(109, '07646', '2025-10-28', 3, 104, 0, NULL, NULL, 'Choutaki', 'Mankachar', 1, '706/00', 'Cartons/Pieces', '8251.81', 'Kg', '8251.81', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-28 12:14:41'),
(110, '08249', '2025-10-28', 21, 39, 0, NULL, NULL, 'Guwahati', 'Karimganj', 3, '1655', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 2, NULL, 42, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-28 12:23:05'),
(111, '07647', '2025-10-28', 3, 13, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '757/00', 'Cartons/Pieces', '8239.665', 'Kg', '8239.665', 'Kg', 'To be Billed', 3, 10, 5, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-28 12:43:15'),
(112, '07648', '2025-10-28', 3, 17, 0, NULL, NULL, 'Choutaki', 'Mahendraganj', 1, '904/00', 'Cartons/Pieces', '8269.64', 'Kg', '8269.64', 'Kg', 'To be Billed', 3, 8, 8, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-28 12:59:13'),
(113, '07649', '2025-10-28', 3, 65, 0, NULL, NULL, 'Choutaki', 'Srirampur ', 1, '852/00', 'Cartons/Pieces', '11043.984', 'Kg', '11043.984', 'Kg', 'To be Billed', 4, NULL, 43, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-28 13:38:59'),
(114, '08248', '2025-10-28', 21, 105, 0, NULL, NULL, 'Guwahati', 'Nalchar ', 3, '1360', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 1, 7, 'Completed', 'Done', 'uploads/pod/pod_114_1762599155.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-29 07:14:23'),
(115, '03849', '2025-10-27', 1, 80, 0, NULL, NULL, 'Gauripur ', 'Sonari', 3, '1729', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, NULL, 11, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-29 07:25:29'),
(116, '03850', '2025-10-29', 1, 106, 0, NULL, NULL, 'Gauripur ', 'Lawngtlai ', 3, '1502', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 2, 1, 'Completed', 'Done', 'uploads/pod/pod_116_1762416949.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-29 11:08:29'),
(117, '07650', '2025-10-29', 3, 11, 0, NULL, NULL, 'Choutaki', 'Tikrikilla', 1, '888/00', 'Cartons/Pieces', '8271.909', 'Kg', '8271.909', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-29 11:35:01'),
(118, '08251', '2025-10-30', 21, 26, 0, NULL, NULL, 'Guwahati', 'Lunglei', 3, '1250', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 1, NULL, 44, 'Completed', 'Done', 'uploads/pod/pod_118_1762413275.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-30 10:46:18'),
(119, '07651', '2025-10-30', 3, 12, 0, NULL, NULL, 'Choutaki', 'Williamnagar', 1, '781/108', 'Cartons/Pieces', '8271.871', 'Kg', '8271.871', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-30 11:13:17'),
(120, '07652', '2025-10-30', 3, 6, 0, NULL, NULL, 'Choutaki', 'Phulbari', 1, '969/00', 'Cartons/Pieces', '8263.045', 'Kg', '8263.045', 'Kg', 'To be Billed', 3, 10, 5, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-30 11:20:29'),
(121, '07653', '2025-10-30', 3, 8, 0, NULL, NULL, 'Choutaki', 'Barengapara', 1, '757/72', 'Cartons/Pieces', '8219.493', 'Kg', '8219.493', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_121_1762759811.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-30 13:39:49'),
(122, '03851', '2025-10-30', 62, 36, 0, NULL, NULL, 'Silchar', 'Silchar', 3, '1340', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 3, 2, 'Completed', 'Done', 'uploads/pod/pod_122_1762415247.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-31 03:27:33'),
(123, '03852', '2025-10-30', 62, 33, 0, NULL, NULL, 'Silchar', 'Dharmanagar', 3, '1389', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 6, 12, 'Completed', 'Done', 'uploads/pod/pod_123_1762416087.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-31 03:30:53'),
(124, '03854', '2025-10-30', 1, 107, 0, NULL, NULL, 'Gauripur ', 'Kokrajhar ', 3, '1557', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 5, NULL, 45, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-31 03:35:05'),
(125, '07654', '2025-10-31', 3, 7, 0, NULL, NULL, 'Choutaki', 'Rongram', 1, '991/18', 'Cartons/Pieces', '8211.882', 'Kg', '8211.882', 'Kg', 'To be Billed', 3, 10, 5, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-31 13:16:40'),
(126, '07655', '2025-10-31', 3, 16, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '741/00', 'Cartons/Pieces', '8276.656', 'Kg', '8276.656', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-10-31 13:19:25'),
(127, '07656', '2025-10-31', 3, 76, 0, NULL, NULL, 'Choutaki', 'Dudhnoi', 1, '1115/43', 'Cartons/Pieces', '9554.169', 'Kg', '9554.169', 'Kg', 'To be Billed', 4, NULL, 23, 'Completed', 'Done', 'uploads/pod/pod_127_1762415044.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-31 13:30:54'),
(128, '07657', '2025-10-31', 3, 108, 0, NULL, NULL, 'Choutaki', 'Barpeta town ', 1, '1021/00', 'Cartons/Pieces', '9045.012', 'Kg', '9045.012', 'Kg', 'To be Billed', 4, NULL, 33, 'Completed', 'Done', 'uploads/pod/pod_128_1762414920.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-31 13:55:29'),
(129, '07658', '2025-10-31', 3, 65, 0, NULL, NULL, 'Choutaki', 'Srirampur ', 1, '1830/23', 'Cartons/Pieces', '16591.435', 'Kg', '16591.435', 'Kg', 'To be Billed', 4, NULL, 46, 'Completed', 'Done', 'uploads/pod/pod_129_1762414869.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-10-31 15:25:08'),
(130, '03855', '2025-10-31', 62, 31, 0, NULL, NULL, 'Silchar', 'Belonia', 3, '1349', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 3, 2, 'Completed', 'Done', 'uploads/pod/pod_130_1762415191.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-01 05:47:19'),
(131, '03853', '2025-10-31', 1, 109, 0, NULL, NULL, 'Gauripur ', 'Gohpur ', 2, '1670', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 11, 10, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-01 05:51:09'),
(132, '07659', '2025-10-31', 3, 4, 0, NULL, NULL, 'Choutaki', 'Mankachar', 1, '827/00', 'Cartons/Pieces', '8331.695', 'Kg', '8331.695', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_132_1762424006.jpeg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-01 06:14:38'),
(133, '07660', '2025-10-31', 3, 110, 0, NULL, NULL, 'Choutaki', 'Bilasipara', 1, '989/27', 'Cartons/Pieces', '9046.423', 'Kg', '9046.423', 'Kg', 'To be Billed', 4, NULL, 47, 'Completed', 'Done', 'uploads/pod/pod_133_1762415018.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-01 11:23:30'),
(134, '08252', '2025-11-01', 21, 26, 0, NULL, NULL, 'Guwahati', 'Lunglei', 3, '1140', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 1, NULL, 48, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-01 12:37:28'),
(135, '07661', '2025-11-02', 3, 4, 0, NULL, NULL, 'Choutaki', 'Mankachar', 1, '868/52', 'Cartons/Pieces', '8261.326', 'Kg', '8261.326', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-02 07:01:45'),
(136, '07662', '2025-11-02', 3, 5, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '775/29', 'Cartons/Pieces', '8258.962', 'Kg', '8258.962', 'Kg', 'To be Billed', 3, 10, 5, 'Completed', 'Done', 'uploads/pod/pod_136_1762497362.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-02 07:06:23'),
(137, 'STC/UBPL/130', '2025-11-03', 67, 85, 0, NULL, NULL, 'Guwahati', 'Siliguri ', 3, '650', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 5, NULL, 49, 'Completed', 'Done', 'uploads/pod/pod_137_1762339484.jpeg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-03 11:09:30'),
(138, '03856', '2025-11-03', 62, 30, 0, NULL, NULL, 'Silchar', 'Katigorah', 3, '1245', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 4, 4, 'Completed', 'Done', 'uploads/pod/pod_138_1762419320.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-03 12:11:39'),
(139, '08253', '2025-11-03', 21, 26, 0, NULL, NULL, 'Guwahati', 'Lunglei', 3, '1200', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 1, NULL, 51, 'In Transit', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-03 14:00:23'),
(140, '02102', '2025-11-01', 111, 112, 0, NULL, NULL, 'JAMURIA', 'GHAZIABAD', 5, 'LOOSE', 'Loose', '41.800', 'Ton', '41.800', 'Ton', 'To be Billed', 10, NULL, 52, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 4, 3, '2025-11-04 12:17:27'),
(141, '07663', '2025-11-04', 3, 6, 0, NULL, NULL, 'Choutaki', 'Phulbari', 1, '943', 'Cartons/Pieces', '8225.943', 'Kg', '8225.943', 'Kg', 'To be Billed', 3, 9, 9, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-04 13:31:44'),
(142, '07664', '2025-11-04', 3, 18, 0, NULL, NULL, 'Choutaki', 'Hatsingimari', 1, '906/00', 'Cartons/Pieces', '8220.55', 'Kg', '8220.25', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_142_1762425601.jpeg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-04 13:32:20'),
(143, '02103', '2025-11-01', 116, 120, 1, 'NAINI PAPERS LTD.', 'MORADABAD ROAD,KASHIPUR-UTTARAKHAND', 'BARJORA', 'KASHIPUR', 5, '', 'Loose', '41.180', 'Ton', '41.180', 'Ton', 'To be Billed', 14, NULL, 53, 'Delivered', 'Reverify', NULL, NULL, NULL, NULL, 'Pending', 4, 3, '2025-11-04 13:41:47'),
(144, '02104', '2025-11-01', 115, 112, 0, NULL, NULL, 'Durgapur', 'GHAZIABAD', 5, '', 'Loose', '42.950', 'Ton', '42.950', 'Ton', 'To be Billed', 10, NULL, 54, 'Delivered', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 4, 3, '2025-11-04 13:45:40'),
(145, '02105', '2025-11-01', 116, 121, 0, NULL, NULL, 'Durgapur', 'FARIDABAD', 5, '', 'Loose', '41.460', 'Ton', '41.460', 'Ton', 'To Pay', 15, NULL, 55, 'Delivered', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 4, 3, '2025-11-04 13:49:35'),
(146, '02106', '2025-11-02', 115, 112, 0, NULL, NULL, 'JAMURIA', 'GHAZIABAD', 5, '', 'Loose', '42.080', 'Ton', '42.080', 'Ton', 'To Pay', 16, NULL, 56, 'Delivered', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 4, 3, '2025-11-04 13:53:21'),
(147, '02107', '2025-11-03', 116, 117, 0, NULL, NULL, 'BARJORA', 'GHAZIABAD', 9, '', 'Loose', '42.280', 'Ton', '42.280', 'Ton', 'To be Billed', 12, NULL, 57, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 4, 3, '2025-11-04 13:57:02'),
(148, '02108', '2025-11-04', 115, 112, 0, NULL, NULL, 'JAMURIA', 'GHAZIABAD', 5, '', 'Loose', '42.710', 'Ton', '42.710', 'Ton', 'To be Billed', 10, NULL, 58, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-04 15:06:19'),
(149, '02109', '2025-11-04', 113, 114, 0, NULL, NULL, 'BURNPUR', 'Bangalore', 10, '', 'Loose', '33.130', 'Ton', '33.130', 'Ton', 'To be Billed', 11, NULL, 60, 'Booked', 'Reverify', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-04 15:23:22'),
(150, '02110', '2025-11-04', 113, 114, 0, NULL, NULL, 'BURNPUR', 'Bangalore', 10, '', 'Loose', '33.180', 'Ton', '33.180', 'Ton', 'To be Billed', 11, NULL, 61, 'Booked', 'Reverify', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-04 15:29:50'),
(151, '02111', '2025-11-04', 113, 114, 0, NULL, NULL, 'BURNPUR', 'Bangalore', 10, '', 'Loose', '33.250', 'Ton', '33.250', 'Ton', 'To be Billed', 11, NULL, 62, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-04 15:38:48'),
(152, '03857', '2025-11-04', 62, 74, 0, NULL, NULL, 'Silchar', 'Kumarghat', 3, '1521', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 4, 4, 'Completed', 'Done', 'uploads/pod/pod_152_1762413220.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-05 02:55:26'),
(153, '08254', '2025-11-04', 21, 25, 0, NULL, NULL, 'Guwahati', 'Santirbazar', 3, '1378', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 6, 12, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-05 02:58:17'),
(154, '07665', '2025-11-05', 3, 15, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '792/00', 'Cartons/Pieces', '8284.302', 'Kg', '8284.302', 'Kg', 'To be Billed', 3, 9, 9, 'Completed', 'Done', 'uploads/pod/pod_154_1762496203.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-05 12:06:36'),
(155, '02112', '2025-11-05', 113, 114, 0, NULL, NULL, 'BURNPUR', 'Bangalore', 10, '', 'Loose', '33.340', 'Ton', '33.340', 'Ton', 'To be Billed', 17, NULL, 63, 'Booked', 'Reverify', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-05 12:13:42'),
(156, '02113', '2025-11-05', 113, 114, 0, NULL, NULL, 'BURNPUR', 'Bangalore', 10, '', 'Loose', '32.760', 'Ton', '32.760', 'Ton', 'To be Billed', 11, NULL, 64, 'Booked', 'Reverify', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-05 12:20:56'),
(157, '03860', '2025-11-05', 1, 125, 0, NULL, NULL, 'Gauripur ', 'Tinsukia', 3, '1780', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 5, 6, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-05 13:54:26'),
(158, '03859', '2025-11-05', 1, 102, 0, NULL, NULL, 'Gauripur ', 'Gohpur ', 3, '1670', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, NULL, 11, 'Completed', 'Done', 'uploads/pod/pod_158_1762496795.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-05 13:57:25'),
(159, '07666', '2025-11-05', 3, 13, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '779/70', 'Cartons/Pieces', '8249.986', 'Kg', '8249.986', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Done', 'uploads/pod/pod_159_1762496235.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-05 14:06:29'),
(160, '03858', '2025-11-06', 1, 126, 0, NULL, NULL, 'Gauripur ', 'Kamalabari', 3, '1789', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 11, 10, 'Delivered', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-06 04:15:45'),
(161, '07667', '2025-11-06', 3, 91, 0, NULL, NULL, 'Choutaki', 'Lakhipur ', 1, '977/00', 'Cartons/Pieces', '9011.65', 'Kg', '9011.65', 'Kg', 'To be Billed', 4, NULL, 23, 'Completed', 'Done', 'uploads/pod/pod_161_1762500501.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-06 12:17:32'),
(162, '07668', '2025-11-06', 3, 8, 0, NULL, NULL, 'Choutaki', 'Barengapara', 1, '822/00', 'Cartons/Pieces', '8275.374', 'Kg', '8275.374', 'Kg', 'To be Billed', 3, 10, 5, 'Completed', 'Done', 'uploads/pod/pod_162_1762497258.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-06 12:30:04'),
(163, '02116', '2025-11-06', 113, 114, 0, NULL, NULL, 'BURNPUR', 'Bangalore', 10, '', 'Loose', '33.310', 'Ton', '33.310', 'Ton', 'To be Billed', 20, NULL, 65, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-06 12:57:57'),
(164, '02117', '2025-11-06', 113, 114, 0, NULL, NULL, 'BURNPUR', 'Bangalore', 10, '', 'Loose', '33.010', 'Ton', '33.010', 'Ton', 'To be Billed', 20, NULL, 66, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-06 13:04:07'),
(165, '02118', '2025-11-06', 113, 114, 0, NULL, NULL, 'BURNPUR', 'Bangalore', 10, '', 'Loose', '34.250', 'Ton', '34.250', 'Ton', 'To be Billed', 20, NULL, 67, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-06 13:24:36'),
(166, '02119', '2025-11-07', 113, 114, 0, NULL, NULL, 'BURNPUR', 'Bangalore', 10, '', 'Loose', '33.300', 'Ton', '33.300', 'Ton', 'To be Billed', 23, NULL, 68, 'Booked', 'Reverify', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-07 11:20:40'),
(167, '02120', '2025-11-07', 113, 114, 0, NULL, NULL, 'BURNPUR', 'Bangalore', 10, '', 'Loose', '33.310', 'Ton', '33.310', 'Ton', 'To be Billed', 11, NULL, 69, 'Booked', 'Reverify', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-07 11:28:41'),
(168, '02115', '2025-11-05', 116, 127, 0, NULL, NULL, 'BARJORA', 'BHIWADI', 5, '', 'Loose', '35.240', 'Ton', '35.240', 'Ton', 'To be Billed', 24, NULL, 70, 'Delivered', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-07 12:06:35'),
(169, '07669', '2025-11-07', 3, 72, 0, NULL, NULL, 'Choutaki', 'Sapatgaram ', 1, '1062/00', 'Cartons/Pieces', '9056.348', 'Kg', '9056.348', 'Kg', 'To be Billed', 4, NULL, 34, 'Completed', 'Done', 'uploads/pod/pod_169_1762581528.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-07 12:18:29'),
(170, '07670', '2025-11-07', 3, 65, 0, NULL, NULL, 'Choutaki', 'Srirampur ', 1, '1051/30', 'Cartons/Pieces', '11004.444', 'Kg', '11004.444', 'Kg', 'To be Billed', 4, NULL, 33, 'Completed', 'Done', 'uploads/pod/pod_170_1762581573.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-07 12:53:42'),
(171, '02121', '2025-11-07', 113, 114, 0, NULL, NULL, 'BURNPUR', 'Bangalore', 10, '', 'Loose', '34.350', 'Ton', '34.350', 'Ton', 'To be Billed', 23, NULL, 71, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-07 13:13:47'),
(172, '03861', '2025-11-07', 62, 37, 0, NULL, NULL, 'Silchar', 'Silchar', 3, '849', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 4, 4, 'Completed', 'Done', 'uploads/pod/pod_172_1762595046.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-08 03:02:42'),
(173, '08255', '2025-11-07', 21, 40, 0, NULL, NULL, 'Guwahati', 'Silchar', 3, '1683', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 1, 7, 'In Transit', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-08 06:07:24'),
(174, '03862', '2025-11-07', 1, 128, 0, NULL, NULL, 'Guwahati', 'Ladrymbai ', 3, '1490', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 3, 2, 'Completed', 'Pending', 'uploads/pod/pod_174_1762769430.jpg', '', NULL, NULL, 'Pending', 8, 2, '2025-11-08 09:53:39'),
(175, '07671', '2025-11-08', 3, 11, 0, NULL, NULL, 'Choutaki', 'Tikrikilla', 1, '919/00', 'Cartons/Pieces', '8204.271', 'Kg', '8204.271', 'Kg', 'To be Billed', 3, 8, 8, 'Completed', 'Pending', 'uploads/pod/pod_175_1762759858.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-08 10:36:55'),
(176, '07672', '2025-11-08', 3, 79, 0, NULL, NULL, 'Choutaki', 'Bilasipara', 1, '989/00', 'Cartons/Pieces', '9044.49', 'Kg', '9044.49', 'Kg', 'To be Billed', 4, NULL, 72, 'Completed', 'Pending', 'uploads/pod/pod_176_1762759901.jpg', '', NULL, NULL, 'Pending', 8, 2, '2025-11-08 10:49:05'),
(177, '08256', '2025-11-08', 21, 58, 0, NULL, NULL, 'Guwahati', 'Aizawl', 2, '1418', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 25, NULL, 44, 'In Transit', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 8, 2, '2025-11-08 11:51:27'),
(178, '02122', '2025-11-07', 115, 112, 0, NULL, NULL, 'JAMURIA', 'GHAZIABAD', 5, '', 'Loose', '43.160', 'Ton', '43.160', 'Ton', 'To be Billed', 10, NULL, 73, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-08 12:14:43'),
(179, '02123', '2025-11-07', 115, 112, 0, NULL, NULL, 'JAMURIA', 'GHAZIABAD', 5, '', 'Loose', '42.650', 'Ton', '42.650', 'Ton', 'To be Billed', 10, NULL, 74, 'Booked', 'Reverify', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-08 12:17:31'),
(180, '07673', '2025-11-08', 3, 129, 0, NULL, NULL, 'Choutaki', 'Mendipathar', 1, '787/00', 'Cartons/Pieces', '8256.041', 'Kg', '8256.041', 'Kg', 'To be Billed', 3, 10, 5, 'Delivered', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 8, 2, '2025-11-08 12:38:18'),
(181, '02124', '2025-11-09', 113, 114, 0, NULL, NULL, 'BURNPUR', 'Bangalore', 10, '', 'Loose', '32.340', 'Ton', '32.340', 'Ton', 'To be Billed', 18, NULL, 75, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-09 13:40:06'),
(182, '02125', '2025-11-09', 113, 114, 0, NULL, NULL, 'BURNPUR', 'Bangalore', 10, '', 'Loose', '32.230', 'Ton', '32.230', 'Ton', 'To be Billed', 18, NULL, 76, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-09 13:50:45'),
(183, '03863', '2025-11-09', 62, 43, 0, NULL, NULL, 'Silchar', 'Vairengte', 3, '2265', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 2, 1, 'Completed', 'Pending', 'uploads/pod/pod_183_1762848388.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-10 02:56:41'),
(184, 'STC/UBPL/131', '2025-11-10', 67, 69, 0, NULL, NULL, 'Guwahati', 'Thimphu town ', 3, '370', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 6, NULL, 77, 'In Transit', 'Done', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-10 09:19:48'),
(185, 'STC/UBPL/132', '2025-11-10', 67, 85, 0, NULL, NULL, 'Guwahati', 'Siliguri', 3, '650', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 6, NULL, 78, 'In Transit', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-10 12:33:04'),
(186, '07674', '2025-11-10', 3, 70, 0, NULL, NULL, 'Choutaki', 'Tura', 1, '666/4', 'Cartons/Pieces', '8242.434', 'Kg', '8242.434', 'Kg', 'To be Billed', 3, 8, 8, 'Delivered', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-10 13:06:48'),
(187, '07675', '2025-11-10', 3, 6, 0, NULL, NULL, 'Choutaki', 'Phulbari', 1, '907/00', 'Cartons/Pieces', '8262.538', 'Kg', '8262.538', 'Kg', 'To be Billed', 3, 10, 5, 'Delivered', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-10 13:09:07'),
(188, '02126', '2025-11-10', 131, 112, 0, NULL, NULL, 'JAMURIA', 'GHAZIABAD', 13, '', 'Loose', '31.000', 'Ton', '31.000', 'Ton', 'To be Billed', 26, NULL, 79, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-10 13:58:37'),
(189, '02127', '2025-11-10', 115, 112, 0, NULL, NULL, 'JAMURIA', 'GHAZIABAD', 5, '', 'Loose', '42.070', 'Ton', '42.070', 'Ton', 'To be Billed', 16, NULL, 80, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-10 14:02:27'),
(190, '02128', '2025-11-10', 116, 112, 0, NULL, NULL, 'BARJORA', 'GHAZIABAD', 5, '', 'Loose', '41.920', 'Ton', '41.920', 'Ton', 'To be Billed', 24, NULL, 81, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-10 14:55:57'),
(191, '02129', '2025-11-10', 113, 114, 0, NULL, NULL, 'BURNPUR', 'Bangalore', 10, '', 'Loose', '27.990/04.880', 'Ton', '32.870', 'Ton', 'To be Billed', 11, NULL, 82, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 2, 3, '2025-11-10 15:11:07'),
(192, '03865', '2025-11-10', 62, 101, 0, NULL, NULL, 'Silchar', 'Hailakandi ', 3, '1309', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 4, 4, 'Completed', 'Pending', 'uploads/pod/pod_192_1762848451.jpg', '', NULL, NULL, 'Pending', 3, 2, '2025-11-10 16:44:46'),
(193, '08257', '2025-11-10', 21, 26, 0, NULL, NULL, 'Guwahati', 'Lunglei', 3, '1160', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 1, NULL, 24, 'In Transit', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-10 16:50:16'),
(194, 'STC/UBPL/133', '2025-11-11', 67, 68, 0, NULL, NULL, 'Guwahati', 'Phuentsholing', 3, '824', 'Cartons', 'FTL', '', 'FTL', '', 'To be Billed', 3, 3, 2, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-11 10:10:38'),
(195, '07676', '2025-11-11', 3, 12, 0, NULL, NULL, 'Choutaki', 'Williamnagar', 1, '866/00', 'Cartons/Pieces', '8254.747', 'Kg', '8254.747', 'Kg', 'To be Billed', 3, 9, 9, 'Booked', 'Pending', NULL, NULL, NULL, NULL, 'Pending', 3, 2, '2025-11-11 10:20:44');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_invoices`
--

CREATE TABLE `shipment_invoices` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `invoice_no` varchar(100) NOT NULL,
  `invoice_date` date DEFAULT NULL,
  `invoice_amount` decimal(10,2) DEFAULT NULL,
  `eway_bill_no` varchar(100) DEFAULT NULL,
  `eway_bill_expiry` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipment_invoices`
--

INSERT INTO `shipment_invoices` (`id`, `shipment_id`, `invoice_no`, `invoice_date`, `invoice_amount`, `eway_bill_no`, `eway_bill_expiry`) VALUES
(3, 1, '1224021576/77/1223015407', '2025-10-03', 1004132.00, 'REFER TO INVOICE', '2025-10-04'),
(4, 2, '1224021603/04/1224021557/1223015388/1223015441', '2025-10-03', 996831.00, 'REFER TO INVOICE', '2025-10-04'),
(5, 3, '1223015465/1224021630', '2025-10-04', 800008.00, 'REFER TO INVOICE', '2025-10-05'),
(6, 4, '1223015468/1224021634/35', '2025-10-04', 1120159.00, 'REFER TO INVOICE', '2025-10-05'),
(7, 5, 'Sinv/Asm/432', '2025-10-04', 485057.00, '881586677880', '2025-10-07'),
(10, 6, 'Sinv/Asm/433', '2025-10-04', 459821.00, '801586703347', '2025-10-07'),
(11, 7, 'Sinv/Asm/434', '2025-10-04', 516150.00, '851586727540', '2025-10-07'),
(12, 8, 'Sinv/Asm/422', '2025-10-04', 538292.00, '851586719914', '2025-10-07'),
(13, 9, 'F22571100031', '2025-10-04', 513722.00, '891586763674', '2025-10-05'),
(14, 10, 'Sinv/Asm/426/435', '2025-10-04', 453099.00, '831586773068/841586787570', '2025-10-06'),
(16, 12, '1223015499/1224021657/58', '2025-10-06', 859860.00, 'REFER TO INVOICE', '2025-10-07'),
(17, 13, '1224021662/63/1223015503', '2025-10-06', 899176.00, 'REFER TO INVOICE', '2025-10-07'),
(18, 14, 'F22570101337/F22521201200', '2025-10-06', 513587.00, '801587370658/821587370386', '2025-10-08'),
(19, 15, 'F22571100034', '2025-10-06', 481844.00, '801587440696', '2025-10-07'),
(20, 16, 'F22571100035', '2025-10-06', 636457.00, '831587487816', '2025-10-07'),
(21, 17, '1223015522/1224021672', '2025-10-07', 803079.00, 'REFER TO INVOICE', '2025-10-08'),
(23, 19, '1224021675/76/1223015524', '2025-10-07', 1133468.00, 'REFER TO INVOICE', '2025-10-08'),
(24, 20, 'F22570101346/F22521201207', '2025-10-07', 548698.00, '811587845993/881587845572', '2025-10-08'),
(26, 22, 'F22570101349/F22521201210', '2025-10-08', 498550.00, '801588256865/841588256643', '2025-10-10'),
(27, 23, '1100321934/35', '2025-10-08', 953350.00, '372092498600/322093409776', '2025-10-11'),
(28, 24, '1100321936', '2025-10-08', 371637.36, '342093574348', '2025-10-11'),
(31, 27, '1224021727/1223015570', '2025-10-09', 616034.00, 'REFER TO INVOICE', '2025-10-10'),
(32, 28, 'F22571100040', '2025-10-09', 443536.00, '841588888082', '2025-10-11'),
(33, 29, 'F22571100038', '2025-10-09', 488152.00, '841588854434', '2025-10-10'),
(34, 30, 'Sinv/Asm/441', '2025-10-09', 561874.00, '871588804467', '2025-10-11'),
(35, 31, 'F22571100042', '2025-10-10', 444088.00, '801589315769', '2025-10-11'),
(38, 33, '1224021741/40/1223015587', '2025-10-10', 900087.00, 'REFER TO INVOICE', '2025-10-11'),
(39, 34, 'Sinv/Asm/443', '2025-10-10', 562244.00, '831589290775', '2025-10-15'),
(40, 35, 'F22570101361/F22521201221', '2025-10-10', 474557.00, '811589343130/871589342966', '2025-10-12'),
(41, 36, '1224021755/56/1223015604', '2025-10-10', 754373.00, 'REFER TO INVOICE', '2025-10-11'),
(42, 37, '1223015605/1224021757/58', '2025-10-10', 700370.00, 'REFER TO INVOICE', '2025-10-11'),
(43, 38, 'F22570101359/F22521201219', '2025-10-10', 465962.00, '88158933190/821589332988', '2025-10-13'),
(45, 40, 'F22571100044', '2025-10-11', 342294.00, '891589723927', '2025-10-12'),
(46, 41, 'F22571100045', '2025-10-11', 440328.00, '891589892032', '2025-10-12'),
(47, 39, '1223015608/1224021762/63', '2025-10-11', 1123141.00, 'REFER TO INVOICE', '2025-10-12'),
(48, 32, '1224021744/45/1223015589', '2025-10-10', 1099932.00, 'REFER TO INVOICE', '2025-10-11'),
(49, 26, '1223015568/1224021723/24', '2025-10-09', 655892.00, 'REFER TO INVOICE', '2025-10-10'),
(50, 25, '1223015566/1223015565/1224021719/20', '2025-10-09', 763171.00, 'REFER TO INVOICE', '2025-10-10'),
(51, 42, '1224021796/97/1223015642', '2025-10-13', 800199.00, 'REFER TO INVOICE', '2025-10-14'),
(52, 43, '1223015643/1224021798/99', '2025-10-13', 950454.00, 'REFER TO INVOICE', '2025-10-14'),
(53, 44, '1223015644/1224021800/01', '2025-10-13', 1050238.00, 'REFER TO INVOICE', '2025-10-14'),
(54, 45, 'F22570101381/F22521201239', '2025-10-13', 497197.00, '831590592798/851590593601', '2025-10-15'),
(55, 46, 'FG2G/25-26/2627', '2025-10-13', 662731.35, '881590684780', '2025-10-15'),
(56, 47, '1223015664/1224021815/16', '2025-10-14', 1029973.00, 'REFER TO INVOICE', '2025-10-15'),
(57, 48, '1224021817/18/1223015665', '2025-10-14', 1310137.00, 'REFER TO INVOICE', '2025-10-15'),
(58, 49, '192014004', '2025-10-14', 297333.00, '811591063408', '2025-10-17'),
(59, 50, '192014012', '2025-10-14', 390186.00, '821591062619', '2025-10-16'),
(60, 51, 'Sinv/Asm/446', '2025-10-14', 566056.00, '851591038606', '2025-10-17'),
(61, 52, '1223015671/1224021826/27', '2025-10-14', 971550.00, 'REFER TO INVOICE', '2025-10-15'),
(62, 53, '1223015692/1224021842', '2025-10-14', 829751.00, 'REFER TO INVOICE', '2025-10-15'),
(63, 54, '1224021841/1223015691', '2025-10-14', 797172.00, 'REFER TO INVOICE', '2025-10-15'),
(64, 55, 'F22570101393/F22521201251', '2025-10-14', 489190.00, '851591222056/891591222339', '2025-10-15'),
(65, 56, 'F22571100050', '2025-10-14', 426621.00, '821591270519', '2025-10-15'),
(66, 57, '1224021843/44/1223015693', '2025-10-15', 1063548.00, 'REFER TO INVOICE', '2025-10-16'),
(67, 58, '1224021855/56/1223015703', '2025-10-15', 810406.00, 'REFER TO INVOICE', '2025-10-16'),
(68, 59, '1224021853/54/1223015702', '2025-10-15', 732265.00, 'REFER TO INVOICE', '2025-10-16'),
(69, 60, 'Sinv/Asm/451', '2025-10-15', 510745.00, '811591761672', '2025-10-19'),
(70, 61, 'F22571100053', '2025-10-15', 428894.00, '881591796451', '2025-10-17'),
(71, 21, '1223015544/1224021695/96', '2025-10-08', 949678.00, 'REFER TO INVOICE', '2025-10-09'),
(72, 18, '1223015523/1224021558/59/1224021673/74', '2025-10-07', 1126761.00, 'REFER TO INVOICE', '2025-10-08'),
(73, 11, '1223015500/1223015390/1224021560/61/1224021659', '2025-10-06', 999966.00, 'REFER TO INVOICE', '2025-10-07'),
(74, 62, '1224021865/66/1223015715', '2025-10-16', 860438.00, 'REFER TO INVOICE', '2025-10-17'),
(75, 63, '1223015716/1224021867/68', '2025-10-16', 839014.00, 'REFER TO INVOICE', '2025-10-17'),
(76, 64, 'F22570101403/F22521201260', '2025-10-16', 549488.00, '881592198458/861592198171', '2025-10-19'),
(77, 65, 'Sinv/Asm/453', '2025-10-16', 462702.00, '841592004856', '2025-10-18'),
(78, 66, 'Sinv/Asm/454', '2025-10-16', 484278.00, '801592104758', '2025-10-19'),
(79, 67, 'Sinv/Asm/456', '2025-10-16', 481609.00, '841592251650', '2025-10-19'),
(81, 69, '1223015732/1224021877/78', '2025-10-17', 587310.00, 'REFER TO INVOICE', '2025-10-18'),
(82, 70, 'F22571100056', '2025-10-16', 491067.00, '891592323602', '2025-10-18'),
(83, 71, 'F22570101413/F22521201270', '2025-10-17', 551877.00, '851592792657/801592792483', '2025-10-19'),
(84, 72, 'F22570101416/F22521201273', '2025-10-18', 504107.00, '861593228211/851593227989', '2025-10-20'),
(85, 73, 'F22571100059', '2025-10-18', 443917.00, '881593283019', '2025-10-20'),
(86, 74, 'F22571100060', '2025-10-18', 442357.00, '881593286570', '2025-10-19'),
(88, 76, '1223015792/1224021929/30/31', '2025-10-21', 1575889.00, 'REFER TO INVOICE', '2025-10-22'),
(89, 77, '1224021932/33/1223015793', '2025-10-21', 948785.00, 'REFER TO INVOICE', '2025-10-22'),
(90, 78, '1224021957/58/1223015813', '2025-10-22', 910419.00, 'REFER TO INVOICE', '2025-10-23'),
(91, 79, '1224021963/64/1223015816', '2025-10-22', 1055101.00, 'REFER TO INVOICE', '2025-10-23'),
(92, 80, 'F22570101425', '2025-10-22', 519831.00, '831594455190', '2025-10-24'),
(93, 81, 'Sinv/Asm/462', '2025-10-21', 468980.00, '821594433669', '2025-10-26'),
(94, 82, 'Sinv/Asm/465', '2025-10-21', 88318.00, '801594452840', '2025-10-26'),
(95, 83, 'F22571100066', '2025-10-22', 445499.00, '871594507477', '2025-10-23'),
(96, 84, '1223015832/1224021972/73', '2025-10-23', 1238841.00, 'REFER TO INVOICE', '2025-10-24'),
(97, 85, '1223015842/1224021987/88', '2025-10-23', 994251.00, 'REFER TO INVOICE', '2025-10-24'),
(98, 86, 'Sinv/Asm/474', '2025-10-23', 482173.00, '871594851741', '2025-10-27'),
(99, 87, 'Sinv/Asm/472', '2025-10-23', 468965.00, '821594803114', '2025-10-26'),
(100, 88, 'Sinv/Asm/475', '2025-10-23', 556813.00, '871594853734', '2025-10-25'),
(101, 89, '1223015858/1224022001/02', '2025-10-24', 799670.00, 'REFER TO INVOICE', '2025-10-25'),
(102, 90, '1224022004/1224022003/1223015859', '2025-10-24', 886908.00, 'REFER TO INVOICE', '2025-10-25'),
(103, 91, 'F22571100069', '2025-10-24', 441967.00, '841595239330', '2025-10-25'),
(105, 92, '1224022008/07/1223015861', '2025-10-24', 1099873.00, 'REFER TO INVOICE', '2025-10-25'),
(107, 93, 'F22521201302/F22570101488', '2025-10-25', 461058.00, '851595549191/851595549485', '2025-10-27'),
(108, 94, '1223015878/1224022023/24', '2025-10-25', 1502486.00, 'REFER TO INVOICE', '2025-10-26'),
(109, 95, '1223015877/1224022021/22', '2025-10-25', 776799.00, 'REFER TO INVOICE', '2025-10-26'),
(110, 96, 'SINV/ASM/478', '2025-10-25', 556734.00, '811595667222', '2025-10-29'),
(111, 97, '1224022027/26/1223015879', '2025-10-25', 810293.00, 'REFER TO INVOICE', '2025-10-26'),
(112, 98, 'SINV/ASM/479', '2025-10-25', 559280.00, '841595714361', '2025-10-29'),
(113, 99, 'F22570101455/F22521201309', '2025-10-25', 501247.00, '851595751329/881595751005', '2025-10-27'),
(114, 100, 'F31055000004/F32572000010', '2025-10-25', 510229.00, '861595806789/801595806624', '2025-10-27'),
(115, 101, '1223015903', '2025-10-27', 502896.00, 'Refer To Invoice', NULL),
(116, 101, '1224022044', '2025-10-27', 196617.00, 'Refer To Invoice', NULL),
(117, 102, '1223015904', '2025-10-27', 316613.00, 'Refer To Invoice', NULL),
(118, 102, '1224022045', '2025-10-27', 1186568.00, 'Refer To Invoice', NULL),
(119, 103, '1224022055/56', '2025-10-27', 870329.00, 'Refer To Invoice', '2025-10-28'),
(120, 103, '1223015915', '2025-10-27', 327727.00, 'Refer To Invoice', '2025-10-28'),
(121, 104, '1224022056/55/1223015915', '2025-10-27', 1198056.00, 'REFER TO INVOICE', '2025-10-28'),
(122, 105, 'F22571100074', '2025-10-27', 449688.00, '891596387055', '2025-10-29'),
(123, 106, 'Sinv/Asm/480', '2025-10-27', 552595.00, '811596303747', '2025-10-30'),
(124, 107, 'Sinv/Asm/481', '2025-10-27', 597408.00, '801596350050', '2025-10-30'),
(125, 108, 'Sinv/Asm/484', '2025-10-28', 561397.00, '871596678966', '2025-10-31'),
(126, 109, '1223015922/1224022061/62', '2025-10-28', 1198757.00, 'REFER TO INVOICE', '2025-10-29'),
(127, 110, 'Sinv/Asm/486', '2025-10-28', 498403.00, '881596746112', '2025-10-30'),
(128, 111, '1223015924/1224022065/66', '2025-10-28', 991207.00, 'REFER TO INVOICE', '2025-10-29'),
(129, 112, '1223015925/1224022068/67', '2025-10-28', 920266.00, 'REFER TO INVOICE', '2025-10-29'),
(130, 113, '1223015939/1224022079/80', '2025-10-28', 1116256.00, 'REFER TO INVOICE', '2025-10-29'),
(131, 114, 'Sinv/Asm/487', '2025-10-28', 510493.00, '801596783579', '2025-11-01'),
(132, 115, 'F22570101463/F22521201317', '2025-10-27', 549532.00, '821596413854/861596413773', '2025-10-30'),
(133, 116, 'F22521201327/F22570101474', '2025-10-29', 642557.00, '841597130060/861597129626', '2025-11-03'),
(134, 117, '1224022093/92/1223015948', '2025-10-29', 809583.00, 'REFER TO INVOICE', '2025-10-30'),
(136, 119, '1224022113/14', '2025-10-30', 1111498.00, 'REFER TO INVOICE', '2025-10-31'),
(137, 120, '1224022116/17/1223015970', '2025-10-30', 800902.00, 'REFER TO INVOICE', '2025-10-31'),
(138, 118, 'Sinv/Asm/489', '2025-10-30', 567751.00, '881597575966', '2025-11-03'),
(139, 121, '1224022112/22/1223015973', '2025-10-30', 1098974.00, 'REFER TO INVOICE', '2025-10-31'),
(140, 122, 'F22571100084', '2025-10-30', 433024.00, '861597771667', '2025-10-31'),
(141, 123, 'F22571100085', '2025-10-30', 408316.00, '831597774753', '2025-10-31'),
(142, 124, 'F22570101487/F22521201339', '2025-10-30', 560604.00, '801597723842/831597723656', '2025-11-01'),
(144, 125, '1223015996/1224022140/41', '2025-10-31', 901567.00, 'REFER TO INVOICE', '2025-11-01'),
(145, 126, '1224022145/46/47/1223015999', '2025-10-31', 1554179.00, 'REFER TO INVOICE', '2025-11-01'),
(147, 127, '1224022025/1224022149/48/1223016000', '2025-10-31', 831592.00, 'REFER TO INVOICE', '2025-11-01'),
(148, 128, '1223016019/1224022155/56', '2025-10-31', 768912.00, 'REFER TO INVOICE', '2025-11-01'),
(150, 129, '1223016021/1224022125/1224022159', '2025-10-31', 2054703.00, 'REFER TO INVOICE', '2025-11-01'),
(151, 130, 'F22571100087', '2025-10-31', 458318.00, '871598221698', '2025-11-02'),
(152, 131, 'F22570101498/F22521201349', '2025-10-31', 510299.00, '801598249318', '2025-11-02'),
(153, 132, '1223016029/1224022172/73', '2025-10-31', 1176537.00, 'REFER TO INVOICE', '2025-11-02'),
(154, 133, '1223016028/1224022170/71', '2025-10-31', 899449.00, 'REFER TO INVOICE', '2025-11-02'),
(155, 134, 'Sinv/Asm/494', '2025-10-31', 555042.00, '881598647280', '2025-11-05'),
(156, 135, '1223016042/1223016030/1224022184/85/1224022174', '2025-10-31', 1819507.00, 'REFER TO INVOICE', '2025-11-03'),
(157, 136, '1223016026/1224022166/67', '2025-10-31', 929653.00, 'REFER TO INVOICE', '2025-11-03'),
(158, 137, '192014062/191221267', '2025-11-03', 549141.00, '891599210732/372110837587', '2025-11-06'),
(159, 138, 'F22571100089', '2025-11-03', 441828.00, '821599260893', '2025-11-04'),
(160, 75, 'F22521201276/F225701419', '2025-10-19', 346282.00, '811593476729/841593476249', '2025-10-22'),
(161, 68, 'F22571100054', '2025-10-16', 401922.00, '811592229517', '2025-10-17'),
(162, 139, 'SINV/ASM/498', '2025-11-03', 576226.00, '881599315609', '2025-11-07'),
(163, 141, '1223016072/1224022206', '2025-11-04', 899248.00, 'REFER TO INVOICE', '2025-11-05'),
(164, 142, '1224022207/1223016073', '2025-11-04', 750548.00, 'REFER TO INVOICE', '2025-11-05'),
(166, 144, 'SG25Y-06679', '2025-11-01', 1992207.29, '871598716008', '2025-11-06'),
(167, 145, 'SARSD/2526/01393', '2025-11-01', 2244036.00, '801598773002', '2025-11-08'),
(168, 146, 'SG25Y-06697', '2025-11-02', 1961817.00, '861598908008', '2025-11-09'),
(169, 147, 'SARSD/2526/01401', '2025-11-03', 2086533.00, '851599335512', '2025-11-08'),
(170, 140, 'DS1925063764', '2025-11-01', 2031095.00, '861598762558/831598761291', '2025-11-06'),
(171, 148, 'SG25Y-06740', '2025-11-04', 2005679.34, '801599660154', '2025-11-10'),
(174, 150, 'OS0511013905', '2025-11-04', 1977979.00, '891599768989', '2025-11-12'),
(175, 151, 'OS0511013904', '2025-11-04', 1982152.00, '811599768985', '2025-11-12'),
(176, 152, 'F22571100091', '2025-11-04', 452845.00, '861599780041', '2025-11-05'),
(177, 153, 'Sinv/Asm/504', '2025-11-04', 498993.00, '841599795643', '2025-11-08'),
(178, 149, 'OS0511013902', '2025-11-04', 1974999.00, '831599768981', '2025-11-12'),
(179, 143, 'SARSD/2526/01389', '2025-11-01', 2140272.00, '821598683781', '2025-11-07'),
(180, 154, '1223016087/1224022216/17', '2025-11-05', 875775.00, 'REFER TO INVOICE', '2025-11-06'),
(183, 157, 'F22521201365/F22570101514', '2025-11-05', 554311.00, '801600145032/801600145160', '2025-11-08'),
(184, 158, 'F22570101516/F22521201367', '2025-11-05', 499155.00, '831600156103/861600155969', '2025-11-07'),
(185, 159, '1223016090/1224022222/23', '2025-11-05', 1198188.00, 'REFER TO INVOICE', '2025-11-06'),
(186, 156, 'OS0511013943', '2025-11-05', 1952942.00, '851600075763', '2025-11-13'),
(188, 155, 'OS0511013942', '2025-11-05', 2008368.00, '821600075751', '2025-11-13'),
(189, 160, 'F22570101519/F22521201370', '2025-11-06', 474394.00, '811600201135/811600201056', '2025-11-07'),
(190, 161, '1224022235/1223016106', '2025-11-06', 887499.00, 'REFER TO INVOICE', '2025-11-07'),
(191, 162, '1224022236/1223016103/1223016108/1224022232', '2025-11-06', 1014362.00, 'REFER TO INVOICE', '2025-11-07'),
(192, 163, 'OS0511014006', '2025-11-06', 2006561.00, '851600474267', '2025-11-14'),
(193, 164, 'OS0511014007', '2025-11-06', 1988489.00, '861600474273', '2025-11-14'),
(194, 165, 'OS0511014012', '2025-11-06', 2063186.00, '801600563106', '2025-11-14'),
(196, 166, 'OS0511014037', '2025-11-07', 2005959.00, '841600842177', '2025-11-15'),
(198, 168, 'SARSD/25-26/01416', '2025-11-05', 1713680.00, '871600158057', '2025-11-10'),
(199, 169, '1223016128', '2025-11-07', 585014.00, 'REFER TO INVOICE', '2025-11-08'),
(200, 170, '1224022256/55/1223016129', '2025-11-07', 799856.00, 'REFER TO INVOICE', '2025-11-08'),
(201, 171, 'OS0511014067', '2025-11-07', 2069210.00, '831600968144', '2025-11-15'),
(202, 172, 'F22571100095', '2025-11-07', 266871.00, '871600950268', '2025-11-08'),
(203, 173, 'Sinv/Asm/508', '2025-11-07', 497663.00, '891601013946', '2025-11-10'),
(204, 167, 'OS0511014036', '2025-11-07', 2006561.00, '861600842173', '2025-11-15'),
(205, 174, 'F22570101531/F22521201380', '2025-11-07', 550386.00, '811600997098/801600996865', '2025-11-08'),
(206, 175, '1223016148/1224022265/66', '2025-11-08', 680196.00, 'REFER TO INVOICE', '2025-11-09'),
(207, 176, '1224022267/68/1223016149', '2025-11-08', 848424.00, 'Refer To Invoice', '2025-11-09'),
(208, 177, 'SINV/ASM/509', '2025-11-08', 508356.00, '811601423295', '2025-11-11'),
(209, 178, 'SG25Y-06803', '2025-11-07', 2051125.57, '821601066712', '2025-11-14'),
(211, 179, 'SG25Y-06802', '2025-11-07', 200547.04, '891601066487', '2025-11-14'),
(212, 180, '1223016158/1224022273/74', '2025-11-08', 1199969.00, 'Refer To Invoice', '2025-11-09'),
(213, 181, 'OS0511014166', '2025-11-09', 1948129.00, '851601707571', '2025-11-16'),
(214, 182, 'OS0511014163', '2025-11-09', 1941503.00, '851601709069', '2025-11-16'),
(215, 183, 'F22571100097', '2025-11-09', 707683.00, '821601665564', '2025-11-10'),
(216, 184, '1100321954', '2025-11-10', 400895.00, '392113584079', '2025-11-10'),
(217, 185, '191221392', '2025-11-10', 370720.00, '392113584079', '2025-11-15'),
(218, 186, '1224022294/93/1223016192', '2025-11-10', 748829.00, 'REFER TO INVOICE', '2025-11-11'),
(219, 187, '1224022296/95/1223016191', '2025-11-10', 712510.00, 'REFER TO INVOICE', '2025-11-11'),
(220, 188, 'SD25Y-04270', '2025-11-10', 1433022.00, '821601965125', '2025-11-17'),
(221, 189, 'SG25Y-06833', '2025-11-10', 1995610.00, '831601954014', '2025-11-17'),
(222, 190, 'SARSD/25-26/01445', '2025-11-10', 2138309.00, '891602105213', '2025-11-17'),
(223, 191, 'OS0511012465/OS0511014275', '2025-11-10', 1977004.00, '861602063462/811602108177', '2025-11-16'),
(224, 192, 'F225711000100', '2025-11-10', 413631.00, '881602169993', '2025-11-11'),
(225, 193, 'Sinv/Asm/511', '2025-11-10', 555889.00, '831602183732', '2025-11-14'),
(226, 194, '1100321957/1100321955', '2025-11-11', 844055.90, '362114956955/372113584817', '2025-11-12'),
(227, 195, '1223016207/1224022304/03', '2025-11-11', 917255.00, 'REFER TO INVOICE', '2025-11-12');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_payments`
--

CREATE TABLE `shipment_payments` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `payment_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `billing_method` varchar(50) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipment_payments`
--

INSERT INTO `shipment_payments` (`id`, `shipment_id`, `payment_type`, `amount`, `billing_method`, `rate`, `payment_date`, `remarks`, `created_by_id`, `created_at`) VALUES
(1, 6, 'Billing Rate', 49700.00, 'Fixed', 49700.00, '2025-10-04', NULL, 5, '2025-10-04 17:55:04'),
(2, 6, 'Lorry Hire', 46000.00, 'Fixed', 46000.00, '2025-10-04', NULL, 5, '2025-10-04 17:55:04'),
(3, 6, 'Advance Cash', 32000.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 17:55:04'),
(4, 6, 'Dala Charge', 400.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 17:55:04'),
(5, 1, 'Billing Rate', 12769.38, 'Kg', 1.50, '2025-10-04', NULL, 5, '2025-10-04 17:57:17'),
(6, 1, 'Lorry Hire', 12000.00, 'Fixed', 12000.00, '2025-10-04', NULL, 5, '2025-10-04 17:57:17'),
(7, 1, 'Advance Cash', 9000.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 17:57:17'),
(8, 1, 'Dala Charge', 250.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 17:57:17'),
(9, 2, 'Billing Rate', 15321.82, 'Kg', 1.80, '2025-10-04', NULL, 5, '2025-10-04 17:57:52'),
(10, 2, 'Lorry Hire', 14500.00, 'Fixed', 14500.00, '2025-10-04', NULL, 5, '2025-10-04 17:57:52'),
(11, 2, 'Advance Cash', 10700.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 17:57:52'),
(12, 2, 'Dala Charge', 250.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 17:57:52'),
(13, 3, 'Billing Rate', 10795.00, 'Kg', 1.27, '2025-10-04', NULL, 5, '2025-10-04 17:58:37'),
(14, 3, 'Lorry Hire', 9800.00, 'Fixed', 9800.00, '2025-10-04', NULL, 5, '2025-10-04 17:58:37'),
(15, 3, 'Advance Cash', 7400.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 17:58:37'),
(16, 3, 'Dala Charge', 250.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 17:58:37'),
(17, 4, 'Billing Rate', 12758.56, 'Kg', 1.50, '2025-10-04', NULL, 5, '2025-10-04 17:59:16'),
(18, 4, 'Lorry Hire', 11500.00, 'Fixed', 11500.00, '2025-10-04', NULL, 5, '2025-10-04 17:59:16'),
(19, 4, 'Advance Cash', 9000.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 17:59:16'),
(20, 4, 'Dala Charge', 250.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 17:59:16'),
(21, 9, 'Billing Rate', 7000.00, 'Fixed', 7000.00, '2025-10-04', NULL, 5, '2025-10-04 19:25:21'),
(22, 9, 'Lorry Hire', 6000.00, 'Fixed', 6000.00, '2025-10-04', NULL, 5, '2025-10-04 19:25:21'),
(23, 9, 'Advance Cash', 3000.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 19:25:21'),
(24, 9, 'Labour Charge', 900.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 19:25:21'),
(25, 9, 'Dala Charge', 250.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 19:25:21'),
(26, 8, 'Billing Rate', 42000.00, 'Fixed', 42000.00, '2025-10-04', NULL, 5, '2025-10-04 19:25:48'),
(27, 8, 'Lorry Hire', 40000.00, 'Fixed', 40000.00, '2025-10-04', NULL, 5, '2025-10-04 19:25:48'),
(28, 8, 'Advance Cash', 31000.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 19:25:48'),
(29, 8, 'Dala Charge', 250.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 19:25:48'),
(34, 5, 'Billing Rate', 49700.00, 'Fixed', 49700.00, '2025-10-04', NULL, 5, '2025-10-04 19:27:14'),
(35, 5, 'Lorry Hire', 46000.00, 'Fixed', 46000.00, '2025-10-04', NULL, 5, '2025-10-04 19:27:14'),
(36, 5, 'Advance Cash', 31000.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 19:27:14'),
(37, 5, 'Dala Charge', 250.00, NULL, NULL, '2025-10-04', NULL, 5, '2025-10-04 19:27:14'),
(38, 31, 'Billing Rate', 4900.00, 'Fixed', 4900.00, '2025-10-13', NULL, 5, '2025-10-13 04:46:29'),
(39, 31, 'Lorry Hire', 4500.00, 'Fixed', 4500.00, '2025-10-13', NULL, 5, '2025-10-13 04:46:29'),
(40, 31, 'Advance Cash', 2000.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:46:29'),
(41, 31, 'Labour Charge', 800.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:46:29'),
(42, 31, 'Dala Charge', 200.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:46:29'),
(43, 40, 'Billing Rate', 4900.00, 'Fixed', 4900.00, '2025-10-13', NULL, 5, '2025-10-13 04:47:05'),
(44, 40, 'Lorry Hire', 4500.00, 'Fixed', 4500.00, '2025-10-13', NULL, 5, '2025-10-13 04:47:05'),
(45, 40, 'Advance Cash', 2000.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:47:05'),
(46, 40, 'Labour Charge', 800.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:47:05'),
(47, 40, 'Dala Charge', 200.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:47:05'),
(48, 34, 'Billing Rate', 85400.00, 'Fixed', 85400.00, '2025-10-13', NULL, 5, '2025-10-13 04:49:16'),
(49, 34, 'Lorry Hire', 75000.00, 'Fixed', 75000.00, '2025-10-13', NULL, 5, '2025-10-13 04:49:16'),
(50, 34, 'Advance Cash', 67750.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:49:16'),
(51, 34, 'Dala Charge', 250.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:49:16'),
(52, 41, 'Billing Rate', 7500.00, 'Fixed', 7500.00, '2025-10-13', NULL, 5, '2025-10-13 04:49:56'),
(53, 41, 'Lorry Hire', 7000.00, 'Fixed', 7000.00, '2025-10-13', NULL, 5, '2025-10-13 04:49:56'),
(54, 41, 'Advance Cash', 4500.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:49:56'),
(55, 41, 'Labour Charge', 800.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:49:56'),
(56, 41, 'Dala Charge', 250.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:49:56'),
(57, 14, 'Billing Rate', 19000.00, 'Fixed', 19000.00, '2025-10-13', NULL, 5, '2025-10-13 04:53:56'),
(58, 14, 'Lorry Hire', 18000.00, 'Fixed', 18000.00, '2025-10-13', NULL, 5, '2025-10-13 04:53:56'),
(59, 14, 'Advance Cash', 13500.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:53:56'),
(60, 14, 'Labour Charge', 800.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:53:56'),
(61, 14, 'Dala Charge', 250.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:53:56'),
(62, 16, 'Billing Rate', 8000.00, 'Fixed', 8000.00, '2025-10-13', NULL, 5, '2025-10-13 04:55:23'),
(63, 16, 'Lorry Hire', 10000.00, 'Fixed', 10000.00, '2025-10-13', NULL, 5, '2025-10-13 04:55:23'),
(64, 16, 'Advance Cash', 5000.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:55:23'),
(65, 16, 'Labour Charge', 800.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:55:23'),
(66, 16, 'Dala Charge', 250.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:55:23'),
(67, 15, 'Billing Rate', 6500.00, 'Fixed', 6500.00, '2025-10-13', NULL, 5, '2025-10-13 04:56:51'),
(68, 15, 'Lorry Hire', 5800.00, 'Fixed', 5800.00, '2025-10-13', NULL, 5, '2025-10-13 04:56:51'),
(69, 15, 'Advance Cash', 3000.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:56:51'),
(70, 15, 'Labour Charge', 800.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:56:51'),
(71, 15, 'Dala Charge', 250.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:56:51'),
(72, 28, 'Billing Rate', 24500.00, 'Fixed', 24500.00, '2025-10-13', NULL, 5, '2025-10-13 04:57:54'),
(73, 28, 'Lorry Hire', 23000.00, 'Fixed', 23000.00, '2025-10-13', NULL, 5, '2025-10-13 04:57:54'),
(74, 28, 'Advance Cash', 17000.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:57:54'),
(75, 28, 'Labour Charge', 800.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:57:54'),
(76, 28, 'Dala Charge', 250.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:57:54'),
(77, 29, 'Billing Rate', 17000.00, 'Fixed', 17000.00, '2025-10-13', NULL, 5, '2025-10-13 04:58:52'),
(78, 29, 'Lorry Hire', 15000.00, 'Fixed', 15000.00, '2025-10-13', NULL, 5, '2025-10-13 04:58:52'),
(79, 29, 'Advance Cash', 9500.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:58:52'),
(80, 29, 'Labour Charge', 800.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:58:52'),
(81, 29, 'Dala Charge', 250.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 04:58:52'),
(82, 20, 'Billing Rate', 19000.00, 'Fixed', 19000.00, '2025-10-13', NULL, 5, '2025-10-13 05:00:08'),
(83, 20, 'Lorry Hire', 18000.00, 'Fixed', 18000.00, '2025-10-13', NULL, 5, '2025-10-13 05:00:08'),
(84, 20, 'Advance Cash', 13000.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:00:08'),
(85, 20, 'Labour Charge', 1000.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:00:08'),
(86, 10, 'Billing Rate', 29500.00, 'Fixed', 29500.00, '2025-10-13', NULL, 5, '2025-10-13 05:02:28'),
(87, 10, 'Lorry Hire', 27500.00, 'Fixed', 27500.00, '2025-10-13', NULL, 5, '2025-10-13 05:02:28'),
(88, 10, 'Advance Cash', 17500.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:02:28'),
(89, 10, 'Labour Charge', 300.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:02:28'),
(90, 10, 'Dala Charge', 250.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:02:28'),
(91, 35, 'Billing Rate', 11000.00, 'Fixed', 11000.00, '2025-10-13', NULL, 5, '2025-10-13 05:04:37'),
(92, 35, 'Lorry Hire', 10500.00, 'Fixed', 10500.00, '2025-10-13', NULL, 5, '2025-10-13 05:04:37'),
(93, 35, 'Advance Cash', 10500.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:04:37'),
(94, 38, 'Billing Rate', 22000.00, 'Fixed', 22000.00, '2025-10-13', NULL, 5, '2025-10-13 05:05:40'),
(95, 38, 'Lorry Hire', 21000.00, 'Fixed', 21000.00, '2025-10-13', NULL, 5, '2025-10-13 05:05:40'),
(96, 38, 'Advance Cash', 13500.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:05:40'),
(97, 38, 'Labour Charge', 800.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:05:40'),
(98, 38, 'Dala Charge', 250.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:05:40'),
(99, 30, 'Billing Rate', 29500.00, 'Fixed', 29500.00, '2025-10-13', NULL, 5, '2025-10-13 05:06:36'),
(100, 30, 'Lorry Hire', 27500.00, 'Fixed', 27500.00, '2025-10-13', NULL, 5, '2025-10-13 05:06:36'),
(101, 30, 'Advance Cash', 19000.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:06:36'),
(102, 30, 'Labour Charge', 300.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:06:36'),
(103, 30, 'Dala Charge', 250.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:06:36'),
(104, 22, 'Billing Rate', 17500.00, 'Fixed', 17500.00, '2025-10-13', NULL, 5, '2025-10-13 05:07:22'),
(105, 22, 'Lorry Hire', 17000.00, 'Fixed', 17000.00, '2025-10-13', NULL, 5, '2025-10-13 05:07:22'),
(106, 22, 'Advance Cash', 12500.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:07:22'),
(107, 22, 'Labour Charge', 800.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:07:22'),
(108, 22, 'Dala Charge', 250.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:07:22'),
(112, 24, 'Billing Rate', 16000.00, 'Fixed', 16000.00, '2025-10-13', NULL, 5, '2025-10-13 05:16:27'),
(113, 24, 'Lorry Hire', 13000.00, 'Fixed', 13000.00, '2025-10-13', NULL, 5, '2025-10-13 05:16:27'),
(114, 24, 'Advance Cash', 13000.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:16:27'),
(115, 23, 'Billing Rate', 27000.00, 'Fixed', 27000.00, '2025-10-13', NULL, 5, '2025-10-13 05:16:58'),
(116, 23, 'Lorry Hire', 22100.00, 'Fixed', 22100.00, '2025-10-13', NULL, 5, '2025-10-13 05:16:58'),
(117, 23, 'Advance Cash', 16000.00, NULL, NULL, '2025-10-13', NULL, 5, '2025-10-13 05:16:58'),
(118, 26, 'Billing Rate', 8003.48, 'Kg', 0.97, '2025-10-16', NULL, 5, '2025-10-16 05:32:18'),
(119, 26, 'Lorry Hire', 7500.00, 'Fixed', 7500.00, '2025-10-16', NULL, 5, '2025-10-16 05:32:18'),
(120, 26, 'Advance Cash', 6200.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:32:18'),
(121, 39, 'Billing Rate', 14878.76, 'Kg', 1.80, '2025-10-16', NULL, 5, '2025-10-16 05:32:48'),
(122, 39, 'Lorry Hire', 14000.00, 'Fixed', 14000.00, '2025-10-16', NULL, 5, '2025-10-16 05:32:48'),
(123, 39, 'Advance Cash', 10700.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:32:48'),
(124, 32, 'Billing Rate', 14771.66, 'Kg', 1.80, '2025-10-16', NULL, 5, '2025-10-16 05:33:25'),
(125, 32, 'Lorry Hire', 14000.00, 'Fixed', 14000.00, '2025-10-16', NULL, 5, '2025-10-16 05:33:25'),
(126, 32, 'Advance Cash', 10300.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:33:25'),
(127, 25, 'Billing Rate', 12323.16, 'Kg', 1.50, '2025-10-16', NULL, 5, '2025-10-16 05:34:02'),
(128, 25, 'Lorry Hire', 11800.00, 'Fixed', 11800.00, '2025-10-16', NULL, 5, '2025-10-16 05:34:02'),
(129, 25, 'Advance Cash', 8800.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:34:02'),
(130, 57, 'Billing Rate', 14894.73, 'Kg', 1.80, '2025-10-16', NULL, 5, '2025-10-16 05:35:00'),
(131, 57, 'Lorry Hire', 14000.00, 'Fixed', 14000.00, '2025-10-16', NULL, 5, '2025-10-16 05:35:00'),
(132, 57, 'Advance Cash', 10700.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:35:00'),
(133, 60, 'Billing Rate', 48000.00, 'Fixed', 48000.00, '2025-10-16', NULL, 5, '2025-10-16 05:36:00'),
(134, 60, 'Lorry Hire', 46000.00, 'Fixed', 46000.00, '2025-10-16', NULL, 5, '2025-10-16 05:36:00'),
(135, 60, 'Advance Cash', 35300.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:36:00'),
(136, 60, 'Dala Charge', 300.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:36:00'),
(137, 61, 'Billing Rate', 22000.00, 'Fixed', 22000.00, '2025-10-16', NULL, 5, '2025-10-16 05:36:34'),
(138, 61, 'Lorry Hire', 20000.00, 'Fixed', 20000.00, '2025-10-16', NULL, 5, '2025-10-16 05:36:34'),
(139, 61, 'Advance Cash', 13000.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:36:34'),
(140, 61, 'Labour Charge', 1000.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:36:34'),
(141, 47, 'Billing Rate', 12370.94, 'Kg', 1.50, '2025-10-16', NULL, 5, '2025-10-16 05:37:20'),
(142, 47, 'Lorry Hire', 12000.00, 'Fixed', 12000.00, '2025-10-16', NULL, 5, '2025-10-16 05:37:20'),
(143, 47, 'Advance Cash', 8800.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:37:20'),
(144, 48, 'Billing Rate', 16597.19, 'Kg', 1.50, '2025-10-16', NULL, 5, '2025-10-16 05:38:22'),
(145, 48, 'Lorry Hire', 14000.00, 'Fixed', 14000.00, '2025-10-16', NULL, 5, '2025-10-16 05:38:22'),
(146, 54, 'Billing Rate', 10424.68, 'Kg', 1.27, '2025-10-16', NULL, 5, '2025-10-16 05:38:57'),
(147, 54, 'Lorry Hire', 9700.00, 'Fixed', 9700.00, '2025-10-16', NULL, 5, '2025-10-16 05:38:57'),
(148, 54, 'Advance Cash', 7400.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:38:57'),
(149, 42, 'Billing Rate', 18101.09, 'Kg', 2.20, '2025-10-16', NULL, 5, '2025-10-16 05:39:49'),
(150, 42, 'Lorry Hire', 17000.00, 'Fixed', 17000.00, '2025-10-16', NULL, 5, '2025-10-16 05:39:49'),
(151, 42, 'Advance Cash', 12200.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:39:49'),
(152, 44, 'Billing Rate', 12321.62, 'Kg', 1.50, '2025-10-16', NULL, 5, '2025-10-16 05:40:56'),
(153, 44, 'Lorry Hire', 11500.00, 'Fixed', 11500.00, '2025-10-16', NULL, 5, '2025-10-16 05:40:56'),
(154, 44, 'Advance Cash', 8800.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:40:56'),
(155, 12, 'Billing Rate', 12070.00, 'Kg', 1.42, '2025-10-16', NULL, 5, '2025-10-16 05:41:32'),
(156, 12, 'Lorry Hire', 11500.00, 'Fixed', 11500.00, '2025-10-16', NULL, 5, '2025-10-16 05:41:32'),
(157, 12, 'Advance Cash', 8800.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:41:32'),
(158, 13, 'Billing Rate', 9605.00, 'Kg', 1.13, '2025-10-16', NULL, 5, '2025-10-16 05:42:12'),
(159, 13, 'Lorry Hire', 8700.00, 'Fixed', 8700.00, '2025-10-16', NULL, 5, '2025-10-16 05:42:12'),
(160, 13, 'Advance Cash', 6700.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:42:12'),
(161, 43, 'Billing Rate', 9312.93, 'Kg', 1.13, '2025-10-16', NULL, 5, '2025-10-16 05:42:38'),
(162, 43, 'Lorry Hire', 8700.00, 'Fixed', 8700.00, '2025-10-16', NULL, 5, '2025-10-16 05:42:38'),
(163, 43, 'Advance Cash', 6700.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:42:38'),
(164, 45, 'Billing Rate', 18900.00, 'Fixed', 18900.00, '2025-10-16', NULL, 5, '2025-10-16 05:50:09'),
(165, 45, 'Lorry Hire', 16800.00, 'Fixed', 16800.00, '2025-10-16', NULL, 5, '2025-10-16 05:50:09'),
(166, 45, 'Advance Cash', 13750.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:50:09'),
(167, 45, 'Dala Charge', 250.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:50:09'),
(168, 21, 'Billing Rate', 12423.07, 'Kg', 1.50, '2025-10-16', NULL, 5, '2025-10-16 05:53:21'),
(169, 21, 'Lorry Hire', 11800.00, 'Fixed', 11800.00, '2025-10-16', NULL, 5, '2025-10-16 05:53:21'),
(170, 21, 'Advance Cash', 8800.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:53:21'),
(171, 18, 'Billing Rate', 12391.90, 'Kg', 1.50, '2025-10-16', NULL, 5, '2025-10-16 05:54:33'),
(172, 18, 'Lorry Hire', 11500.00, 'Fixed', 11500.00, '2025-10-16', NULL, 5, '2025-10-16 05:54:33'),
(173, 18, 'Advance Cash', 8800.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:54:33'),
(174, 11, 'Billing Rate', 12389.43, 'Kg', 1.50, '2025-10-16', NULL, 5, '2025-10-16 05:55:38'),
(175, 11, 'Lorry Hire', 11500.00, 'Fixed', 11500.00, '2025-10-16', NULL, 5, '2025-10-16 05:55:38'),
(176, 11, 'Advance Cash', 8800.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:55:38'),
(177, 19, 'Billing Rate', 14882.42, 'Kg', 1.80, '2025-10-16', NULL, 5, '2025-10-16 05:56:30'),
(178, 19, 'Lorry Hire', 14000.00, 'Fixed', 14000.00, '2025-10-16', NULL, 5, '2025-10-16 05:56:30'),
(179, 19, 'Advance Cash', 10700.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:56:30'),
(180, 51, 'Billing Rate', 68500.00, 'Fixed', 68500.00, '2025-10-16', NULL, 5, '2025-10-16 05:57:15'),
(181, 51, 'Lorry Hire', 65000.00, 'Fixed', 65000.00, '2025-10-16', NULL, 5, '2025-10-16 05:57:15'),
(182, 51, 'Advance Cash', 59750.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:57:15'),
(183, 51, 'Dala Charge', 250.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:57:15'),
(184, 55, 'Billing Rate', 9000.00, 'Fixed', 9000.00, '2025-10-16', NULL, 5, '2025-10-16 05:57:51'),
(185, 55, 'Lorry Hire', 8000.00, 'Fixed', 8000.00, '2025-10-16', NULL, 5, '2025-10-16 05:57:51'),
(186, 55, 'Advance Cash', 5000.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:57:51'),
(187, 55, 'Labour Charge', 800.00, NULL, NULL, '2025-10-16', NULL, 5, '2025-10-16 05:57:51'),
(188, 17, 'Billing Rate', 10919.36, 'Kg', 0.98, '2025-10-16', NULL, 5, '2025-10-16 06:00:59'),
(189, 17, 'Lorry Hire', 9700.00, 'Fixed', 9700.00, '2025-10-16', NULL, 5, '2025-10-16 06:00:59'),
(190, 53, 'Billing Rate', 11446.35, 'Kg', 0.98, '2025-10-16', NULL, 5, '2025-10-16 06:01:52'),
(191, 53, 'Lorry Hire', 9700.00, 'Fixed', 9700.00, '2025-10-16', NULL, 5, '2025-10-16 06:01:52'),
(192, 92, 'Billing Rate', 14769.16, 'Kg', 1.80, '2025-10-30', NULL, 5, '2025-10-30 11:30:47'),
(193, 92, 'Lorry Hire', 13800.00, 'Fixed', 13800.00, '2025-10-30', NULL, 5, '2025-10-30 11:30:47'),
(194, 92, 'Advance Cash', 10100.00, NULL, NULL, '2025-10-30', NULL, 5, '2025-10-30 11:30:47'),
(195, 92, 'Dala Charge', 250.00, NULL, NULL, '2025-10-30', NULL, 5, '2025-10-30 11:30:47'),
(196, 119, 'Billing Rate', 11746.06, 'Kg', 1.42, '2025-10-31', NULL, 5, '2025-10-31 08:56:48'),
(197, 119, 'Lorry Hire', 11000.00, 'Fixed', 11000.00, '2025-10-31', NULL, 5, '2025-10-31 08:56:48'),
(198, 119, 'Advance Cash', 8600.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 08:56:48'),
(199, 123, 'Billing Rate', 16000.00, 'Fixed', 16000.00, '2025-10-31', NULL, 5, '2025-10-31 08:57:39'),
(200, 123, 'Lorry Hire', 15000.00, 'Fixed', 15000.00, '2025-10-31', NULL, 5, '2025-10-31 08:57:39'),
(201, 123, 'Advance Cash', 8000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 08:57:39'),
(202, 123, 'Labour Charge', 1100.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 08:57:39'),
(203, 122, 'Billing Rate', 4900.00, 'Fixed', 4900.00, '2025-10-31', NULL, 5, '2025-10-31 08:58:29'),
(204, 122, 'Lorry Hire', 4500.00, 'Fixed', 4500.00, '2025-10-31', NULL, 5, '2025-10-31 08:58:29'),
(205, 122, 'Advance Cash', 2000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 08:58:29'),
(206, 122, 'Labour Charge', 1000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 08:58:29'),
(207, 111, 'Billing Rate', 12359.50, 'Kg', 1.50, '2025-10-31', NULL, 5, '2025-10-31 08:59:02'),
(208, 111, 'Lorry Hire', 11500.00, 'Fixed', 11500.00, '2025-10-31', NULL, 5, '2025-10-31 08:59:02'),
(209, 111, 'Advance Cash', 8800.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 08:59:02'),
(210, 120, 'Billing Rate', 9337.24, 'Kg', 1.13, '2025-10-31', NULL, 5, '2025-10-31 09:02:22'),
(211, 120, 'Lorry Hire', 8500.00, 'Fixed', 8500.00, '2025-10-31', NULL, 5, '2025-10-31 09:02:22'),
(212, 120, 'Advance Cash', 6700.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:02:22'),
(213, 121, 'Billing Rate', 14795.09, 'Kg', 1.80, '2025-10-31', NULL, 5, '2025-10-31 09:04:42'),
(214, 121, 'Lorry Hire', 13700.00, 'Fixed', 13700.00, '2025-10-31', NULL, 5, '2025-10-31 09:04:42'),
(215, 121, 'Advance Cash', 10100.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:04:42'),
(216, 116, 'Billing Rate', 86000.00, 'Fixed', 86000.00, '2025-10-31', NULL, 5, '2025-10-31 09:06:10'),
(217, 116, 'Lorry Hire', 81000.00, 'Fixed', 81000.00, '2025-10-31', NULL, 5, '2025-10-31 09:06:10'),
(218, 116, 'Advance Cash', 50000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:06:10'),
(219, 116, 'Labour Charge', 1000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:06:10'),
(223, 112, 'Billing Rate', 18193.21, 'Kg', 2.20, '2025-10-31', NULL, 5, '2025-10-31 09:07:30'),
(224, 112, 'Lorry Hire', 17000.00, 'Fixed', 17000.00, '2025-10-31', NULL, 5, '2025-10-31 09:07:30'),
(225, 112, 'Advance Cash', 12400.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:07:30'),
(226, 102, 'Billing Rate', 14872.95, 'Kg', 1.80, '2025-10-31', NULL, 5, '2025-10-31 09:07:59'),
(227, 102, 'Lorry Hire', 14000.00, 'Fixed', 14000.00, '2025-10-31', NULL, 5, '2025-10-31 09:07:59'),
(228, 102, 'Advance Cash', 10700.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:07:59'),
(229, 117, 'Billing Rate', 8023.75, 'Kg', 0.97, '2025-10-31', NULL, 5, '2025-10-31 09:08:38'),
(230, 117, 'Lorry Hire', 7500.00, 'Fixed', 7500.00, '2025-10-31', NULL, 5, '2025-10-31 09:08:38'),
(231, 117, 'Advance Cash', 6200.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:08:38'),
(232, 109, 'Billing Rate', 14853.26, 'Kg', 1.80, '2025-10-31', NULL, 5, '2025-10-31 09:09:11'),
(233, 109, 'Lorry Hire', 14000.00, 'Fixed', 14000.00, '2025-10-31', NULL, 5, '2025-10-31 09:09:11'),
(234, 109, 'Advance Cash', 10700.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:09:11'),
(235, 101, 'Billing Rate', 10478.41, 'Kg', 1.27, '2025-10-31', NULL, 5, '2025-10-31 09:09:52'),
(236, 101, 'Lorry Hire', 9700.00, 'Fixed', 9700.00, '2025-10-31', NULL, 5, '2025-10-31 09:09:52'),
(237, 101, 'Advance Cash', 7400.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:09:52'),
(238, 97, 'Billing Rate', 10472.97, 'Kg', 1.27, '2025-10-31', NULL, 5, '2025-10-31 09:10:39'),
(239, 97, 'Lorry Hire', 9700.00, 'Fixed', 9700.00, '2025-10-31', NULL, 5, '2025-10-31 09:10:39'),
(240, 97, 'Advance Cash', 7400.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:10:39'),
(241, 76, 'Billing Rate', 12387.66, 'Kg', 1.50, '2025-10-31', NULL, 5, '2025-10-31 09:12:19'),
(242, 76, 'Lorry Hire', 11500.00, 'Fixed', 11500.00, '2025-10-31', NULL, 5, '2025-10-31 09:12:19'),
(243, 76, 'Advance Cash', 8800.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:12:19'),
(244, 84, 'Billing Rate', 14860.57, 'Kg', 1.80, '2025-10-31', NULL, 5, '2025-10-31 09:12:49'),
(245, 84, 'Lorry Hire', 14000.00, 'Fixed', 14000.00, '2025-10-31', NULL, 5, '2025-10-31 09:12:49'),
(246, 84, 'Advance Cash', 10700.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:12:49'),
(247, 79, 'Billing Rate', 12302.55, 'Kg', 1.50, '2025-10-31', NULL, 5, '2025-10-31 09:13:20'),
(248, 79, 'Lorry Hire', 11500.00, 'Fixed', 11500.00, '2025-10-31', NULL, 5, '2025-10-31 09:13:20'),
(249, 79, 'Advance Cash', 8800.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:13:20'),
(250, 77, 'Billing Rate', 11722.02, 'Kg', 1.42, '2025-10-31', NULL, 5, '2025-10-31 09:14:01'),
(251, 77, 'Lorry Hire', 11000.00, 'Fixed', 11000.00, '2025-10-31', NULL, 5, '2025-10-31 09:14:01'),
(252, 77, 'Advance Cash', 8600.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:14:01'),
(253, 69, 'Billing Rate', 9311.06, 'Kg', 1.13, '2025-10-31', NULL, 5, '2025-10-31 09:15:27'),
(254, 69, 'Lorry Hire', 8800.00, 'Fixed', 8800.00, '2025-10-31', NULL, 5, '2025-10-31 09:15:27'),
(255, 69, 'Advance Cash', 6700.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:15:27'),
(256, 78, 'Billing Rate', 15174.78, 'Kg', 1.85, '2025-10-31', NULL, 5, '2025-10-31 09:17:22'),
(257, 78, 'Lorry Hire', 14000.00, 'Fixed', 14000.00, '2025-10-31', NULL, 5, '2025-10-31 09:17:22'),
(258, 78, 'Advance Cash', 9900.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:17:22'),
(259, 85, 'Billing Rate', 11553.47, 'Kg', 0.98, '2025-10-31', NULL, 5, '2025-10-31 09:18:26'),
(260, 85, 'Lorry Hire', 9700.00, 'Fixed', 9700.00, '2025-10-31', NULL, 5, '2025-10-31 09:18:26'),
(261, 85, 'Advance Cash', 9700.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:18:26'),
(262, 83, 'Billing Rate', 7500.00, 'Fixed', 7500.00, '2025-10-31', NULL, 5, '2025-10-31 09:22:36'),
(263, 83, 'Lorry Hire', 6800.00, 'Fixed', 6800.00, '2025-10-31', NULL, 5, '2025-10-31 09:22:36'),
(264, 83, 'Advance Cash', 4000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:22:36'),
(265, 83, 'Labour Charge', 1000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:22:36'),
(266, 106, 'Billing Rate', 29000.00, 'Fixed', 29000.00, '2025-10-31', NULL, 5, '2025-10-31 09:23:56'),
(267, 106, 'Lorry Hire', 27000.00, 'Fixed', 27000.00, '2025-10-31', NULL, 5, '2025-10-31 09:23:56'),
(268, 106, 'Advance Cash', 17500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:23:56'),
(269, 106, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:23:56'),
(270, 105, 'Billing Rate', 25000.00, 'Fixed', 25000.00, '2025-10-31', NULL, 5, '2025-10-31 09:25:22'),
(271, 105, 'Lorry Hire', 23000.00, 'Fixed', 23000.00, '2025-10-31', NULL, 5, '2025-10-31 09:25:22'),
(272, 105, 'Advance Cash', 16000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:25:22'),
(273, 105, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:25:22'),
(274, 107, 'Billing Rate', 68500.00, 'Fixed', 68500.00, '2025-10-31', NULL, 5, '2025-10-31 09:26:45'),
(275, 107, 'Lorry Hire', 65000.00, 'Fixed', 65000.00, '2025-10-31', NULL, 5, '2025-10-31 09:26:45'),
(276, 107, 'Advance Cash', 59750.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:26:45'),
(277, 107, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:26:45'),
(278, 108, 'Billing Rate', 68500.00, 'Fixed', 68500.00, '2025-10-31', NULL, 5, '2025-10-31 09:28:09'),
(279, 108, 'Lorry Hire', 65000.00, 'Fixed', 65000.00, '2025-10-31', NULL, 5, '2025-10-31 09:28:09'),
(280, 108, 'Advance Cash', 59750.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:28:09'),
(281, 108, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:28:09'),
(282, 96, 'Billing Rate', 68500.00, 'Fixed', 68500.00, '2025-10-31', NULL, 5, '2025-10-31 09:28:54'),
(283, 96, 'Lorry Hire', 65000.00, 'Fixed', 65000.00, '2025-10-31', NULL, 5, '2025-10-31 09:28:54'),
(284, 96, 'Advance Cash', 59750.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:28:54'),
(285, 96, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:28:54'),
(286, 98, 'Billing Rate', 68500.00, 'Fixed', 68500.00, '2025-10-31', NULL, 5, '2025-10-31 09:29:45'),
(287, 98, 'Lorry Hire', 65000.00, 'Fixed', 65000.00, '2025-10-31', NULL, 5, '2025-10-31 09:29:45'),
(288, 98, 'Advance Cash', 60000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:29:45'),
(289, 98, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:29:45'),
(290, 99, 'Billing Rate', 15500.00, 'Fixed', 15500.00, '2025-10-31', NULL, 5, '2025-10-31 09:30:39'),
(291, 99, 'Lorry Hire', 14700.00, 'Fixed', 14700.00, '2025-10-31', NULL, 5, '2025-10-31 09:30:39'),
(292, 99, 'Advance Cash', 9500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:30:39'),
(293, 99, 'Labour Charge', 800.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:30:39'),
(294, 100, 'Billing Rate', 36990.00, 'Fixed', 36990.00, '2025-10-31', NULL, 5, '2025-10-31 09:31:24'),
(295, 100, 'Lorry Hire', 34000.00, 'Fixed', 34000.00, '2025-10-31', NULL, 5, '2025-10-31 09:31:24'),
(296, 100, 'Advance Cash', 19000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:31:24'),
(297, 100, 'Labour Charge', 1100.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:31:24'),
(298, 100, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:31:24'),
(299, 118, 'Billing Rate', 68500.00, 'Fixed', 68500.00, '2025-10-31', NULL, 5, '2025-10-31 09:35:01'),
(300, 118, 'Lorry Hire', 65000.00, 'Fixed', 65000.00, '2025-10-31', NULL, 5, '2025-10-31 09:35:01'),
(301, 118, 'Advance Cash', 59750.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:35:01'),
(302, 118, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:35:01'),
(303, 93, 'Billing Rate', 17800.00, 'Fixed', 17800.00, '2025-10-31', NULL, 5, '2025-10-31 09:35:49'),
(304, 93, 'Lorry Hire', 17000.00, 'Fixed', 17000.00, '2025-10-31', NULL, 5, '2025-10-31 09:35:49'),
(305, 93, 'Advance Cash', 12500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:35:49'),
(306, 93, 'Labour Charge', 1000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:35:49'),
(307, 93, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:35:49'),
(308, 33, 'Billing Rate', 5618.95, 'Kg', 0.62, '2025-10-31', NULL, 5, '2025-10-31 09:37:19'),
(309, 33, 'Lorry Hire', 4700.00, 'Fixed', 4700.00, '2025-10-31', NULL, 5, '2025-10-31 09:37:19'),
(310, 33, 'Advance Cash', 4700.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:37:19'),
(311, 113, 'Billing Rate', 10823.10, 'Kg', 0.98, '2025-10-31', NULL, 5, '2025-10-31 09:37:50'),
(312, 113, 'Lorry Hire', 9700.00, 'Fixed', 9700.00, '2025-10-31', NULL, 5, '2025-10-31 09:37:50'),
(313, 113, 'Advance Cash', 9700.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:37:50'),
(314, 95, 'Billing Rate', 6128.34, 'Kg', 0.68, '2025-10-31', NULL, 5, '2025-10-31 09:39:10'),
(315, 95, 'Lorry Hire', 5500.00, 'Fixed', 5500.00, '2025-10-31', NULL, 5, '2025-10-31 09:39:10'),
(316, 95, 'Advance Cash', 5500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:39:10'),
(317, 94, 'Billing Rate', 7209.07, 'Kg', 0.80, '2025-10-31', NULL, 5, '2025-10-31 09:40:17'),
(318, 94, 'Lorry Hire', 6500.00, 'Fixed', 6500.00, '2025-10-31', NULL, 5, '2025-10-31 09:40:17'),
(319, 94, 'Advance Cash', 6500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:40:17'),
(320, 62, 'Billing Rate', 6133.60, 'Kg', 0.68, '2025-10-31', NULL, 5, '2025-10-31 09:42:25'),
(321, 62, 'Lorry Hire', 5500.00, 'Fixed', 5500.00, '2025-10-31', NULL, 5, '2025-10-31 09:42:25'),
(322, 62, 'Advance Cash', 5500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:42:25'),
(323, 63, 'Billing Rate', 6499.68, 'Kg', 0.72, '2025-10-31', NULL, 5, '2025-10-31 09:43:37'),
(324, 63, 'Lorry Hire', 5300.00, 'Fixed', 5300.00, '2025-10-31', NULL, 5, '2025-10-31 09:43:37'),
(325, 63, 'Advance Cash', 5300.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:43:37'),
(326, 58, 'Billing Rate', 7243.17, 'Kg', 0.80, '2025-10-31', NULL, 5, '2025-10-31 09:44:36'),
(327, 58, 'Lorry Hire', 6500.00, 'Fixed', 6500.00, '2025-10-31', NULL, 5, '2025-10-31 09:44:36'),
(328, 58, 'Advance Cash', 6500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:44:36'),
(329, 27, 'Billing Rate', 7205.37, 'Kg', 0.80, '2025-10-31', NULL, 5, '2025-10-31 09:46:10'),
(330, 27, 'Lorry Hire', 6500.00, 'Fixed', 6500.00, '2025-10-31', NULL, 5, '2025-10-31 09:46:10'),
(331, 27, 'Advance Cash', 6500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:46:10'),
(332, 37, 'Billing Rate', 6133.18, 'Kg', 0.68, '2025-10-31', NULL, 5, '2025-10-31 09:47:25'),
(333, 37, 'Lorry Hire', 5500.00, 'Fixed', 5500.00, '2025-10-31', NULL, 5, '2025-10-31 09:47:25'),
(334, 37, 'Advance Cash', 5500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:47:25'),
(335, 36, 'Billing Rate', 3332.13, 'Kg', 0.37, '2025-10-31', NULL, 5, '2025-10-31 09:48:13'),
(336, 36, 'Lorry Hire', 3100.00, 'Fixed', 3100.00, '2025-10-31', NULL, 5, '2025-10-31 09:48:13'),
(337, 36, 'Advance Cash', 3100.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:48:13'),
(338, 52, 'Billing Rate', 3357.76, 'Kg', 0.37, '2025-10-31', NULL, 5, '2025-10-31 09:48:46'),
(339, 52, 'Lorry Hire', 3100.00, 'Fixed', 3100.00, '2025-10-31', NULL, 5, '2025-10-31 09:48:46'),
(340, 52, 'Advance Cash', 3100.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:48:46'),
(341, 64, 'Billing Rate', 21990.00, 'Fixed', 21990.00, '2025-10-31', NULL, 5, '2025-10-31 09:52:56'),
(342, 64, 'Lorry Hire', 19000.00, 'Fixed', 19000.00, '2025-10-31', NULL, 5, '2025-10-31 09:52:56'),
(343, 64, 'Advance Cash', 13500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:52:56'),
(344, 64, 'Labour Charge', 800.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:52:56'),
(345, 64, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:52:56'),
(346, 90, 'Billing Rate', 12353.96, 'Kg', 1.50, '2025-10-31', NULL, 5, '2025-10-31 09:53:41'),
(347, 90, 'Lorry Hire', 11500.00, 'Fixed', 11500.00, '2025-10-31', NULL, 5, '2025-10-31 09:53:41'),
(348, 90, 'Advance Cash', 8800.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:53:41'),
(353, 91, 'Billing Rate', 16000.00, 'Fixed', 16000.00, '2025-10-31', NULL, 5, '2025-10-31 09:56:44'),
(354, 91, 'Lorry Hire', 14500.00, 'Fixed', 14500.00, '2025-10-31', NULL, 5, '2025-10-31 09:56:44'),
(355, 91, 'Advance Cash', 8000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:56:44'),
(356, 91, 'Labour Charge', 1000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:56:44'),
(357, 91, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:56:44'),
(358, 88, 'Billing Rate', 29500.00, 'Fixed', 29500.00, '2025-10-31', NULL, 5, '2025-10-31 09:57:31'),
(359, 88, 'Lorry Hire', 27000.00, 'Fixed', 27000.00, '2025-10-31', NULL, 5, '2025-10-31 09:57:31'),
(360, 88, 'Advance Cash', 19000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:57:31'),
(361, 88, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:57:31'),
(362, 87, 'Billing Rate', 42000.00, 'Fixed', 42000.00, '2025-10-31', NULL, 5, '2025-10-31 09:58:24'),
(363, 87, 'Lorry Hire', 40000.00, 'Fixed', 40000.00, '2025-10-31', NULL, 5, '2025-10-31 09:58:24'),
(364, 87, 'Advance Cash', 33000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:58:24'),
(365, 87, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:58:24'),
(366, 86, 'Billing Rate', 47000.00, 'Fixed', 47000.00, '2025-10-31', NULL, 5, '2025-10-31 09:59:14'),
(367, 86, 'Lorry Hire', 44000.00, 'Fixed', 44000.00, '2025-10-31', NULL, 5, '2025-10-31 09:59:14'),
(368, 86, 'Advance Cash', 37000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 09:59:14'),
(369, 56, 'Billing Rate', 7500.00, 'Fixed', 7500.00, '2025-10-31', NULL, 5, '2025-10-31 10:08:15'),
(370, 56, 'Lorry Hire', 6700.00, 'Fixed', 6700.00, '2025-10-31', NULL, 5, '2025-10-31 10:08:15'),
(371, 56, 'Advance Cash', 4000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 10:08:15'),
(372, 56, 'Labour Charge', 1000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 10:08:15'),
(373, 59, 'Billing Rate', 6330.90, 'Kg', 0.70, '2025-10-31', NULL, 5, '2025-10-31 10:14:30'),
(374, 59, 'Lorry Hire', 5500.00, 'Fixed', 5500.00, '2025-10-31', NULL, 5, '2025-10-31 10:14:30'),
(375, 59, 'Advance Cash', 5500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 10:14:30'),
(376, 89, 'Billing Rate', 9362.77, 'Kg', 1.13, '2025-10-31', NULL, 5, '2025-10-31 10:44:44'),
(377, 89, 'Lorry Hire', 8700.00, 'Fixed', 8700.00, '2025-10-31', NULL, 5, '2025-10-31 10:44:44'),
(378, 89, 'Advance Cash', 6700.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 10:44:44'),
(379, 89, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 10:44:44'),
(380, 70, 'Billing Rate', 24500.00, 'Fixed', 24500.00, '2025-10-31', NULL, 5, '2025-10-31 11:04:31'),
(381, 70, 'Lorry Hire', 23000.00, 'Fixed', 23000.00, '2025-10-31', NULL, 5, '2025-10-31 11:04:31'),
(382, 70, 'Advance Cash', 15000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:04:31'),
(383, 124, 'Billing Rate', 11500.00, 'Fixed', 11500.00, '2025-10-31', NULL, 5, '2025-10-31 11:08:41'),
(384, 124, 'Lorry Hire', 10500.00, 'Fixed', 10500.00, '2025-10-31', NULL, 5, '2025-10-31 11:08:41'),
(385, 124, 'Advance Cash', 10500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:08:41'),
(386, 110, 'Billing Rate', 29500.00, 'Fixed', 29500.00, '2025-10-31', NULL, 5, '2025-10-31 11:10:07'),
(387, 110, 'Lorry Hire', 27500.00, 'Fixed', 27500.00, '2025-10-31', NULL, 5, '2025-10-31 11:10:07'),
(388, 110, 'Advance Cash', 23950.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:10:07'),
(389, 110, 'Dala Charge', 550.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:10:07'),
(394, 67, 'Billing Rate', 29000.00, 'Fixed', 29000.00, '2025-10-31', NULL, 5, '2025-10-31 11:17:47'),
(395, 67, 'Lorry Hire', 0.00, 'Fixed', 0.00, '2025-10-31', NULL, 5, '2025-10-31 11:17:47'),
(396, 115, 'Billing Rate', 21990.00, 'Fixed', 21990.00, '2025-10-31', NULL, 5, '2025-10-31 11:19:13'),
(397, 115, 'Lorry Hire', 20000.00, 'Fixed', 20000.00, '2025-10-31', NULL, 5, '2025-10-31 11:19:13'),
(398, 115, 'Advance Cash', 13500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:19:13'),
(399, 115, 'Labour Charge', 1000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:19:13'),
(400, 115, 'Dala Charge', 550.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:19:13'),
(401, 74, 'Billing Rate', 7000.00, 'Fixed', 7000.00, '2025-10-31', NULL, 5, '2025-10-31 11:22:43'),
(402, 74, 'Lorry Hire', 6500.00, 'Fixed', 6500.00, '2025-10-31', NULL, 5, '2025-10-31 11:22:43'),
(403, 74, 'Advance Cash', 4000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:22:43'),
(404, 74, 'Labour Charge', 1000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:22:43'),
(405, 73, 'Billing Rate', 25000.00, 'Fixed', 25000.00, '2025-10-31', NULL, 5, '2025-10-31 11:23:22'),
(406, 73, 'Lorry Hire', 23000.00, 'Fixed', 23000.00, '2025-10-31', NULL, 5, '2025-10-31 11:23:22'),
(407, 73, 'Advance Cash', 16000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:23:22'),
(408, 73, 'Labour Charge', 1000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:23:22'),
(409, 80, 'Billing Rate', 26900.00, 'Fixed', 26900.00, '2025-10-31', NULL, 5, '2025-10-31 11:24:13'),
(410, 80, 'Lorry Hire', 24000.00, 'Fixed', 24000.00, '2025-10-31', NULL, 5, '2025-10-31 11:24:13'),
(411, 80, 'Advance Cash', 21750.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:24:13'),
(412, 80, 'Dala Charge', 250.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:24:13'),
(413, 71, 'Billing Rate', 17800.00, 'Fixed', 17800.00, '2025-10-31', NULL, 5, '2025-10-31 11:25:45'),
(414, 71, 'Lorry Hire', 16800.00, 'Fixed', 16800.00, '2025-10-31', NULL, 5, '2025-10-31 11:25:45'),
(415, 71, 'Advance Cash', 12500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:25:45'),
(416, 71, 'Labour Charge', 800.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:25:45'),
(417, 114, 'Billing Rate', 46000.00, 'Fixed', 46000.00, '2025-10-31', NULL, 5, '2025-10-31 11:27:56'),
(418, 114, 'Lorry Hire', 44000.00, 'Fixed', 44000.00, '2025-10-31', NULL, 5, '2025-10-31 11:27:56'),
(419, 114, 'Advance Cash', 35000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:27:56'),
(420, 66, 'Billing Rate', 39000.00, 'Fixed', 39000.00, '2025-10-31', NULL, 5, '2025-10-31 11:29:10'),
(421, 66, 'Lorry Hire', 36000.00, 'Fixed', 36000.00, '2025-10-31', NULL, 5, '2025-10-31 11:29:10'),
(422, 66, 'Advance Cash', 27000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:29:10'),
(423, 65, 'Billing Rate', 39000.00, 'Fixed', 39000.00, '2025-10-31', NULL, 5, '2025-10-31 11:29:46'),
(424, 65, 'Lorry Hire', 36000.00, 'Fixed', 36000.00, '2025-10-31', NULL, 5, '2025-10-31 11:29:46'),
(425, 65, 'Advance Cash', 24000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:29:46'),
(426, 81, 'Billing Rate', 46000.00, 'Fixed', 46000.00, '2025-10-31', NULL, 5, '2025-10-31 11:32:08'),
(427, 81, 'Lorry Hire', 44000.00, 'Fixed', 44000.00, '2025-10-31', NULL, 5, '2025-10-31 11:32:08'),
(428, 81, 'Advance Cash', 35000.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:32:08'),
(432, 72, 'Billing Rate', 11000.00, 'Fixed', 11000.00, '2025-10-31', NULL, 5, '2025-10-31 11:34:18'),
(433, 72, 'Lorry Hire', 10500.00, 'Fixed', 10500.00, '2025-10-31', NULL, 5, '2025-10-31 11:34:18'),
(434, 72, 'Advance Cash', 10500.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 11:34:18'),
(435, 125, 'Billing Rate', 12317.82, 'Kg', 1.50, '2025-10-31', NULL, 5, '2025-10-31 13:53:35'),
(436, 125, 'Lorry Hire', 11500.00, 'Fixed', 11500.00, '2025-10-31', NULL, 5, '2025-10-31 13:53:35'),
(437, 125, 'Advance Cash', 8600.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 13:53:35'),
(438, 126, 'Billing Rate', 12414.98, 'Kg', 1.50, '2025-10-31', NULL, 5, '2025-10-31 13:53:59'),
(439, 126, 'Lorry Hire', 11500.00, 'Fixed', 11500.00, '2025-10-31', NULL, 5, '2025-10-31 13:53:59'),
(440, 126, 'Advance Cash', 8800.00, NULL, NULL, '2025-10-31', NULL, 5, '2025-10-31 13:53:59'),
(441, 135, 'Billing Rate', 14870.39, 'Kg', 1.80, '2025-11-02', NULL, 5, '2025-11-02 10:52:53'),
(442, 135, 'Lorry Hire', 14000.00, 'Fixed', 14000.00, '2025-11-02', NULL, 5, '2025-11-02 10:52:53'),
(443, 135, 'Advance Cash', 10700.00, NULL, NULL, '2025-11-02', NULL, 5, '2025-11-02 10:52:53'),
(444, 136, 'Billing Rate', 12388.44, 'Kg', 1.50, '2025-11-02', NULL, 5, '2025-11-02 10:53:27'),
(445, 136, 'Lorry Hire', 11500.00, 'Fixed', 11500.00, '2025-11-02', NULL, 5, '2025-11-02 10:53:27'),
(446, 136, 'Advance Cash', 8800.00, NULL, NULL, '2025-11-02', NULL, 5, '2025-11-02 10:53:27'),
(447, 130, 'Billing Rate', 25000.00, 'Fixed', 25000.00, '2025-11-02', NULL, 5, '2025-11-02 10:53:54'),
(448, 130, 'Lorry Hire', 23000.00, 'Fixed', 23000.00, '2025-11-02', NULL, 5, '2025-11-02 10:53:54'),
(449, 130, 'Advance Cash', 18000.00, NULL, NULL, '2025-11-02', NULL, 5, '2025-11-02 10:53:54'),
(450, 130, 'Labour Charge', 1000.00, NULL, NULL, '2025-11-02', NULL, 5, '2025-11-02 10:53:54'),
(451, 134, 'Billing Rate', 68500.00, 'Fixed', 68500.00, '2025-11-02', NULL, 5, '2025-11-02 10:54:41'),
(452, 134, 'Lorry Hire', 65000.00, 'Fixed', 65000.00, '2025-11-02', NULL, 5, '2025-11-02 10:54:41'),
(453, 134, 'Advance Cash', 59750.00, NULL, NULL, '2025-11-02', NULL, 5, '2025-11-02 10:54:41'),
(454, 134, 'Dala Charge', 250.00, NULL, NULL, '2025-11-02', NULL, 5, '2025-11-02 10:54:41'),
(455, 129, 'Billing Rate', 16259.61, 'Kg', 0.98, '2025-11-02', NULL, 5, '2025-11-02 10:55:37'),
(456, 129, 'Lorry Hire', 14000.00, 'Fixed', 14000.00, '2025-11-02', NULL, 5, '2025-11-02 10:55:37'),
(457, 129, 'Advance Cash', 8000.00, NULL, NULL, '2025-11-02', NULL, 5, '2025-11-02 10:55:37'),
(458, 132, 'Billing Rate', 14997.05, 'Kg', 1.80, '2025-11-02', NULL, 5, '2025-11-02 10:56:12'),
(459, 132, 'Lorry Hire', 14000.00, 'Fixed', 14000.00, '2025-11-02', NULL, 5, '2025-11-02 10:56:12'),
(460, 132, 'Advance Cash', 10700.00, NULL, NULL, '2025-11-02', NULL, 5, '2025-11-02 10:56:12'),
(461, 127, 'Billing Rate', 5923.58, 'Kg', 0.62, '2025-11-03', NULL, 5, '2025-11-03 10:58:19'),
(462, 127, 'Lorry Hire', 4700.00, 'Fixed', 4700.00, '2025-11-03', NULL, 5, '2025-11-03 10:58:19'),
(463, 127, 'Advance Cash', 4700.00, NULL, NULL, '2025-11-03', NULL, 5, '2025-11-03 10:58:19'),
(464, 138, 'Billing Rate', 6500.00, 'Fixed', 6500.00, '2025-11-04', NULL, 5, '2025-11-04 07:29:10'),
(465, 138, 'Lorry Hire', 6000.00, 'Fixed', 6000.00, '2025-11-04', NULL, 5, '2025-11-04 07:29:10'),
(466, 138, 'Advance Cash', 3000.00, NULL, NULL, '2025-11-04', NULL, 5, '2025-11-04 07:29:10'),
(467, 138, 'Labour Charge', 1000.00, NULL, NULL, '2025-11-04', NULL, 5, '2025-11-04 07:29:10'),
(468, 139, 'Billing Rate', 68500.00, 'Fixed', 68500.00, '2025-11-04', NULL, 5, '2025-11-04 07:31:21'),
(469, 139, 'Lorry Hire', 78000.00, 'Fixed', 78000.00, '2025-11-04', NULL, 5, '2025-11-04 07:31:21'),
(470, 139, 'Advance Cash', 72750.00, NULL, NULL, '2025-11-04', NULL, 5, '2025-11-04 07:31:21'),
(471, 139, 'Dala Charge', 250.00, NULL, NULL, '2025-11-04', NULL, 5, '2025-11-04 07:31:21'),
(472, 7, 'Billing Rate', 29000.00, 'Fixed', 29000.00, '2025-11-04', NULL, 5, '2025-11-04 12:42:18'),
(473, 7, 'Lorry Hire', 27000.00, 'Fixed', 27000.00, '2025-11-04', NULL, 5, '2025-11-04 12:42:18'),
(474, 7, 'Advance Cash', 17500.00, NULL, NULL, '2025-11-04', NULL, 5, '2025-11-04 12:42:18'),
(475, 7, 'Dala Charge', 250.00, NULL, NULL, '2025-11-04', NULL, 5, '2025-11-04 12:42:18'),
(476, 143, 'Billing Rate', 127658.00, 'Ton', 3100.00, '2025-11-04', NULL, 4, '2025-11-04 15:30:51'),
(477, 143, 'Lorry Hire', 119422.00, 'Ton', 2900.00, '2025-11-04', NULL, 4, '2025-11-04 15:30:51'),
(478, 143, 'Advance Cash', 95000.00, NULL, NULL, '2025-11-04', NULL, 4, '2025-11-04 15:30:51'),
(479, 143, 'Dala Charge', 500.00, NULL, NULL, '2025-11-04', NULL, 4, '2025-11-04 15:30:51'),
(480, 140, 'Billing Rate', 96140.00, 'Ton', 2300.00, '2025-11-04', NULL, 4, '2025-11-04 15:49:28'),
(481, 140, 'Lorry Hire', 94050.00, 'Ton', 2250.00, '2025-11-04', NULL, 4, '2025-11-04 15:49:28'),
(482, 140, 'Advance Cash', 78550.00, NULL, NULL, '2025-11-04', NULL, 4, '2025-11-04 15:49:28'),
(483, 140, 'Dala Charge', 500.00, NULL, NULL, '2025-11-04', NULL, 4, '2025-11-04 15:49:28'),
(484, 82, 'Billing Rate', 0.00, 'Fixed', 0.00, '2025-11-05', NULL, 5, '2025-11-05 07:08:32'),
(485, 82, 'Lorry Hire', 0.00, 'Fixed', 0.00, '2025-11-05', NULL, 5, '2025-11-05 07:08:32'),
(486, 141, 'Billing Rate', 9295.32, 'Kg', 1.13, '2025-11-05', NULL, 5, '2025-11-05 07:14:50'),
(487, 141, 'Lorry Hire', 8700.00, 'Fixed', 8700.00, '2025-11-05', NULL, 5, '2025-11-05 07:14:50'),
(488, 141, 'Advance Cash', 6700.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 07:14:50'),
(489, 142, 'Billing Rate', 10439.72, 'Kg', 1.27, '2025-11-05', NULL, 5, '2025-11-05 07:15:20'),
(490, 142, 'Lorry Hire', 9500.00, 'Fixed', 9500.00, '2025-11-05', NULL, 5, '2025-11-05 07:15:20'),
(491, 142, 'Advance Cash', 7400.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 07:15:20'),
(492, 152, 'Billing Rate', 17000.00, 'Fixed', 17000.00, '2025-11-05', NULL, 5, '2025-11-05 07:15:50'),
(493, 152, 'Lorry Hire', 15500.00, 'Fixed', 15500.00, '2025-11-05', NULL, 5, '2025-11-05 07:15:50'),
(494, 152, 'Advance Cash', 9500.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 07:15:50'),
(495, 152, 'Labour Charge', 1000.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 07:15:50'),
(496, 152, 'Dala Charge', 250.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 07:15:50'),
(497, 75, 'Billing Rate', 20000.00, 'Fixed', 20000.00, '2025-11-05', NULL, 5, '2025-11-05 07:17:18'),
(498, 75, 'Lorry Hire', 18000.00, 'Fixed', 18000.00, '2025-11-05', NULL, 5, '2025-11-05 07:17:18'),
(499, 75, 'Advance Cash', 13500.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 07:17:18'),
(500, 75, 'Dala Charge', 250.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 07:17:18'),
(501, 68, 'Billing Rate', 7000.00, 'Fixed', 7000.00, '2025-11-05', NULL, 5, '2025-11-05 07:17:26'),
(502, 68, 'Lorry Hire', 6500.00, 'Fixed', 6500.00, '2025-11-05', NULL, 5, '2025-11-05 07:17:26'),
(503, 68, 'Advance Cash', 4000.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 07:17:26'),
(504, 68, 'Labour Charge', 1000.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 07:17:26'),
(505, 131, 'Billing Rate', 15500.00, 'Fixed', 15500.00, '2025-11-05', NULL, 5, '2025-11-05 07:18:25'),
(506, 131, 'Lorry Hire', 14500.00, 'Fixed', 14500.00, '2025-11-05', NULL, 5, '2025-11-05 07:18:25'),
(507, 131, 'Advance Cash', 9500.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 07:18:25'),
(508, 131, 'Labour Charge', 1800.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 07:18:25'),
(509, 131, 'Dala Charge', 250.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 07:18:25'),
(510, 153, 'Billing Rate', 47000.00, 'Fixed', 47000.00, '2025-11-05', NULL, 5, '2025-11-05 07:20:42'),
(511, 153, 'Lorry Hire', 45000.00, 'Fixed', 45000.00, '2025-11-05', NULL, 5, '2025-11-05 07:20:42'),
(512, 153, 'Advance Cash', 35000.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 07:20:42'),
(513, 153, 'Dala Charge', 250.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 07:20:42'),
(514, 133, 'Billing Rate', 6151.57, 'Kg', 0.68, '2025-11-05', NULL, 5, '2025-11-05 08:43:21'),
(515, 133, 'Lorry Hire', 5500.00, 'Fixed', 5500.00, '2025-11-05', NULL, 5, '2025-11-05 08:43:21'),
(516, 133, 'Advance Cash', 5500.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 08:43:21'),
(517, 128, 'Billing Rate', 6331.51, 'Kg', 0.70, '2025-11-05', NULL, 5, '2025-11-05 08:43:54'),
(518, 128, 'Lorry Hire', 5500.00, 'Fixed', 5500.00, '2025-11-05', NULL, 5, '2025-11-05 08:43:54'),
(519, 128, 'Advance Cash', 5500.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 08:43:54'),
(520, 103, 'Billing Rate', 5579.68, 'Kg', 0.68, '2025-11-05', NULL, 5, '2025-11-05 08:45:05'),
(521, 103, 'Lorry Hire', 5000.00, 'Fixed', 5000.00, '2025-11-05', NULL, 5, '2025-11-05 08:45:05'),
(522, 103, 'Advance Cash', 3800.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 08:45:05'),
(523, 103, 'Dala Charge', 250.00, NULL, NULL, '2025-11-05', NULL, 5, '2025-11-05 08:45:05'),
(524, 154, 'Billing Rate', 12426.45, 'Kg', 1.50, '2025-11-06', NULL, 5, '2025-11-06 08:25:12'),
(525, 154, 'Lorry Hire', 11500.00, 'Fixed', 11500.00, '2025-11-06', NULL, 5, '2025-11-06 08:25:12'),
(526, 154, 'Advance Cash', 8800.00, NULL, NULL, '2025-11-06', NULL, 5, '2025-11-06 08:25:12'),
(527, 157, 'Billing Rate', 24900.00, 'Fixed', 24900.00, '2025-11-06', NULL, 5, '2025-11-06 08:30:18'),
(528, 157, 'Lorry Hire', 23900.00, 'Fixed', 23900.00, '2025-11-06', NULL, 5, '2025-11-06 08:30:18'),
(529, 157, 'Advance Cash', 17000.00, NULL, NULL, '2025-11-06', NULL, 5, '2025-11-06 08:30:18'),
(530, 157, 'Labour Charge', 900.00, NULL, NULL, '2025-11-06', NULL, 5, '2025-11-06 08:30:18'),
(531, 157, 'Dala Charge', 250.00, NULL, NULL, '2025-11-06', NULL, 5, '2025-11-06 08:30:18'),
(532, 160, 'Billing Rate', 21990.00, 'Fixed', 21990.00, '2025-11-06', NULL, 5, '2025-11-06 08:45:04'),
(533, 160, 'Lorry Hire', 20900.00, 'Fixed', 20900.00, '2025-11-06', NULL, 5, '2025-11-06 08:45:04'),
(534, 160, 'Advance Cash', 16000.00, NULL, NULL, '2025-11-06', NULL, 5, '2025-11-06 08:45:04'),
(535, 160, 'Labour Charge', 800.00, NULL, NULL, '2025-11-06', NULL, 5, '2025-11-06 08:45:04'),
(536, 160, 'Dala Charge', 250.00, NULL, NULL, '2025-11-06', NULL, 5, '2025-11-06 08:45:04'),
(537, 159, 'Billing Rate', 12374.98, 'Kg', 1.50, '2025-11-06', NULL, 5, '2025-11-06 08:45:57'),
(538, 159, 'Lorry Hire', 11500.00, 'Fixed', 11500.00, '2025-11-06', NULL, 5, '2025-11-06 08:45:57'),
(539, 159, 'Advance Cash', 8800.00, NULL, NULL, '2025-11-06', NULL, 5, '2025-11-06 08:45:57'),
(540, 159, 'Dala Charge', 250.00, NULL, NULL, '2025-11-06', NULL, 5, '2025-11-06 08:45:57'),
(541, 158, 'Billing Rate', 15500.00, 'Fixed', 15500.00, '2025-11-06', NULL, 5, '2025-11-06 08:48:52'),
(542, 158, 'Lorry Hire', 14500.00, 'Fixed', 14500.00, '2025-11-06', NULL, 5, '2025-11-06 08:48:52'),
(543, 158, 'Advance Cash', 9500.00, NULL, NULL, '2025-11-06', NULL, 5, '2025-11-06 08:48:52'),
(544, 158, 'Labour Charge', 800.00, NULL, NULL, '2025-11-06', NULL, 5, '2025-11-06 08:48:52'),
(545, 158, 'Dala Charge', 250.00, NULL, NULL, '2025-11-06', NULL, 5, '2025-11-06 08:48:52'),
(546, 158, 'Lifting Charge', 1100.00, NULL, NULL, '2025-11-06', NULL, 5, '2025-11-06 08:48:52'),
(547, 172, 'Billing Rate', 4900.00, 'Fixed', 4900.00, '2025-11-08', NULL, 5, '2025-11-08 08:45:31');
INSERT INTO `shipment_payments` (`id`, `shipment_id`, `payment_type`, `amount`, `billing_method`, `rate`, `payment_date`, `remarks`, `created_by_id`, `created_at`) VALUES
(548, 172, 'Lorry Hire', 4500.00, 'Fixed', 4500.00, '2025-11-08', NULL, 5, '2025-11-08 08:45:31'),
(549, 172, 'Advance Cash', 3000.00, NULL, NULL, '2025-11-08', NULL, 5, '2025-11-08 08:45:31'),
(550, 172, 'Labour Charge', 1000.00, NULL, NULL, '2025-11-08', NULL, 5, '2025-11-08 08:45:31'),
(551, 173, 'Billing Rate', 29000.00, 'Fixed', 29000.00, '2025-11-08', NULL, 5, '2025-11-08 08:46:25'),
(552, 173, 'Lorry Hire', 27000.00, 'Fixed', 27000.00, '2025-11-08', NULL, 5, '2025-11-08 08:46:25'),
(553, 173, 'Advance Cash', 19000.00, NULL, NULL, '2025-11-08', NULL, 5, '2025-11-08 08:46:25'),
(554, 173, 'Labour Charge', 800.00, NULL, NULL, '2025-11-08', NULL, 5, '2025-11-08 08:46:25'),
(555, 161, 'Billing Rate', 6488.39, 'Kg', 0.72, '2025-11-08', NULL, 5, '2025-11-08 08:47:09'),
(556, 161, 'Lorry Hire', 5200.00, 'Fixed', 5200.00, '2025-11-08', NULL, 5, '2025-11-08 08:47:09'),
(557, 161, 'Advance Cash', 5200.00, NULL, NULL, '2025-11-08', NULL, 5, '2025-11-08 08:47:09'),
(558, 162, 'Billing Rate', 15723.21, 'Kg', 1.90, '2025-11-08', NULL, 5, '2025-11-08 08:48:12'),
(559, 162, 'Lorry Hire', 14500.00, 'Fixed', 14500.00, '2025-11-08', NULL, 5, '2025-11-08 08:48:12'),
(560, 162, 'Advance Cash', 10100.00, NULL, NULL, '2025-11-08', NULL, 5, '2025-11-08 08:48:12'),
(561, 162, 'Dala Charge', 250.00, NULL, NULL, '2025-11-08', NULL, 5, '2025-11-08 08:48:12'),
(562, 170, 'Billing Rate', 10784.36, 'Kg', 0.98, '2025-11-08', NULL, 5, '2025-11-08 08:48:41'),
(563, 170, 'Lorry Hire', 9700.00, 'Fixed', 9700.00, '2025-11-08', NULL, 5, '2025-11-08 08:48:41'),
(564, 170, 'Advance Cash', 9700.00, NULL, NULL, '2025-11-08', NULL, 5, '2025-11-08 08:48:41'),
(565, 137, 'Billing Rate', 0.00, 'Kg', 15500.00, '2025-11-08', NULL, 5, '2025-11-08 08:49:43'),
(566, 137, 'Lorry Hire', 15000.00, 'Fixed', 15000.00, '2025-11-08', NULL, 5, '2025-11-08 08:49:43'),
(567, 137, 'Advance Cash', 13000.00, NULL, NULL, '2025-11-08', NULL, 5, '2025-11-08 08:49:43'),
(568, 137, 'Labour Charge', 2000.00, NULL, NULL, '2025-11-08', NULL, 5, '2025-11-08 08:49:43'),
(569, 169, 'Billing Rate', 7245.08, 'Kg', 0.80, '2025-11-08', NULL, 5, '2025-11-08 08:51:00'),
(570, 169, 'Lorry Hire', 6500.00, 'Fixed', 6500.00, '2025-11-08', NULL, 5, '2025-11-08 08:51:00'),
(571, 169, 'Advance Cash', 6500.00, NULL, NULL, '2025-11-08', NULL, 5, '2025-11-08 08:51:00'),
(572, 184, 'Billing Rate', 14500.00, 'Fixed', 14500.00, '2025-11-10', NULL, 5, '2025-11-10 10:20:05'),
(573, 184, 'Lorry Hire', 11100.00, 'Fixed', 11100.00, '2025-11-10', NULL, 5, '2025-11-10 10:20:05'),
(574, 184, 'Advance Cash', 10000.00, NULL, NULL, '2025-11-10', NULL, 5, '2025-11-10 10:20:05'),
(575, 184, 'Labour Charge', 1100.00, NULL, NULL, '2025-11-10', NULL, 5, '2025-11-10 10:20:05');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_tracking`
--

CREATE TABLE `shipment_tracking` (
  `id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `remarks` text DEFAULT NULL,
  `updated_by_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipment_tracking`
--

INSERT INTO `shipment_tracking` (`id`, `shipment_id`, `location`, `remarks`, `updated_by_id`, `created_at`) VALUES
(1, 8, 'Nagaon', '', 5, '2025-10-04 19:29:44'),
(2, 7, 'Nagaon', '', 5, '2025-10-04 19:30:03'),
(3, 6, 'Nagaon', '', 5, '2025-10-04 19:30:13'),
(4, 5, 'Nagaon', '', 5, '2025-10-04 19:30:21'),
(5, 9, 'Silchar', '', 5, '2025-10-04 19:30:43'),
(6, 1, 'Tura', '', 5, '2025-10-04 19:31:58'),
(7, 2, 'Mankachar', '', 5, '2025-10-04 19:32:16'),
(8, 4, 'Choutaki', '', 5, '2025-10-04 19:32:34'),
(9, 3, 'Choutaki', '', 5, '2025-10-04 19:32:46'),
(10, 1, 'Tura', '', 5, '2025-10-04 19:33:28'),
(11, 2, 'Mankachar', '', 5, '2025-10-04 19:33:39'),
(12, 1, 'Tura', '', 5, '2025-10-04 19:33:48'),
(13, 2, 'Tura', '', 5, '2025-10-04 19:33:57'),
(14, 18, 'Tura', '', 3, '2025-10-08 08:19:06'),
(15, 18, 'Tura', '', 3, '2025-10-08 08:21:19'),
(16, 18, 'Tura', '', 3, '2025-10-08 08:21:53'),
(17, 18, 'Tura', '', 3, '2025-10-08 08:27:10'),
(18, 20, 'Ladrymbai', '', 3, '2025-10-08 08:28:26'),
(19, 19, 'Mankachar', '', 3, '2025-10-08 08:28:56'),
(20, 20, 'Ladrymbai', '', 3, '2025-10-08 08:29:40'),
(21, 19, 'Mankachar', '', 3, '2025-10-08 08:30:31'),
(22, 8, 'Teliamura', '', 3, '2025-10-08 08:32:11'),
(23, 16, 'Vairengte', '', 3, '2025-10-08 08:33:08'),
(24, 16, 'Vairengte', '', 3, '2025-10-08 08:33:36'),
(25, 9, 'Silchar', '', 3, '2025-10-08 08:35:24'),
(26, 9, 'Silchar', '', 3, '2025-10-08 08:35:53'),
(27, 9, 'Silchar', '', 3, '2025-10-08 08:36:21'),
(28, 16, 'Vairengte', '', 3, '2025-10-08 08:36:45'),
(29, 7, 'Silchar', '', 3, '2025-10-08 08:37:32'),
(30, 7, 'Silchar', '', 3, '2025-10-08 08:37:59'),
(31, 7, 'Silchar', '', 3, '2025-10-08 08:38:26'),
(32, 15, 'Katigorah', '', 3, '2025-10-08 08:39:04'),
(33, 17, 'Srirampur', '', 3, '2025-10-08 08:39:24'),
(34, 14, 'North Lakhimpur', '', 3, '2025-10-08 08:39:53'),
(35, 13, 'Phulbari', '', 3, '2025-10-08 08:40:18'),
(36, 12, 'Williamnagar', '', 3, '2025-10-08 08:40:33'),
(37, 10, 'Karimganj', '', 3, '2025-10-08 08:40:52'),
(38, 11, 'Tura', '', 3, '2025-10-08 08:41:03'),
(39, 15, 'Katigorah', '', 3, '2025-10-08 08:41:27'),
(40, 15, 'Katigorah', '', 3, '2025-10-08 08:41:52'),
(41, 17, 'Srirampur', '', 3, '2025-10-08 08:42:17'),
(42, 14, 'North Lakhimpur', '', 3, '2025-10-08 08:42:37'),
(43, 13, 'Phulbari', '', 3, '2025-10-08 08:42:59'),
(44, 12, 'Williamnagar', '', 3, '2025-10-08 08:43:18'),
(45, 11, 'Tura', '', 3, '2025-10-08 08:43:37'),
(46, 10, 'Karimganj', '', 3, '2025-10-08 08:43:53'),
(47, 10, 'Karimganj', '', 3, '2025-10-08 08:44:31'),
(48, 17, 'Srirampur', '', 3, '2025-10-08 08:44:54'),
(49, 15, 'Katigorah', '', 3, '2025-10-08 08:45:08'),
(50, 14, 'North Lakhimpur', '', 3, '2025-10-08 08:45:22'),
(51, 13, 'Phulbari', '', 3, '2025-10-08 08:45:39'),
(52, 12, 'Williamnagar', '', 3, '2025-10-08 08:45:57'),
(53, 11, 'Tura', '', 3, '2025-10-08 08:46:19'),
(54, 10, 'Karimganj', '', 3, '2025-10-08 08:46:34'),
(55, 6, 'Khanpui', '', 3, '2025-10-08 08:51:20'),
(56, 5, 'Khanpui', '', 3, '2025-10-08 08:51:45'),
(57, 22, 'Gauripur', '', 3, '2025-10-08 13:53:17'),
(58, 23, 'Gauripur', '', 3, '2025-10-09 06:11:26'),
(59, 8, 'Ranirbazar', '', 3, '2025-10-09 06:14:34'),
(60, 20, 'Ladrymbai', '', 3, '2025-10-09 06:15:03'),
(61, 19, 'Mankachar', '', 3, '2025-10-09 06:15:46'),
(62, 19, 'Mankachar', '', 3, '2025-10-09 06:16:11'),
(63, 24, 'Thimphu town', '', 3, '2025-10-10 02:09:06'),
(64, 20, 'Ladrymbai', '', 3, '2025-10-10 02:09:33'),
(65, 19, 'Mankachar', '', 3, '2025-10-10 02:09:54'),
(66, 8, 'Agartala', '', 3, '2025-10-10 02:10:17'),
(67, 24, 'Thimphu town', '', 3, '2025-10-10 02:10:39'),
(68, 24, 'Thimphu town', '', 3, '2025-10-10 02:10:54'),
(69, 30, 'Lamding', '', 3, '2025-10-10 04:47:31'),
(70, 29, 'Churaibari', '', 3, '2025-10-10 07:06:25'),
(71, 28, 'Churaibari', '', 3, '2025-10-10 07:06:41'),
(72, 27, 'Sapatgaram', '', 3, '2025-10-10 07:06:53'),
(73, 27, 'Sapatgaram', '', 3, '2025-10-10 07:07:24'),
(74, 27, 'Sapatgaram', '', 3, '2025-10-10 07:07:37'),
(75, 23, 'Srirampur', '', 3, '2025-10-10 07:08:12'),
(76, 22, 'North Lakhimpur', '', 3, '2025-10-10 07:10:17'),
(77, 6, 'Bawngkawn', '', 3, '2025-10-10 07:10:42'),
(78, 5, 'Rangvamual', '', 3, '2025-10-10 07:11:01'),
(79, 6, 'Bawngkawn', '', 3, '2025-10-10 07:11:16'),
(80, 5, 'Rangvamual', '', 3, '2025-10-10 07:11:40'),
(81, 5, 'Rangvamual', '', 3, '2025-10-10 07:11:59'),
(82, 4, 'Tura', '', 3, '2025-10-10 07:12:37'),
(83, 3, 'Hastingamari', '', 3, '2025-10-10 07:13:17'),
(84, 4, 'Tura', '', 3, '2025-10-10 07:13:33'),
(85, 3, 'Hastingamari', '', 3, '2025-10-10 07:13:58'),
(86, 21, 'Tura', '', 3, '2025-10-10 08:24:11'),
(87, 26, 'Tikrikilla', '', 3, '2025-10-10 08:24:29'),
(88, 25, 'Tura', '', 3, '2025-10-10 08:24:42'),
(89, 34, 'Guwahati', '', 3, '2025-10-10 14:26:14'),
(90, 23, 'Phuentsholing', '', 3, '2025-10-11 03:47:21'),
(91, 31, 'Silchar', '', 3, '2025-10-11 03:48:17'),
(92, 22, 'North Lakhimpur', '', 3, '2025-10-11 06:09:04'),
(93, 31, 'Silchar', '', 3, '2025-10-11 06:09:37'),
(94, 29, 'Kumarghat', '', 3, '2025-10-11 06:10:02'),
(95, 30, 'Maibong', '', 3, '2025-10-11 06:10:33'),
(96, 26, 'Tikrikilla', '', 3, '2025-10-11 06:11:02'),
(97, 25, 'Tura', '', 3, '2025-10-11 06:11:19'),
(98, 21, 'Tura', '', 3, '2025-10-11 06:11:36'),
(99, 31, 'Silchar', '', 3, '2025-10-11 06:11:56'),
(100, 29, 'Kumarghat', '', 3, '2025-10-11 06:12:08'),
(101, 26, 'Tikrikilla', '', 3, '2025-10-11 06:12:25'),
(102, 25, 'Tura', '', 3, '2025-10-11 06:12:36'),
(103, 21, 'Tura', '', 3, '2025-10-11 06:12:45'),
(104, 37, 'Bilasipara', '', 3, '2025-10-11 06:13:12'),
(105, 35, 'Dhubri', '', 3, '2025-10-11 06:13:34'),
(106, 36, 'Bongaigaon', '', 3, '2025-10-11 06:13:50'),
(107, 35, 'Dhubri', '', 3, '2025-10-11 06:14:14'),
(108, 35, 'Dhubri', '', 3, '2025-10-11 06:14:30'),
(109, 35, 'Dhubri', '', 3, '2025-10-11 06:14:49'),
(110, 38, 'Jorhat', '', 3, '2025-10-11 06:37:40'),
(111, 37, 'Bilasipara', '', 3, '2025-10-11 06:44:29'),
(112, 38, 'Jorhat', '', 3, '2025-10-11 06:44:47'),
(113, 37, 'Bilasipara', '', 3, '2025-10-11 06:44:58'),
(114, 36, 'Bongaigaon', '', 3, '2025-10-11 06:45:12'),
(115, 36, 'Bongaigaon', '', 3, '2025-10-11 06:45:24'),
(116, 34, 'Shilong', '', 3, '2025-10-11 14:55:53'),
(117, 34, 'Malidor', '', 3, '2025-10-12 09:12:40'),
(118, 38, 'Sonari', '', 3, '2025-10-12 09:12:56'),
(119, 28, 'Udaipur', '', 3, '2025-10-12 09:13:13'),
(120, 38, 'Sonari', '', 3, '2025-10-12 09:13:24'),
(121, 28, 'Udaipur', '', 3, '2025-10-12 09:13:35'),
(122, 30, 'Silchar', '', 3, '2025-10-12 13:12:11'),
(123, 40, 'Silchar', '', 3, '2025-10-12 13:12:28'),
(124, 39, 'Mankachar', '', 3, '2025-10-12 13:12:48'),
(125, 33, 'Dudhnoi', '', 3, '2025-10-12 13:13:04'),
(126, 32, 'Barengapara', '', 3, '2025-10-12 13:13:26'),
(127, 33, 'Dudhnoi', '', 3, '2025-10-12 13:13:42'),
(128, 33, 'Dudhnoi', '', 3, '2025-10-12 13:13:52'),
(129, 34, 'Khanpui', '', 3, '2025-10-13 09:46:30'),
(130, 32, 'Barengapara', '', 3, '2025-10-13 09:47:04'),
(131, 40, 'Silchar', '', 3, '2025-10-14 07:39:06'),
(132, 39, 'Mankachar', '', 3, '2025-10-14 07:39:20'),
(133, 30, 'Karimganj', '', 3, '2025-10-14 07:39:36'),
(134, 46, 'Karupetia', '', 3, '2025-10-14 07:40:01'),
(135, 45, 'Williamnagar', '', 3, '2025-10-14 07:40:15'),
(136, 34, 'Sairang', '', 3, '2025-10-15 06:07:22'),
(137, 40, 'Silchar', '', 3, '2025-10-15 06:07:35'),
(138, 39, 'Mankachar', '', 3, '2025-10-15 06:07:49'),
(139, 32, 'Barengapara', '', 3, '2025-10-15 06:08:01'),
(140, 30, 'Karimganj', '', 3, '2025-10-15 06:08:14'),
(141, 52, 'Bongaigaon', '', 3, '2025-10-15 06:08:41'),
(142, 51, 'Lumding', '', 3, '2025-10-15 06:09:57'),
(143, 52, 'Bongaigaon', '', 3, '2025-10-15 06:10:34'),
(144, 54, 'Hastingamari', '', 3, '2025-10-15 06:11:40'),
(145, 54, 'Hastingamari', '', 3, '2025-10-15 11:33:01'),
(146, 52, 'Bongaigaon', '', 3, '2025-10-15 11:33:35'),
(147, 54, 'Hastingamari', '', 3, '2025-10-15 11:34:11'),
(148, 46, 'Biswanath charali', '', 3, '2025-10-15 11:34:43'),
(149, 45, 'Williamnagar', '', 3, '2025-10-15 11:35:07'),
(150, 52, 'Bongaigaon', '', 3, '2025-10-15 11:35:29'),
(151, 45, 'Williamnagar', '', 3, '2025-10-15 11:35:45'),
(152, 56, 'Karimganj', '', 3, '2025-10-15 11:36:11'),
(153, 55, 'Krishnai', '', 3, '2025-10-15 11:36:31'),
(154, 53, 'Srirampur', '', 3, '2025-10-15 11:36:47'),
(155, 44, 'Tura', '', 3, '2025-10-15 11:37:19'),
(156, 43, 'Phulbari', '', 3, '2025-10-15 11:37:32'),
(157, 42, 'Mahendraganj', '', 3, '2025-10-15 11:37:59'),
(158, 41, 'Karimganj', '', 3, '2025-10-15 11:38:14'),
(159, 47, 'Tura', '', 3, '2025-10-15 11:38:27'),
(160, 48, 'Tura', '', 3, '2025-10-15 11:38:39'),
(161, 56, 'Karimganj', '', 3, '2025-10-15 11:39:02'),
(162, 55, 'Krishnai', '', 3, '2025-10-15 11:39:26'),
(163, 53, 'Srirampur', '', 3, '2025-10-15 11:39:40'),
(164, 48, 'Tura', '', 3, '2025-10-15 11:39:55'),
(165, 47, 'Tura', '', 3, '2025-10-15 11:40:08'),
(166, 44, 'Tura', '', 3, '2025-10-15 11:40:23'),
(167, 43, 'Phulbari', '', 3, '2025-10-15 11:40:39'),
(168, 42, 'Mahendraganj', '', 3, '2025-10-15 11:41:00'),
(169, 41, 'Karimganj', '', 3, '2025-10-15 11:41:22'),
(170, 53, 'Srirampur', '', 3, '2025-10-15 11:41:57'),
(171, 51, 'Haflong', '', 3, '2025-10-16 07:41:02'),
(172, 60, 'Haflong', '', 3, '2025-10-16 07:43:29'),
(173, 56, 'Karimganj', '', 3, '2025-10-16 07:43:44'),
(174, 55, 'Krishnai', '', 3, '2025-10-16 07:43:54'),
(175, 54, 'Hastingamari', '', 3, '2025-10-16 07:44:10'),
(176, 48, 'Tura', '', 3, '2025-10-16 07:44:19'),
(177, 47, 'Tura', '', 3, '2025-10-16 07:44:28'),
(178, 48, 'Tura', '', 3, '2025-10-16 07:44:44'),
(179, 44, 'Tura', '', 3, '2025-10-16 07:44:58'),
(180, 43, 'Phulbari', '', 3, '2025-10-16 07:45:08'),
(181, 42, 'Mahendraganj', '', 3, '2025-10-16 07:45:19'),
(182, 41, 'Karimganj', '', 3, '2025-10-16 07:45:29'),
(183, 61, 'Teliamura', '', 3, '2025-10-16 10:36:58'),
(184, 59, 'Howly', '', 3, '2025-10-16 10:37:09'),
(185, 58, 'Sapatgaram', '', 3, '2025-10-16 10:37:22'),
(186, 57, 'Mankachar', '', 3, '2025-10-16 10:37:32'),
(187, 50, 'Singimari', '', 3, '2025-10-16 10:37:51'),
(188, 49, 'Singimari', '', 3, '2025-10-16 10:38:01'),
(189, 46, 'Biswanath charali', '', 3, '2025-10-16 10:38:15'),
(190, 59, 'Howly', '', 3, '2025-10-16 10:38:34'),
(191, 58, 'Sapatgaram', '', 3, '2025-10-16 10:38:50'),
(192, 57, 'Mankachar', '', 3, '2025-10-16 10:39:03'),
(193, 59, 'Howly', '', 3, '2025-10-16 10:39:18'),
(194, 58, 'Sapatgaram', '', 3, '2025-10-16 10:39:30'),
(195, 57, 'Mankachar', '', 3, '2025-10-16 10:39:39'),
(196, 68, 'Maibong', '', 3, '2025-10-17 07:51:19'),
(197, 68, 'Bhaga Bazar', '', 3, '2025-10-17 07:52:12'),
(198, 67, 'Maibong', '', 3, '2025-10-17 07:52:23'),
(199, 66, 'Maibong', '', 3, '2025-10-17 07:52:32'),
(200, 65, 'Maibong', '', 3, '2025-10-17 07:52:40'),
(201, 63, 'Lakhipur', '', 3, '2025-10-17 07:53:05'),
(202, 62, 'Bilasipara', '', 3, '2025-10-17 07:53:14'),
(203, 63, 'Lakhipur', '', 3, '2025-10-17 07:54:09'),
(204, 61, 'Champaknagar', '', 3, '2025-10-17 07:54:38'),
(205, 61, 'Champaknagar', '', 3, '2025-10-17 07:55:04'),
(206, 68, 'Bhaga Bazar', '', 3, '2025-10-17 11:28:25'),
(207, 51, 'Kolasib', '', 3, '2025-10-17 11:28:59'),
(208, 67, 'Silchar', '', 3, '2025-10-19 04:16:05'),
(209, 62, 'Bilasipara', '', 3, '2025-10-19 04:16:33'),
(210, 60, 'Churaibari', '', 3, '2025-10-19 04:16:50'),
(211, 50, 'Singimari', '', 3, '2025-10-19 04:17:15'),
(212, 34, 'Saiha', '', 3, '2025-10-19 04:17:40'),
(213, 68, 'Bhaga Bazar', '', 3, '2025-10-19 04:17:53'),
(214, 67, 'Silchar', '', 3, '2025-10-19 04:18:02'),
(215, 63, 'Lakhipur', '', 3, '2025-10-19 04:18:12'),
(216, 62, 'Bilasipara', '', 3, '2025-10-19 04:18:21'),
(217, 50, 'Singimari', '', 3, '2025-10-19 04:18:32'),
(218, 34, 'Sahia', '', 3, '2025-10-19 04:18:42'),
(219, 71, 'Sivsagar', '', 3, '2025-10-19 04:19:05'),
(220, 71, 'Sivsagar', '', 3, '2025-10-19 04:19:18'),
(221, 71, 'Sivsagar', '', 3, '2025-10-19 04:19:30'),
(222, 69, 'Phulbari', '', 3, '2025-10-19 04:19:48'),
(223, 64, 'Sonari', '', 3, '2025-10-19 04:20:30'),
(224, 60, 'Udaipur', '', 3, '2025-10-19 04:26:24'),
(225, 69, 'Phulbari', '', 3, '2025-10-19 04:26:41'),
(226, 64, 'Sonari', '', 3, '2025-10-19 04:26:57'),
(227, 69, 'Phulbari', '', 3, '2025-10-19 04:27:07'),
(228, 64, 'Sonari', '', 3, '2025-10-19 04:27:17'),
(229, 75, 'Moranhat', '', 3, '2025-10-22 05:42:04'),
(230, 74, 'Abdullapur', '', 3, '2025-10-22 05:42:20'),
(231, 73, 'Gandacherra', '', 3, '2025-10-22 05:42:32'),
(232, 72, 'Dhubri', '', 3, '2025-10-22 05:42:42'),
(233, 70, 'Kalacherra', '', 3, '2025-10-22 05:43:12'),
(234, 60, 'Udaipur', '', 3, '2025-10-22 05:43:27'),
(235, 75, 'Moranhat', '', 3, '2025-10-22 05:43:49'),
(236, 74, 'Abdullapur', '', 3, '2025-10-22 05:43:59'),
(237, 73, 'Gandacherra', '', 3, '2025-10-22 05:44:10'),
(238, 72, 'Dhubri', '', 3, '2025-10-22 05:44:21'),
(239, 70, 'Kalacherra', '', 3, '2025-10-22 05:44:31'),
(240, 66, 'Kolasib', '', 3, '2025-10-22 05:44:43'),
(241, 65, 'Dharmanagar', '', 3, '2025-10-22 05:45:05'),
(242, 51, 'Lunglie', '', 3, '2025-10-22 05:45:19'),
(243, 49, 'Siliguri', '', 3, '2025-10-22 05:45:32'),
(244, 75, 'Moranhat', '', 3, '2025-10-22 05:46:59'),
(245, 74, 'Abdullapur', '', 3, '2025-10-22 05:47:08'),
(246, 73, 'Gandacherra', '', 3, '2025-10-22 05:47:17'),
(247, 72, 'Dhubri', '', 3, '2025-10-22 05:47:26'),
(248, 70, 'Kalacherra', '', 3, '2025-10-22 05:47:37'),
(249, 66, 'Kolasib', '', 3, '2025-10-22 05:47:44'),
(250, 65, 'Dharmanagar', '', 3, '2025-10-22 05:47:59'),
(251, 51, 'Lunglie', '', 3, '2025-10-22 05:48:09'),
(252, 88, 'Maibong', '', 3, '2025-10-25 05:57:28'),
(253, 87, 'Maibong', '', 3, '2025-10-25 05:58:57'),
(254, 86, 'Maibong', '', 3, '2025-10-25 05:59:22'),
(255, 84, 'Mankachar', '', 3, '2025-10-25 05:59:53'),
(256, 83, 'Hailakandi', '', 3, '2025-10-25 06:00:15'),
(257, 82, 'Agartala', '', 3, '2025-10-25 06:01:10'),
(258, 81, 'Agartala', '', 3, '2025-10-25 06:01:32'),
(259, 80, 'Bagmara', '', 3, '2025-10-25 06:01:48'),
(260, 79, 'Tura', '', 3, '2025-10-25 06:02:05'),
(261, 78, 'Garobada', '', 3, '2025-10-25 06:02:29'),
(262, 77, 'Williamnagar', '', 3, '2025-10-25 06:02:47'),
(263, 76, 'Tura', '', 3, '2025-10-25 06:02:59'),
(264, 85, 'Shirampur', '', 3, '2025-10-25 06:03:12'),
(265, 85, 'Shirampur', '', 3, '2025-10-25 06:04:21'),
(266, 49, 'Siliguri', '', 3, '2025-10-25 06:04:56'),
(267, 85, 'Shirampur', '', 3, '2025-10-25 06:05:16'),
(268, 84, 'Mankachar', '', 3, '2025-10-25 06:05:51'),
(269, 83, 'Hailakandi', '', 3, '2025-10-25 06:06:14'),
(270, 80, 'Bagmara', '', 3, '2025-10-25 06:06:39'),
(271, 84, 'Mankachar', '', 3, '2025-10-25 06:06:52'),
(272, 83, 'Hailakandi', '', 3, '2025-10-25 06:07:01'),
(273, 80, 'Bagmara', '', 3, '2025-10-25 06:07:10'),
(274, 97, 'Hastingamari', '', 3, '2025-10-27 12:05:01'),
(275, 98, 'Kolasib', '', 3, '2025-10-27 12:05:42'),
(276, 107, 'Digarkhal', '', 3, '2025-10-28 05:51:18'),
(277, 97, 'Hastingamari', '', 3, '2025-10-29 06:50:01'),
(278, 88, 'Karimganj', '', 3, '2025-10-29 06:50:31'),
(279, 87, 'Khowai', '', 3, '2025-10-29 06:50:46'),
(280, 86, 'Belonia', '', 3, '2025-10-29 06:51:03'),
(281, 82, 'Udaipur', '', 3, '2025-10-29 06:51:32'),
(282, 81, 'Tulamura', '', 3, '2025-10-29 06:51:51'),
(283, 79, 'Tura', '', 3, '2025-10-29 06:52:04'),
(284, 78, 'Garobadha', '', 3, '2025-10-29 06:52:21'),
(285, 77, 'Williamnagar', '', 3, '2025-10-29 06:52:35'),
(286, 76, 'Tura', '', 3, '2025-10-29 06:52:46'),
(287, 97, 'Hastingamari', '', 3, '2025-10-29 06:53:01'),
(288, 88, 'Karimganj', '', 3, '2025-10-29 06:53:09'),
(289, 87, 'Khowai', '', 3, '2025-10-29 06:53:18'),
(290, 86, 'Belonia', '', 3, '2025-10-29 06:53:27'),
(291, 82, 'Udaipur', '', 3, '2025-10-29 06:53:37'),
(292, 81, 'Tulamura', '', 3, '2025-10-29 06:53:47'),
(293, 79, 'Tura', '', 3, '2025-10-29 06:53:56'),
(294, 78, 'Garobadha', '', 3, '2025-10-29 06:54:04'),
(295, 77, 'Williamnagar', '', 3, '2025-10-29 06:54:15'),
(296, 76, 'Tura', '', 3, '2025-10-29 06:54:24'),
(297, 113, 'Srirampur', '', 3, '2025-10-29 06:55:00'),
(298, 106, 'Maibong', '', 3, '2025-10-29 06:56:27'),
(299, 105, 'Kumarghat', '', 3, '2025-10-29 06:56:47'),
(300, 104, 'Mendhipathar', '', 3, '2025-10-29 06:57:02'),
(301, 103, 'Mendhipathar', '', 3, '2025-10-29 06:57:16'),
(302, 102, 'Mankachar', '', 3, '2025-10-29 06:57:27'),
(303, 101, 'Hastingamari', '', 3, '2025-10-29 06:57:38'),
(304, 100, 'Silchar', '', 3, '2025-10-29 06:57:51'),
(305, 99, 'Gohpur', '', 3, '2025-10-29 06:58:06'),
(306, 96, 'Aizwal', '', 3, '2025-10-29 07:03:31'),
(307, 95, 'Bilasipara', '', 3, '2025-10-29 07:04:09'),
(308, 94, 'Sapatgaram', '', 3, '2025-10-29 07:04:18'),
(309, 93, 'Sivsagar', '', 3, '2025-10-29 07:04:29'),
(310, 92, 'Barengapara', '', 3, '2025-10-29 07:04:41'),
(311, 91, 'Dharmanagar', '', 3, '2025-10-29 07:04:50'),
(312, 90, 'Tura', '', 3, '2025-10-29 07:04:57'),
(313, 89, 'Phulbari', '', 3, '2025-10-29 07:05:05'),
(314, 104, 'Mendhipathar', '', 3, '2025-10-29 07:31:06'),
(315, 103, 'Mendhipathar', '', 3, '2025-10-29 07:31:26'),
(316, 102, 'Mankachar', '', 3, '2025-10-29 07:31:51'),
(317, 101, 'Hastingamari', '', 3, '2025-10-29 07:35:00'),
(318, 99, 'Gohpur', '', 3, '2025-10-29 07:35:19'),
(319, 98, 'Aizwal', '', 3, '2025-10-29 07:36:00'),
(320, 113, 'Srirampur', '', 3, '2025-10-29 07:36:20'),
(321, 117, 'Tikrikilla', '', 3, '2025-10-30 09:11:41'),
(322, 109, 'Mankachar', '', 3, '2025-10-30 09:11:54'),
(323, 108, 'Kolasib', '', 3, '2025-10-30 09:12:07'),
(324, 115, 'Sonari', '', 3, '2025-10-30 09:12:31'),
(325, 110, 'Karimganj', '', 3, '2025-10-30 09:12:45'),
(326, 113, 'Srirampur', '', 3, '2025-10-30 09:13:01'),
(327, 104, 'Mendhipathar', '', 3, '2025-10-30 09:13:09'),
(328, 102, 'Mankachar', '', 3, '2025-10-30 09:13:21'),
(329, 101, 'Hastingamari', '', 3, '2025-10-30 09:13:31'),
(330, 99, 'Gohpur', '', 3, '2025-10-30 09:13:41'),
(331, 117, 'Tikrikilla', '', 3, '2025-10-30 09:13:51'),
(332, 117, 'Tikrikilla', '', 3, '2025-10-30 09:14:07'),
(333, 110, 'Karimganj', '', 3, '2025-10-30 09:14:36'),
(334, 109, 'Mankachar', '', 3, '2025-10-30 09:14:49'),
(335, 115, 'Sonari', '', 3, '2025-10-30 09:15:09'),
(336, 105, 'Kakraban', '', 3, '2025-10-30 09:15:38'),
(337, 100, 'Silchar', '', 3, '2025-10-30 09:15:57'),
(338, 95, 'Bilasipara', '', 3, '2025-10-30 09:16:18'),
(339, 94, 'Sapatgaram', '', 3, '2025-10-30 09:16:37'),
(340, 93, 'Sivsagar', '', 3, '2025-10-30 09:16:54'),
(341, 92, 'Barengapara', '', 3, '2025-10-30 09:17:19'),
(342, 109, 'Mankachar', '', 3, '2025-10-30 09:17:35'),
(343, 115, 'Sonari', '', 3, '2025-10-30 09:17:47'),
(344, 105, 'Kakraban', '', 3, '2025-10-30 09:18:01'),
(345, 103, 'Mendhipathar', '', 3, '2025-10-30 09:18:18'),
(346, 95, 'Bilasipara', '', 3, '2025-10-30 09:18:30'),
(347, 100, 'Silchar', '', 3, '2025-10-30 09:18:54'),
(348, 117, 'Tikrikilla', '', 3, '2025-11-01 05:25:11'),
(349, 110, 'Karimganj', '', 3, '2025-11-01 05:25:25'),
(350, 94, 'Sapatgaram', '', 3, '2025-11-01 05:25:34'),
(351, 93, 'Sivsagar', '', 3, '2025-11-01 05:25:44'),
(352, 92, 'Barengapara', '', 3, '2025-11-01 05:25:56'),
(353, 91, 'Dharmanagar', '', 3, '2025-11-01 05:26:40'),
(354, 91, 'Dharmanagar', '', 3, '2025-11-01 05:26:54'),
(355, 127, 'Dudhnoi', '', 3, '2025-11-01 06:01:59'),
(356, 125, 'Rongram', '', 3, '2025-11-01 06:02:16'),
(357, 124, 'Kokrajhar', '', 3, '2025-11-01 06:02:29'),
(358, 123, 'Dharmanagar', '', 3, '2025-11-01 06:02:42'),
(359, 122, 'Silchar', '', 3, '2025-11-01 06:02:53'),
(360, 121, 'Barengapara', '', 3, '2025-11-01 06:03:01'),
(361, 120, 'Phulbari', '', 3, '2025-11-01 06:03:10'),
(362, 119, 'Williamnagar', '', 3, '2025-11-01 06:03:19'),
(363, 118, 'Digarkhal', '', 3, '2025-11-01 06:05:19'),
(364, 116, 'Lumding', '', 3, '2025-11-01 06:05:47'),
(365, 114, 'Lumding', '', 3, '2025-11-01 06:06:01'),
(366, 112, 'Mahendraganj', '', 3, '2025-11-01 06:06:09'),
(367, 111, 'Tura', '', 3, '2025-11-01 06:06:16'),
(368, 126, 'Tura', '', 3, '2025-11-01 06:06:23'),
(369, 126, 'Tura', '', 3, '2025-11-01 06:06:58'),
(370, 125, 'Rongram', '', 3, '2025-11-01 06:07:11'),
(371, 125, 'Rongram', '', 3, '2025-11-01 06:07:25'),
(372, 124, 'Kokrajhar', '', 3, '2025-11-01 06:07:35'),
(373, 123, 'Dharmanagar', '', 3, '2025-11-01 06:07:53'),
(374, 123, 'Dharmanagar', '', 3, '2025-11-01 06:08:05'),
(375, 122, 'Silchar', '', 3, '2025-11-01 06:08:14'),
(376, 121, 'Barengapara', '', 3, '2025-11-01 06:08:29'),
(377, 120, 'Phulbari', '', 3, '2025-11-01 06:18:45'),
(378, 119, 'Williamnagar', '', 3, '2025-11-01 06:18:57'),
(379, 111, 'Tura', '', 3, '2025-11-01 06:19:24'),
(380, 112, 'Mahendraganj', '', 3, '2025-11-01 06:19:47'),
(381, 124, 'Kokrajhar', '', 3, '2025-11-01 06:20:14'),
(382, 123, 'Dharmanagar', '', 3, '2025-11-01 06:20:25'),
(383, 122, 'Silchar', '', 3, '2025-11-01 06:20:40'),
(384, 121, 'Barengapara', '', 3, '2025-11-01 06:20:50'),
(385, 120, 'Phulbari', '', 3, '2025-11-01 06:21:06'),
(386, 119, 'Williamnagar', '', 3, '2025-11-01 06:21:17'),
(387, 111, 'Tura', '', 3, '2025-11-01 06:21:33'),
(388, 112, 'Mahendraganj', '', 3, '2025-11-01 06:21:44'),
(389, 126, 'Tura', '', 3, '2025-11-02 16:51:44'),
(390, 125, 'Rongram', '', 3, '2025-11-02 16:51:54'),
(391, 127, 'Dudhnoi', '', 3, '2025-11-02 16:52:08'),
(392, 133, 'Bilasipara', '', 3, '2025-11-02 16:52:45'),
(393, 132, 'Mankachar', '', 3, '2025-11-02 16:52:58'),
(394, 131, 'Gohpur', '', 3, '2025-11-02 16:53:13'),
(395, 130, 'Belonia', '', 3, '2025-11-02 16:53:28'),
(396, 129, 'Srirampur', '', 3, '2025-11-02 16:53:38'),
(397, 128, 'Barpeta town', '', 3, '2025-11-02 16:53:56'),
(398, 133, 'Bilasipara', '', 3, '2025-11-02 16:54:08'),
(399, 132, 'Mankachar', '', 3, '2025-11-02 16:54:20'),
(400, 131, 'Gohpur', '', 3, '2025-11-02 16:54:34'),
(401, 129, 'Srirampur', '', 3, '2025-11-02 16:54:46'),
(402, 128, 'Barpeta town', '', 3, '2025-11-02 16:54:58'),
(403, 130, 'Belonia', '', 3, '2025-11-02 16:55:10'),
(404, 133, 'Bilasipara', '', 3, '2025-11-02 16:55:23'),
(405, 132, 'Mankachar', '', 3, '2025-11-02 16:55:33'),
(406, 129, 'Srirampur', '', 3, '2025-11-02 16:55:46'),
(407, 128, 'Barpeta town', '', 3, '2025-11-02 16:56:17'),
(408, 127, 'Dudhnoi', '', 3, '2025-11-02 16:56:25'),
(409, 90, 'Tura', '', 3, '2025-11-03 07:34:38'),
(410, 106, 'Silchar', '', 3, '2025-11-03 07:35:08'),
(411, 89, 'Phulbari', '', 3, '2025-11-03 07:35:52'),
(412, 89, 'Phulbari', '', 3, '2025-11-03 07:37:08'),
(413, 90, 'Tura', '', 3, '2025-11-03 07:37:24'),
(414, 130, 'Belonia', '', 3, '2025-11-03 07:37:50'),
(415, 106, 'Silchar', '', 3, '2025-11-03 07:38:15'),
(416, 114, 'Churaibari', '', 3, '2025-11-03 08:02:24'),
(417, 107, 'Kolashib', '', 3, '2025-11-03 08:03:16'),
(418, 108, 'Lunglei', '', 3, '2025-11-03 08:04:03'),
(419, 116, 'Aizawl', '', 3, '2025-11-03 08:04:31'),
(420, 118, 'Lunglei', '', 3, '2025-11-03 08:05:37'),
(421, 98, 'Lunglei', '', 3, '2025-11-03 08:10:35'),
(422, 98, 'Lunglei', '', 3, '2025-11-03 08:10:50'),
(423, 131, 'Gohpur', '', 3, '2025-11-03 09:25:15'),
(424, 134, 'GUWAHATI', '', 3, '2025-11-03 09:25:35'),
(425, 135, 'Mankachar', '', 3, '2025-11-03 09:26:17'),
(426, 136, 'Tura', '', 3, '2025-11-03 09:26:30'),
(427, 137, 'Guwahati', '', 3, '2025-11-03 11:19:00'),
(428, 127, 'Dudhnoi', '', 3, '2025-11-03 11:51:22'),
(429, 139, 'Guwahati', '', 3, '2025-11-04 06:38:17'),
(430, 138, 'Silchar', '', 3, '2025-11-04 06:38:31'),
(431, 138, 'Silchar', '', 3, '2025-11-04 06:38:50'),
(432, 136, 'Tura', '', 3, '2025-11-04 06:39:07'),
(433, 135, 'Mankachar', '', 3, '2025-11-04 06:39:25'),
(434, 118, 'Lunglie', '', 3, '2025-11-04 06:39:45'),
(435, 138, 'Katigorah', '', 3, '2025-11-04 06:40:06'),
(436, 136, 'Tura', '', 3, '2025-11-04 06:40:15'),
(437, 135, 'Mankachar', '', 3, '2025-11-04 06:40:23'),
(438, 118, 'Lunglie', '', 3, '2025-11-04 06:40:55'),
(439, 134, 'Lumding', '', 3, '2025-11-04 07:18:07'),
(440, 114, 'Agartala', '', 3, '2025-11-04 07:37:23'),
(441, 108, 'Lunglie', '', 3, '2025-11-04 07:37:52'),
(442, 137, 'Bilasipara', '', 3, '2025-11-04 07:41:46'),
(443, 96, 'Lunglei', '', 3, '2025-11-04 08:41:33'),
(444, 96, 'Lunglei', '', 3, '2025-11-04 08:41:58'),
(445, 107, 'Lunglei', '', 3, '2025-11-04 08:43:20'),
(446, 116, 'Lunglei', '', 3, '2025-11-04 08:45:22'),
(447, 108, 'Lunglei', '', 3, '2025-11-04 08:46:14'),
(448, 140, 'JAMURIA', 'NOW AT UNLOADING POINT', 4, '2025-11-04 12:45:23'),
(449, 140, 'GHAZIABAD', '', 4, '2025-11-04 15:21:59'),
(450, 140, 'GHAZIABAD', '', 4, '2025-11-04 15:22:18'),
(451, 144, 'GHAZIABAD', '', 4, '2025-11-05 09:12:39'),
(452, 145, 'FARIDABAD', '', 4, '2025-11-05 09:13:03'),
(453, 146, 'GHAZIABAD', '', 4, '2025-11-05 09:13:28'),
(454, 144, 'GHAZIABAD', '', 4, '2025-11-05 09:13:56'),
(455, 145, 'FARIDABAD', '', 4, '2025-11-05 09:14:07'),
(456, 146, 'GHAZIABAD', '80 KG SHORTAGE', 4, '2025-11-05 09:14:33'),
(457, 144, 'GHAZIABAD', '', 4, '2025-11-05 09:14:53'),
(458, 145, 'FARIDABAD', '', 4, '2025-11-05 09:15:06'),
(459, 146, 'GHAZIABAD', '', 4, '2025-11-05 09:15:29'),
(460, 143, 'KASHIPUR', '', 4, '2025-11-05 09:17:56'),
(461, 143, 'KASHIPUR', '', 4, '2025-11-05 09:18:12'),
(462, 143, 'KASHIPUR', '', 4, '2025-11-05 09:18:24'),
(463, 152, 'Dharmanagar', '', 5, '2025-11-05 10:27:30'),
(464, 153, 'Guwahati', '', 5, '2025-11-05 10:34:13'),
(465, 141, 'Choutaki', '', 5, '2025-11-05 10:34:59'),
(466, 141, 'Agia', '', 5, '2025-11-05 10:36:14'),
(467, 142, 'Hastingamari', '', 3, '2025-11-05 10:36:32'),
(468, 142, 'Hastingamari', '', 3, '2025-11-05 10:37:01'),
(469, 137, 'Siliguri', '', 3, '2025-11-05 10:37:27'),
(470, 137, 'Siliguri', '', 3, '2025-11-05 10:37:39'),
(471, 141, 'Phulbari', '', 5, '2025-11-05 10:38:37'),
(472, 141, 'Phulbari', '', 5, '2025-11-05 10:39:26'),
(473, 116, 'Laungtalai', '', 3, '2025-11-05 10:40:13'),
(474, 116, 'Laungtalai', '', 3, '2025-11-05 10:40:28'),
(475, 142, 'Hastingamari', '', 3, '2025-11-05 10:40:40'),
(476, 114, 'Nalchar', '', 3, '2025-11-05 10:59:17'),
(477, 114, 'Nalchar', '', 3, '2025-11-05 10:59:27'),
(478, 107, 'Lunglie', '', 3, '2025-11-05 11:02:27'),
(479, 134, 'Haflong', '', 3, '2025-11-05 11:03:01'),
(480, 154, 'Choutaki', '', 3, '2025-11-05 12:10:29'),
(481, 159, 'Choutaki', '', 3, '2025-11-05 14:08:05'),
(482, 158, 'Gauripur', '', 3, '2025-11-05 14:08:53'),
(483, 157, 'Gauripur', '', 3, '2025-11-05 14:09:12'),
(484, 160, 'Gauripur', '', 3, '2025-11-06 04:16:33'),
(485, 107, 'Lunglie', '', 3, '2025-11-06 04:16:47'),
(486, 159, 'Tura', '', 3, '2025-11-06 06:50:17'),
(487, 154, 'Tura', '', 3, '2025-11-06 06:50:54'),
(488, 158, 'Gohpur', '', 3, '2025-11-06 06:52:08'),
(489, 152, 'Kumarghat', '', 3, '2025-11-06 06:53:18'),
(490, 152, 'Kumarghat', '', 3, '2025-11-06 06:53:34'),
(491, 160, 'Sonapur', '', 3, '2025-11-06 06:56:38'),
(492, 157, 'Sivsagar', '', 3, '2025-11-06 07:00:17'),
(493, 139, 'Guwahati', '', 3, '2025-11-06 08:44:28'),
(494, 153, 'Guwahati', '', 3, '2025-11-06 08:45:31'),
(495, 134, 'Haflong', 'Road blocked', 3, '2025-11-06 08:46:54'),
(496, 158, 'Gohpur', '', 3, '2025-11-06 12:01:26'),
(497, 159, 'Tura', '', 3, '2025-11-07 06:06:29'),
(498, 154, 'Tura', '', 3, '2025-11-07 06:08:29'),
(499, 161, 'Lakhipur', '', 3, '2025-11-07 06:09:15'),
(500, 157, 'Tinsukia', '', 3, '2025-11-07 06:10:11'),
(501, 162, 'Choutaki', '', 3, '2025-11-07 06:11:16'),
(502, 162, 'Birangapara', '', 3, '2025-11-07 06:11:52'),
(503, 162, 'Birangapara', '', 3, '2025-11-07 06:12:15'),
(504, 153, 'Lumding', '', 3, '2025-11-07 06:15:13'),
(505, 134, 'Silchar', '', 3, '2025-11-07 06:15:39'),
(506, 160, 'North Lakhimpur', '', 3, '2025-11-07 07:09:31'),
(507, 161, 'Lakhipur', '', 3, '2025-11-07 07:27:15'),
(508, 161, 'Lakhipur', '', 3, '2025-11-07 07:27:33'),
(509, 139, 'Guwahati', '', 3, '2025-11-07 07:31:48'),
(510, 157, 'Tinsukia', '', 3, '2025-11-07 11:01:18'),
(511, 172, 'Silchar', '', 3, '2025-11-08 03:03:59'),
(512, 160, 'Kamlabari', '', 8, '2025-11-08 05:45:11'),
(513, 170, 'Srirampur', '', 3, '2025-11-08 05:51:59'),
(514, 169, 'Sapatgaram', '', 3, '2025-11-08 05:52:49'),
(515, 170, 'Srirampur', '', 3, '2025-11-08 05:53:05'),
(516, 169, 'Sapatgaram', '', 3, '2025-11-08 05:53:22'),
(517, 170, 'Srirampur', '', 3, '2025-11-08 05:53:32'),
(518, 169, 'Sapatgaram', '', 3, '2025-11-08 05:53:42'),
(519, 139, 'Silchar', '', 8, '2025-11-08 06:00:20'),
(520, 173, 'Nelie', '', 8, '2025-11-08 06:08:03'),
(521, 134, 'Aizwal', '', 8, '2025-11-08 06:27:02'),
(522, 153, 'Bhandarkhal', '', 8, '2025-11-08 06:28:20'),
(523, 172, 'Silchar', '', 8, '2025-11-08 06:43:57'),
(524, 160, 'Kamlabari', '', 8, '2025-11-08 09:37:43'),
(525, 172, 'Silchar', '', 8, '2025-11-08 09:40:52'),
(526, 168, 'BARJORA', '', 2, '2025-11-08 11:57:04'),
(527, 168, 'BARJORA', '', 2, '2025-11-08 11:57:04'),
(528, 168, 'BARJORA', '', 2, '2025-11-08 11:57:04'),
(529, 168, 'BARJORA', '', 2, '2025-11-08 11:57:53'),
(530, 168, 'BHIWADI', '', 2, '2025-11-08 12:05:21'),
(531, 180, 'Choutaki', '', 3, '2025-11-08 14:22:57'),
(532, 177, 'Guwahati', '', 3, '2025-11-08 14:23:09'),
(533, 176, 'Choutaki', '', 3, '2025-11-08 14:23:20'),
(534, 175, 'Choutaki', '', 3, '2025-11-08 14:23:34'),
(535, 174, 'Guwahati', '', 3, '2025-11-08 14:23:46'),
(536, 180, 'Mendhipathar', '', 3, '2025-11-09 05:52:15'),
(537, 176, 'Bilasipara', '', 3, '2025-11-09 05:52:34'),
(538, 175, 'Tikrikilla', '', 3, '2025-11-09 05:52:47'),
(539, 176, 'Bilasipara', '', 8, '2025-11-10 05:03:04'),
(540, 180, 'Mehendipathar', '', 8, '2025-11-10 05:03:34'),
(541, 175, 'Tikkrikila', '', 8, '2025-11-10 05:03:55'),
(542, 183, 'Variengte', '', 8, '2025-11-10 05:07:11'),
(543, 183, 'Viriengte', '', 8, '2025-11-10 05:07:36'),
(544, 134, 'Dhigharkhal', '', 8, '2025-11-10 05:37:39'),
(545, 173, 'Lamding', '', 8, '2025-11-10 05:38:50'),
(546, 174, 'Ladrymbai', '', 8, '2025-11-10 05:39:10'),
(547, 174, 'Ladrymbai', '', 8, '2025-11-10 05:39:25'),
(548, 153, 'Santibazar', '', 8, '2025-11-10 05:40:41'),
(549, 134, 'Aizawl', '', 8, '2025-11-10 05:43:59'),
(550, 177, 'Dhigharkhal', '', 8, '2025-11-10 05:44:54'),
(551, 139, 'Lunglie', '', 3, '2025-11-10 05:47:26'),
(552, 193, 'Guwahati', '', 3, '2025-11-11 06:50:49'),
(553, 192, 'Silchr', '', 3, '2025-11-11 06:51:09'),
(554, 187, 'Phulbari', '', 3, '2025-11-11 06:51:25'),
(555, 186, 'Tura', '', 3, '2025-11-11 06:51:37'),
(556, 184, 'Srirampur', '', 3, '2025-11-11 06:53:02'),
(557, 185, 'Guwahati', '', 3, '2025-11-11 06:53:28'),
(558, 153, 'Santibazar', '', 3, '2025-11-11 06:53:49'),
(559, 183, 'Vairengte', '', 3, '2025-11-11 06:54:36'),
(560, 192, 'Hailakandi', '', 3, '2025-11-11 06:54:55'),
(561, 187, 'Phulbari', '', 3, '2025-11-11 06:55:13'),
(562, 186, 'Tura', '', 3, '2025-11-11 06:55:33'),
(563, 192, 'Hailakandi', '', 3, '2025-11-11 06:56:57'),
(564, 173, 'Lumding', '', 3, '2025-11-11 06:58:59'),
(565, 177, 'Kolasib', '', 3, '2025-11-11 07:00:46'),
(566, 134, 'Lunglei', '', 3, '2025-11-11 07:21:05'),
(567, 134, 'Lunglei', '', 3, '2025-11-11 07:21:17'),
(568, 187, 'Phulbari', '', 3, '2025-11-11 07:25:34'),
(569, 186, 'Tura', '', 3, '2025-11-11 07:25:43');

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

CREATE TABLE `states` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `country_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `states`
--

INSERT INTO `states` (`id`, `name`, `country_id`) VALUES
(1, 'West Bengal', 1),
(2, 'Chhattisgarh', 1),
(3, 'Haryana', 1),
(4, 'Karnataka', 1),
(5, 'Jharkhand', 1),
(6, 'Himachal Pradesh', 1),
(7, 'Bihar', 1),
(8, 'Delhi', 1),
(9, 'Maharashtra', 1),
(10, 'Assam', 1),
(11, 'Tripura', 1),
(12, 'Mizoram', 1),
(13, 'Uttar Pradesh', 1),
(14, 'Nagaland', 1),
(15, 'Bhutan', 2),
(18, 'Punjab', 1),
(19, 'Uttarakhand', 1),
(20, 'Jammu and Kashmir', 1),
(21, 'Madhya Pradesh', 1),
(22, 'Kerala', 1),
(23, 'Andhra Pradesh', 1),
(24, 'Odisha', 1),
(25, 'Meghalaya', 1),
(26, 'RAJASTHAN', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tyre_inventory`
--

CREATE TABLE `tyre_inventory` (
  `id` int(11) NOT NULL,
  `tyre_brand` varchar(100) NOT NULL,
  `tyre_model` varchar(100) DEFAULT NULL,
  `tyre_number` varchar(100) NOT NULL,
  `purchase_date` date NOT NULL,
  `purchase_cost` decimal(8,2) NOT NULL,
  `vendor_name` varchar(255) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'In Stock'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tyre_inventory`
--

INSERT INTO `tyre_inventory` (`id`, `tyre_brand`, `tyre_model`, `tyre_number`, `purchase_date`, `purchase_cost`, `vendor_name`, `status`) VALUES
(1, 'MRF', 'Nylon', '3E083843925', '0000-00-00', 14700.00, 'INDO COMMERCIAL', 'In Stock'),
(2, 'APPOLO', 'Nylon ', 'T2146531625', '0000-00-00', 13500.00, '', 'Mounted'),
(3, 'APPOLO', '8*25*20 Nylon ', 'U2874492524', '0000-00-00', 13500.00, '', 'Mounted'),
(4, 'APPOLO', '8*25*20 Nylon ', 'U1471852824', '0000-00-00', 13500.00, '', 'Mounted');

-- --------------------------------------------------------

--
-- Table structure for table `tyre_retreading`
--

CREATE TABLE `tyre_retreading` (
  `id` int(11) NOT NULL,
  `tyre_id` int(11) NOT NULL,
  `retread_date` date NOT NULL,
  `vendor_name` varchar(255) DEFAULT NULL,
  `cost` decimal(8,2) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tyre_retreading`
--

INSERT INTO `tyre_retreading` (`id`, `tyre_id`, `retread_date`, `vendor_name`, `cost`, `description`) VALUES
(1, 4, '2025-11-06', 'Bhura Tyres', 4300.00, 'INV NO -257 06/11/25'),
(2, 3, '2025-11-06', 'Bhura Tyres', 4600.00, 'INV NO- 257 (PATCH-JB3)');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'viewer',
  `role_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `consignor_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `pan_no` varchar(20) DEFAULT NULL,
  `aadhaar_no` varchar(20) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `pan_doc_path` varchar(255) DEFAULT NULL,
  `aadhaar_doc_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `role_id`, `branch_id`, `consignor_id`, `address`, `pan_no`, `aadhaar_no`, `photo_path`, `pan_doc_path`, `aadhaar_doc_path`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$PaYhu4csjn8X5BFBohOK1ONg9v9Kbe4hlr28nSo7.FxIK5GH8jwCC', 'admin@example.com', 'admin', 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-11-11 09:46:50', '2025-08-27 14:38:54'),
(2, 'stc.pawan', '$2y$10$MsqZuHXPh085o5aGq2Dwu.Lgq8Rqz2K.9Nv269xfX0ULZhizIgANW', 'stc.pawan@stclogistics.in', 'staff', 3, 3, NULL, '', '', '', '', '', '', 1, '2025-11-10 14:52:56', '2025-09-20 12:30:19'),
(3, 'stc.abhinash', '$2y$10$Ar7eP4R4Kr.FyK6PVtwmL.62x/jAmMq9559bfJmf/XG5GxfRkxZBy', 'stc.abhinash@stclogistics.in', 'staff', 3, 2, NULL, '', '', '', '', '', '', 1, '2025-11-11 10:06:01', '2025-09-20 12:31:14'),
(4, 'stcdgp', '$2y$10$7pBY8sLvsFlBAE1GtlVwG.0YhXLsYIhgapuuUyHTIb0oLK6t2DCXq', 'durgapur@stclogistics.in', 'manager', 2, 3, NULL, '', '', '', '', '', '', 1, '2025-11-10 14:41:22', '2025-09-20 12:33:51'),
(5, 'stcghy', '$2y$10$5HLMQwLT7MQ0roTyHaXO7OKig35wApbZVzrY8ccEr1TiN/t0PDZ5i', 'guwahati@stclogistics.in', 'manager', 2, 2, NULL, '', '', '', '', '', '', 1, '2025-11-11 10:03:40', '2025-09-20 12:34:19'),
(6, 'stcsilchar', '$2y$10$/llCP0A24Iw46luQ3TTtW.TEEyrzz7PWkh48Aa9/JZY2rZoVD7vi.', 'silchar@stclogistics.in', 'manager', 2, 4, NULL, '', '', '', '', '', '', 1, NULL, '2025-09-20 12:36:14'),
(7, '8724903313', '$2y$10$ZMvihKGFrtjhIMgUmxvLdOHurHj3eYfIYH/N8ANdtG3iyEYp2pkq2', 'client@example.com', 'client', NULL, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-09-25 08:31:12', '2025-09-25 08:30:57'),
(8, 'stc.sunil', '$2y$10$CNqS.HPFWRhvB5e3xGgc8OYGOmYKbXEV/pFD9bM3RAe3rtO9Dkmxu', 'info@stclogistics.in', 'staff', NULL, 2, NULL, 'C/O- LOKNATH SHARMA, AMLIGHAT, SONAIKUSHI, MARIGAON, ASSAM- 782413', 'GAEPR6642K', '860457341555', '', 'uploads/users/user_8_pan_doc_1762426927.jpeg', 'uploads/users/user_8_aadhaar_doc_1762426927.jpeg', 1, '2025-11-11 09:32:46', '2025-11-06 11:02:07');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `vehicle_number` varchar(50) NOT NULL,
  `vehicle_type` varchar(100) DEFAULT NULL,
  `ownership_type` enum('Owned','Hired') NOT NULL DEFAULT 'Hired',
  `owner_name` varchar(255) DEFAULT NULL,
  `rc_expiry` date DEFAULT NULL,
  `owner_contact` varchar(20) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `fitness_expiry` date DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `tax_expiry` date DEFAULT NULL,
  `puc_expiry` date DEFAULT NULL,
  `permit_expiry` date DEFAULT NULL,
  `permit_details` text DEFAULT NULL,
  `rc_doc_path` varchar(255) DEFAULT NULL,
  `insurance_doc_path` varchar(255) DEFAULT NULL,
  `permit_doc_path` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `vehicle_number`, `vehicle_type`, `ownership_type`, `owner_name`, `rc_expiry`, `owner_contact`, `driver_id`, `registration_date`, `fitness_expiry`, `insurance_expiry`, `tax_expiry`, `puc_expiry`, `permit_expiry`, `permit_details`, `rc_doc_path`, `insurance_doc_path`, `permit_doc_path`, `branch_id`, `is_active`, `created_at`) VALUES
(1, 'AS01SC1546', 'Truck 6W', 'Owned', 'STC LOGISTICS', NULL, '8707024051', 2, '2024-07-20', '2026-07-19', '2026-06-24', '2025-12-31', '2026-07-19', '2026-07-23', '', '', '', '', NULL, 1, '2025-10-04 08:54:50'),
(2, 'AS01SC1746', 'Truck 6W', 'Owned', 'STC LOGISTICS', NULL, '8707024051', 3, '2024-07-23', '2026-07-19', '2026-06-24', '2025-12-31', '2026-07-19', '2026-07-23', '', '', '', '', NULL, 1, '2025-10-04 08:56:53'),
(3, 'AS01HC9826', 'Truck 6W', 'Owned', 'STC LOGISTICS', NULL, '8707024051', 7, '2017-07-31', '2026-03-29', '2025-11-23', '2025-09-30', '2026-07-01', '2025-10-16', '', '', '', '', NULL, 1, '2025-10-04 08:58:48'),
(4, 'AS01TC5246', 'Truck 6W', 'Owned', 'STC LOGISTICS', NULL, '8707024051', 4, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '', NULL, 1, '2025-10-04 09:00:34'),
(5, 'AS01TC5346', 'Truck 6W', 'Owned', 'STC LOGISTICS', NULL, '8707024051', 10, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '', NULL, 1, '2025-10-04 09:00:59'),
(6, 'AS01TC2956', 'Truck 6W', 'Owned', 'STC LOGISTICS', NULL, '8707024051', 5, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '', NULL, 1, '2025-10-04 09:02:00'),
(7, 'AS01TC2946', 'Truck 6W', 'Owned', 'STC LOGISTICS', NULL, '8707024051', 1, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '', NULL, 1, '2025-10-04 09:02:41'),
(8, 'AS01HC6766', 'Truck 6W', 'Owned', 'Jagadish Prasad Chaubey', NULL, '9435341688', 8, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '', NULL, 1, '2025-10-04 09:05:32'),
(9, 'AS01KC7546', 'Truck 6W', 'Owned', 'Jagadish Prasad Chaubey', NULL, '9435341688', 9, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '', NULL, 1, '2025-10-04 09:05:49'),
(10, 'AS01EC2246', 'Truck 6W', 'Owned', 'Jagadish Prasad Chaubey', NULL, '9435341688', 11, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '', NULL, 1, '2025-10-04 09:08:07'),
(11, 'AS01FC4276', 'Truck 6W', 'Owned', 'Jagadish Prasad Chaubey', NULL, '9435341688', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '', NULL, 1, '2025-10-04 09:09:17'),
(12, 'AS01JC6546', 'Truck 6W', 'Owned', 'Jagadish Prasad Chaubey', NULL, '9435341688', 6, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '', NULL, 1, '2025-10-04 09:10:10'),
(14, 'AS26C5829', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-07 05:53:25'),
(15, 'WB174082', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-08 13:04:24'),
(16, 'HR38AB1663', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-08 13:09:25'),
(17, 'As17b1160', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-09 13:47:43'),
(22, 'AS01D9125', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-10 14:11:29'),
(23, 'AS01DC9125', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-10 14:15:47'),
(24, 'AS01LC5146', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-10 14:22:07'),
(25, 'AS01JC3936', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-10 14:50:33'),
(26, 'AS17C1012', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-10 15:21:42'),
(27, 'AS01KC6314', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-13 12:55:56'),
(28, 'AS01RC1264', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-14 11:36:40'),
(29, 'WB19Q9912', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-14 11:53:09'),
(30, 'AS01JC9883', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-14 13:40:33'),
(31, 'AS15AC5619', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-14 15:13:21'),
(32, 'AS28AC0921', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-15 12:56:10'),
(33, 'AS13AC5357', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-15 12:59:54'),
(34, 'AS26C9064', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-16 12:30:07'),
(35, 'AS17C0621', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-18 17:59:24'),
(36, 'AS25CC8699', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-22 14:43:04'),
(37, 'AS17C5768', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-25 11:34:48'),
(38, 'AS19AC5447', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-25 11:42:09'),
(39, 'TR01AE1577', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-25 11:56:08'),
(40, 'AS26C9346', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-27 14:59:59'),
(41, 'NL01AE4721', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-28 11:08:10'),
(42, 'AS10C7317', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-28 12:22:59'),
(43, 'AS26AC0181', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-28 13:38:54'),
(44, 'NL01AD7077', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-30 10:46:15'),
(45, 'AS01TC2088', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-31 03:35:01'),
(46, 'AS16C3988', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-10-31 15:25:07'),
(47, 'AS19C3974', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-11-01 11:23:26'),
(48, 'AS01NC6049', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-11-01 12:37:19'),
(49, 'AS25CC7996', '', 'Hired', '', NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '', 2, 1, '2025-11-03 11:08:27'),
(51, 'AS01LC4817', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-11-03 14:00:16'),
(52, 'HR58E2933', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-04 12:14:35'),
(53, 'HR65A8645', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-04 13:41:36'),
(54, 'HR38AE8001', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-04 13:45:34'),
(55, 'RJ32GC3431', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-04 13:49:34'),
(56, 'RJ02GB6186', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-04 13:53:19'),
(57, 'BR27GA5559', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-04 13:56:57'),
(58, 'HR58E2618', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-04 15:06:02'),
(60, 'NL01AF4716', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-04 15:22:44'),
(61, 'NL01N9405', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-04 15:29:39'),
(62, 'NL01N4281', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-04 15:38:14'),
(63, 'NL01AD2495', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-05 12:13:31'),
(64, 'NL01AH0412', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-05 12:20:17'),
(65, 'NL01AF7302', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-06 12:57:42'),
(66, 'NL01AD2494', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-06 13:04:01'),
(67, 'NL01AG2757', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-06 13:24:23'),
(68, 'OD14M8850', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-07 11:20:33'),
(69, 'NL01AE3610', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-07 11:28:33'),
(70, 'RJ02GB3151', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-07 12:06:26'),
(71, 'NL01N9497', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-07 13:12:13'),
(72, 'AS17C1086', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-11-08 10:49:00'),
(73, 'HR58E9824', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-08 12:14:35'),
(74, 'HR58C7092', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-08 12:17:24'),
(75, 'NL01AA6195', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-09 13:39:49'),
(76, 'NL01AA3427', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-09 13:50:38'),
(77, 'WB19J2240', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-11-10 09:18:05'),
(78, 'HR38AB0578', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2025-11-10 12:32:50'),
(79, 'UP84T5624', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-10 13:58:31'),
(80, 'RJ32GC0020', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-10 14:02:22'),
(81, 'RJ29GA9367', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-10 14:55:42'),
(82, 'JH05DJ8903', NULL, 'Hired', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 1, '2025-11-10 15:10:37');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_tyres`
--

CREATE TABLE `vehicle_tyres` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `tyre_id` int(11) NOT NULL,
  `position` varchar(50) NOT NULL,
  `mount_date` date NOT NULL,
  `mount_odometer` int(11) NOT NULL,
  `unmount_date` date DEFAULT NULL,
  `unmount_odometer` int(11) DEFAULT NULL,
  `unmount_reason` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_tyres`
--

INSERT INTO `vehicle_tyres` (`id`, `vehicle_id`, `tyre_id`, `position`, `mount_date`, `mount_odometer`, `unmount_date`, `unmount_odometer`, `unmount_reason`) VALUES
(1, 12, 2, 'Rear-Outer-Right', '2025-11-05', 0, NULL, NULL, NULL),
(2, 12, 3, 'Front-Left', '2025-11-06', 12, '2025-11-06', 12, 'Other'),
(3, 12, 3, 'Rear-Inner-Left', '2025-11-06', 12, NULL, NULL, NULL),
(4, 12, 4, 'Rear-Outer-Left', '2025-11-06', 12, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `brokers`
--
ALTER TABLE `brokers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `state_id` (`state_id`);

--
-- Indexes for table `company_details`
--
ALTER TABLE `company_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `consignment_descriptions`
--
ALTER TABLE `consignment_descriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `description` (`description`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD KEY `fk_driver_branch` (`branch_id`),
  ADD KEY `fk_driver_employee` (`employee_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_id` (`shipment_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `fk_expense_employee` (`employee_id`);

--
-- Indexes for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `filled_by_driver_id` (`filled_by_driver_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `consignor_id` (`consignor_id`),
  ADD KEY `created_by_id` (`created_by_id`),
  ADD KEY `fk_invoice_branch` (`branch_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shipment_id` (`shipment_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `reconciliation_voucher_id` (`reconciliation_voucher_id`);

--
-- Indexes for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `maintenance_service_types`
--
ALTER TABLE `maintenance_service_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `parties`
--
ALTER TABLE `parties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_party_branch` (`branch_id`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_month_unique` (`employee_id`,`month_year`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `reconciliation_vouchers`
--
ALTER TABLE `reconciliation_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voucher_no` (`voucher_no`),
  ADD KEY `party_id` (`party_id`),
  ADD KEY `reconciled_by_id` (`reconciled_by_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `salary_structures`
--
ALTER TABLE `salary_structures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `consignment_no` (`consignment_no`),
  ADD KEY `consignor_id` (`consignor_id`),
  ADD KEY `consignee_id` (`consignee_id`),
  ADD KEY `broker_id` (`broker_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `created_by_id` (`created_by_id`),
  ADD KEY `fk_shipment_branch` (`branch_id`),
  ADD KEY `fk_shipment_description` (`description_id`);

--
-- Indexes for table `shipment_invoices`
--
ALTER TABLE `shipment_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_id` (`shipment_id`);

--
-- Indexes for table `shipment_payments`
--
ALTER TABLE `shipment_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_id` (`shipment_id`),
  ADD KEY `created_by_id` (`created_by_id`);

--
-- Indexes for table `shipment_tracking`
--
ALTER TABLE `shipment_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_id` (`shipment_id`),
  ADD KEY `updated_by_id` (`updated_by_id`);

--
-- Indexes for table `states`
--
ALTER TABLE `states`
  ADD PRIMARY KEY (`id`),
  ADD KEY `country_id` (`country_id`);

--
-- Indexes for table `tyre_inventory`
--
ALTER TABLE `tyre_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tyre_number` (`tyre_number`);

--
-- Indexes for table `tyre_retreading`
--
ALTER TABLE `tyre_retreading`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tyre_id` (`tyre_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_user_branch` (`branch_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicle_number` (`vehicle_number`),
  ADD KEY `fk_vehicle_driver` (`driver_id`),
  ADD KEY `fk_vehicle_branch` (`branch_id`);

--
-- Indexes for table `vehicle_tyres`
--
ALTER TABLE `vehicle_tyres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `tyre_id` (`tyre_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `brokers`
--
ALTER TABLE `brokers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT for table `company_details`
--
ALTER TABLE `company_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `consignment_descriptions`
--
ALTER TABLE `consignment_descriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `maintenance_service_types`
--
ALTER TABLE `maintenance_service_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `parties`
--
ALTER TABLE `parties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reconciliation_vouchers`
--
ALTER TABLE `reconciliation_vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `salary_structures`
--
ALTER TABLE `salary_structures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=196;

--
-- AUTO_INCREMENT for table `shipment_invoices`
--
ALTER TABLE `shipment_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=228;

--
-- AUTO_INCREMENT for table `shipment_payments`
--
ALTER TABLE `shipment_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=576;

--
-- AUTO_INCREMENT for table `shipment_tracking`
--
ALTER TABLE `shipment_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=570;

--
-- AUTO_INCREMENT for table `states`
--
ALTER TABLE `states`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `tyre_inventory`
--
ALTER TABLE `tyre_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tyre_retreading`
--
ALTER TABLE `tyre_retreading`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `vehicle_tyres`
--
ALTER TABLE `vehicle_tyres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `brokers`
--
ALTER TABLE `brokers`
  ADD CONSTRAINT `brokers_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `drivers`
--
ALTER TABLE `drivers`
  ADD CONSTRAINT `fk_driver_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_driver_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `expenses_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_expense_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  ADD CONSTRAINT `fuel_logs_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  ADD CONSTRAINT `fuel_logs_ibfk_2` FOREIGN KEY (`filled_by_driver_id`) REFERENCES `drivers` (`id`),
  ADD CONSTRAINT `fuel_logs_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fuel_logs_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoice_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`consignor_id`) REFERENCES `parties` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  ADD CONSTRAINT `invoice_payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_payments_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `invoice_payments_ibfk_3` FOREIGN KEY (`reconciliation_voucher_id`) REFERENCES `reconciliation_vouchers` (`id`);

--
-- Constraints for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD CONSTRAINT `maintenance_logs_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  ADD CONSTRAINT `maintenance_logs_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `maintenance_logs_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `parties`
--
ALTER TABLE `parties`
  ADD CONSTRAINT `fk_party_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payslips`
--
ALTER TABLE `payslips`
  ADD CONSTRAINT `payslips_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reconciliation_vouchers`
--
ALTER TABLE `reconciliation_vouchers`
  ADD CONSTRAINT `reconciliation_vouchers_ibfk_1` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`),
  ADD CONSTRAINT `reconciliation_vouchers_ibfk_2` FOREIGN KEY (`reconciled_by_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `salary_structures`
--
ALTER TABLE `salary_structures`
  ADD CONSTRAINT `salary_structures_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `fk_shipment_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_shipment_description` FOREIGN KEY (`description_id`) REFERENCES `consignment_descriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shipments_ibfk_1` FOREIGN KEY (`consignor_id`) REFERENCES `parties` (`id`),
  ADD CONSTRAINT `shipments_ibfk_2` FOREIGN KEY (`consignee_id`) REFERENCES `parties` (`id`),
  ADD CONSTRAINT `shipments_ibfk_3` FOREIGN KEY (`broker_id`) REFERENCES `brokers` (`id`),
  ADD CONSTRAINT `shipments_ibfk_4` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
  ADD CONSTRAINT `shipments_ibfk_5` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  ADD CONSTRAINT `shipments_ibfk_6` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `shipment_invoices`
--
ALTER TABLE `shipment_invoices`
  ADD CONSTRAINT `shipment_invoices_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipment_payments`
--
ALTER TABLE `shipment_payments`
  ADD CONSTRAINT `shipment_payments_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shipment_payments_ibfk_2` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `shipment_tracking`
--
ALTER TABLE `shipment_tracking`
  ADD CONSTRAINT `shipment_tracking_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shipment_tracking_ibfk_2` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `states`
--
ALTER TABLE `states`
  ADD CONSTRAINT `states_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tyre_retreading`
--
ALTER TABLE `tyre_retreading`
  ADD CONSTRAINT `tyre_retreading_ibfk_1` FOREIGN KEY (`tyre_id`) REFERENCES `tyre_inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `fk_vehicle_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vehicle_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vehicle_tyres`
--
ALTER TABLE `vehicle_tyres`
  ADD CONSTRAINT `vehicle_tyres_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  ADD CONSTRAINT `vehicle_tyres_ibfk_2` FOREIGN KEY (`tyre_id`) REFERENCES `tyre_inventory` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
