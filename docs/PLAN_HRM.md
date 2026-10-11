# PLAN: Aplikasi Penggajian & Kasbon

> Status: **Draft v1**, hasil brainstorming kebutuhan bersama klien. Bagian bertanda **[Usulan]** belum diputuskan klien dan boleh direvisi.

## 1. Ringkasan

Aplikasi web internal untuk mengelola data karyawan, penggajian bulanan, pemotongan (dengan history), tanda terima/slip gaji, dan kasbon. Alur kerjanya: **admin** mengelola dan menginput data, **direktur** melakukan approval dan melihat laporan.

## 2. Keputusan yang Sudah Disepakati

| Area | Keputusan |
|---|---|
| Bentuk aplikasi | Web app responsif (bisa dibuka di HP) |
| Skala | Kurang dari 20 karyawan |
| Pengguna login | Admin dan direktur saja (karyawan **tidak** login) |
| Stack | Laravel + MySQL (Blade/Livewire) |
| Periode gaji | Bulanan |
| Alur gaji | Admin input & hitung, direktur approve, lalu dibayar |
| Revisi gaji | Setelah approved masih bisa diubah admin, tetapi **wajib approval ulang direktur** |
| Data karyawan | Selengkap sistem HR pada umumnya, termasuk upload CV/dokumen |
| Tunjangan & potongan | Komponen bebas, admin bisa menambah dan mengustomisasi jenisnya sendiri |
| Kasbon | Admin input, direktur approve |
| Pelunasan kasbon | Potong gaji sekali lunas, cicilan per bulan, atau bayar manual di luar gaji |
| Slip gaji / tanda terima | PDF, dikirim via WhatsApp/email |
| History pemotongan | Selengkap mungkin (audit trail) |
| Hak akses | Admin kelola semua data; direktur lihat laporan dan approve |
| Laporan | Dashboard/rekap lengkap (jumlah karyawan, total kasbon bulan ini, total penggajian bulan ini, dll), export PDF |
| Hosting | VPS sendiri, backup database harian otomatis |
| Strategi rilis | Bertahap: Fase 1 data karyawan + gaji, Fase 2 kasbon + laporan |

## 3. Di Luar Scope (Saat Ini)

- Absensi dan lembur (belum diminta klien; desain gaji dibuat agar mudah ditambah nanti)
- Portal / login karyawan
- Aplikasi mobile native
- Perhitungan otomatis BPJS dan PPh 21 (sementara lewat komponen custom)

## 4. Peran & Hak Akses

| Fitur | Admin | Direktur |
|---|---|---|
| Kelola data karyawan | ✅ | 👁 lihat |
| Kelola master komponen gaji | ✅ | 👁 lihat |
| Input dan hitung gaji | ✅ | 👁 lihat |
| Approve / tolak gaji | ❌ | ✅ |
| Input kasbon dan pembayarannya | ✅ | 👁 lihat |
| Approve / tolak kasbon | ❌ | ✅ |
| Cetak dan kirim slip | ✅ | 👁 lihat |
| Laporan dan dashboard | ✅ | ✅ |
| Audit log | ✅ | 👁 lihat |

## 5. Modul & Fitur

### 5.1 Autentikasi & Pengguna
- Login email/username + password, dua role: `admin` dan `direktur`
- Session timeout dan proteksi brute force (rate limit)
- **[Usulan]** Opsi 2FA untuk direktur

### 5.2 Data Karyawan
- Identitas: NIK internal, nama, jenis kelamin, tempat/tanggal lahir, alamat, no HP, email
- Kepegawaian: **tanggal join**, jabatan, departemen/divisi, status (tetap/kontrak/magang), tanggal keluar dan alasan (jika resign)
- Legal & keuangan: no KTP, NPWP, no BPJS, bank dan no rekening, status pernikahan/tanggungan
- Kontak darurat
- Dokumen: upload CV, KTP, kontrak, ijazah, dsb. (per kategori, disimpan di storage private)
- Riwayat jabatan dan gaji (history perubahan)
- Karyawan non-aktif tidak dihapus, hanya diberi status non-aktif

### 5.3 Master Komponen Gaji (Tunjangan & Potongan)
- Admin dapat menambah/mengubah komponen sendiri
- Atribut komponen: nama, tipe (`tunjangan` atau `potongan`), cara hitung (nominal tetap / persentase dari gaji pokok / input manual tiap periode), aktif/nonaktif
- Komponen dipasang per karyawan dengan nilai default masing-masing

### 5.4 Penggajian Bulanan
- Buat periode gaji (bulan/tahun), sistem menarik semua karyawan aktif
- Per karyawan: gaji pokok + tunjangan − potongan = gaji bersih
- Admin bisa menambah/mengubah baris komponen per periode (misalnya bonus atau potongan sekali)
- Preview sebelum diajukan
- Status alur: `draft → diajukan → disetujui → dibayar`, dan `ditolak` (kembali ke admin dengan catatan)
- Revisi setelah disetujui: `disetujui → revisi → diajukan ulang → disetujui`, tiap perubahan tercatat
- Pencatatan pembayaran: tanggal bayar, metode (transfer/tunai), catatan
- Data gaji per periode disimpan sebagai **snapshot** agar tidak berubah walau master komponen berubah

### 5.5 Pemotongan & History
- Setiap potongan tersimpan dengan: jenis, nominal, alasan, penginput, approver, waktu
- Audit trail lengkap: nilai sebelum dan sesudah tiap perubahan
- Halaman history per karyawan dan per periode

### 5.6 Tanda Terima / Slip Gaji
- Generate PDF per karyawan (kop/logo perusahaan, rincian tunjangan dan potongan, gaji bersih)
- **[Usulan]** Nomor slip otomatis (format `SLP/YYYY/MM/NNN`)
- Kirim via email (Laravel Mail) dan WhatsApp
- **[Usulan]** WhatsApp fase awal via tombol click-to-chat (`wa.me`) yang membawa link/lampiran manual; gateway WA otomatis bisa menyusul jika klien mau
- Riwayat pengiriman slip (kapan dan lewat apa)

### 5.7 Kasbon (Fase 2)
- Admin menginput kasbon: karyawan, tanggal, nominal, alasan, skema pelunasan
- Direktur approve/tolak
- Skema pelunasan: potong gaji sekali lunas, cicilan per bulan (tenor diatur), atau bayar manual di luar gaji
- Cicilan otomatis muncul sebagai baris potongan saat periode gaji dibuat
- Riwayat pembayaran dan sisa saldo per kasbon
- Status: `diajukan → disetujui → berjalan → lunas` (atau `ditolak`)
- **[Usulan]** Kasbon yang belum lunas saat karyawan resign ditandai agar admin bisa menindaklanjuti (potong gaji terakhir atau tagih manual)

### 5.8 Laporan & Dashboard (Fase 2)
- Dashboard direktur: jumlah karyawan aktif, total penggajian bulan ini, total kasbon bulan ini, total kasbon berjalan, status periode gaji
- Laporan: rekap gaji per periode, rekap kasbon dan pemotongan per karyawan, riwayat gaji per karyawan
- Export PDF

### 5.9 Audit Log
- Mencatat login, perubahan data gaji, potongan, kasbon, dan approval (siapa, kapan, apa yang berubah)

## 6. Rancangan Data (Draft ERD) **[Usulan]**

| Tabel | Isi utama |
|---|---|
| `users` | akun admin/direktur, role |
| `employees` | data karyawan, tanggal join, status, tanggal keluar |
| `employee_documents` | file per karyawan, kategori |
| `employee_histories` | riwayat jabatan dan gaji |
| `departments` | master departemen |
| `salary_components` | master tunjangan/potongan |
| `employee_components` | komponen dan nilai default per karyawan |
| `payroll_periods` | periode, status, versi revisi |
| `payroll_items` | gaji per karyawan per periode (snapshot) |
| `payroll_item_lines` | baris tunjangan/potongan per item |
| `approvals` | riwayat approval (target polimorfik: gaji atau kasbon), catatan |
| `payments` | pembayaran gaji |
| `payslips` | nomor slip, file PDF, log pengiriman |
| `cash_advances` | pengajuan kasbon |
| `cash_advance_installments` | jadwal cicilan |
| `cash_advance_payments` | pembayaran kasbon (potong gaji/manual) |
| `audit_logs` | jejak perubahan |

## 7. Rekomendasi Teknis **[Usulan]**

- Laravel (versi LTS terbaru yang didukung) + MySQL, Livewire untuk interaksi form dan tabel
- PDF: `barryvdh/laravel-dompdf`
- Role/permission: `spatie/laravel-permission` (atau policy sederhana karena hanya dua role)
- Audit trail: `owen-it/laravel-auditing` atau implementasi custom
- Backup: `spatie/laravel-backup` + cron harian, salinan disimpan di luar VPS
- Storage dokumen: disk private, akses lewat controller yang mengecek role
- Deploy: Nginx + PHP-FPM di VPS, HTTPS (Let's Encrypt), queue untuk kirim email/PDF
- Uang disimpan sebagai integer (rupiah) atau `decimal`, bukan float

## 8. Keamanan

- Password di-hash, HTTPS wajib, rate limit login
- Data sensitif (gaji, no rekening, NPWP, KTP) hanya dapat diakses role berwenang
- **[Usulan]** Enkripsi kolom untuk nomor identitas (KTP, NPWP, rekening)
- File dokumen tidak diakses langsung lewat URL publik
- Backup harian otomatis dan uji restore berkala

## 9. Fase Pengerjaan

### Fase 1: Fondasi, Data Karyawan & Gaji
1. Setup proyek, autentikasi, role admin/direktur
2. Modul data karyawan + upload dokumen + history
3. Master komponen gaji dan pemasangan per karyawan
4. Periode gaji, perhitungan, preview
5. Alur approval direktur (termasuk tolak dan revisi)
6. Pencatatan pembayaran
7. Slip gaji PDF + kirim email/WhatsApp
8. Audit log dasar, backup harian, deploy ke VPS

> Catatan desain: struktur `payroll_item_lines` dibuat generik agar potongan kasbon di Fase 2 tinggal masuk sebagai baris potongan baru tanpa mengubah alur gaji.

### Fase 2: Kasbon & Laporan
1. Modul kasbon (input, approval, skema pelunasan)
2. Integrasi cicilan kasbon ke perhitungan gaji
3. Riwayat pembayaran dan saldo kasbon
4. Dashboard direktur
5. Laporan dan export PDF

### Fase 3 (Opsional, belum diminta)
- Absensi dan lembur
- Perhitungan BPJS/PPh 21 otomatis
- Portal karyawan

## 10. Pertanyaan Terbuka

1. Format dan kop slip gaji: apakah klien sudah punya template yang harus diikuti?
2. Pengiriman WhatsApp: cukup click-to-chat manual, atau perlu otomatis lewat gateway (ada biaya bulanan)?
3. Apakah ada lebih dari satu direktur, dan siapa yang approve jika direktur berhalangan?
4. Batas kasbon (nominal maksimal atau persentase dari gaji) dan penanganan jika gaji tidak cukup untuk memotong cicilan?
5. Apakah THR dan bonus tahunan perlu fitur khusus, atau cukup lewat komponen custom?
6. Nomor slip gaji: perlu format khusus?
7. Data migrasi: apakah data karyawan dan riwayat gaji lama sudah ada di Excel yang perlu diimpor?
8. Timeline dan target go-live tiap fase.
