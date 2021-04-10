CREATE TABLE `spielplan_custom` (
  `Team` varchar(100) NOT NULL,
  `SpielTyp` varchar(30) NOT NULL,
  `Spielstatus` varchar(50) DEFAULT NULL,
  `Bezeichnung` varchar(100) DEFAULT NULL,
  `Spielnummer` int(11) NOT NULL,
  `TagKurz` varchar(2) DEFAULT NULL,
  `Spieldatum` date NOT NULL,
  `Spielzeit` time NOT NULL,
  `TeamnameA` varchar(50) DEFAULT NULL,
  `VereinsnummerA` int(10) DEFAULT NULL,
  `TeamLigaA` varchar(20) DEFAULT NULL,
  `TeamnameB` varchar(50) DEFAULT NULL,
  `VereinsnummerB` int(10) DEFAULT NULL,
  `TeamLigaB` varchar(20) DEFAULT NULL,
  `Spielort` varchar(50) NOT NULL,
  `Sportanlage` varchar(50) DEFAULT NULL,
  `Ort` varchar(50) DEFAULT NULL,
  `Wettspielfeld` varchar(255) DEFAULT NULL,
  `bemerkungen` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `spielplan_custom`
  ADD PRIMARY KEY (`Spielnummer`),
  ADD KEY `Team` (`Team`),
  ADD KEY `SpielTyp` (`SpielTyp`),
  ADD KEY `Spieldatum` (`Spieldatum`);

ALTER TABLE `spielplan_custom`
  MODIFY `Spielnummer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;
COMMIT;