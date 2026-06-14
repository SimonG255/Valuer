# Cenitve Nepremičnin – Navodila za namestitev

Spletna aplikacija za upravljanje cenitev nepremičnin, zgrajena s PHP, MySQL, HTML/CSS in JavaScript.

## Tehnološki sklad

- **PHP 8.x** (PDO razširitvitev za MySQL)
- **MySQL 8.x** (ali MariaDB 10.x)
- **Vanilla JavaScript** (ES2020, Fetch API za AJAX)
- **HTML5 / CSS3** (brez zunanjih CSS ogrodij)

## Struktura projekta

```
cenitve/
├── index.php               # Začetna stran
├── login.php               # Prijava
├── register.php            # Registracija
├── cenitve.php             # Upravljanje cenitev (zaščitena stran)
├── logout.php              # Odjava
├── database.sql            # SQL skripta za ustvarjanje baze
├── includes/
│   ├── config.php          # Konfiguracija, DB povezava, pomožne funkcije
│   ├── header.php          # Skupna glava
│   └── footer.php          # Skupna noga
├── assets/
│   ├── css/style.css       # Celoten slog aplikacije
│   └── js/app.js           # JavaScript (modali, AJAX, validacija, toasti)
└── api/
    └── delete.php          # AJAX endpoint za brisanje cenitev
```

## Namestitev

### 1. Ustvarite bazo podatkov

```bash
mysql -u root -p < database.sql
```

Ali uvozite `database.sql` prek phpMyAdmin.

### 2. Konfigurirajte podatkovno bazo

Odprite `includes/config.php` in prilagodite:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cenitve_db');
define('DB_USER', 'vaš_uporabnik');
define('DB_PASS', 'vaše_geslo');
```

### 3. Namestite aplikacijo

Kopirajte mapo `cenitve/` v vaš spletni strežnik (npr. `/var/www/html/cenitve` ali `htdocs/cenitve`).

### 4. Dostop

Odprite brskalnik in pojdite na:
```
http://localhost/cenitve/
```

## Funkcionalnosti

### Registracija in prijava
- Registracija z imenom, priimkom, emailom in geslom (bcrypt hash)
- Prijava z emailom in geslom
- Zaščita sej (session_regenerate_id po prijavi)

### Upravljanje cenitev
- **Dodajanje** – modalni obrazec s polnim naborom polj
- **Urejanje** – prednapolnitev modalnega obrazca s obstoječimi podatki
- **Brisanje** – AJAX brez osvežitve strani, animirano odstranjevanje vrstice
- **Pregled** – tabela vseh cenitev z značkami (badges) za namen

### Polja cenitve
| Polje | Tip | Možnosti |
|-------|-----|----------|
| Naziv naročnika | Besedilo | ročni vnos |
| Naslov naročnika | Besedilo | ročni vnos |
| Namen cenitve | Izbira | 6 možnosti |
| Podlaga vrednosti | Izbira | 4 možnosti |
| Premisa vrednosti | Izbira | 3 možnosti |
| Prvi ogled | Datum/ura | datetime-local |

### Varnost
- Prepared statements (PDO) – zaščita pred SQL injekcijo
- `htmlspecialchars()` – zaščita pred XSS
- Gesla hashirana z bcrypt (`PASSWORD_BCRYPT`)
- Avtorizacija: vsak uporabnik vidi/ureja samo svoje cenitve
- CSRF: obrazci so zaščiteni z PHP sejami

## Zahteve

- PHP >= 8.0 z razširitvami: `pdo`, `pdo_mysql`
- MySQL >= 8.0 ali MariaDB >= 10.4
- Spletni strežnik (Apache, Nginx, ali PHP vgrajeni strežnik za razvoj)

## Razvoj

```bash
# Zagon PHP razvojnega strežnika
php -S localhost:8000 -t /pot/do/cenitve
```

Nato odprite: http://localhost:8000
