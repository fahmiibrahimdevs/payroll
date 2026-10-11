# Review Teknis Modul Kasbon

Tanggal review: 2026-10-03

> Status implementasi: sebagian besar temuan prioritas di bawah sudah diperbaiki pada kode saat ini, termasuk policy/Gate, row locking, validasi kepemilikan cicilan, sequence transaksi, dan loading state UI. Bagian yang masih relevan terutama lifecycle file privat, validasi tanggal, performa ledger, serta pengujian ketika database test tersedia.

Dokumen ini berisi temuan review modul kasbon dan arahan teknis singkat untuk perbaikan. Fokus review mencakup alur pengajuan, approval, pembayaran, pelunasan lebih awal, cicilan, ledger, validasi, otorisasi, dan UI approval.

## Ringkasan Prioritas

Urutan perbaikan yang disarankan:

1. Tambahkan authorization berdasarkan role/permission.
2. Amankan mutasi saldo dan cicilan dengan row locking.
3. Pastikan installment yang dipilih memang milik cash advance terkait.
4. Amankan generator nomor transaksi dari concurrent request.
5. Perbaiki lifecycle file bukti pembayaran.
6. Tambahkan test untuk unauthorized access, manipulasi payload, dan concurrency.
7. Setelah integritas data aman, rapikan performa ledger dan UI.

## Temuan dan Solusi Teknis

### 1. Authorization belum diterapkan secara nyata — Kritis

Route kasbon hanya menggunakan middleware `auth`. Action Livewire seperti approve, reject, edit, delete, pembayaran, dan early settlement tidak terlihat memiliki pemeriksaan role/permission di service atau policy.

Referensi:

- `routes/web.php`
- `app/Livewire/CashAdvances/Index.php`
- `app/Services/CashAdvanceService.php`
- `resources/views/livewire/cash-advances/approval-modal.blade.php`

Solusi teknis:

- Tetapkan hak akses sesuai rancangan bisnis: admin menginput dan mencatat pembayaran; direktur approve/reject.
- Tambahkan Policy atau permission check pada setiap operasi sensitif.
- Jangan hanya mengandalkan tombol yang disembunyikan di Blade.
- Lakukan pemeriksaan ulang di service sebelum mutasi database karena request Livewire dapat dipanggil langsung.
- Tambahkan test untuk admin yang mencoba approve/reject dan user tanpa permission yang mencoba mutasi.

### 2. Race condition pada pembayaran dan saldo — Kritis

Saldo dibaca dan divalidasi sebelum transaksi, lalu di-update di dalam transaksi tanpa mengunci row cash advance. Dua request bersamaan dapat sama-sama lolos validasi dan menyebabkan pembayaran melebihi saldo.

Lokasi utama:

- `app/Services/CashAdvanceService.php`, method `recordPayment`
- `app/Services/CashAdvanceService.php`, method `recordMonthlySettlement`

Solusi teknis:

- Mulai transaksi lebih dahulu.
- Ambil ulang cash advance menggunakan `lockForUpdate()`.
- Validasi status dan `remaining_amount` setelah row terkunci.
- Kunci juga installment yang akan diubah.
- Hitung ulang saldo dari data terbaru di dalam transaksi.
- Tambahkan test concurrent payment atau minimal test terhadap saldo yang berubah sebelum proses commit.

### 3. Installment dapat berasal dari cash advance lain — Tinggi

Validasi hanya menggunakan:

```php
exists:cash_advance_installments,id
```

Artinya ID installment valid secara global, tetapi belum tentu milik cash advance yang sedang dibayar.

Lokasi:

- `app/Livewire/Forms/CashAdvancePaymentForm.php`
- `app/Services/CashAdvanceService.php`

Solusi teknis:

- Batasi validation rule berdasarkan `cash_advance_id`.
- Validasi ulang di service.
- Pastikan installment ditemukan melalui relasi cash advance yang sedang diproses.
- Tolak pembayaran jika installment tidak dimiliki cash advance tersebut.

Contoh pendekatan validasi:

```php
Rule::exists('cash_advance_installments', 'id')
    ->where('cash_advance_id', $this->cashAdvance?->id)
```

Validasi service tetap wajib karena state Livewire dapat dimanipulasi.

### 4. Generator nomor transaksi tidak aman untuk request bersamaan — Tinggi

`generateReferenceNumber()` dan `generatePaymentNumber()` mencari nomor terakhir lalu menambah satu. Dua request paralel dapat membaca nomor terakhir yang sama.

Lokasi:

- `app/Services/CashAdvanceService.php`, method `generateReferenceNumber`
- `app/Services/CashAdvanceService.php`, method `generatePaymentNumber`

Solusi teknis:

- Gunakan tabel counter per periode dengan `lockForUpdate()`, atau
- Gunakan ID database sebagai bagian sequence, atau
- Pertahankan format sekarang tetapi tambahkan retry terbatas ketika terjadi duplicate key.
- Unique index tetap dipertahankan sebagai lapisan terakhir.

### 5. File bukti pembayaran dapat menjadi orphan file — Sedang

File di-upload dan disimpan sebelum transaksi payment selesai. Jika database gagal, file tetap berada di storage tanpa record payment.

Lokasi:

- `app/Livewire/Forms/CashAdvancePaymentForm.php`
- `app/Livewire/CashAdvances/Index.php` untuk early payment

Solusi teknis:

- Simpan file ke temporary path terlebih dahulu.
- Commit record database dengan aman.
- Pindahkan file setelah commit menggunakan `DB::afterCommit()` atau job setelah commit.
- Hapus temporary file jika transaksi gagal.
- Jika proses menggunakan job, pastikan job memiliki retry dan cleanup yang jelas.

### 6. Ledger mengambil seluruh data sebelum pagination — Sedang

`getEmployeeLedger()` mengambil seluruh cash advance dan payment dengan `get()`, menggabungkannya di memory, mengurutkan, menghitung saldo berjalan, lalu membuat pagination manual.

Lokasi:

- `app/Services/CashAdvanceService.php`, method `getEmployeeLedger`

Dampak:

- Memory bertambah seiring jumlah transaksi.
- Query dan proses render melambat.
- Pagination bukan pagination database sebenarnya.

Solusi teknis:

- Untuk kebutuhan saldo berjalan, pertimbangkan tabel ledger/transaksi terpadu atau query database khusus.
- Gunakan pagination di level database.
- Jika saldo berjalan tetap membutuhkan kalkulasi collection, batasi data dengan filter periode dan evaluasi kebutuhan snapshot saldo.
- Pastikan sorting memiliki tie-breaker unik seperti `created_at` lalu `id`.

### 7. Summary aktif memuat seluruh record ke memory — Sedang

Summary mengambil semua cash advance aktif lalu menjalankan `sum()` dan `count()` pada collection.

Lokasi:

- `app/Services/CashAdvanceService.php`, method `getSummaryStats`
- `app/Services/CashAdvanceService.php`, method `getActiveCashAdvanceSummaryByEmployee`

Solusi teknis:

- Gunakan aggregate database:

```php
$count = CashAdvance::query()->where(...)->count();
$total = CashAdvance::query()->where(...)->sum('remaining_amount');
```

- Gunakan `exists()` jika hanya perlu mengetahui apakah data tersedia.
- Jika daftar detail tetap dibutuhkan, paginate atau batasi kolom yang diambil.

### 8. Pesan exception internal ditampilkan langsung ke user — Sedang

Beberapa component menampilkan `$e->getMessage()` langsung pada SweetAlert. Pesan tersebut dapat berisi detail SQL, constraint, path file, atau informasi internal lainnya.

Lokasi:

- `app/Livewire/CashAdvances/Index.php`, action store/update/approve/reject/payment/delete

Solusi teknis:

- Tampilkan pesan bisnis yang aman dan generik kepada user.
- Log exception lengkap melalui `ActivityLogService`.
- Untuk `InvalidArgumentException` yang memang berisi pesan bisnis, boleh tampilkan pesan terkontrol.
- Jangan menampilkan exception database atau exception framework secara langsung.

### 9. Logging belum mengikuti static message rule — Rendah

Ada log message yang menggabungkan nomor transaksi ke dalam message, misalnya payment number di message pelunasan bulanan.

Solusi teknis:

- Gunakan message statis.
- Masukkan nomor transaksi dan data dinamis ke `context`.

Contoh:

```php
$logger->info(
    action: 'MONTHLY_PAYMENT',
    module: 'cash_advances',
    message: 'Cash advance monthly payment recorded',
    context: [
        'payment_number' => $paymentNumber,
    ],
);
```

## Review Khusus Modal Approval

File terkait:

- `resources/views/livewire/cash-advances/approval-modal.blade.php`

Hal yang perlu diperhatikan:

- Tombol approval dan rejection memakai JavaScript confirmation. Pastikan event tersebut tidak hanya memanggil Livewire tanpa authorization server-side.
- Tombol approval/rejection belum memiliki loading state dan spinner sesuai standar UI proyek.
- Saat proses berlangsung, tombol harus disabled agar tidak terjadi double submit.
- Modal menampilkan informasi karyawan dan nominal, tetapi keputusan akses tidak boleh bergantung pada modal tersebut.
- Pesan rejection wajib divalidasi di server, bukan hanya melalui prompt JavaScript.
- Setelah approval/rejection berhasil, state `selectedCashAdvance`, `selectedCashAdvanceId`, `approvalNotes`, dan `rejectionReason` sebaiknya dibersihkan.
- Jika record sudah berubah status karena request lain, service harus menolak operasi kedua dengan pesan bisnis yang aman.

Solusi teknis singkat:

1. Tambahkan `wire:loading.attr="disabled"` dan `wire:target` pada tombol.
2. Tambahkan spinner FontAwesome ketika approve/reject berjalan.
3. Pertahankan confirmation di sisi UI, tetapi ulangi authorization dan status check di service.
4. Gunakan policy/permission untuk membatasi hanya direktur yang dapat approve/reject.
5. Tambahkan test untuk double approval dan approval oleh user tanpa permission.

## Ketidaksesuaian UI yang Terlihat

Beberapa bagian halaman kasbon masih menggunakan inline style, arbitrary utility, dan badge manual.

Contoh area:

- `resources/views/livewire/cash-advances/index.blade.php`
- `resources/views/livewire/cash-advances/form-modal.blade.php`
- `resources/views/livewire/cash-advances/payment-modal.blade.php`
- `resources/views/livewire/cash-advances/approval-modal.blade.php`
- `resources/views/livewire/cash-advances/detail-modal.blade.php`
- `resources/views/livewire/cash-advances/early-payment-modal.blade.php`

Solusi teknis singkat:

- Pindahkan style berulang ke semantic class di `public/assets/css/components-tailwind.css`.
- Jalankan `npm run build:shizuefi` setelah perubahan CSS.
- Gunakan accessor/model enum untuk label dan badge status.
- Tambahkan loading state pada semua action Livewire.
- Gunakan icon detail yang sesuai, bukan icon edit untuk action melihat rincian.
- Pertimbangkan tidak memilih karyawan pertama secara otomatis saat halaman dibuka; gunakan state pilih karyawan agar data yang tampil tidak arbitrer.

## Cakupan Test yang Perlu Ditambahkan

Tambahkan feature test untuk:

- User tanpa role direktur mencoba approve.
- User biasa mencoba reject.
- User tanpa permission mencoba delete atau mencatat payment.
- Payment memakai installment dari cash advance lain.
- Payment melebihi saldo melalui payload yang dimanipulasi.
- Double payment pada cash advance yang sama.
- Double approval pada pengajuan yang sama.
- Duplicate reference/payment number pada request bersamaan.
- Kegagalan database setelah file bukti di-upload.
- Early settlement bersamaan pada bulan yang sama.
- Status dan saldo tetap konsisten ketika transaksi gagal.

## Catatan Domain yang Perlu Dipastikan

Sebelum implementasi final, pastikan keputusan bisnis berikut jelas:

- Apakah admin boleh approve, atau approval hanya direktur?
- Apakah satu karyawan boleh memiliki lebih dari satu kasbon aktif setelah konfirmasi?
- Apakah pembayaran manual boleh dilakukan untuk status `approved`, atau harus `active`?
- Apakah tanggal pembayaran boleh berada di masa depan atau sebelum tanggal pencairan?
- Jika pembayaran dilakukan sebelum jatuh tempo, bagaimana aturan prioritas cicilan?
- Bagaimana penanganan saldo kasbon saat karyawan resign?
- Apakah batas kasbon berdasarkan nominal atau persentase gaji?

## Kesimpulan

Modul kasbon sudah memiliki struktur service, model, enum, form, Livewire component, cicilan, payment, ledger, dan test alur normal. Namun, sebelum digunakan untuk data finansial nyata, authorization dan integritas transaksi perlu diperbaiki terlebih dahulu. Perapihan UI dan optimasi ledger dapat dilakukan setelah dua area tersebut aman.
