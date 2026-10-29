# LARASHIZ FRONTEND & UI/UX GUIDELINES (EXECUTIVE UI STANDARD)

Dokumen ini adalah acuan standar baku pengembangan antarmuka (UI/UX), komponen styling, struktur tabel data, modal, chart analitik, dan tata letak Blade pada aplikasi **Larashiz**. Setiap penambahan atau perubahan fitur frontend **WAJIB** mematuhi aturan baku di bawah ini, mengacu pada standar visual eksekutif modern yang telah diterapkan pada modul **Kasbon** (`cash-advances`), **Penggajian** (`payrolls`), **Karyawan** (`employees`), dan **Lembur** (`overtimes`).

---

## 1. Prinsip Utama: Executive UI Modern (Bukan Template Shizuefi Mentah)

1. **Executive UI Standard**:
   - Seluruh halaman utama menggunakan antarmuka modern eksekutif dengan palet warna bersih, kontras tinggi, elevasi kartu halus (*soft elevation*), dan tipografi proporsional.
   - **Dilarang** menggunakan styling badge solid pekat klasik bawaan Stisla/Shizuefi mentah (`badge-primary`, `badge-danger` tebal) untuk indikator status data.
   - Komponen visual harus konsisten lintas modul (Kasbon, Payrolls, Employees, Overtimes).

2. **Konsistensi Warna Primary Proyek (`#0b52aa`)**:
   - Seluruh elemen warna utama (tombol `.btn-primary`, tombol FAB, tab aktif `.nav-pills .nav-link.active`, highlight nominal, border fokus) **WAJIB** menggunakan warna primary resmi proyek: `#0b52aa`.
   - **Dilarang** menggunakan warna default Stisla lama ungu/indigo (`#6777ef`).

---

## 2. Standar Struktur Header Halaman & Breadcrumb

Setiap view utama Blade / Livewire wajib mendefinisikan slot header dan seksi lead:

```blade
<x-slot name="header">
    <h1>Nama Modul</h1>
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Kategori</div>
        <div class="breadcrumb-item">Nama Modul</div>
    </div>
</x-slot>

<!-- Header Section & Action Button -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2 mb-md-3">
    <div class="mr-md-3">
        <h2 class="section-title mt-0">Judul Fitur Lengkap</h2>
        <p class="section-lead mb-0">
            Deskripsi fungsional ringkas dan jelas mengenai apa yang dapat dikelola pada halaman ini.
        </p>
    </div>

    <!-- Tombol Aksi Utama di Kanan Atas -->
    <div class="mt-3 mt-md-0 flex-shrink-0 d-flex align-items-center" style="gap: 8px;">
        <button type="button" wire:click="openModal" class="btn btn-primary d-inline-flex align-items-center font-weight-bold shadow-sm" style="background-color: #0b52aa; border-color: #0b52aa; border-radius: 8px; padding: 9px 16px; font-size: 13px;">
            <i class="fas fa-plus mr-1.5"></i> Tambah Data
        </button>
    </div>
</div>
```

> [!IMPORTANT]
> **Dilarang Menuliskan Ulang Tag `<section>` dan `<div class="section-body">`**:
> Layout utama [`resources/views/layouts/app.blade.php`](file:///var/www/projects/laravel/hrm/resources/views/layouts/app.blade.php) sudah otomatis membungkus `$slot` dengan `<section class="section custom-section">` dan `<div class="section-body">`. Jangan membuat wrapper nesting ganda.

---

## 3. Top Metric Cards (Grid Ringkasan KPI 4-Card)

Tepat di bawah header, setiap modul utama menyajikan 4 kartu ringkasan metrik menggunakan struktur baku `.cash-advance-stat-grid`:

```blade
<div class="cash-advance-stat-grid mb-4">
    <!-- Card 1: Biru (Total / Volume Utama) -->
    <div class="card cash-advance-stat-card">
        <div class="card-body cash-advance-stat-body">
            <div class="cash-advance-stat-icon cash-advance-stat-icon-blue">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="cash-advance-stat-content">
                <div class="cash-advance-stat-title">Metrik Utama</div>
                <div class="cash-advance-stat-value-row">
                    <div class="cash-advance-stat-value text-dark">Rp 12.500.000</div>
                    <span class="cash-advance-stat-badge cash-advance-stat-badge-blue"><i class="fas fa-chart-line"></i></span>
                </div>
                <div class="cash-advance-stat-subtext">Sub-keterangan ringkas</div>
            </div>
        </div>
    </div>

    <!-- Card 2: Kuning / Amber (Pending / Verifikasi) -->
    <div class="card cash-advance-stat-card">
        <div class="card-body cash-advance-stat-body">
            <div class="cash-advance-stat-icon cash-advance-stat-icon-amber">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="cash-advance-stat-content">
                <div class="cash-advance-stat-title">Menunggu Verifikasi</div>
                <div class="cash-advance-stat-value-row">
                    <div class="cash-advance-stat-value text-warning">3 Pengajuan</div>
                    <span class="cash-advance-stat-badge cash-advance-stat-badge-amber"><i class="fas fa-clock"></i></span>
                </div>
                <div class="cash-advance-stat-subtext">Perlu tindakan review</div>
            </div>
        </div>
    </div>

    <!-- Card 3: Ungu / Purple (Approval Tingkat Lanjut) -->
    <div class="card cash-advance-stat-card">
        <div class="card-body cash-advance-stat-body">
            <div class="cash-advance-stat-icon cash-advance-stat-icon-purple">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="cash-advance-stat-content">
                <div class="cash-advance-stat-title">Approval Direktur</div>
                <div class="cash-advance-stat-value-row">
                    <div class="cash-advance-stat-value text-primary">2 Pengajuan</div>
                    <span class="cash-advance-stat-badge cash-advance-stat-badge-blue"><i class="fas fa-stamp"></i></span>
                </div>
                <div class="cash-advance-stat-subtext">Telah lolos verifikasi awal</div>
            </div>
        </div>
    </div>

    <!-- Card 4: Hijau / Green (Disetujui / Selesai / Terbayar) -->
    <div class="card cash-advance-stat-card">
        <div class="card-body cash-advance-stat-body">
            <div class="cash-advance-stat-icon cash-advance-stat-icon-green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="cash-advance-stat-content">
                <div class="cash-advance-stat-title">Selesai / Disetujui</div>
                <div class="cash-advance-stat-value-row">
                    <div class="cash-advance-stat-value text-success">15 Berkas</div>
                    <span class="cash-advance-stat-badge cash-advance-stat-badge-green"><i class="fas fa-check"></i></span>
                </div>
                <div class="cash-advance-stat-subtext">Rp 8.750.000 tereksekusi</div>
            </div>
        </div>
    </div>
</div>
```

---

## 4. Tata Letak 1 Baris 2 Card (1 Row Split Layout: Analisis Kiri & Tabel Kanan)

Untuk halaman modul utama dengan analitik visual (seperti Kasbon, Payrolls, dan Lembur), gunakan tata letak terbagi (*split layout*) dalam 1 baris:

```blade
<div class="row align-items-start">
    <!-- Kolom Kiri: 1 Card Berisi 2 Grafik & Analisis Eksekutif (col-12 col-lg-3 col-md-4) -->
    <div class="col-12 col-lg-3 col-md-4 mb-4">
        @include('livewire.[modul].charts-card')
    </div>

    <!-- Kolom Kanan: Card Tabel Data Utama (col-12 col-lg-9 col-md-8) -->
    <div class="col-12 col-lg-9 col-md-8 mb-4">
        <div class="card cash-advance-main-card shadow-sm mb-0" style="border-radius: 12px; border: 1px solid #e9edf2;">
            ...
        </div>
    </div>
</div>
```

### Spesifikasi Card Grafik Kiri:
- **Card Container**: Border radius `12px`, border `1px solid #e9edf2`, shadow halus.
- **Grafik 1 (Bar Chart Tren 6 Bulan)**: Menampilkan tren bulanan (jam, nominal, atau frekuensi) dengan ApexCharts warna `#0b52aa`.
- **Grafik 2 (Donut Chart Distribusi Departemen)**: Donut chart dengan counter total di tengah (*center overlay text*), legenda metrik, dan bar progres persentase per departemen.

---

## 5. Standar Judul Card Header & Bar Kontrol Tabel

### 1. Format Judul Card Header:
- Format baku: `[Nama Modul] - [Scope / Status]` dengan penekanan warna primary pada scope:
  - Contoh: `<h4>Lembur - <span class="text-primary font-weight-bold">Semua Karyawan</span></h4>`
  - Mode karyawan: `<h4>Lembur - <span class="text-primary font-weight-bold">Pengajuan Saya</span></h4>`
  - Tab riwayat periode: `<h4>Daftar Riwayat Periode</h4>`

### 2. Bar Kontrol DataTables (`.dt-control-wrapper`):
- Terdiri dari:
  - **Kiri**: `Show [perPage] Entries` (select dropdown 10, 15, 25, 50).
  - **Kanan**: `Search: [input]` text input dengan debounce 300ms.
- **Larangan Filter Dropdown Status Berlebih**: Dilarang menambahkan select dropdown filter status ganda di dalam bar kontrol tabel jika tidak diminta secara spesifik oleh user, agar tampilan tetap ringkas dan tidak memakan ruang horizontal.

### 3. Segmented Native Tabs (`.nav-pills`):
- Gunakan `.nav.nav-pills` di bawah kontrol tabel atau di card header untuk beralih antar tab (misal: *Daftar Pengajuan* vs *Periode Mingguan*).

### 4. Larangan Tombol Aksi Redundan (Prinsip Single Primary Action):
- **DILARANG KERAS** membuat tombol aksi utama (seperti Tambah Data / Buat Pengajuan) berulang-ulang di berbagai tempat dalam satu halaman yang sama sehingga membingungkan user.
- Jika modul sudah memiliki **Floating Action Button** (`.btn-floating-add`) di pojok kanan bawah, Card Header tabel **wajib bersih hanya berisi judul modul** dan **DILARANG** menambahkan tombol tambah lagi di Card Header (tiru halaman Kasbon).
- Tombol aksi tambah di dalam area tabel hanya diperbolehkan pada kondisi **`empty-state`** (ketika data tabel benar-benar kosong 0 baris) sebagai panduan first-time user.

---

## 6. Standar Desain Tabel Data (`.table.table-clean`)

1. **Header Tabel (`<thead>`)**:
   - Huruf kapital tebal (`font-weight: 800`, font-size `11px` - `11.5px`, color `#1e293b`, letter-spacing `0.5px`).
   - Seluruh tag `<th>` **wajib** menggunakan kelas `text-nowrap`.
   - Ditulis rapi dalam satu baris.

2. **Kolom Identitas Karyawan**:
   - Menampilkan avatar lingkaran dengan inisial nama berlatar soft pastel (`width: 32px; height: 32px; background-color: #eff6ff; color: #0284c7; border: 1.5px solid #bae6fd; font-weight: 700; font-size: 11px;`).
   - Nama karyawan tebal (`font-weight: 700; color: #0f172a;`).
   - Subteks departemen dan jabatan dengan warna netral (`font-size: 11.5px; color: #64748b;`).

3. **Status Badges (Soft Pastel Standard)**:
   - Status badge **wajib** menggunakan palet pastel lembut dengan border tipis 1px dan teks kontras tinggi (tersedia via enum `badgeStyle()`):
     - **Pending / Menunggu**: `background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;`
     - **Disetujui / Aktif**: `background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;`
     - **Selesai / Lunas**: `background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;`
     - **Ditolak / Batal**: `background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;`

4. **Kolom Aksi**:
   - Tombol aksi rapi dengan icon proporsional (lihat rincian, edit, verifikasi, hapus).

---

## 7. Standar Modal, Form Input, & Label

Setiap modal pop-up wajib mengikuti struktur baku berikut:

### 1. Struktur Kontainer & Header Modal:
```blade
<div wire:ignore.self class="modal fade" id="modalId" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content bg-white" style="border: 1px solid #eaedf2; border-radius: 8px;">
            <div class="modal-header bg-white border-bottom py-3">
                <h5 class="modal-title text-dark" id="modalLabel" style="font-weight: 500;">
                    Judul Modal
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form wire:submit.prevent="submitMethod">
                <div class="modal-body py-3 px-4 bg-white">
                    ...
                </div>

                <div class="modal-footer bg-white border-top px-4 py-3">
                    <button type="button" class="btn btn-secondary shadow-none" data-dismiss="modal" style="font-weight: 500;">Batal</button>
                    <button type="submit" class="btn btn-primary shadow-none" wire:loading.attr="disabled" wire:target="submitMethod" style="font-weight: 500;">
                        <span wire:loading wire:target="submitMethod"><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span>
                        <span wire:loading.remove wire:target="submitMethod"><i class="fas fa-save mr-1"></i> Simpan Data</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

### 2. Standar Form Group & Label:
- Gunakan `<div class="form-group">`.
- Label wajib bersih: `<label for="field">Nama Label <span class="text-danger">*</span></label>` atau `<label for="field">Nama Label <small class="text-muted">(Opsional)</small></label>`.
- Input control: Gunakan class murni `class="form-control @error('...') is-invalid @enderror"`. **DILARANG** menambahkan inline style `border-radius: 8px` pada setiap tag input karena styling form-control sudah diatur global oleh tema.

### 3. Box Notice & Callout di Dalam Modal:
- Gunakan box alert putih bersih dengan sudut rounded 12px dan soft shadow:
  ```blade
  <div class="alert alert-light border small text-muted mb-3" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);">
      <div class="d-flex align-items-center text-dark font-weight-500 mb-1" style="font-size: 12px;">
          <i class="fas fa-info-circle text-primary mr-1.5"></i>
          <span>Judul Informasi</span>
      </div>
      <div style="font-size: 11px; line-height: 1.45; color: #64748b;">
          Penjelasan detail mengenai proses terkait.
      </div>
  </div>
  ```

### 4. Modal Rincian / Detail:
- Menampilkan avatar inisial karyawan (lingkaran 60x60px).
- Garis pemisah putus-putus (*dashed divider*): `<div style="border-top: 1px dashed #e2e8f0; margin: 14px 0;"></div>`.
- Highlight angka besar: `font-size: 26px; font-weight: 800; color: #0f172a;`.
- Rincian box dengan border halus `1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);` dan ikon berwarna `#0b52aa`.
- **Nilai Data dalam Kotak Rincian**: Seluruh teks baris data (nomor referensi, tanggal, jam kerja, nominal, dll.) menggunakan bobot Reguler/Normal (`font-weight: 400` / `500`). Dilarang mem-bold baris demi baris secara seragam. Semi-bold (`font-weight: 600`) hanya boleh ada pada teks badge atau penekanan yang benar-benar bermakna.
- **Kotak Uraian / Catatan / Callout**: Wajib berlatar putih bersih (`background: #ffffff;`), sudut `border-radius: 12px;`, border halus `#e2e8f0`, dan `box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);`. Dilarang menggunakan latar abu-abu pudar (`#f8fafc`) atau biru muda (`#eff6ff`).

---

## 8. Konvensi Wording & Terminologi Kompensasi

1. **Dilarang Menggunakan Kata "Tarif" atau "Lokal"**:
   - Untuk skema uang makan, kompensasi harian, atau uang lembur, **DILARANG** menggunakan kata *"Tarif"* atau *"Lokal"*.
2. **Gunakan Terminologi Baku**:
   - Gunakan kata **"Nominal"** (misal: *Nominal per Jam*, *Nominal Uang Makan*).
   - Gunakan **"Nominal Standar"** untuk skema global/perusahaan.
   - Gunakan **"Nominal Khusus"** untuk skema perorangan/karyawan tertentu.

---

## 9. Loading State & Dialog SweetAlert2

1. **Wajib `wire:loading.attr="disabled"`**:
   - Setiap tombol submit atau tombol aksi interaktif wajib dilengkapi `wire:loading.attr="disabled"` dan `wire:target="..."` spesifik untuk mencegah double-click / race conditions.
2. **Indikator Spinner FontAwesome**:
   - Gunakan `<span wire:loading wire:target="..."><i class="fas fa-spinner fa-spin mr-1"></i> Memproses...</span>` berdampingan dengan `<span wire:loading.remove wire:target="...">`.
3. **SweetAlert2 untuk Feedback & Konfirmasi**:
   - Seluruh notifikasi sukses/gagal menggunakan `swal:alert` modal pop-up terpusat.
   - Aksi berdampak signifikan (penolakan, penghapusan, pembatalan) memicu dialog konfirmasi `swal:confirm`.

---

## 10. Standar Tipografi Bermakna (Meaningful Typography & Zero Blanket-Bold Principle)

1. **Default Bobot Teks Selalu Reguler/Normal (`font-weight: 400` / `500`)**:
   - Seluruh teks nilai data, nomor referensi, tanggal, waktu, nominal angka, uraian pekerjaan, deskripsi, dan catatan **WAJIB** menggunakan bobot normal (`font-weight: 400` atau `500`).
   - **DILARANG KERAS** mem-bold atau semi-bold data baris demi baris secara serentak (*blanket-bold*) tanpa alasan, karena menghilangkan hierarki visual dan melelahkan mata pembaca.

2. **Semi-Bold (`font-weight: 600`) HANYA Diperuntukkan Jika Memiliki Makna/Tujuan Tertentu**:
   - Teks di dalam **Badge** (status pengajuan, badge durasi seperti `(2 jam)` atau `1 Hari Penuh`, tag kategori/asal pengajuan) agar kontras di atas warna badge tetap terbaca tegas.
   - **Nama Subjek/Entitas Utama** pada kartu rincian (misal nama karyawan).
   - **Peringatan / Alert Kritis** yang membutuhkan atensi segera pengguna.

---

## 11. Standar Desain Kartu (Clean Executive White Card Standard)

1. **Background Putih Bersih 100% (No Gray / No Light Blue)**:
   - **DILARANG KERAS** menggunakan background abu-abu muda (`.bg-light`, `.bg-whitesmoke`, `#f8fafc`, `#f1f5f9`) atau biru muda (`#eff6ff`, `#dbeafe`) pada Card, Card Header, Card Body, maupun Card Footer.
   - Seluruh card dan kotak informasi (termasuk kotak uraian/alasan/catatan di modal) **WAJIB** berlatar putih murni (`#ffffff` / `bg-white`).
   - Card Footer wajib menggunakan `bg-white border-top` (bukan `.bg-whitesmoke`).

2. **Sudut Rounded Modern (Sesuai Card Analisis & Data)**:
   - **Card Data & Form Utama:** `border-radius: 12px;` (atau `rounded-xl`).
   - **Card Analisis & Grafik Metrik:** `border-radius: 16px;`.
   - **Kotak Rincian/Callout:** `border-radius: 12px;`.
   - Dilarang menggunakan sudut kotak kaku bawaan template lama (radius 3px–4px).

3. **Elevasi Halus (Soft Shadow) & Border Penegas**:
   - Card dilengkapi bayangan modern: `box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.03);` atau `shadow-sm`.
   - Garis tepi halus: `border: 1px solid #e9edf2;` (atau `#e2e8f0`).

---

## 12. Standar Kontras Teks Tinggi (Zero Faint-Gray Text Principle)

1. **Dilarang Menggunakan Teks Abu-Abu Pudar**:
   - **DILARANG KERAS** menggunakan styling teks abu-abu pudar atau *washed-out light gray* (`.text-muted`, `#94a3b8`, `#98a6ad`, `#a0aec0`, `#cbd5e1`) untuk teks deskripsi, instruksi petunjuk modal, keterangan box form, atau sub-label di atas background putih (`#ffffff`).
   - Teks yang terlalu terang/pudar akan menyatu dengan background putih sehingga tidak terbaca dan merusak kenyamanan mata pengguna.

2. **Gunakan Warna Slate Berkontras Tinggi**:
   - Seluruh teks petunjuk, deskripsi, sub-label, helper text, dan uraian penjelasan **WAJIB** menggunakan warna ber-kontras tinggi yang tajam, tegas, dan nyaman dibaca:
     - **Teks Judul & Label Utama:** Slate 900 (`#0f172a`) atau Slate 800 (`#1e293b`).
     - **Teks Deskripsi & Paragraf Penjelasan:** Slate 700 (`#334155`).
     - **Teks Sub-label / Sub-teks Keterangan:** Slate 600 (`#475569`).


