-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Mar 22, 2026 at 09:40 PM
-- Server version: 8.0.44
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `palmGlow`
--

-- --------------------------------------------------------

--
-- Table structure for table `BlockedUser`
--

CREATE TABLE `BlockedUser` (
  `id` int NOT NULL,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `emailAddress` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `BlockedUser`
--

INSERT INTO `BlockedUser` (`id`, `firstName`, `lastName`, `emailAddress`) VALUES
(1, 'Amanda', 'Jackson', 'Amanda@gmail.com'),
(2, 'James', 'Chen', 'jamesch@hotmail.com'),
(3, 'Ommar', 'Hassan', 'Omar.hassan@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `COMMENT`
--

CREATE TABLE `COMMENT` (
  `id` int NOT NULL,
  `recipeID` int NOT NULL,
  `userID` int NOT NULL,
  `COMMENT` text NOT NULL,
  `DATE` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `COMMENT`
--

INSERT INTO `COMMENT` (`id`, `recipeID`, `userID`, `COMMENT`, `DATE`) VALUES
(1, 1, 3, 'Looks delicious and healthy!', '2026-03-12 00:00:00'),
(2, 2, 2, 'This is my favorite breakfast recipe.', '2026-03-12 00:00:00'),
(3, 3, 1, 'Beautiful presentation and great flavor idea.', '2026-03-12 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `Favourites`
--

CREATE TABLE `Favourites` (
  `userID` int NOT NULL,
  `recipeID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Favourites`
--

INSERT INTO `Favourites` (`userID`, `recipeID`) VALUES
(2, 1),
(3, 2),
(2, 3);

-- --------------------------------------------------------

--
-- Table structure for table `Ingredients`
--

CREATE TABLE `Ingredients` (
  `id` int NOT NULL,
  `recipeID` int NOT NULL,
  `ingredientName` varchar(255) NOT NULL,
  `ingredientQuantity` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Ingredients`
--

INSERT INTO `Ingredients` (`id`, `recipeID`, `ingredientName`, `ingredientQuantity`) VALUES
(1, 1, 'Salmon fillet', '2 pieces'),
(2, 1, 'Qahwa spice mix', '1 tsp'),
(3, 1, 'Olive oil', '1 tbsp'),
(4, 2, 'Greek yogurt', '1 cup'),
(5, 2, 'Medjool dates', '3 chopped'),
(6, 2, 'Almonds', '2 tbsp'),
(7, 3, 'Matcha powder', '1.5 tsp'),
(8, 3, 'Milk', '1 cup'),
(9, 3, 'Rose water', '0.5 tsp');

-- --------------------------------------------------------

--
-- Table structure for table `Instructions`
--

CREATE TABLE `Instructions` (
  `id` int NOT NULL,
  `recipeID` int NOT NULL,
  `step` text NOT NULL,
  `stepOrder` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Instructions`
--

INSERT INTO `Instructions` (`id`, `recipeID`, `step`, `stepOrder`) VALUES
(1, 1, 'Pat the salmon dry and brush it with olive oil.', 1),
(2, 1, 'Season with qahwa spice mix and grill until cooked.', 2),
(3, 1, 'Serve with herbs and lemon.', 3),
(4, 2, 'Mix yogurt with chopped dates.', 1),
(5, 2, 'Top with almonds.', 2),
(6, 2, 'Serve chilled.', 3),
(7, 3, 'Whisk the matcha with warm water.', 1),
(8, 3, 'Add milk and rose water.', 2),
(9, 3, 'Top with foam and serve.', 3);

-- --------------------------------------------------------

--
-- Table structure for table `Likes`
--

CREATE TABLE `Likes` (
  `userID` int NOT NULL,
  `recipeID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Likes`
--

INSERT INTO `Likes` (`userID`, `recipeID`) VALUES
(3, 1),
(1, 2),
(2, 3);

-- --------------------------------------------------------

--
-- Table structure for table `Recipe`
--

CREATE TABLE `Recipe` (
  `id` int NOT NULL,
  `userID` int NOT NULL,
  `categoryID` int NOT NULL,
  `NAME` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `photoFileName` varchar(255) NOT NULL,
  `videoFilePath` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Recipe`
--

INSERT INTO `Recipe` (`id`, `userID`, `categoryID`, `NAME`, `description`, `photoFileName`, `videoFilePath`) VALUES
(1, 2, 1, 'Salmon with Qahwa Spice Rub', 'Grilled salmon with qahwa spices and fresh herbs.', 'glowPlate1.png', 'recipe1.mp4'),
(2, 2, 3, 'Date & Almond Yogurt Bowl', 'Creamy yogurt topped with dates and almonds.', 'palmTreat1.png', 'recipe2.mp4'),
(3, 3, 2, 'Matcha with Rose Water Foam', 'Smooth matcha drink with a soft rose foam layer.', 'glowSip4.png', 'recipe3.mp4');

-- --------------------------------------------------------

--
-- Table structure for table `RecipeCategory`
--

CREATE TABLE `RecipeCategory` (
  `id` int NOT NULL,
  `categoryName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `RecipeCategory`
--

INSERT INTO `RecipeCategory` (`id`, `categoryName`) VALUES
(1, 'Glow plates'),
(2, 'Glow sips'),
(3, 'Glow treats');

-- --------------------------------------------------------

--
-- Table structure for table `Report`
--

CREATE TABLE `Report` (
  `id` int NOT NULL,
  `userID` int NOT NULL,
  `recipeID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `Report`
--

INSERT INTO `Report` (`id`, `userID`, `recipeID`) VALUES
(1, 2, 3),
(2, 3, 1),
(3, 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `User`
--

CREATE TABLE `User` (
  `id` int NOT NULL,
  `userType` enum('user','admin') NOT NULL,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `emailAddress` varchar(255) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `photoFileName` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `User`
--

INSERT INTO `User` (`id`, `userType`, `firstName`, `lastName`, `emailAddress`, `PASSWORD`, `photoFileName`) VALUES
(1, 'admin', 'Jana', 'Ali', 'jana.admin@gmail.com', '$2y$10$yk1e48phcVVUcZETxOXkeOdA7MpgvNq6BmdJi7iMFDC2G39i.JCwO', 'Avatar.png'),
(2, 'user', 'Dalia', 'Alrasheed', 'dalia.alrasheed@gmail.com', '$2y$10$wZ7ejBcBxzeBtxxfp3W1kuiXB80Wr6GVFaS7YwxrJX6Lz6vVjOPji', 'Avatar.png'),
(3, 'user', 'Hala', 'Ahmed', 'hala.ahmed@gmail.com', '$2y$10$CGlQy1Y/C8JYYoKYPSfBPuZJlJdG9xiE5Atl5LrtMqyJztU4gA9kC', 'Avatar.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `BlockedUser`
--
ALTER TABLE `BlockedUser`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emailAddress` (`emailAddress`);

--
-- Indexes for table `COMMENT`
--
ALTER TABLE `COMMENT`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipeID` (`recipeID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `Favourites`
--
ALTER TABLE `Favourites`
  ADD PRIMARY KEY (`userID`,`recipeID`),
  ADD KEY `recipeID` (`recipeID`);

--
-- Indexes for table `Ingredients`
--
ALTER TABLE `Ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipeID` (`recipeID`);

--
-- Indexes for table `Instructions`
--
ALTER TABLE `Instructions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipeID` (`recipeID`);

--
-- Indexes for table `Likes`
--
ALTER TABLE `Likes`
  ADD PRIMARY KEY (`userID`,`recipeID`),
  ADD KEY `recipeID` (`recipeID`);

--
-- Indexes for table `Recipe`
--
ALTER TABLE `Recipe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userID` (`userID`),
  ADD KEY `categoryID` (`categoryID`);

--
-- Indexes for table `RecipeCategory`
--
ALTER TABLE `RecipeCategory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `Report`
--
ALTER TABLE `Report`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userID` (`userID`),
  ADD KEY `recipeID` (`recipeID`);

--
-- Indexes for table `User`
--
ALTER TABLE `User`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emailAddress` (`emailAddress`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `BlockedUser`
--
ALTER TABLE `BlockedUser`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `COMMENT`
--
ALTER TABLE `COMMENT`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `Ingredients`
--
ALTER TABLE `Ingredients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `Instructions`
--
ALTER TABLE `Instructions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `Recipe`
--
ALTER TABLE `Recipe`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `RecipeCategory`
--
ALTER TABLE `RecipeCategory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `Report`
--
ALTER TABLE `Report`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `User`
--
ALTER TABLE `User`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `COMMENT`
--
ALTER TABLE `COMMENT`
  ADD CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`recipeID`) REFERENCES `Recipe` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_ibfk_2` FOREIGN KEY (`userID`) REFERENCES `USER` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `Favourites`
--
ALTER TABLE `Favourites`
  ADD CONSTRAINT `favourites_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `USER` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favourites_ibfk_2` FOREIGN KEY (`recipeID`) REFERENCES `Recipe` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `Ingredients`
--
ALTER TABLE `Ingredients`
  ADD CONSTRAINT `ingredients_ibfk_1` FOREIGN KEY (`recipeID`) REFERENCES `Recipe` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `Instructions`
--
ALTER TABLE `Instructions`
  ADD CONSTRAINT `instructions_ibfk_1` FOREIGN KEY (`recipeID`) REFERENCES `Recipe` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `Likes`
--
ALTER TABLE `Likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `USER` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`recipeID`) REFERENCES `Recipe` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `Recipe`
--
ALTER TABLE `Recipe`
  ADD CONSTRAINT `recipe_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `USER` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recipe_ibfk_2` FOREIGN KEY (`categoryID`) REFERENCES `RecipeCategory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `Report`
--
ALTER TABLE `Report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `USER` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_ibfk_2` FOREIGN KEY (`recipeID`) REFERENCES `Recipe` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
