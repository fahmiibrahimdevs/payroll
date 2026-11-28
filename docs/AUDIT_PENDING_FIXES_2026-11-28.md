# Daftar Temuan Audit Kode Program (Belum Diperbaiki)

> **Status:** Disimpan sebagai catatan arsip. **Jangan diperbaiki sebelum ada instruksi eksplisit dari pemilik project.**
> **Tanggal Audit:** 2026-11-28
> **Project:** `/var/www/projects/laravel/hrm`

---

## A. Hasil Test Suite Saat Audit (`php artisan test --compact`)

Total **489 tests**: **485 passed**, **1 failed**, **1 error**, **2 skipped**.

1. **Error — `Tests\Feature\PayrollTest::test_custom_higher_cash_advance_deduction_waterfalls_cleanly_through_payment_flow`**
   - File: `tests/Feature/PayrollTest.php:3331-3385`
   - Penyebab: `Employee::factory()->create()` memakai `join_date => fake()->dateTimeBetween('-3 years', 'now')` (`database/factories/EmployeeFactory.php:30`). Saat tanggal acak jatuh setelah `2026-10-31`, periode Oktober 2026 gagal dibuat karena tidak ada karyawan bulanan aktif.
2. **Failure — `Tests\Feature\PayrollTaxIntegrationTest::test_toggle_off_deduction_and_company_contributions_in_manage_item`**
   - File: `tests/Feature/PayrollTaxIntegrationTest.php:688-796`
   - Penyebab: Fixture memakai `'join_date' => Carbon::now()->subMonths(6)` (`2026-05-28`) untuk periode Juni 2026 (`2026-05-21` s.d. `2026-06-20`). Karyawan dianggap masuk di tengah siklus sehingga gaji pokok `6.000.000` ter-prorata di bawah batas TER Kategori A (`5.400.000`), menghasilkan `pph21_amount = 0.0`.

---

## B. Bug Logika & Inkonsistensi Bisnis

1. **Payroll — `missingEmployees` Menampilkan Pegawai Harian**
   - File: `app/Livewire/Payrolls/Show.php:167-180`, `resources/views/livewire/payrolls/show.blade.php:418`
   - Masalah: Query `getMissingEmployeesProperty()` tidak memfilter `employment_status != 'harian'`, sehingga pegawai harian aktif muncul di daftar karyawan yang belum masuk periode bulanan, padahal `syncMissingEmployees()` dan `addEmployeeToPeriod()` menolak pegawai harian.

2. **Payroll — Kolom `PayrollItem::$notes` Bertugas Ganda**
   - File: `app/Services/PayrollService.php:887,901,1634-1639`, `app/Livewire/Payrolls/ManageItem.php:291-300,3118-3140`
   - Masalah: `PayrollService::createPeriod()` mengisi `notes` dengan teks prorata, sedangkan `ManageItem::persistCompanyBpjsStatus()` menimpa kolom yang sama dengan JSON (`disabled_company_bpjs`, `enabled_company_bpjs`, `text_note`).

3. **Payroll & Kasbon — `CashAdvanceService::syncOpenPayrollLinesForInstallment()` Bisa Mengubah Periode `submitted`/`approved`**
   - File: `app/Services/CashAdvanceService.php:2645-2670`
   - Masalah: Query menggunakan `whereNotIn('status', ['paid', 'completed'])` (padahal `PayrollPeriodStatus` tidak memiliki case `'completed'`). Akibatnya pelunasan kasbon manual dapat mengubah potongan & net salary periode yang sudah berstatus `submitted` atau `approved` oleh Direktur tanpa mereset approval.

4. **Absensi — Auto Cut-Off Lembur Dini Hari Menambah 24 Jam**
   - File: `app/Services/AttendanceService.php:1655-1713`
   - Masalah: Ketika `$isPastCutoffToday` aktif (`$attDate->isToday()`, waktu sekarang `>= 06:00:00`, dan `overtime_in < 06:00:00`), kalkulasi `$out = Carbon::parse($attDate->toDateString().' 06:00:00')->addDay()` menambah 1 hari penuh sehingga durasi lembur bertambah +24 jam.

5. **Absensi — Geofencing Gagal Saat `accuracy` `null` & Shift Lintas Tengah Malam Belum Didukung**
   - File: `app/Services/AttendanceService.php:1315-1320,1406-1416`, `app/Livewire/Attendances/Index.php:1002,1010`
   - Masalah:
     - `recordWebClock()` mensyaratkan `$accuracyMeters !== null`, sehingga saat akurasi GPS tidak dikirim browser, absen tetap dianggap di luar radius meski jarak 0 meter.
     - `clock_out` hanya mencari record di `$today` dan hanya menghitung `duration_minutes` jika `$out->gte($in)` (belum mendukung shift malam lintas tengah malam); `Attendances\Index::resetToFingerprint()` juga mengabaikan wrap tengah malam.

6. **Lembur & Slip Mingguan — Tiga Inkonsistensi**
   - File: `app/Livewire/Overtimes/Index.php:231-234,616-642,698-700,999-1018`
     - Toggle `form_daily_meal_source = 'overtime'` dan `adjust_daily_meal_source = 'overtime'` diabaikan saat `syncFormMealAllowance()`, `storeSubmission()`, dan `submitAdjustment()`, karena selalu ditimpa dengan `formMealRate` / `adjustMealRate`.
     - `Overtimes\Index::reSubmit()` memanggil `Carbon::parse($submission->start_time)` dan `end_time` tanpa guard `null` untuk pengajuan tipe `DAILY` serta tidak memulihkan `overtime_type`.
   - File: `app/Livewire/Overtimes/PeriodAttendance.php:1033-1040`, `app/Http/Controllers/OvertimePrintController.php:260-266`
     - `dailyMatrix()` dan `buildSlip()` memberikan uang makan siang jika ada log absensi di hari libur/akhir pekan/off-day, berbeda dengan `AttendanceService::syncToWeeklyOvertimePeriod()` yang mengecualikan hari non-kerja.
   - File: `app/Livewire/Overtimes/PeriodAttendance.php:619`, `app/Livewire/Overtimes/Index.php:1157-1170,1313`, `app/Services/OvertimeService.php:702-712`
     - `recalcEmployeeMealTotal()` belum memasukkan `overtime_meal` ke `grand_total`; `Index::openAttendanceModal()` tidak memakai `employeeWorkDays()` pada mode roster; `savePeriodAttendances()` tidak memakai `DB::transaction` dan tidak skip karyawan berstatus `paid`; `autoLinkSubmissionToDraftPeriod()` belum menangani `casual_work_type = 'hourly'` dan `casual_pay_override`.

7. **Kasbon — Karyawan Non-Aktif dengan Tunggakan Hilang dari Rekap**
   - File: `app/Services/CashAdvanceService.php:1538-1540`, `app/Livewire/CashAdvances/Index.php:1092`
   - Masalah: `getEmployeesCashAdvanceSummary()` dan dropdown karyawan memfilter `status = 'active'`, sehingga karyawan non-aktif/resign yang masih memiliki sisa hutang tidak tampil di tabel rekap meski terhitung di `inactive_with_debt_count`.
   - Tambahan di Kasbon: `CashAdvances\Index::storeEarlyPayment()` (`:1550`) menerima semua `PaymentMethod` termasuk `salary_deduction` untuk pelunasan manual.

---

## C. Keamanan & Otorisasi

1. **Hak Tulis `direktur` Terlalu Luas di Beberapa Komponen Livewire**
   - `app/Livewire/Concerns/HasSimpleCrudModal.php:65,79,106,132,161,188` (Departments, Positions, WorkLocations CRUD)
   - `app/Livewire/WorkLocations/Index.php:92,112,129`
   - `app/Livewire/SalaryComponents/Index.php:90,104,129,152,179,209,239,275`
   - `app/Livewire/CompanyProfile/Index.php:208`
   - `app/Livewire/Attendances/Index.php:228,325,373,399,517,580,728,868,888,930`
   - Catatan: Berlawanan dengan `docs/PLAN_HRM.md` dan `config/laratrust_seeder.php` di mana `direktur` bersifat read/approve-only.

2. **Missing Role/Ownership Check pada `Attendances\Index::openAuditModal()`**
   - File: `app/Livewire/Attendances/Index.php:443-513`
   - Komponen dapat dibuka role `karyawan`, namun `openAuditModal()` belum memeriksa role `admin`/`direktur` atau kepemilikan record.

3. **Bypass Hashids Anti-Enumeration di `HasObfuscatedId::resolveRouteBinding()`**
   - File: `app/Traits/HasObfuscatedId.php:38-41`
   - Tetap menerima ID numerik mentah di production saat `IdObfuscator::decode()` mengembalikan `null`.

4. **Hardening Dokumen Inline di `SecureDocumentController`**
   - File: `app/Http/Controllers/SecureDocumentController.php:71-150`
   - `employeeCv()`, `paymentProof()`, `payrollItemProof()`, dan `overtimeAttendanceProof()` belum menerapkan MIME allowlist, path traversal guard (`..`), dan header `X-Content-Type-Options: nosniff`.

5. **Guard Tambahan & Service Layer Authorization**
   - `OvertimeService` (di luar endpoint mingguan), `AttendanceService`, dan `EmployeeService` belum menerapkan `Gate::forUser($user)->authorize(...)` di level service.
   - `PayrollPrintController::printYearlySummary()`, `AdminDashboard`, `Show::mount()`, `Overtimes\Index::reSubmit()`, dan `pages.admin.user-index` belum memiliki guard role eksplisit di controller/mount (hanya mengandalkan route middleware).
   - `AssignRequestId` (`app/Http/Middleware/AssignRequestId.php:18`) menerima nilai header `X-Request-ID` mentah tanpa validasi format/panjang.

---

## D. Performa, Lazy Loading, Kode Legacy & Deviasi Standar

1. **Potensi `LazyLoadingViolationException` (`preventLazyLoading` aktif di non-production):**
   - `DashboardClock::loadEmployee()` (`app/Livewire/Attendances/DashboardClock.php:73,82`) memanggil `getValidWorkLocationsOnDate()` tanpa eager load `workLocation` / `department`.
   - `CashAdvanceService` (`:806,874,1172,1237`) & `OvertimeService` (`:608,894,933`) mengakses `employee->user` setelah query/lock tanpa eager load.
   - `CashAdvanceService::syncOpenPayrollLinesForInstallment()` (`:2650,2667`) eager-load `'item'` tanpa `'item.payrollPeriod'`.
   - `OvertimeService::syncUnlinkedApprovedSubmissions()` (`:746,790`) tidak eager-load `'employee'`.

2. **Performa & Query Berulang:**
   - `PayrollService::syncMissingEmployees()` (`:1370-1377`) menjalankan `calculateProratedSalary()` dan `addEmployeeToPeriod()` satu per satu dalam loop.
   - `WorkLocations\Index::render()` (`:520-564`) & `Attendances\Index::render()` (`:1500-1511`) memuat ulang seluruh karyawan aktif pada setiap render Livewire.
   - `NavbarDropdown::open()` (`app/Livewire/Notifications/NavbarDropdown.php:28-54`) memanggil `$notification->markAsRead()` langsung tanpa menghapus cache `navbar_notifications_{$userId}`.

3. **Kode Legacy & Deviasi Dokumentasi:**
   - `app/Livewire/Payrolls/Show.php:333-470`: Metode `openComponentModal()` dan `saveComponents()` untuk `#manageComponentModal` masih ada meski modalnya sudah tidak dipakai di `show.blade.php`.
   - `app/Services/CompanyProfileService.php:30-107`: Mutasi file logo dan DB belum berada dalam satu `DB::transaction()` penuh bersama `syncEmployeeEmailDomains()`, serta pesan log masih dinamis.
   - `app/Http/Controllers/OvertimePrintController.php:166,184`: Pesan log masih menginterpolasi variabel dinamis (melanggar `STANDARD_LOGGING.md`).
   - `resources/views/livewire/pages/admin/user-index.blade.php`, `resources/views/livewire/logs/index.blade.php`, dan `resources/views/livewire/notifications/navbar-dropdown.blade.php`: Masih menggunakan `.text-muted`, solid Stisla badge, atau background `#f8fafc` yang belum sesuai `docs/RULES_FRONTEND.md`.
