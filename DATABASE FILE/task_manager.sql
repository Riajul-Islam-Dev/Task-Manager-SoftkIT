-- phpMyAdmin SQL Dump
-- version 4.6.5.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2021 at 08:34 PM
-- Server version: 5.6.21
-- PHP Version: 5.6.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `task_manager`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_lists`
--

CREATE TABLE `tbl_lists` (
  `list_id` int(10) UNSIGNED NOT NULL,
  `list_name` varchar(50) NOT NULL,
  `list_description` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_lists`
--

INSERT INTO `tbl_lists` (`list_id`, `list_name`, `list_description`) VALUES
(1, 'To Do', 'All the tasks that must be done soon'),
(2, 'In Progress', 'All the Tasks that are currently in progress'),
(3, 'Completed', 'All the Tasks that are completed  '),
(9, 'Submitted', 'All the task that are completed and submitted.');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_tasks`
--

CREATE TABLE `tbl_tasks` (
  `task_id` int(10) UNSIGNED NOT NULL,
  `task_name` varchar(150) NOT NULL,
  `task_description` text NOT NULL,
  `list_id` int(11) NOT NULL,
  `priority` varchar(10) NOT NULL,
  `deadline` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_tasks`
--

INSERT INTO `tbl_tasks` (`task_id`, `task_name`, `task_description`, `list_id`, `priority`, `deadline`) VALUES
(2, 'Logo Design', 'Logo Design for Nepz Technologies Pvt. Ltd.                        ', 1, 'High', '2021-05-12'),
(4, 'Website Design & Development', 'All the Tasks for Web Page Design and Development                                              ', 9, 'Medium', '2021-04-01'),
(5, 'Flutter App Development', 'Need to complete developing app based on quiz app               ', 3, 'Medium', '2021-03-04'),
(9, 'UI/UX', 'UI/UX Design', 2, 'High', '2021-05-07'),
(11, 'Content Writing', 'Content writing on Trending Topics', 1, 'Medium', '2021-05-06'),
(12, 'Mockup', 'Finalize logo mockup', 1, 'High', '2021-05-07'),
(13, 'Regarding Posts', 'Write and Submit Posts                        ', 9, 'High', '2021-05-05'),
(14, 'PSD to HTML Conversion', 'PSD to HTML Conversion', 2, 'Medium', '2021-05-12'),
(15, 'Testings', 'Flutter App Testing', 1, 'High', '2021-05-08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_lists`
--
ALTER TABLE `tbl_lists`
  ADD PRIMARY KEY (`list_id`);

--
-- Indexes for table `tbl_tasks`
--
ALTER TABLE `tbl_tasks`
  ADD PRIMARY KEY (`task_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_lists`
--
ALTER TABLE `tbl_lists`
  MODIFY `list_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `tbl_tasks`
--
ALTER TABLE `tbl_tasks`
  MODIFY `task_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
