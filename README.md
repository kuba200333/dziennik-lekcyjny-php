# Dziennik Lekcyjny w PHP

Projekt edukacyjny w pełni funkcjonalnego dziennika elektronicznego, tworzony od podstaw w PHP z wykorzystaniem bazy danych MySQL.

## Podgląd Aplikacji

**Ekran Logowania**
![Ekran Logowania](https://github.com/kuba200333/dziennik-lekcyjny-php/blob/main/_screenshots/ekran_logowania.png?raw=true)

**Główny Panel Admina**
![Panel Admina](https://github.com/kuba200333/dziennik-lekcyjny-php/blob/main/_screenshots/glowny_ekran.png?raw=true)

**Podgląd Ocen**
![Podgląd Ocen](https://github.com/kuba200333/dziennik-lekcyjny-php/blob/main/_screenshots/podglad_ocen.png?raw=true)

## Status Projektu
Projekt jest w trakcie aktywnego rozwoju.

## Technologie i Wymagania
* **Backend:** PHP (wersja 7.4 lub nowsza)
* **Baza Danych:** MySQL (MariaDB)
* **Frontend:** HTML, CSS, JavaScript (bez frameworków)

## Główne Funkcjonalności
* **System Użytkowników i Ról:** Podział na uczniów, nauczycieli, dyrektorów i administratorów.
* **Bezpieczne Logowanie:** Uwierzytelnianie oparte na sesjach z hashowaniem haseł (`password_hash`).
* **Ustawienia Globalne:** Zarządzanie latami szkolnymi i ramami czasowymi semestrów.
* **Zarządzanie Strukturą Szkoły:** Panele do zarządzania klasami, grupami, przedmiotami i przydziałami nauczycieli.
* **Ocenianie:** Zaawansowany, interaktywny arkusz ocen z obliczaniem średnich ważonych oraz seryjnym dodawaniem i edycją ocen.
* **Plan Lekcji i Frekwencja:** Moduły do tworzenia planu i realizacji programu nauczania.

## Instalacja i Uruchomienie
1.  Sklonuj repozytorium na swój komputer.
2.  Zaimportuj schemat bazy danych (plik `.sql`) do swojego serwera MySQL (np. przez phpMyAdmin).
3.  W głównym folderze projektu stwórz plik `connect.php` na podstawie pliku `connect.php.example`.
4.  Uzupełnij `connect.php` swoimi danymi dostępowymi do bazy danych.
5.  Umieść cały folder projektu w środowisku serwera WWW (np. w folderze `htdocs` w XAMPP).
6.  Uruchom projekt w przeglądarce.

---
*Uwaga: Nazwy plików i zmiennych w kodzie są w języku polskim, co jest świadomą decyzją podjętą na potrzeby projektu.*