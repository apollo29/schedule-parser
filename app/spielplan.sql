CREATE TABLE `spielplan` (
  `Team` varchar(100) NOT NULL,
  `SpielTyp` varchar(30) NOT NULL,
  `Spielstatus` varchar(50) NOT NULL,
  `Bezeichnung` varchar(100) NOT NULL,
  `Spielnummer` int(10) NOT NULL,
  `TagKurz` varchar(2) NOT NULL,
  `Spieldatum` date NOT NULL,
  `Spielzeit` time NOT NULL,
  `TeamnameA` varchar(50) NOT NULL,
  `VereinsnummerA` int(10) NOT NULL,
  `TeamLigaA` varchar(20) NOT NULL,
  `TeamnameB` varchar(50) NOT NULL,
  `VereinsnummerB` int(10) NOT NULL,
  `TeamLigaB` varchar(20) NOT NULL,
  `Spielort` varchar(50) NOT NULL,
  `Sportanlage` varchar(50) NOT NULL,
  `Ort` varchar(50) NOT NULL,
  `Wettspielfeld` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `spielplan`
  ADD PRIMARY KEY (`Spieldatum`),
  ADD KEY `Team` (`Team`),
  ADD KEY `SpielTyp` (`SpielTyp`),
  ADD KEY `VereinsnummerA` (`VereinsnummerA`),
  ADD KEY `VereinsnummerB` (`VereinsnummerB`);
COMMIT;
