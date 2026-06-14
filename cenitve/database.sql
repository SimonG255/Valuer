-- ============================================
-- Aplikacija za upravljanje cenitev nepremicnin
-- SQL Skripta za kreiranje baze podatkov
-- ============================================

CREATE DATABASE IF NOT EXISTS cenitve_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cenitve_db;

CREATE TABLE IF NOT EXISTS uporabniki (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ime         VARCHAR(100) NOT NULL,
    priimek     VARCHAR(100) NOT NULL,
    email       VARCHAR(255) NOT NULL UNIQUE,
    geslo       VARCHAR(255) NOT NULL,
    ustvarjeno  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cenitve (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uporabnik_id     INT UNSIGNED NOT NULL,
    naziv_narocnika  VARCHAR(255) NOT NULL,
    naslov_narocnika VARCHAR(500) NOT NULL,
    namen_cenitve    ENUM(
        'zavarovano_posojanje',
        'sodni_postopek',
        'stecajni_postopek',
        'racunovodsko_porocanje',
        'davcni_postopek',
        'poslovna_odlocitev'
    ) NOT NULL,
    podlaga_vrednosti ENUM(
        'trzna_vrednost',
        'likvidacijska_vrednost',
        'trzna_najemnina',
        'pravicna_vrednost'
    ) NOT NULL,
    premisa_vrednosti ENUM(
        'sedanja_uporaba',
        'najgospodarnejsa_uporaba',
        'redna_likvidacija'
    ) NOT NULL,
    prvi_ogled   DATETIME NOT NULL,
    ustvarjeno   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    posodobljeno DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cenitve_uporabnik
        FOREIGN KEY (uporabnik_id) REFERENCES uporabniki(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_cenitve_uporabnik ON cenitve (uporabnik_id);
CREATE INDEX idx_cenitve_namen     ON cenitve (namen_cenitve);
CREATE INDEX idx_cenitve_datum     ON cenitve (prvi_ogled);
