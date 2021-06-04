SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dnsstats`
--

-- --------------------------------------------------------

--
-- Table structure for table `ltsv`
--

CREATE TABLE `ltsv` (
  `time` int(11) NOT NULL,
  `host` varchar(50) NOT NULL,
  `message` varchar(100) NOT NULL,
  `type` varchar(20) NOT NULL,
  `return_value` varchar(20) NOT NULL,
  `cached` tinyint(1) NOT NULL,
  `duration` smallint(5) NOT NULL,
  `server` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ltsv`
--
ALTER TABLE `ltsv`
  ADD KEY `time` (`time`),
  ADD KEY `host` (`host`),
  ADD KEY `message` (`message`),
  ADD KEY `type` (`type`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
