-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Wrz 10, 2025 at 02:26 PM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nowy_dziennik_lekcyjny`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `frekwencja`
--

CREATE TABLE `frekwencja` (
  `id` int(11) NOT NULL,
  `rok_szkolny_id` int(11) NOT NULL,
  `uczen_id` int(11) NOT NULL,
  `data_zajec` date NOT NULL,
  `nr_lekcji` tinyint(4) NOT NULL,
  `przedmiot_id` int(11) NOT NULL,
  `nauczyciel_id` int(11) NOT NULL,
  `realizacja_id` int(11) DEFAULT NULL,
  `status` enum('obecny','nieobecny','spozniony','usprawiedliwiony','zwolniony') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `grupy`
--

CREATE TABLE `grupy` (
  `id` int(11) NOT NULL,
  `rok_szkolny_id` int(11) NOT NULL,
  `nazwa_grupy` varchar(100) NOT NULL,
  `skrot_grupy` varchar(20) NOT NULL,
  `opis` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `kategorie_ocen`
--

CREATE TABLE `kategorie_ocen` (
  `id` int(11) NOT NULL,
  `nazwa_kategorii` varchar(100) NOT NULL,
  `waga` decimal(3,2) NOT NULL DEFAULT 1.00,
  `kolor` varchar(20) DEFAULT '#ffffff',
  `nauczyciel_id` int(11) DEFAULT NULL,
  `typ_oceny` enum('biezaca','proponowana','klasyfikacyjna') NOT NULL DEFAULT 'biezaca'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `klasy`
--

CREATE TABLE `klasy` (
  `id` int(11) NOT NULL,
  `rok_szkolny_id` int(11) NOT NULL,
  `nazwa_klasy` varchar(50) NOT NULL COMMENT 'Np. Klasa 1A',
  `skrot_klasy` varchar(10) NOT NULL COMMENT 'Np. 1A',
  `wychowawca_id` int(11) DEFAULT NULL COMMENT 'ID nauczyciela z tabeli uzytkownicy'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `lata_szkolne`
--

CREATE TABLE `lata_szkolne` (
  `id` int(11) NOT NULL,
  `nazwa` varchar(9) NOT NULL COMMENT 'Format RRRR/RRRR',
  `aktywny` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = tak, 0 = nie'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `nauczanie`
--

CREATE TABLE `nauczanie` (
  `id` int(11) NOT NULL,
  `rok_szkolny_id` int(11) NOT NULL,
  `nauczyciel_id` int(11) NOT NULL,
  `przedmiot_id` int(11) NOT NULL,
  `klasa_id` int(11) DEFAULT NULL,
  `grupa_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `nauczyciele_info`
--

CREATE TABLE `nauczyciele_info` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `oceny`
--

CREATE TABLE `oceny` (
  `id` int(11) NOT NULL,
  `rok_szkolny_id` int(11) NOT NULL,
  `uczen_id` int(11) NOT NULL,
  `przedmiot_id` int(11) NOT NULL,
  `nauczyciel_id` int(11) NOT NULL,
  `kategoria_id` int(11) NOT NULL,
  `ocena` decimal(4,2) NOT NULL,
  `data_wystawienia` date NOT NULL,
  `semestr` tinyint(1) NOT NULL,
  `komentarz` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `plan_lekcji`
--

CREATE TABLE `plan_lekcji` (
  `id` int(11) NOT NULL,
  `rok_szkolny_id` int(11) NOT NULL,
  `dzien_tygodnia` tinyint(4) NOT NULL COMMENT '1=Pon, 2=Wt, ..., 5=Pt',
  `nr_lekcji` tinyint(4) NOT NULL,
  `nauczanie_id` int(11) NOT NULL COMMENT 'Powiązanie z tabelą nauczanie (nauczyciel, przedmiot, klasa/grupa)',
  `data_od` date NOT NULL,
  `data_do` date NOT NULL,
  `sala` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `przedmioty`
--

CREATE TABLE `przedmioty` (
  `id` int(11) NOT NULL,
  `rok_szkolny_id` int(11) NOT NULL,
  `nazwa_przedmiotu` varchar(100) NOT NULL,
  `skrot` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `przypisania_grup`
--

CREATE TABLE `przypisania_grup` (
  `id` int(11) NOT NULL,
  `grupa_id` int(11) NOT NULL,
  `uczen_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `realizacja_tematu`
--

CREATE TABLE `realizacja_tematu` (
  `id` int(11) NOT NULL,
  `rok_szkolny_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `nr_lekcji` tinyint(4) NOT NULL,
  `nauczanie_id` int(11) NOT NULL,
  `temat` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `semestry`
--

CREATE TABLE `semestry` (
  `id` int(11) NOT NULL,
  `rok_szkolny_id` int(11) NOT NULL,
  `numer_semestru` tinyint(1) NOT NULL,
  `data_od` date NOT NULL,
  `data_do` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `uczniowie_info`
--

CREATE TABLE `uczniowie_info` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `nr_dziennika` int(11) DEFAULT NULL,
  `klasa_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `uzytkownicy`
--

CREATE TABLE `uzytkownicy` (
  `id` int(11) NOT NULL,
  `login` varchar(50) NOT NULL,
  `haslo` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `imie` varchar(50) NOT NULL,
  `nazwisko` varchar(100) NOT NULL,
  `rola` enum('uczen','nauczyciel','dyrektor','admin') NOT NULL,
  `aktywny` tinyint(1) NOT NULL DEFAULT 1,
  `data_utworzenia` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `zastepstwa`
--

CREATE TABLE `zastepstwa` (
  `id` int(11) NOT NULL,
  `rok_szkolny_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `nr_lekcji` tinyint(4) NOT NULL,
  `oryginalne_nauczanie_id` int(11) NOT NULL COMMENT 'Który przydział jest zastępowany',
  `nowe_nauczanie_id` int(11) DEFAULT NULL COMMENT 'Jaki jest nowy przydział (jeśli to zastępstwo)',
  `nowa_sala` varchar(20) DEFAULT NULL,
  `typ` enum('zastepstwo','odwolane','przesuniete') NOT NULL,
  `komentarz` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `frekwencja`
--
ALTER TABLE `frekwencja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rok_szkolny_id` (`rok_szkolny_id`),
  ADD KEY `uczen_id` (`uczen_id`),
  ADD KEY `przedmiot_id` (`przedmiot_id`),
  ADD KEY `nauczyciel_id` (`nauczyciel_id`),
  ADD KEY `realizacja_id` (`realizacja_id`);

--
-- Indeksy dla tabeli `grupy`
--
ALTER TABLE `grupy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rok_szkolny_id` (`rok_szkolny_id`);

--
-- Indeksy dla tabeli `kategorie_ocen`
--
ALTER TABLE `kategorie_ocen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nauczyciel_id` (`nauczyciel_id`);

--
-- Indeksy dla tabeli `klasy`
--
ALTER TABLE `klasy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rok_szkolny_id` (`rok_szkolny_id`);

--
-- Indeksy dla tabeli `lata_szkolne`
--
ALTER TABLE `lata_szkolne`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nazwa` (`nazwa`);

--
-- Indeksy dla tabeli `nauczanie`
--
ALTER TABLE `nauczanie`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rok_szkolny_id` (`rok_szkolny_id`),
  ADD KEY `nauczyciel_id` (`nauczyciel_id`),
  ADD KEY `przedmiot_id` (`przedmiot_id`),
  ADD KEY `klasa_id` (`klasa_id`),
  ADD KEY `grupa_id` (`grupa_id`);

--
-- Indeksy dla tabeli `nauczyciele_info`
--
ALTER TABLE `nauczyciele_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uzytkownik_id` (`uzytkownik_id`);

--
-- Indeksy dla tabeli `oceny`
--
ALTER TABLE `oceny`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rok_szkolny_id` (`rok_szkolny_id`),
  ADD KEY `uczen_id` (`uczen_id`),
  ADD KEY `przedmiot_id` (`przedmiot_id`),
  ADD KEY `nauczyciel_id` (`nauczyciel_id`),
  ADD KEY `kategoria_id` (`kategoria_id`);

--
-- Indeksy dla tabeli `plan_lekcji`
--
ALTER TABLE `plan_lekcji`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rok_szkolny_id` (`rok_szkolny_id`),
  ADD KEY `nauczanie_id` (`nauczanie_id`);

--
-- Indeksy dla tabeli `przedmioty`
--
ALTER TABLE `przedmioty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nazwa_przedmiotu` (`nazwa_przedmiotu`),
  ADD KEY `rok_szkolny_id` (`rok_szkolny_id`);

--
-- Indeksy dla tabeli `przypisania_grup`
--
ALTER TABLE `przypisania_grup`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unikalne_przypisanie` (`grupa_id`,`uczen_id`),
  ADD KEY `uczen_id` (`uczen_id`);

--
-- Indeksy dla tabeli `realizacja_tematu`
--
ALTER TABLE `realizacja_tematu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rok_szkolny_id` (`rok_szkolny_id`),
  ADD KEY `nauczanie_id` (`nauczanie_id`);

--
-- Indeksy dla tabeli `semestry`
--
ALTER TABLE `semestry`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rok_semestr` (`rok_szkolny_id`,`numer_semestru`);

--
-- Indeksy dla tabeli `uczniowie_info`
--
ALTER TABLE `uczniowie_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uzytkownik_id` (`uzytkownik_id`);

--
-- Indeksy dla tabeli `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- Indeksy dla tabeli `zastepstwa`
--
ALTER TABLE `zastepstwa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rok_szkolny_id` (`rok_szkolny_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `frekwencja`
--
ALTER TABLE `frekwencja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grupy`
--
ALTER TABLE `grupy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kategorie_ocen`
--
ALTER TABLE `kategorie_ocen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `klasy`
--
ALTER TABLE `klasy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lata_szkolne`
--
ALTER TABLE `lata_szkolne`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nauczanie`
--
ALTER TABLE `nauczanie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nauczyciele_info`
--
ALTER TABLE `nauczyciele_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `oceny`
--
ALTER TABLE `oceny`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan_lekcji`
--
ALTER TABLE `plan_lekcji`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `przedmioty`
--
ALTER TABLE `przedmioty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `przypisania_grup`
--
ALTER TABLE `przypisania_grup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `realizacja_tematu`
--
ALTER TABLE `realizacja_tematu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `semestry`
--
ALTER TABLE `semestry`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uczniowie_info`
--
ALTER TABLE `uczniowie_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zastepstwa`
--
ALTER TABLE `zastepstwa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `frekwencja`
--
ALTER TABLE `frekwencja`
  ADD CONSTRAINT `frekwencja_ibfk_1` FOREIGN KEY (`rok_szkolny_id`) REFERENCES `lata_szkolne` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `frekwencja_ibfk_2` FOREIGN KEY (`uczen_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `frekwencja_ibfk_3` FOREIGN KEY (`przedmiot_id`) REFERENCES `przedmioty` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `frekwencja_ibfk_4` FOREIGN KEY (`nauczyciel_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `frekwencja_ibfk_5` FOREIGN KEY (`realizacja_id`) REFERENCES `realizacja_tematu` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grupy`
--
ALTER TABLE `grupy`
  ADD CONSTRAINT `grupy_ibfk_1` FOREIGN KEY (`rok_szkolny_id`) REFERENCES `lata_szkolne` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kategorie_ocen`
--
ALTER TABLE `kategorie_ocen`
  ADD CONSTRAINT `kategorie_ocen_ibfk_1` FOREIGN KEY (`nauczyciel_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `klasy`
--
ALTER TABLE `klasy`
  ADD CONSTRAINT `klasy_ibfk_1` FOREIGN KEY (`rok_szkolny_id`) REFERENCES `lata_szkolne` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nauczanie`
--
ALTER TABLE `nauczanie`
  ADD CONSTRAINT `nauczanie_ibfk_1` FOREIGN KEY (`rok_szkolny_id`) REFERENCES `lata_szkolne` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nauczanie_ibfk_2` FOREIGN KEY (`nauczyciel_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nauczanie_ibfk_3` FOREIGN KEY (`przedmiot_id`) REFERENCES `przedmioty` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nauczanie_ibfk_4` FOREIGN KEY (`klasa_id`) REFERENCES `klasy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nauczanie_ibfk_5` FOREIGN KEY (`grupa_id`) REFERENCES `grupy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nauczyciele_info`
--
ALTER TABLE `nauczyciele_info`
  ADD CONSTRAINT `nauczyciele_info_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `oceny`
--
ALTER TABLE `oceny`
  ADD CONSTRAINT `oceny_ibfk_1` FOREIGN KEY (`rok_szkolny_id`) REFERENCES `lata_szkolne` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `oceny_ibfk_2` FOREIGN KEY (`uczen_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `oceny_ibfk_3` FOREIGN KEY (`przedmiot_id`) REFERENCES `przedmioty` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `oceny_ibfk_4` FOREIGN KEY (`nauczyciel_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `oceny_ibfk_5` FOREIGN KEY (`kategoria_id`) REFERENCES `kategorie_ocen` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `plan_lekcji`
--
ALTER TABLE `plan_lekcji`
  ADD CONSTRAINT `plan_lekcji_ibfk_1` FOREIGN KEY (`rok_szkolny_id`) REFERENCES `lata_szkolne` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plan_lekcji_ibfk_2` FOREIGN KEY (`nauczanie_id`) REFERENCES `nauczanie` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `przedmioty`
--
ALTER TABLE `przedmioty`
  ADD CONSTRAINT `przedmioty_ibfk_1` FOREIGN KEY (`rok_szkolny_id`) REFERENCES `lata_szkolne` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `przypisania_grup`
--
ALTER TABLE `przypisania_grup`
  ADD CONSTRAINT `przypisania_grup_ibfk_1` FOREIGN KEY (`grupa_id`) REFERENCES `grupy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `przypisania_grup_ibfk_2` FOREIGN KEY (`uczen_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `realizacja_tematu`
--
ALTER TABLE `realizacja_tematu`
  ADD CONSTRAINT `realizacja_tematu_ibfk_1` FOREIGN KEY (`rok_szkolny_id`) REFERENCES `lata_szkolne` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `realizacja_tematu_ibfk_2` FOREIGN KEY (`nauczanie_id`) REFERENCES `nauczanie` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `semestry`
--
ALTER TABLE `semestry`
  ADD CONSTRAINT `semestry_ibfk_1` FOREIGN KEY (`rok_szkolny_id`) REFERENCES `lata_szkolne` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `uczniowie_info`
--
ALTER TABLE `uczniowie_info`
  ADD CONSTRAINT `uczniowie_info_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `zastepstwa`
--
ALTER TABLE `zastepstwa`
  ADD CONSTRAINT `zastepstwa_ibfk_1` FOREIGN KEY (`rok_szkolny_id`) REFERENCES `lata_szkolne` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
