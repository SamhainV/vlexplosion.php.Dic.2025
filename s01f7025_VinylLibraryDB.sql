-- phpMyAdmin SQL Dump
-- version 4.9.4
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 30-12-2025 a las 11:58:08
-- Versión del servidor: 5.7.44
-- Versión de PHP: 7.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `s01f7025_VinylLibraryDB`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `AUTHORS_TBL`
--

CREATE TABLE `AUTHORS_TBL` (
  `Id` int(11) NOT NULL,
  `Author_Name` varchar(50) NOT NULL,
  `Debut_album_release` year(4) DEFAULT NULL,
  `Original_members` int(11) DEFAULT NULL,
  `Breakup_date` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `AUTHORS_TBL`
--

INSERT INTO `AUTHORS_TBL` (`Id`, `Author_Name`, `Debut_album_release`, `Original_members`, `Breakup_date`) VALUES
(1, 'David Bowie', NULL, NULL, NULL),
(2, 'The Clash', NULL, NULL, NULL),
(3, 'The Pretenders', NULL, NULL, NULL),
(4, 'Ramones', NULL, NULL, NULL),
(5, 'TDeK', NULL, NULL, NULL),
(6, 'Ramones', NULL, NULL, NULL),
(7, 'The Cramps', NULL, NULL, NULL),
(8, 'Black Sabbath', NULL, NULL, NULL),
(9, 'Black Sabbath', NULL, NULL, NULL),
(10, 'Black Sabbath', NULL, NULL, NULL),
(11, 'Green on Red', NULL, NULL, NULL),
(12, 'Black Sabbath', NULL, NULL, NULL),
(13, 'Green on Red', NULL, NULL, NULL),
(14, 'Alien Sex Fiend', NULL, NULL, NULL),
(15, 'Damned', NULL, NULL, NULL),
(16, 'The Darling Buds', NULL, NULL, NULL),
(17, 'Varios', NULL, NULL, NULL),
(18, 'Varios', NULL, NULL, NULL),
(19, 'Varios', NULL, NULL, NULL),
(20, 'Alien Sex Fiend', NULL, NULL, NULL),
(21, 'Roxy Music', NULL, NULL, NULL),
(22, 'Asia', NULL, NULL, NULL),
(23, 'Paralisis Permanente', NULL, NULL, NULL),
(24, 'Orchestral Manoeuvres in the D', NULL, NULL, NULL),
(25, 'Ultravox', NULL, NULL, NULL),
(26, 'New York Dolls', NULL, NULL, NULL),
(27, 'New York Dolls', NULL, NULL, NULL),
(28, 'Ultravox', NULL, NULL, NULL),
(29, 'Eskorbuto', NULL, NULL, NULL),
(30, 'Orchestral Maoeuvres In The Da', NULL, NULL, NULL),
(31, 'Desechables', NULL, NULL, NULL),
(32, 'Mozart', NULL, NULL, NULL),
(33, 'OMD', NULL, NULL, NULL),
(34, 'Ramones', NULL, NULL, NULL),
(35, 'The Cure', NULL, NULL, NULL),
(36, 'Ultravox', NULL, NULL, NULL),
(37, 'Los Elegantes', NULL, NULL, NULL),
(38, 'Ultravox', NULL, NULL, NULL),
(39, 'The Last', NULL, NULL, NULL),
(40, 'OMD', NULL, NULL, NULL),
(41, 'Howard Devoto', NULL, NULL, NULL),
(42, 'The Cure', NULL, NULL, NULL),
(43, 'Ultravox', NULL, NULL, NULL),
(44, 'Pixies', NULL, NULL, NULL),
(45, 'The Lord of the New Church', NULL, NULL, NULL),
(46, 'Jonathan Richman', NULL, NULL, NULL),
(47, 'Pixies', NULL, NULL, NULL),
(48, 'Varios', NULL, NULL, NULL),
(49, 'The Jam', NULL, NULL, NULL),
(50, 'Varios', NULL, NULL, NULL),
(51, 'Varios', NULL, NULL, NULL),
(52, 'Tears For Fears', NULL, NULL, NULL),
(53, 'Alien Sex Fiend', NULL, NULL, NULL),
(54, 'The Fall', NULL, NULL, NULL),
(55, 'Surf Punks', NULL, NULL, NULL),
(56, 'Hoodoo Gurus', NULL, NULL, NULL),
(57, 'Fuzztones', NULL, NULL, NULL),
(58, 'Alan Parsons Project', NULL, NULL, NULL),
(59, 'Joan Manuel Serrat', NULL, NULL, NULL),
(60, 'The Damned', NULL, NULL, NULL),
(61, 'Airbag', NULL, NULL, NULL),
(62, 'Airbag', NULL, NULL, NULL),
(63, 'Siniestro Total', NULL, NULL, NULL),
(64, 'Jonathan Richman', NULL, NULL, NULL),
(65, 'Eddie Cochran', NULL, NULL, NULL),
(66, 'Violent Femmes', NULL, NULL, NULL),
(67, 'AC/DC', NULL, NULL, NULL),
(68, 'Ruts', NULL, NULL, NULL),
(69, 'Cochran Brothers', NULL, NULL, NULL),
(70, 'Los Potros', NULL, NULL, NULL),
(71, 'Eddie Cochran', NULL, NULL, NULL),
(72, 'Eddie Cochran', NULL, NULL, NULL),
(73, 'Green on Red', NULL, NULL, NULL),
(74, 'Mega City Four', NULL, NULL, NULL),
(75, 'Gabinete Caligari', NULL, NULL, NULL),
(76, 'Supertramp', NULL, NULL, NULL),
(77, 'Supertramp', NULL, NULL, NULL),
(78, 'Varios', NULL, NULL, NULL),
(79, 'Stray Cats', NULL, NULL, NULL),
(80, 'Stray Cats', NULL, NULL, NULL),
(81, 'Kraftwerk', NULL, NULL, NULL),
(82, 'Dion And The Belmonts', NULL, NULL, NULL),
(83, 'Desechables', NULL, NULL, NULL),
(84, 'El caso de la Habana', NULL, NULL, NULL),
(85, 'Siniestro Total', NULL, NULL, NULL),
(86, 'XTC', NULL, NULL, NULL),
(87, 'Madness', NULL, NULL, NULL),
(88, 'Elvis Costello', NULL, NULL, NULL),
(89, 'Bocaaadiscooo', NULL, NULL, NULL),
(90, 'The adventure babies', NULL, NULL, NULL),
(91, 'Locura por la musica', NULL, NULL, NULL),
(92, 'The Pogues', NULL, NULL, NULL),
(93, 'One Way System', NULL, NULL, NULL),
(94, 'The Primitives', NULL, NULL, NULL),
(95, 'Bauhaus', NULL, NULL, NULL),
(96, 'New Order', NULL, NULL, NULL),
(97, 'The Cult', NULL, NULL, NULL),
(98, 'The Fall', NULL, NULL, NULL),
(99, 'Jonathan Richman & the Modern Lovers', NULL, NULL, NULL),
(100, 'Jonathan Richman & the Modern Lovers', NULL, NULL, NULL),
(101, 'Ramones', NULL, NULL, NULL),
(102, 'Ramones', NULL, NULL, NULL),
(103, 'Charlie Sexton', NULL, NULL, NULL),
(104, 'Ramones', NULL, NULL, NULL),
(105, 'Ramones', NULL, NULL, NULL),
(106, 'Venom', NULL, NULL, NULL),
(107, 'Keane', NULL, NULL, NULL),
(108, 'Decibelios', NULL, NULL, NULL),
(109, 'Black Sabbath', NULL, NULL, NULL),
(110, 'Black Sabbath', NULL, NULL, NULL),
(111, 'Scorpions', NULL, NULL, NULL),
(112, 'Heavy Metal', NULL, NULL, NULL),
(113, 'Plays the orchestral Jethero Tull', NULL, NULL, NULL),
(114, 'Cool Jerks', NULL, NULL, NULL),
(115, 'Los Del Tonos', NULL, NULL, NULL),
(116, 'Placticland', NULL, NULL, NULL),
(117, 'Los Tiki Phantoms', NULL, NULL, NULL),
(118, 'Surfin\' Lungs', NULL, NULL, NULL),
(119, 'Orchestral Manouvres in the dark', NULL, NULL, NULL),
(120, 'Black Sabbath', NULL, NULL, NULL),
(121, 'Mr Big', NULL, NULL, NULL),
(122, 'Al Stewart', NULL, NULL, NULL),
(123, 'Electric Light Orchestra', NULL, NULL, NULL),
(124, 'Deep Purple', NULL, NULL, NULL),
(125, 'The Clash', NULL, NULL, NULL),
(126, 'The Meteors', NULL, NULL, NULL),
(127, 'The Clash', NULL, NULL, NULL),
(128, 'Bauhaus', NULL, NULL, NULL),
(129, 'Magazine', NULL, NULL, NULL),
(130, 'The Clash', NULL, NULL, NULL),
(131, 'The Young Fresh Fellows', NULL, NULL, NULL),
(132, 'Varios', NULL, NULL, NULL),
(133, 'The Young Fresh Fellows', NULL, NULL, NULL),
(134, 'The Young Fresh Fellows', NULL, NULL, NULL),
(135, 'The Young Fresh Fellows', NULL, NULL, NULL),
(136, 'Shock Treatment', NULL, NULL, NULL),
(137, 'Blur', NULL, NULL, NULL),
(138, 'Misfits', NULL, NULL, NULL),
(139, 'The Clash', NULL, NULL, NULL),
(140, 'Slipknot', NULL, NULL, NULL),
(141, 'Elvis Costello', NULL, NULL, NULL),
(142, 'Electric Light Orchestra', NULL, NULL, NULL),
(143, 'Jethro tull', NULL, NULL, NULL),
(144, 'Black Sabbath', NULL, NULL, NULL),
(145, 'Deep Purple', NULL, NULL, NULL),
(146, 'Scorpions', NULL, NULL, NULL),
(147, 'Scorpions', NULL, NULL, NULL),
(148, 'Varios Artistas', NULL, NULL, NULL),
(149, 'Jethro tull', NULL, NULL, NULL),
(150, 'Black Sabbath', NULL, NULL, NULL),
(151, 'Medina Azahara', NULL, NULL, NULL),
(152, 'Medina Azahara', NULL, NULL, NULL),
(153, 'Triana', NULL, NULL, NULL),
(154, 'Yngwie J. Malmsteen\'s', NULL, NULL, NULL),
(155, 'Black Sabbath', NULL, NULL, NULL),
(156, 'Electric Light Orchestra', NULL, NULL, NULL),
(157, 'Los Romeos', NULL, NULL, NULL),
(158, 'The Meteors', NULL, NULL, NULL),
(159, 'Los Nikis', NULL, NULL, NULL),
(160, 'The Weaklings', NULL, NULL, NULL),
(161, 'Robert Gordon', NULL, NULL, NULL),
(162, 'Electric Light Orchestra', NULL, NULL, NULL),
(163, 'Chicago', NULL, NULL, NULL),
(164, 'Electric Light Orchestra', NULL, NULL, NULL),
(165, 'Electric Light Orchestra', NULL, NULL, NULL),
(166, 'Electric Light Orchestra', NULL, NULL, NULL),
(167, 'Electric Light Orchestra', NULL, NULL, NULL),
(168, 'Electric Light Orchestra', NULL, NULL, NULL),
(169, 'Electric Light Orchestra', NULL, NULL, NULL),
(170, 'Electric Light Orchestra', NULL, NULL, NULL),
(171, 'Electric Light Orchestra', NULL, NULL, NULL),
(172, 'Godfathers', NULL, NULL, NULL),
(173, 'The Last', NULL, NULL, NULL),
(174, 'Ian Dury & The BlockHeads', NULL, NULL, NULL),
(175, 'Triana', NULL, NULL, NULL),
(176, 'Men at Work', NULL, NULL, NULL),
(177, 'Alien Sex Fiend', NULL, NULL, NULL),
(178, 'King Trigger', NULL, NULL, NULL),
(179, 'Ramones', NULL, NULL, NULL),
(180, 'Ramones', NULL, NULL, NULL),
(181, 'Intronautas', NULL, NULL, NULL),
(182, 'Electric Light Orchestra', NULL, NULL, NULL),
(183, 'Mingus', NULL, NULL, NULL),
(184, 'Duke Ellington', NULL, NULL, NULL),
(185, 'Los Nikis', NULL, NULL, NULL),
(186, 'Blondie', NULL, NULL, NULL),
(187, 'Deep Purple', NULL, NULL, NULL),
(188, 'Electric Light Orchestra', NULL, NULL, NULL),
(189, 'John Coltrane', NULL, NULL, NULL),
(190, 'Alan Parsons Project', NULL, NULL, NULL),
(191, 'Blondie', NULL, NULL, NULL),
(192, 'Blondie', NULL, NULL, NULL),
(193, 'Los Nikis', NULL, NULL, NULL),
(194, 'Alan Parsons Project', NULL, NULL, NULL),
(195, 'Johnny Kidd & the Pirates', NULL, NULL, NULL),
(196, 'Loquillo y los trogloditas', NULL, NULL, NULL),
(197, 'Ramones', NULL, NULL, NULL),
(198, 'Misfits', NULL, NULL, NULL),
(199, 'Joey Ramone', NULL, NULL, NULL),
(200, 'Ramones', NULL, NULL, NULL),
(201, 'Ramones', NULL, NULL, NULL),
(202, 'Ramones', NULL, NULL, NULL),
(203, 'Ramones', NULL, NULL, NULL),
(204, 'Miles Davis', NULL, NULL, NULL),
(205, 'Ramones', NULL, NULL, NULL),
(206, 'Ramones', NULL, NULL, NULL),
(207, 'Ramones', NULL, NULL, NULL),
(208, 'Ramones', NULL, NULL, NULL),
(209, 'Ramones', NULL, NULL, NULL),
(210, 'Ramones', NULL, NULL, NULL),
(211, 'Ramones', NULL, NULL, NULL),
(212, 'Ramones', NULL, NULL, NULL),
(213, 'Ramones', NULL, NULL, NULL),
(214, 'Ramones', NULL, NULL, NULL),
(215, 'Ramones', NULL, NULL, NULL),
(216, 'Ramones', NULL, NULL, NULL),
(217, 'Ramones', NULL, NULL, NULL),
(218, 'Ramones', NULL, NULL, NULL),
(219, 'Ramones', NULL, NULL, NULL),
(220, 'Ramones', NULL, NULL, NULL),
(221, 'Ramones', NULL, NULL, NULL),
(222, 'Bad Chopper', NULL, NULL, NULL),
(223, 'Ramones', NULL, NULL, NULL),
(224, 'Varios', NULL, NULL, NULL),
(225, 'Link Wray', NULL, NULL, NULL),
(226, 'varios', NULL, NULL, NULL),
(227, 'Bo Diddley', NULL, NULL, NULL),
(228, 'Wanda Jackson', NULL, NULL, NULL),
(229, 'Cool Jerks', NULL, NULL, NULL),
(230, 'Varios', NULL, NULL, NULL),
(231, 'Gaby, Fofo y Miloki', NULL, NULL, NULL),
(232, 'Surfin\' Lungs', NULL, NULL, NULL),
(233, 'Jethro Tull', NULL, NULL, NULL),
(234, 'Dionne Warwick', NULL, NULL, NULL),
(235, 'Sam & Dave', NULL, NULL, NULL),
(236, 'Varios', NULL, NULL, NULL),
(237, 'Rocky Sharpe & the Replays', NULL, NULL, NULL),
(238, 'Rocky Sharpe & the Replays', NULL, NULL, NULL),
(239, 'Carl Perkins', NULL, NULL, NULL),
(240, 'Derek and the dominos', NULL, NULL, NULL),
(241, 'Lake', NULL, NULL, NULL),
(242, 'Eric Clapton', NULL, NULL, NULL),
(243, 'Eric Clapton', NULL, NULL, NULL),
(244, 'Phoenix', NULL, NULL, NULL),
(245, 'Johnny burnette', NULL, NULL, NULL),
(246, 'El gran Count Basie', NULL, NULL, NULL),
(247, 'The Sound of New Orleans', NULL, NULL, NULL),
(248, 'Varios', NULL, NULL, NULL),
(249, 'Ray Charles', NULL, NULL, NULL),
(250, 'Varios', NULL, NULL, NULL),
(251, 'Varios', NULL, NULL, NULL),
(252, 'The Drifters\'', NULL, NULL, NULL),
(253, 'Varios', NULL, NULL, NULL),
(254, 'Crazy Cavan \'n\' The Rhythm Rockers', NULL, NULL, NULL),
(255, 'James Brown', NULL, NULL, NULL),
(256, 'Wanda Jackson', NULL, NULL, NULL),
(257, 'Various', NULL, NULL, NULL),
(258, 'Bo Diddley', NULL, NULL, NULL),
(259, 'Buddy Holly', NULL, NULL, NULL),
(260, 'Jerry Lee Lewis', NULL, NULL, NULL),
(261, 'West Coast Doo Wop', NULL, NULL, NULL),
(262, 'Chuck Berry', NULL, NULL, NULL),
(263, 'Los Tornados', NULL, NULL, NULL),
(264, 'La frontera', NULL, NULL, NULL),
(265, 'Johnny and the Hurricanes', NULL, NULL, NULL),
(266, 'The madness Invasion', NULL, NULL, NULL),
(267, 'Depeche Mode', NULL, NULL, NULL),
(271, 'The Cure', NULL, NULL, NULL),
(278, 'Camel', NULL, NULL, NULL),
(281, 'Depeche mode', NULL, NULL, NULL),
(282, 'The Clash', NULL, NULL, NULL),
(283, '1', NULL, NULL, NULL),
(284, 'The Clash', NULL, NULL, NULL),
(285, 'Ultravox ', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `AUTOR_VINYLS_TBL`
--

CREATE TABLE `AUTOR_VINYLS_TBL` (
  `Autor_Id` int(11) NOT NULL,
  `Vinilo_Id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `AUTOR_VINYLS_TBL`
--

INSERT INTO `AUTOR_VINYLS_TBL` (`Autor_Id`, `Vinilo_Id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5),
(6, 6),
(7, 7),
(8, 8),
(9, 9),
(10, 10),
(11, 11),
(12, 12),
(13, 13),
(14, 14),
(15, 15),
(16, 16),
(17, 17),
(18, 18),
(19, 19),
(20, 20),
(21, 21),
(22, 22),
(23, 23),
(24, 24),
(25, 25),
(26, 26),
(27, 27),
(28, 28),
(29, 29),
(30, 30),
(31, 31),
(32, 32),
(33, 33),
(34, 34),
(35, 35),
(36, 36),
(37, 37),
(38, 38),
(39, 39),
(40, 40),
(41, 41),
(42, 42),
(43, 43),
(44, 44),
(45, 45),
(46, 46),
(47, 47),
(48, 48),
(49, 49),
(50, 50),
(51, 51),
(52, 52),
(53, 53),
(54, 54),
(55, 55),
(56, 56),
(57, 57),
(58, 58),
(59, 59),
(60, 60),
(61, 61),
(62, 62),
(63, 63),
(64, 64),
(65, 65),
(66, 66),
(67, 67),
(68, 68),
(69, 69),
(70, 70),
(71, 71),
(72, 72),
(73, 73),
(74, 74),
(75, 75),
(76, 76),
(77, 77),
(78, 78),
(79, 79),
(80, 80),
(81, 81),
(82, 82),
(83, 83),
(84, 84),
(85, 85),
(86, 86),
(87, 87),
(88, 88),
(89, 89),
(90, 90),
(91, 91),
(92, 92),
(93, 93),
(94, 94),
(95, 95),
(96, 96),
(97, 97),
(98, 98),
(99, 99),
(100, 100),
(101, 101),
(102, 102),
(103, 103),
(104, 104),
(105, 105),
(106, 106),
(107, 107),
(108, 108),
(109, 109),
(110, 110),
(111, 111),
(112, 112),
(113, 113),
(114, 114),
(115, 115),
(116, 116),
(117, 117),
(118, 118),
(119, 119),
(120, 120),
(121, 121),
(122, 122),
(123, 123),
(124, 124),
(125, 125),
(126, 126),
(127, 127),
(128, 128),
(129, 129),
(130, 130),
(131, 131),
(132, 132),
(133, 133),
(134, 134),
(135, 135),
(136, 136),
(137, 137),
(138, 138),
(139, 139),
(140, 140),
(141, 141),
(142, 142),
(143, 143),
(144, 144),
(145, 145),
(146, 146),
(147, 147),
(148, 148),
(149, 149),
(150, 150),
(151, 151),
(152, 152),
(153, 153),
(154, 154),
(155, 155),
(156, 156),
(157, 157),
(158, 158),
(159, 159),
(160, 160),
(161, 161),
(162, 162),
(163, 163),
(164, 164),
(165, 165),
(166, 166),
(167, 167),
(168, 168),
(169, 169),
(170, 170),
(171, 171),
(172, 172),
(173, 173),
(174, 174),
(175, 175),
(176, 176),
(177, 177),
(178, 178),
(179, 179),
(180, 180),
(181, 181),
(182, 182),
(183, 183),
(184, 184),
(185, 185),
(186, 186),
(187, 187),
(188, 188),
(189, 189),
(190, 190),
(191, 191),
(192, 192),
(193, 193),
(194, 194),
(195, 195),
(196, 196),
(197, 197),
(198, 198),
(199, 199),
(200, 200),
(201, 201),
(202, 202),
(203, 203),
(204, 204),
(205, 205),
(206, 206),
(207, 207),
(208, 208),
(209, 209),
(210, 210),
(211, 211),
(212, 212),
(213, 213),
(214, 214),
(215, 215),
(216, 216),
(217, 217),
(218, 218),
(219, 219),
(220, 220),
(221, 221),
(222, 222),
(223, 223),
(224, 224),
(225, 225),
(226, 226),
(227, 227),
(228, 228),
(229, 229),
(230, 230),
(231, 231),
(232, 232),
(233, 233),
(234, 234),
(235, 235),
(236, 236),
(237, 237),
(238, 238),
(239, 239),
(240, 240),
(241, 241),
(242, 242),
(243, 243),
(244, 244),
(245, 245),
(246, 246),
(247, 247),
(248, 248),
(249, 249),
(250, 250),
(251, 251),
(252, 252),
(253, 253),
(254, 254),
(255, 255),
(256, 256),
(257, 257),
(258, 258),
(259, 259),
(260, 260),
(261, 261),
(262, 262),
(263, 263),
(264, 264),
(265, 265),
(266, 266),
(267, 267),
(271, 271),
(278, 278),
(285, 279);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `CONDITION_TBL`
--

CREATE TABLE `CONDITION_TBL` (
  `Id` int(11) NOT NULL,
  `Condition_Name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `CONDITION_TBL`
--

INSERT INTO `CONDITION_TBL` (`Id`, `Condition_Name`) VALUES
(1, 'Mint'),
(2, 'Near Mint'),
(3, 'Excelent'),
(4, 'Very good Plus'),
(5, 'Very Good'),
(6, 'Good'),
(7, 'Poor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `EDITION_TBL`
--

CREATE TABLE `EDITION_TBL` (
  `Id` int(11) NOT NULL,
  `Edition_Name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `EDITION_TBL`
--

INSERT INTO `EDITION_TBL` (`Id`, `Edition_Name`) VALUES
(1, 'Original'),
(2, 'Reedición'),
(3, 'Edición Remasterizada'),
(4, 'Edición Deluxe'),
(5, 'Edición Limitada'),
(6, 'Edición Expandida'),
(7, 'Box Set / Caja Recopilatoria'),
(8, 'Picture Disc'),
(9, 'Edición en color'),
(10, 'Edición Acustica');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `FORMAT_TBL`
--

CREATE TABLE `FORMAT_TBL` (
  `Id` int(11) NOT NULL,
  `Format_Name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `FORMAT_TBL`
--

INSERT INTO `FORMAT_TBL` (`Id`, `Format_Name`) VALUES
(1, 'LP'),
(2, 'Single'),
(3, '2 LP'),
(4, 'Maxi Single'),
(5, '4 LP');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `GENRES_TBL`
--

CREATE TABLE `GENRES_TBL` (
  `Id` int(11) NOT NULL,
  `Genre_Name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `GENRES_TBL`
--

INSERT INTO `GENRES_TBL` (`Id`, `Genre_Name`) VALUES
(1, 'American Rock Band'),
(2, 'American Singer'),
(3, 'Black Metal'),
(4, 'British rock'),
(5, 'Cantautor Español'),
(6, 'Compositor Britanico'),
(7, 'Doo wop'),
(8, 'Garage Rock'),
(9, 'Ghotic Rock'),
(10, 'Hard Rock'),
(11, 'Heavy Metal'),
(12, 'Indie'),
(13, 'Jazz Rock'),
(14, 'Metal Alternativo'),
(15, 'Musica Clasica'),
(16, 'Música Electrónica'),
(17, 'New Wave'),
(18, 'Oi!'),
(19, 'Pop'),
(20, 'Pop Punk'),
(21, 'Pop/Rock Español'),
(22, 'Post Punk'),
(23, 'Power Pop'),
(24, 'Psychedelic Rock'),
(25, 'Psychobilly'),
(26, 'Punk'),
(27, 'Punk Rock'),
(28, 'Rhythm and Blues'),
(29, 'Rock'),
(30, 'Rock Alternativo'),
(31, 'Rock Progresivo'),
(32, 'Rock sinfónico'),
(33, 'Rock&Roll 50\'s'),
(34, 'Rockabilly'),
(35, 'Ska'),
(36, 'Soft Rock'),
(37, 'Soul'),
(38, 'Surf'),
(39, 'Synth Pop / New Wave');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `RECORD_LABEL_TBL`
--

CREATE TABLE `RECORD_LABEL_TBL` (
  `Id` int(11) NOT NULL,
  `Record_Label_Name` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `RECORD_LABEL_TBL`
--

INSERT INTO `RECORD_LABEL_TBL` (`Id`, `Record_Label_Name`) VALUES
(1, '4AD'),
(2, 'Ace Records'),
(3, 'Acme Records'),
(4, 'Arista'),
(5, 'Astan music AG/Luzern'),
(6, 'Atlantic'),
(7, 'Avispa Records'),
(8, 'BBC'),
(9, 'Beggars Banquet Records LTD'),
(10, 'Beserkley Records'),
(11, 'Big Beat'),
(12, 'Black tulip'),
(13, 'Bomp Records'),
(14, 'Buto Eskor'),
(15, 'Capitol Records'),
(16, 'Cascade Records'),
(17, 'Castle Communications'),
(18, 'CBS'),
(19, 'Chiswick Records'),
(20, 'Chrysalis'),
(21, 'Columbia'),
(22, 'Combat Records'),
(23, 'Demon Records'),
(24, 'DRO'),
(25, 'E.G. Records Ltd'),
(26, 'Electrobird'),
(27, 'EMI'),
(28, 'Enigma Record'),
(29, 'Epic'),
(30, 'EVA'),
(31, 'Factory Records'),
(32, 'Fiction Records'),
(33, 'Fonomusic'),
(34, 'Food Records'),
(35, 'Fontana'),
(36, 'Frontier Records'),
(37, 'G.G.G.B.H.'),
(38, 'Geffen Records'),
(39, 'GM Gramusic'),
(40, 'GMG Records'),
(41, 'Grabaciones Accidentales'),
(42, 'Grabaciones Interferencias'),
(43, 'Harvest'),
(44, 'Hi-fi Discos Electronica'),
(45, 'Hispavox'),
(46, 'Impulse'),
(47, 'Iberofon'),
(48, 'Island'),
(49, 'Jazz Life'),
(50, 'Jet Records'),
(51, 'Junk Records'),
(52, 'La Fabrica Magnetica'),
(53, 'La Rosa Records'),
(54, 'Lazy Records'),
(55, 'Liberty'),
(56, 'London Records'),
(57, 'Magnum House'),
(58, 'Metronome Musik GMBH'),
(59, 'Misfits Records'),
(60, 'Movieplay'),
(61, 'MCA Records'),
(62, 'Munster Records'),
(63, 'Music Manic Records'),
(64, 'Mute Records Ltd'),
(65, 'Nems'),
(66, 'No lo pone'),
(67, 'No Tomorrow Record'),
(68, 'Norton Records'),
(69, 'Philips'),
(70, 'Plan 9 Records'),
(71, 'Plangent Visions'),
(72, 'Polydor LTD'),
(73, 'Polygram Iberica'),
(74, 'Ppfront'),
(75, 'RCA'),
(76, 'Red Star Music Inc'),
(77, 'Rhino Records'),
(78, 'RSO Records'),
(79, 'Roadrunner Records'),
(80, 'Rockstar Records'),
(81, 'Rounder Records / DRO'),
(82, 'Rumble Records'),
(83, 'Running circle'),
(84, 'Sanctuary Records'),
(85, 'See for miles records ltd'),
(86, 'Serdisco'),
(87, 'Sirex'),
(88, 'Slash Records'),
(89, 'Speakers Corner Records'),
(90, 'spew noise'),
(91, 'SST Records'),
(92, 'Stiff Records'),
(93, 'Sun Records'),
(94, 'Taco Tunes ASCAP / Westminster Music LTD'),
(95, 'Tres Cipreses'),
(96, 'United Artist Records'),
(97, 'Vemsa'),
(98, 'Virgin'),
(99, 'Voxx Records'),
(100, 'White Label'),
(101, 'Wild Punk Records'),
(102, 'Zafiro'),
(103, 'Zyx Music'),
(104, 'Zoo Entertainment');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Roles_TBL`
--

CREATE TABLE `Roles_TBL` (
  `id` int(11) NOT NULL,
  `rol_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `Roles_TBL`
--

INSERT INTO `Roles_TBL` (`id`, `rol_name`) VALUES
(1, 'admin'),
(2, 'user');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Users_Roles`
--

CREATE TABLE `Users_Roles` (
  `User_Id` int(11) NOT NULL,
  `Role_Id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `Users_Roles`
--

INSERT INTO `Users_Roles` (`User_Id`, `Role_Id`) VALUES
(1, 1),
(2, 2),
(9, 2),
(10, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Users_TBL`
--

CREATE TABLE `Users_TBL` (
  `id` int(11) NOT NULL,
  `username` varchar(32) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(254) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `Users_TBL`
--

INSERT INTO `Users_TBL` (`id`, `username`, `password`, `email`) VALUES
(1, 'admin', '$2y$10$73G0DGZZFkHPUHMtL85VUOHLz3K7Fj9jrJrdH/XXd8epLW8ncpIVe', 'admin@vlexplosion.org'),
(2, 'Antonio', '$2y$10$i3JX.uYgwqhTDPVfCsgSrehmJiWOcN0zH/Yyw/3gCjrSdyE0NxVWG', 'antoniomartinezramirez@gmail.com'),
(9, 'rosa', '$2y$10$IyUQXISCECNxYPflcIXfxenozWThVij.Mbeda6RdFvLzFXbfxTX3q', 'rosa@gmail.con'),
(10, 'oleta.little@ourtimesupport.com', '$2y$10$aArPLURq3SfaHMQZRYZix.iXI8kbZlbwJhhh2zdlpKPHytlZuC8nm', 'oleta.little@ourtimesupport.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `VINYLS_TBL`
--

CREATE TABLE `VINYLS_TBL` (
  `Id` int(11) NOT NULL,
  `Title` varchar(65) NOT NULL,
  `Genres_Id` int(11) NOT NULL,
  `Format_Id` int(11) NOT NULL,
  `Condition_Id` int(11) NOT NULL,
  `Record_Label_Id` int(11) NOT NULL,
  `Producer` varchar(50) NOT NULL,
  `Release_date` year(4) NOT NULL,
  `Edition_Id` int(11) NOT NULL,
  `User_Id` int(11) NOT NULL,
  `Is_Favorite` tinyint(1) DEFAULT '0',
  `Is_Desired` tinyint(1) DEFAULT '0',
  `Image_Path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `VINYLS_TBL`
--

INSERT INTO `VINYLS_TBL` (`Id`, `Title`, `Genres_Id`, `Format_Id`, `Condition_Id`, `Record_Label_Id`, `Producer`, `Release_date`, `Edition_Id`, `User_Id`, `Is_Favorite`, `Is_Desired`, `Image_Path`) VALUES
(1, 'David Bowie', 1, 1, 1, 1, 'David Bowie', 1966, 1, 2, 0, 0, NULL),
(2, 'London Calling', 1, 1, 1, 1, 'The clash', 1979, 1, 2, 0, 0, NULL),
(3, 'Back on the Chain Gang', 1, 1, 1, 1, 'Pretenders', 1981, 1, 2, 0, 0, NULL),
(4, 'Animal Boy', 1, 1, 1, 1, 'Ramones', 1983, 1, 2, 0, 0, NULL),
(5, 'CarneVision', 27, 1, 1, 1, 'Tdk', 1986, 1, 2, 0, 0, NULL),
(6, 'Leave Home', 1, 1, 1, 1, '', 1977, 1, 2, 0, 0, NULL),
(7, 'Look Mom No Head!', 1, 1, 1, 1, 'The cramps', 1992, 1, 2, 0, 0, NULL),
(8, 'Never Say Die', 1, 1, 1, 1, '', 1978, 1, 2, 0, 0, NULL),
(9, 'Black Sabbath', 1, 1, 1, 1, '', 1970, 1, 2, 0, 0, NULL),
(10, 'Technical Ecstasy', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(11, 'Scapegoats', 1, 1, 1, 1, '', 1991, 1, 2, 0, 0, NULL),
(12, 'Heaven and Hell', 1, 1, 1, 1, '', 1996, 1, 2, 0, 0, NULL),
(13, 'Here come the snakes', 1, 1, 1, 1, '', 1988, 1, 2, 0, 0, NULL),
(14, 'It', 1, 1, 1, 1, '', 1986, 1, 2, 0, 0, NULL),
(15, 'Machine gun etiquete', 1, 1, 1, 1, '', 1979, 1, 2, 0, 0, NULL),
(16, 'Said Pop', 1, 1, 1, 1, '', 1988, 1, 2, 0, 0, NULL),
(17, 'Recuerdos del rock-ola', 1, 1, 1, 1, '', 1987, 1, 2, 0, 0, NULL),
(18, 'The Paisley UnderGround', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(19, 'The Rebeld Kind (A Collection Of Garage Rock And Psychodelia)', 1, 1, 1, 1, '', 1987, 1, 2, 0, 0, NULL),
(20, 'Curse', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(21, 'Flesh+Blood', 1, 1, 1, 1, '', 1980, 1, 2, 0, 0, NULL),
(22, 'Asia', 1, 1, 1, 1, '', 1982, 1, 2, 0, 0, NULL),
(23, 'Los Singles', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(24, 'Orchestral Manoeuvres in the Dark', 1, 1, 1, 1, '', 1980, 1, 2, 0, 0, NULL),
(25, 'Quartet', 1, 1, 1, 1, '', 1982, 1, 2, 0, 0, NULL),
(26, 'New York Dolls', 1, 1, 1, 1, '', 1973, 1, 2, 0, 0, NULL),
(27, 'Endless Party', 1, 1, 1, 1, '', 2000, 1, 2, 0, 0, NULL),
(28, 'Rage in Eden', 1, 1, 1, 1, '', 1981, 1, 2, 0, 0, NULL),
(29, 'Las más macabras de las vidas', 1, 1, 1, 1, 'butoskor', 1988, 1, 2, 0, 0, NULL),
(30, 'Architecture & Morality', 1, 1, 1, 1, '', 2009, 1, 2, 0, 0, NULL),
(31, 'Nada que entender', 1, 1, 1, 1, '', 1987, 1, 2, 0, 0, NULL),
(32, 'Mozarmania', 1, 1, 1, 1, '', 1991, 1, 2, 0, 0, NULL),
(33, 'Crush', 1, 1, 1, 1, '', 1985, 1, 2, 0, 0, NULL),
(34, 'Chasing the night / Howling at the moon', 1, 4, 1, 1, 'Tommy Ramone', 1984, 1, 2, 0, 0, NULL),
(35, 'Seventee Seconds', 1, 1, 1, 1, '', 1980, 1, 2, 0, 0, NULL),
(36, 'Systems of Romance', 1, 1, 1, 1, '', 1978, 1, 2, 0, 0, NULL),
(37, 'La calle del Ritmo', 1, 1, 1, 1, '', 1983, 1, 2, 0, 0, NULL),
(38, 'Three Into One ', 1, 1, 1, 1, '', 1980, 1, 2, 0, 0, NULL),
(39, 'Awakening', 1, 1, 1, 1, '', 1989, 1, 2, 0, 0, NULL),
(40, 'Organisation', 1, 1, 1, 1, '', 1981, 1, 2, 0, 0, NULL),
(41, 'Jerky Versions Of The Dream', 1, 1, 1, 1, '', 1983, 1, 2, 0, 0, NULL),
(42, 'Three Imaginary Boys', 1, 1, 1, 1, '', 1979, 1, 2, 0, 0, NULL),
(43, 'The Collection', 1, 1, 1, 1, 'Ultravox', 1984, 1, 2, 0, 0, NULL),
(44, 'Doolittle', 30, 1, 1, 1, 'Pixies', 1989, 1, 2, 0, 0, NULL),
(45, 'The Method to our Madness', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(46, 'Modern Lovers 88', 1, 1, 1, 1, '', 1987, 1, 2, 0, 0, NULL),
(47, 'Surfer Rosa', 30, 1, 1, 1, 'Pixies', 1988, 1, 2, 0, 0, NULL),
(48, 'Clasic Mania (Los exitos de los últimos siglos)', 1, 1, 1, 1, '', 2009, 1, 2, 0, 0, NULL),
(49, 'In the City', 1, 1, 1, 1, '', 1977, 1, 2, 0, 0, NULL),
(50, 'Atlantic Rhythm and Blues Vol 3 1955-1958', 1, 1, 1, 1, '', 1985, 1, 2, 0, 0, NULL),
(51, 'Atlantic Rhythm and Blues Vol 4 1958-1962', 1, 1, 1, 1, '', 1985, 1, 2, 0, 0, NULL),
(52, 'The Seeds of Love', 1, 1, 1, 1, '', 1989, 1, 2, 0, 0, NULL),
(53, 'Liquid Head In Tokyo', 1, 1, 1, 1, 'Ben Hillier', 1985, 1, 2, 0, 0, NULL),
(54, 'The Frenz Experiment', 1, 1, 1, 1, '', 1988, 1, 2, 0, 0, NULL),
(55, 'My Beah', 1, 1, 1, 1, '', 1980, 1, 2, 0, 0, NULL),
(56, 'Crank', 1, 1, 1, 1, '', 1994, 1, 2, 0, 0, NULL),
(57, 'Lysergic Ejaculations', 1, 1, 1, 1, '', 1994, 1, 2, 0, 0, NULL),
(58, 'Eye in the sky', 1, 1, 1, 1, 'Tommy Ramone', 1982, 1, 2, 0, 0, NULL),
(59, 'Album de Oro', 1, 1, 1, 1, '', 1981, 1, 2, 0, 0, NULL),
(60, 'The light At the end of the tunnel', 1, 1, 1, 1, '', 1987, 1, 2, 0, 0, NULL),
(61, 'Alto Disco', 29, 1, 1, 1, 'Airbag', 2008, 1, 2, 0, 0, NULL),
(62, 'Ensamble Cohetes', 1, 1, 1, 1, '', 2003, 1, 2, 0, 0, NULL),
(63, 'Cuando de come aquÃ­', 1, 1, 1, 1, 'Siniestro total ', 1982, 1, 2, 0, 0, NULL),
(64, 'A que venimos si no a caer', 1, 1, 1, 1, 'Ritchman', 2009, 1, 2, 0, 0, NULL),
(65, 'Pioneros del rock', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(66, 'Violent Femmes', 1, 1, 1, 1, '', 1983, 1, 2, 0, 0, NULL),
(67, 'Flick of the Switch', 1, 1, 1, 1, 'Angus Young', 1983, 1, 2, 0, 0, NULL),
(68, 'The Crack', 1, 1, 1, 1, '', 1979, 1, 2, 0, 0, NULL),
(69, 'Eddie & Hank', 1, 1, 1, 1, '', 1991, 1, 2, 0, 0, NULL),
(70, 'Black Light', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(71, 'Thinkin\' about you', 1, 1, 1, 1, '', 1989, 1, 2, 0, 0, NULL),
(72, 'The Early Years', 1, 1, 1, 1, '', 1986, 1, 2, 0, 0, NULL),
(73, 'Gravity Talks', 1, 1, 1, 1, '', 1983, 1, 2, 0, 0, NULL),
(74, 'Tranzophobia', 1, 1, 1, 1, '', 1989, 1, 2, 0, 0, NULL),
(75, 'Cuatro Rosas', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(76, 'Crime of the Century', 1, 1, 1, 1, '', 1974, 1, 2, 0, 0, NULL),
(77, 'Breakfast in America', 1, 1, 1, 1, '', 1979, 1, 2, 0, 0, NULL),
(78, 'Rockabilly Psychosis & The Garage Disease', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(79, 'Stray Cats', 1, 1, 1, 1, '', 1981, 1, 2, 0, 0, NULL),
(80, 'Rant N\' Rave With The Stray Cats', 1, 1, 1, 1, '', 1983, 1, 2, 0, 0, NULL),
(81, 'The man machine Die Mensch-Maschine', 1, 1, 1, 1, '', 1978, 1, 2, 0, 0, NULL),
(82, 'Wish Upon a Star With ', 1, 1, 1, 1, '', 1985, 1, 2, 0, 0, NULL),
(83, 'Amor Pirata', 20, 1, 1, 1, 'Desechables ', 1988, 1, 2, 0, 0, NULL),
(84, 'El caso de la Habana', 1, 1, 1, 1, '', 1992, 1, 2, 0, 0, NULL),
(85, 'Menos mal que nos queda Portugal', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(86, 'White Music', 1, 1, 1, 1, '', 1985, 1, 2, 0, 0, NULL),
(87, '7', 1, 1, 1, 1, '', 1981, 1, 2, 0, 0, NULL),
(88, 'My aim is true', 1, 1, 1, 1, '', 1982, 1, 2, 0, 0, NULL),
(89, 'Bocaaadiscooo', 1, 1, 1, 1, '', 1981, 1, 2, 0, 0, NULL),
(90, 'Laugh', 1, 1, 1, 1, '', 1992, 1, 2, 0, 0, NULL),
(91, 'Locura por la musica', 1, 1, 1, 1, '', 1982, 1, 2, 0, 0, NULL),
(92, 'If i Should Fall from grace with god', 1, 1, 1, 1, '', 1988, 1, 2, 0, 0, NULL),
(93, 'Writing on the wall', 1, 1, 1, 1, '', 2009, 1, 2, 0, 0, NULL),
(94, 'Lazy 86-88', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(95, 'Swing the Heartache. The BBC Sessions', 1, 1, 1, 1, '', 1989, 1, 2, 0, 0, NULL),
(96, 'Low Life', 1, 1, 1, 1, '', 1985, 1, 2, 0, 0, NULL),
(97, 'Ceremony', 1, 1, 1, 1, '', 1991, 1, 2, 0, 0, NULL),
(98, 'Telephome Thing', 1, 1, 1, 1, '', 1991, 1, 2, 0, 0, NULL),
(99, 'Jonathan Richman & the Moderns Lovers', 1, 1, 1, 1, '', 1976, 1, 2, 0, 0, NULL),
(100, 'Back In Your Life', 1, 1, 1, 1, '', 1979, 1, 2, 0, 0, NULL),
(101, 'Road To Ruin', 1, 1, 1, 1, 'Tommy Ramone', 1978, 1, 2, 0, 0, NULL),
(102, 'Rocket to Russia', 1, 1, 1, 1, 'Tommy Ramone', 1977, 1, 2, 0, 0, NULL),
(103, 'Pictures for pleasure', 1, 1, 1, 1, '', 1985, 1, 2, 0, 0, NULL),
(104, 'Road to Ruin (Radio Sampler)', 1, 4, 1, 1, 'Tommy Ramone', 1978, 1, 2, 0, 0, NULL),
(105, 'End Of The Century', 1, 1, 1, 1, '', 1980, 1, 2, 0, 0, NULL),
(106, 'Black Metal', 1, 1, 1, 1, '', 1985, 1, 2, 0, 0, NULL),
(107, 'Hopes And Fears', 1, 1, 1, 1, '', 2004, 1, 2, 0, 0, NULL),
(108, 'Caldo de pollo', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(109, 'Sabotage', 1, 1, 1, 1, 'Black Sabbath', 1975, 1, 2, 0, 0, NULL),
(110, 'Paranoic', 1, 1, 1, 1, 'black sabbath', 1970, 1, 2, 0, 0, NULL),
(111, 'Fly into trhe rainbow', 1, 1, 1, 1, '', 1974, 1, 2, 0, 0, NULL),
(112, 'Heavy Metal', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(113, 'Ian Anderson', 1, 1, 1, 1, '', 2005, 1, 2, 0, 0, NULL),
(114, 'Soul Teller', 1, 1, 1, 1, '', 1991, 1, 2, 0, 0, NULL),
(115, 'Tres hombres enfermos', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(116, 'Wonder Wonderful Wonderland', 1, 1, 1, 1, '', 1985, 1, 2, 0, 0, NULL),
(117, 'Los Tiki Phantoms regresan de la tumba', 1, 1, 1, 1, '', 2007, 1, 2, 0, 0, NULL),
(118, 'Surf, Drags & Rock \'n\' Roll', 1, 1, 1, 1, '', 2005, 1, 2, 0, 0, NULL),
(119, 'The best of OMD', 1, 1, 1, 1, '', 1988, 1, 2, 0, 0, NULL),
(120, 'Vol 4', 1, 1, 1, 1, 'black sabbath', 1972, 1, 2, 0, 0, NULL),
(121, 'Mr Big', 1, 1, 1, 1, '', 1977, 1, 2, 0, 0, NULL),
(122, 'The Early Years', 1, 1, 1, 1, '', 1978, 1, 2, 0, 0, NULL),
(123, 'Discovery', 1, 1, 1, 1, 'Elo', 1979, 1, 2, 0, 0, NULL),
(124, 'Mark I & MArk II', 10, 1, 1, 1, 'Deep Purple', 1974, 1, 2, 0, 0, NULL),
(125, 'Combat Rock', 1, 1, 1, 1, '', 1982, 1, 2, 0, 0, NULL),
(126, 'The meteors live', 1, 1, 1, 1, 'Ben Hillier', 1983, 1, 2, 0, 0, NULL),
(127, 'The Clash', 1, 1, 1, 1, 'The Clash', 1977, 1, 2, 0, 0, NULL),
(128, 'Press the Eject and Give Me the Tape', 1, 1, 1, 1, 'Bauhaus ', 1982, 1, 2, 0, 0, NULL),
(129, 'Secondhand Daylight', 1, 1, 1, 1, '', 1979, 1, 2, 0, 0, NULL),
(130, 'Cut the Crap', 1, 1, 1, 1, 'Ben Hillier', 1985, 1, 2, 0, 0, NULL),
(131, 'Electrobird', 1, 1, 1, 1, '', 1991, 1, 2, 0, 0, NULL),
(132, 'No Tomorrow', 1, 1, 1, 1, '', 1993, 1, 2, 0, 0, NULL),
(133, 'Its Low Beat Time', 1, 1, 1, 1, '', 1992, 1, 2, 0, 0, NULL),
(134, 'Somos los mejores', 1, 1, 1, 1, '', 1991, 1, 2, 0, 0, NULL),
(135, 'Electric Bird Digest', 1, 1, 1, 1, '', 1991, 1, 2, 0, 0, NULL),
(136, 'Shock Treatment', 1, 1, 1, 1, '', 1993, 1, 2, 0, 0, NULL),
(137, 'Parklife', 1, 1, 1, 1, '', 1994, 1, 2, 0, 0, NULL),
(138, 'Legacy of Brutality', 1, 1, 1, 1, '', 1985, 1, 2, 0, 0, NULL),
(139, 'Give \'Em Enough Rope', 1, 1, 1, 1, '', 1978, 1, 2, 0, 0, NULL),
(140, 'Vol. 3: (The Subliminal Verses)', 1, 1, 1, 1, '', 2004, 1, 2, 0, 0, NULL),
(141, 'Armed Forces', 1, 1, 1, 1, 'Elvis costello', 1979, 1, 2, 0, 0, NULL),
(142, 'Discovery', 1, 1, 1, 1, '', 1979, 1, 2, 0, 0, NULL),
(143, 'Aqualung', 1, 1, 1, 1, '', 1971, 1, 2, 0, 0, NULL),
(144, 'Master Of Reality', 1, 1, 1, 1, '', 1971, 1, 2, 0, 0, NULL),
(145, 'Made in Japan', 10, 1, 1, 1, 'Deep Purple', 1972, 1, 2, 0, 0, NULL),
(146, 'Lonesome Crow', 1, 1, 1, 1, '', 1972, 1, 2, 0, 0, NULL),
(147, 'Animal Magnetism', 1, 1, 1, 1, '', 1980, 1, 2, 0, 0, NULL),
(148, 'Hot Metal', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(149, 'Aqualung', 1, 1, 1, 1, '', 1971, 1, 2, 0, 0, NULL),
(150, 'Live at Last', 1, 1, 1, 1, '', 1985, 1, 2, 0, 0, NULL),
(151, 'Medina Azahara', 1, 1, 1, 1, '', 1979, 1, 2, 0, 0, NULL),
(152, 'Caravana española', 29, 1, 1, 1, 'Medina Azahara', 1986, 1, 2, 0, 0, NULL),
(153, '5º Aniversario', 1, 1, 1, 1, '', 1988, 1, 2, 0, 0, NULL),
(154, 'OIdyssey', 1, 1, 1, 1, '', 1988, 1, 2, 0, 0, NULL),
(155, 'Born Again', 1, 1, 1, 1, 'Robin Black', 1983, 1, 2, 0, 0, NULL),
(156, 'El Dorado', 1, 1, 1, 1, '', 1975, 1, 2, 0, 0, NULL),
(157, 'Los Romeos', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(158, 'Bad Moon Rising (The meteors play cover versions)', 1, 1, 1, 1, '', 1991, 1, 2, 0, 0, NULL),
(159, 'Lollipop', 1, 1, 1, 1, 'Nikis', 1988, 1, 2, 0, 0, NULL),
(160, 'Just the way we like it', 1, 1, 1, 1, '', 1999, 1, 2, 0, 0, NULL),
(161, 'To fast to live too young to die', 1, 1, 1, 1, 'Ben Hillier', 1989, 1, 2, 0, 0, NULL),
(162, 'Ole elo', 1, 1, 1, 1, '', 1976, 1, 2, 0, 0, NULL),
(163, 'Chicago X', 1, 1, 1, 1, '', 1976, 1, 2, 0, 0, NULL),
(164, 'Elo\'s Greatest Hits', 1, 1, 1, 1, '', 1979, 1, 2, 0, 0, NULL),
(165, 'The Very Best of', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(166, 'On the Third Day', 1, 1, 1, 1, '', 1973, 1, 2, 0, 0, NULL),
(167, 'Balance of Power', 1, 1, 1, 1, '', 1986, 1, 2, 0, 0, NULL),
(168, 'A new world record', 1, 1, 1, 1, '', 1976, 1, 2, 0, 0, NULL),
(169, 'Electric Light Orchestra', 1, 1, 1, 1, '', 1972, 1, 2, 0, 0, NULL),
(170, 'Time', 1, 1, 1, 1, '', 1981, 1, 2, 0, 0, NULL),
(171, 'Electric Light Orchestra II', 1, 1, 1, 1, '', 1973, 1, 2, 0, 0, NULL),
(172, 'Bird School Work Death', 1, 1, 1, 1, '', 1987, 1, 2, 0, 0, NULL),
(173, 'L.A. Explosion!', 1, 1, 1, 1, 'The Last', 1979, 1, 2, 0, 0, NULL),
(174, 'Sex Drugs & Rock & Roll', 1, 1, 1, 1, '', 1987, 1, 2, 0, 0, NULL),
(175, 'El Patio', 1, 1, 1, 1, '', 1975, 1, 2, 0, 0, NULL),
(176, 'Business as Usual', 1, 1, 1, 1, '', 1981, 1, 2, 0, 0, NULL),
(177, 'Inferno (The 00455E4 Continues)', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(178, 'River', 1, 1, 1, 1, '', 1982, 1, 2, 0, 0, NULL),
(179, 'Teenage Lobotomy', 1, 1, 1, 1, '', 2009, 1, 2, 0, 0, NULL),
(180, 'Rock \'n\' Roll Paradise', 1, 1, 1, 1, '', 2003, 1, 2, 0, 0, NULL),
(181, 'Comunion', 1, 1, 1, 1, '', 1995, 1, 2, 0, 0, NULL),
(182, 'Out of the Blue', 1, 1, 1, 1, '', 1977, 1, 2, 0, 0, NULL),
(183, 'The black saint & the sinner lady', 1, 1, 1, 1, '', 1963, 1, 2, 0, 0, NULL),
(184, 'Ellington\'55', 1, 1, 1, 1, '', 1955, 1, 2, 0, 0, NULL),
(185, 'Marines a pleno sol', 1, 1, 1, 1, '', 1985, 1, 2, 0, 0, NULL),
(186, 'Eat to the Beat', 17, 1, 1, 1, 'Mike Chapman', 1979, 1, 2, 0, 0, NULL),
(187, 'Stormbringer', 10, 1, 1, 1, 'Deep purple', 1974, 1, 2, 0, 0, NULL),
(188, 'Face the music', 1, 1, 1, 1, '', 1977, 1, 2, 0, 0, NULL),
(189, 'A Love supreme', 1, 1, 1, 1, '', 1964, 1, 2, 0, 0, NULL),
(190, 'Pyramid', 6, 1, 1, 1, 'Alan parson', 1978, 1, 2, 0, 0, NULL),
(191, 'Autoamerican', 17, 1, 1, 1, 'Mike Chapman', 1980, 1, 2, 0, 0, NULL),
(192, 'Parallel Lines', 17, 1, 1, 1, 'Mike Chapman', 1978, 1, 2, 0, 0, NULL),
(193, 'Submarines a Pleno sol', 1, 1, 1, 1, '', 1987, 1, 2, 0, 0, NULL),
(194, 'The turn of a friendly card', 1, 1, 1, 1, 'La tuya', 1979, 1, 2, 0, 0, NULL),
(195, 'Rarities', 1, 1, 1, 1, 'Johnny Kidd & the Pirates', 1983, 1, 2, 0, 0, NULL),
(196, 'El ritmo del garage', 1, 1, 1, 1, '', 1983, 1, 2, 0, 0, NULL),
(197, 'Loco Live', 1, 1, 1, 1, 'Tommy Ramone', 1991, 1, 2, 0, 0, NULL),
(198, 'Land of the dead', 1, 1, 1, 1, '', 2009, 1, 2, 0, 0, NULL),
(199, 'Don\'t Worry About Me', 1, 1, 1, 1, 'Tommy Ramone', 2002, 1, 2, 0, 0, NULL),
(200, 'Adios Amigos', 1, 1, 1, 1, '', 1995, 1, 2, 0, 0, NULL),
(201, 'Half way to the sanity', 1, 1, 1, 1, '', 1987, 1, 2, 0, 0, NULL),
(202, 'Merry Christmas', 1, 1, 1, 1, '', 1987, 1, 2, 0, 0, NULL),
(203, 'Teenage Lobotomy', 1, 1, 1, 1, '', 2009, 1, 2, 0, 0, NULL),
(204, 'Kind of Blue', 13, 1, 1, 1, 'Ben Hillier', 2001, 1, 2, 0, 0, NULL),
(205, 'Ramonesmania', 1, 1, 1, 1, '', 1988, 1, 2, 0, 0, NULL),
(206, 'It\'s Alive', 1, 1, 1, 1, '', 1977, 1, 2, 0, 0, NULL),
(207, 'Ramones Play their 60\'s favorites', 1, 1, 1, 1, '', 1993, 1, 2, 0, 0, NULL),
(208, 'Joey is a Punk', 1, 1, 1, 1, '', 1976, 1, 2, 0, 0, NULL),
(209, 'Rock\'n\' Roll high School', 1, 1, 1, 1, '', 1979, 1, 2, 0, 0, NULL),
(210, 'Live, January 7, 1978 At the Palladium, NYC Part II', 1, 1, 1, 1, '', 2003, 1, 2, 0, 0, NULL),
(211, 'Brain Drain', 1, 1, 1, 1, '', 1989, 1, 2, 0, 0, NULL),
(212, 'Greatest Hits', 1, 1, 1, 1, '', 1986, 1, 2, 0, 0, NULL),
(213, 'Too Tough To Die', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(214, 'Pleasant Dreams', 1, 1, 1, 1, '', 1981, 1, 2, 0, 0, NULL),
(215, 'Pleasant Dreams', 1, 1, 1, 1, '', 1981, 1, 2, 0, 0, NULL),
(216, 'Subterranean jungle', 1, 1, 1, 1, '', 1983, 1, 2, 0, 0, NULL),
(217, 'Original 1965 demo version', 1, 1, 1, 1, '', 1975, 1, 2, 0, 0, NULL),
(218, 'Ramones', 1, 1, 1, 1, '', 1976, 1, 2, 0, 0, NULL),
(219, 'Road To ruin', 1, 1, 1, 1, '', 1978, 1, 2, 0, 0, NULL),
(220, 'Rocket to russia', 1, 1, 1, 1, '', 1977, 1, 2, 0, 0, NULL),
(221, 'Gabba Gabba Hey(17 rare and unreleased tracks)', 1, 1, 1, 1, '', 2002, 1, 2, 0, 0, NULL),
(222, 'Bad chopper', 1, 1, 1, 1, 'Ben Hillier', 2007, 1, 2, 0, 0, NULL),
(223, 'Mondo Bizarro', 1, 1, 1, 1, '', 1992, 1, 2, 0, 0, NULL),
(224, 'Atlantic Rhythm & Blues Vol 5', 28, 1, 1, 1, 'Varios ', 1985, 1, 2, 0, 0, NULL),
(225, 'Hillbilly Wolf (missing Links col.1)', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(226, 'Rare Rockers from small 1950\'s Labels vol.1', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(227, 'Road Runner', 1, 1, 1, 1, '', 1989, 1, 2, 0, 0, NULL),
(228, 'Rockabilly Fever', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(229, 'Sweet & Wild', 1, 1, 1, 1, '', 1992, 1, 2, 0, 0, NULL),
(230, '20 great unknown soul classics', 1, 1, 1, 1, '', 1992, 1, 2, 0, 0, NULL),
(231, 'Habia una vez el circo', 1, 1, 1, 1, '', 1973, 1, 2, 0, 0, NULL),
(232, 'Surf, Drags & Rock \'n\' Roll', 1, 1, 1, 1, '', 2005, 1, 2, 0, 0, NULL),
(233, 'Living in the past', 1, 1, 1, 1, '', 1972, 1, 2, 0, 0, NULL),
(234, 'Lo mejor de', 1, 1, 1, 1, '', 1974, 1, 2, 0, 0, NULL),
(235, 'The best of Sam & Dave', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(236, '60 Golden Memories', 1, 1, 1, 1, '', 1983, 1, 2, 0, 0, NULL),
(237, 'Rock-it-to mars', 1, 1, 1, 1, '', 1980, 1, 2, 0, 0, NULL),
(238, 'Rama Lama Ding Dong', 1, 1, 1, 1, '', 1979, 1, 2, 0, 0, NULL),
(239, 'Tennessee Bop', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(240, 'in concert', 1, 1, 1, 1, '', 1973, 1, 2, 0, 0, NULL),
(241, 'Paradise Island', 1, 1, 1, 1, '', 1979, 1, 2, 0, 0, NULL),
(242, 'No reason to cry', 1, 1, 1, 1, '', 1976, 1, 2, 0, 0, NULL),
(243, 'Blackless', 1, 1, 1, 1, '', 1978, 1, 2, 0, 0, NULL),
(244, 'Grand Funk', 1, 1, 1, 1, '', 1972, 1, 2, 0, 0, NULL),
(245, 'The best of', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(246, 'La historia de Jazz', 1, 1, 1, 1, '', 1974, 1, 2, 0, 0, NULL),
(247, 'All These things', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(248, 'The madness Invasion', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(249, 'Me llaman blues', 1, 1, 1, 1, '', 1991, 1, 2, 0, 0, NULL),
(250, 'The Chess Story From R&B To Soul', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(251, 'The Chess Story From BluesTo DooWop', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(252, 'Golden Hits', 1, 1, 1, 1, '', 1968, 1, 2, 0, 0, NULL),
(253, 'DooWop Uptempo vol 2', 1, 1, 1, 1, '', 1989, 1, 2, 0, 0, NULL),
(254, 'rocakbillity', 1, 1, 1, 1, '', 1988, 1, 2, 0, 0, NULL),
(255, 'Sex Machine', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(256, 'All the Best', 1, 1, 1, 1, '', 1984, 1, 2, 0, 0, NULL),
(257, '30 Years Of Number Ones, Vol. 3', 29, 1, 1, 1, 'Varios ', 1989, 1, 2, 0, 0, NULL),
(258, 'Hey Bo Diddley!', 1, 1, 1, 1, '', 1989, 1, 2, 0, 0, NULL),
(259, 'The hit singles Collection', 1, 1, 1, 1, '', 1985, 1, 2, 0, 0, NULL),
(260, 'Rare Tracks', 1, 1, 1, 1, '', 1989, 1, 2, 0, 0, NULL),
(261, 'Volume One', 1, 1, 1, 1, '', 1989, 1, 2, 0, 0, NULL),
(262, 'Grandes exitos', 1, 1, 1, 1, '', 1991, 1, 2, 0, 0, NULL),
(263, 'Los Tornados', 1, 1, 1, 1, '', 1986, 1, 2, 0, 0, NULL),
(264, 'Tren de medianoche', 1, 1, 1, 1, '', 1987, 1, 2, 0, 0, NULL),
(265, 'The Collector Series', 1, 1, 1, 1, '', 1988, 1, 2, 0, 0, NULL),
(266, 'Volume 3', 1, 1, 1, 1, 'Invation', 1988, 1, 2, 0, 0, NULL),
(267, 'Violator ', 1, 1, 1, 1, '', 1990, 1, 2, 0, 0, NULL),
(271, 'Pornography ', 9, 1, 2, 32, 'Phil Thornalley and The Cure', 1982, 2, 2, 0, 0, NULL),
(278, 'Moonmadnrss', 31, 1, 3, 18, 'Rhett Davis y Camel', 2024, 1, 2, 0, 0, NULL),
(279, 'Viena', 17, 1, 2, 20, 'Ultravox and  Conny Plank', 1980, 2, 2, 0, 0, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `AUTHORS_TBL`
--
ALTER TABLE `AUTHORS_TBL`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `AUTOR_VINYLS_TBL`
--
ALTER TABLE `AUTOR_VINYLS_TBL`
  ADD PRIMARY KEY (`Autor_Id`,`Vinilo_Id`),
  ADD KEY `fk_vinilo` (`Vinilo_Id`);

--
-- Indices de la tabla `CONDITION_TBL`
--
ALTER TABLE `CONDITION_TBL`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `EDITION_TBL`
--
ALTER TABLE `EDITION_TBL`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `FORMAT_TBL`
--
ALTER TABLE `FORMAT_TBL`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `GENRES_TBL`
--
ALTER TABLE `GENRES_TBL`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `RECORD_LABEL_TBL`
--
ALTER TABLE `RECORD_LABEL_TBL`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `Roles_TBL`
--
ALTER TABLE `Roles_TBL`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rol_name` (`rol_name`);

--
-- Indices de la tabla `Users_Roles`
--
ALTER TABLE `Users_Roles`
  ADD PRIMARY KEY (`User_Id`,`Role_Id`),
  ADD KEY `fk_role_id` (`Role_Id`);

--
-- Indices de la tabla `Users_TBL`
--
ALTER TABLE `Users_TBL`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD UNIQUE KEY `unique_username` (`username`);

--
-- Indices de la tabla `VINYLS_TBL`
--
ALTER TABLE `VINYLS_TBL`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `fk_genero` (`Genres_Id`),
  ADD KEY `fk_formato` (`Format_Id`),
  ADD KEY `fk_condition` (`Condition_Id`),
  ADD KEY `fk_record_label` (`Record_Label_Id`),
  ADD KEY `fk_edition` (`Edition_Id`),
  ADD KEY `fk_user` (`User_Id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `AUTHORS_TBL`
--
ALTER TABLE `AUTHORS_TBL`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=286;

--
-- AUTO_INCREMENT de la tabla `CONDITION_TBL`
--
ALTER TABLE `CONDITION_TBL`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `EDITION_TBL`
--
ALTER TABLE `EDITION_TBL`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `FORMAT_TBL`
--
ALTER TABLE `FORMAT_TBL`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `GENRES_TBL`
--
ALTER TABLE `GENRES_TBL`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `RECORD_LABEL_TBL`
--
ALTER TABLE `RECORD_LABEL_TBL`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT de la tabla `Roles_TBL`
--
ALTER TABLE `Roles_TBL`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `Users_TBL`
--
ALTER TABLE `Users_TBL`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `VINYLS_TBL`
--
ALTER TABLE `VINYLS_TBL`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=280;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `AUTOR_VINYLS_TBL`
--
ALTER TABLE `AUTOR_VINYLS_TBL`
  ADD CONSTRAINT `fk_autor` FOREIGN KEY (`Autor_Id`) REFERENCES `AUTHORS_TBL` (`Id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vinilo` FOREIGN KEY (`Vinilo_Id`) REFERENCES `VINYLS_TBL` (`Id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `Users_Roles`
--
ALTER TABLE `Users_Roles`
  ADD CONSTRAINT `fk_role_id` FOREIGN KEY (`Role_Id`) REFERENCES `Roles_TBL` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`User_Id`) REFERENCES `Users_TBL` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `VINYLS_TBL`
--
ALTER TABLE `VINYLS_TBL`
  ADD CONSTRAINT `fk_condition` FOREIGN KEY (`Condition_Id`) REFERENCES `CONDITION_TBL` (`Id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_edition` FOREIGN KEY (`Edition_Id`) REFERENCES `EDITION_TBL` (`Id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_formato` FOREIGN KEY (`Format_Id`) REFERENCES `FORMAT_TBL` (`Id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_genero` FOREIGN KEY (`Genres_Id`) REFERENCES `GENRES_TBL` (`Id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_record_label` FOREIGN KEY (`Record_Label_Id`) REFERENCES `RECORD_LABEL_TBL` (`Id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`User_Id`) REFERENCES `Users_TBL` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
