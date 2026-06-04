# Perpustakaan Novel Dinamis - Docker Setup

## Prasyarat
- Docker & Docker Compose sudah terinstall
- Port 8080, 8081, 8082, 3306 tersedia

## Cara Menjalankan

### 1. Build dan Start Services
```bash
docker-compose up --build -d
```

### 2. Akses Aplikasi
- **Web Dinamis (PHP):** http://localhost:8080
- **PHPMyAdmin:** http://localhost:8081
- **Web Statis (Nginx):** http://localhost:8082

### 3. Setup Admin Account
Akses reset-password.php untuk membuat akun admin:
```
http://localhost:8080/reset-password.php
```

Gunakan kredensial default:
- **Admin:** username: `admin` | password: `admin123`
- **Member:** username: `member` | password: `member123`

### 4. Database
Database otomatis di-import dari `web-dinamis/database.sql` saat MySQL container pertama kali dijalankan.

Database credentials:
- Host: `db` (from inside containers) atau `localhost` (from host machine)
- Database: `perpustakaan_novel`
- User: `root`
- Password: (kosong)

## Troubleshooting

### Koneksi Database Gagal
1. Pastikan MySQL container sudah fully started (tunggu ~10 detik)
2. Check logs: `docker-compose logs db`
3. Verify database import: `docker-compose exec db mysql -uroot perpustakaan_novel -e "SHOW TABLES;"`

### Port Conflict
Ubah port mapping di `docker-compose.yml` jika port sudah terpakai:
```yaml
services:
  web:
    ports:
      - "8090:80"  # Ubah 8090 ke port yang tersedia
```

## File Penting
- `web-dinamis/Dockerfile` - PHP Apache configuration
- `web-dinamis/database.sql` - Database schema & seeding
- `web-dinamis/config/db.php` - Database connection
- `docker-compose.yml` - Container orchestration

## Berhenti Services
```bash
docker-compose down
```

Untuk hapus semua volume dan data:
```bash
docker-compose down -v
```
