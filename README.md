# Dziennik Lekcyjny w PHP

Projekt edukacyjny w pełni funkcjonalnego dziennika elektronicznego, tworzony od podstaw w PHP z wykorzystaniem bazy danych MySQL.

## Status Projektu
Projekt jest w trakcie aktywnego rozwoju.

## Główne Funkcjonalności
* **System Użytkowników i Ról:** Podział na uczniów, nauczycieli, dyrektorów i administratorów.
* **Bezpieczne Logowanie:** Uwierzytelnianie oparte na sesjach z hashowaniem haseł.
* **Ustawienia Globalne:**
    * Zarządzanie latami szkolnymi.
    * Definiowanie ram czasowych dla semestrów.
* **Zarządzanie Strukturą Szkoły:**
    * Panel do zarządzania klasami w danym roku szkolnym.
    * Panel do zarządzania przedmiotami.
    * Panel do zarządzania grupami (wewnątrzklasowymi i międzyklasowymi).
    * Panel do zarządzania przydziałami (kto, kogo, czego uczy).
* **Ocenianie:**
    * Zaawansowany arkusz ocen dla nauczycieli i administratorów.
    * Seryjne wstawianie ocen z automatycznym wykrywaniem semestru.
    * Edycja i usuwanie ocen z weryfikacją uprawnień.
    * System kategorii ocen (systemowe i własne nauczyciela).
* **Plan Lekcji i Frekwencja:**
    * Moduł do tworzenia i zarządzania planem lekcji.
    * Moduł do sprawdzania obecności i wpisywania tematów lekcji.

## Technologie
* **Backend:** PHP
* **Baza Danych:** MySQL (MariaDB)
* **Frontend:** HTML, CSS, JavaScript (bez frameworków)

## Instalacja i Uruchomienie
1.  Sklonuj repozytorium na swój komputer.
2.  Zaimportuj schemat bazy danych (plik `.sql`) do swojego serwera MySQL (np. przez phpMyAdmin).
3.  W głównym folderze projektu stwórz plik `connect.php` na podstawie pliku `connect.php.example` (plik-wzór, który należy dodać) i uzupełnij go danymi dostępowymi do Twojej bazy danych. Plik `connect.php` jest ignorowany przez Git ze względów bezpieczeństwa.
4.  Umieść cały folder projektu w środowisku serwera WWW (np. w folderze `htdocs` w XAMPP).
5.  Uruchom projekt w przeglądarce.