# App-build

Blueprint awal untuk aplikasi **Laravel WhatsApp Blast & Nomor Checker** berbasis **Green API**.

## 1) Tujuan Aplikasi

Aplikasi ini dipakai untuk:

1. Mengirim pesan blast WhatsApp saat:
   - terjadi kendala/incident,
   - ada informasi maintenance terjadwal.
2. Mengelola blast per **product** (misalnya SMS Bulk, SMS Banking, Chatbot, dll) dengan jumlah grup WA yang bisa bertambah dari waktu ke waktu.
3. Melakukan pengecekan nomor dari **multiple database** (multi koneksi) sebelum pengiriman.

## 2) Cakupan Fitur Utama

### A. Master Data Product & Grup

- CRUD Product (contoh: `SMS Bulk`, `SMS Banking`, `Chatbot`).
- CRUD Grup WA per product.
- Setiap product dapat memiliki banyak grup (relasi 1:N).
- Mendukung penambahan product baru dan grup baru tanpa ubah kode inti.

### B. Template & Kategori Blast

- Template pesan untuk kategori:
  - `INCIDENT`
  - `MAINTENANCE`
- Template bisa pakai placeholder:
  - `{product_name}`
  - `{start_time}`
  - `{end_time}`
  - `{impact}`
  - `{ticket_id}`

### C. Blast Engine (Green API)

- Integrasi ke Green API endpoint kirim pesan ke grup.
- Queue-based send (Laravel Queue + Redis) agar tidak blocking.
- Pengaturan throttling/rate-limit untuk hindari spam atau limit API.
- Retry otomatis saat gagal (exponential backoff).
- Logging detail request/response per grup.

### D. Number Checker (Multi Database)

- Konfigurasi banyak sumber DB (misalnya core banking, CRM, billing).
- Validasi nomor berdasarkan aturan E.164 (normalisasi 62xxxxxxxxxx).
- Cek duplikasi nomor lintas database.
- Simpan hasil validasi dalam tabel audit.

### E. Monitoring & Audit

- Dashboard ringkas:
  - total blast,
  - success/failed,
  - durasi proses.
- Riwayat blast per product & kategori.
- Export CSV untuk laporan.

## 3) Arsitektur Teknis (Laravel)

### Layer Utama

1. **Web/API Layer**: Controller untuk trigger blast, CRUD master data.
2. **Service Layer**:
   - `BlastService`
   - `GreenApiService`
   - `NumberCheckerService`
3. **Queue Worker Layer**:
   - job `SendBlastToGroupJob`
4. **Persistence Layer**:
   - MySQL/PostgreSQL untuk data aplikasi
   - multiple DB connections untuk number checker

### Komponen yang Disarankan

- Laravel 11+
- Laravel Queue (Redis)
- Laravel Horizon (opsional, strongly recommended)
- Laravel Scheduler untuk job terjadwal maintenance reminder

## 4) Desain Database Awal

### Tabel `products`

- `id`
- `name` (unique)
- `is_active`
- timestamps

### Tabel `wa_groups`

- `id`
- `product_id` (FK ke products)
- `group_name`
- `chat_id` (ID grup dari Green API)
- `is_active`
- timestamps

### Tabel `blast_templates`

- `id`
- `category` (`INCIDENT`, `MAINTENANCE`)
- `title`
- `body`
- `is_active`
- timestamps

### Tabel `blast_jobs`

- `id`
- `product_id`
- `category`
- `payload_json`
- `status` (`QUEUED`, `RUNNING`, `DONE`, `FAILED`, `PARTIAL`)
- `requested_by`
- timestamps

### Tabel `blast_job_details`

- `id`
- `blast_job_id`
- `wa_group_id`
- `status`
- `response_code`
- `response_body`
- `sent_at`
- timestamps

### Tabel `number_check_logs`

- `id`
- `source_db`
- `raw_number`
- `normalized_number`
- `is_valid`
- `exists_in_source`
- `checked_at`

## 5) Alur Blast (High Level)

1. User pilih kategori (`INCIDENT` / `MAINTENANCE`).
2. User pilih product target (misal `SMS Bulk`).
3. Sistem ambil seluruh grup aktif product tsb.
4. Sistem render template dengan payload dinamis.
5. Sistem buat `blast_jobs` + detail per grup.
6. Worker queue kirim pesan ke Green API per grup.
7. Sistem simpan status sukses/gagal per grup.
8. Dashboard menampilkan rekap real-time.

## 6) Skalabilitas untuk Product/Group Bertambah

- Tidak hardcode jumlah grup (20/15/21) di kode.
- Semua pemetaan product-group berbasis tabel DB.
- Tambah product/grup cukup lewat menu master data.
- Jika volume besar, pakai batching dan beberapa queue worker.

## 7) Integrasi Green API (Konsep)

Simpan credential di `.env`:

```env
GREEN_API_INSTANCE_ID=xxxxx
GREEN_API_TOKEN=xxxxx
GREEN_API_BASE_URL=https://api.green-api.com
```

Contoh service method (konseptual):

- `sendGroupMessage(string $chatId, string $message): array`
- endpoint mengikuti dokumen resmi Green API untuk group send message.

> Catatan: sesuaikan endpoint final dengan dokumentasi Green API yang dipakai pada environment Anda.

## 8) Keamanan & Governance

- Role-based access (Admin, Operator, Viewer).
- Approval opsional untuk kategori `INCIDENT` prioritas rendah.
- Simpan audit trail siapa kirim apa, kapan, ke grup mana.
- Masking data sensitif di log.

## 9) Roadmap Implementasi Bertahap

### Phase 1 (MVP)

- Master product + grup.
- Template incident/maintenance.
- Manual trigger blast per product.
- Log sukses/gagal.

### Phase 2

- Number checker multi DB.
- Dashboard KPI & export report.
- Retry policy + alert jika fail rate tinggi.

### Phase 3

- Approval workflow.
- Blast terjadwal (scheduler).
- Multi channel fallback (Email/Telegram) jika WA gagal.

## 10) Contoh Struktur Folder Laravel

```text
app/
  Services/
    BlastService.php
    GreenApiService.php
    NumberCheckerService.php
  Jobs/
    SendBlastToGroupJob.php
  Models/
    Product.php
    WaGroup.php
    BlastJob.php
    BlastJobDetail.php
```

## 11) Next Step yang Bisa Langsung Dikerjakan

1. Inisialisasi proyek Laravel + auth.
2. Buat migration sesuai desain tabel di atas.
3. Buat seeder product awal:
   - SMS Bulk
   - SMS Banking
   - Chatbot
4. Buat CRUD product/grup.
5. Implement `GreenApiService` + queue job.
6. Tambahkan halaman trigger blast & monitoring.
7. Lanjut modul number checker multi DB.

---

Jika Anda mau, langkah berikutnya saya bisa bantu buatkan:

- draft migration SQL,
- struktur model & relation Eloquent,
- skeleton service + job Laravel,
- dan endpoint API pertama untuk trigger blast.
<<<<<<< HEAD

## 12) Progress Implementasi (Update)

Skeleton kode awal sudah ditambahkan untuk mempercepat development:

- Service:
  - `App\Services\GreenApiService`
  - `App\Services\BlastService`
  - `App\Services\NumberCheckerService`
- Job queue:
  - `App\Jobs\SendBlastToGroupJob`
- Model Eloquent:
  - `Product`, `WaGroup`, `BlastTemplate`, `BlastJob`, `BlastJobDetail`, `NumberCheckLog`
- Migration tabel utama:
  - products, wa_groups, blast_templates, blast_jobs, blast_job_details, number_check_logs
- Endpoint API awal:
  - `POST /api/blast/send`

> Skeleton ini adalah baseline dan masih perlu disesuaikan dengan proyek Laravel final (auth, policies, retry strategy detail, observability, dan endpoint Green API sesuai akun Anda).
=======
>>>>>>> main
