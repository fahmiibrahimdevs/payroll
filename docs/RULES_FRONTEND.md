# LARASHIZ FRONTEND & UI/UX GUIDELINES

Dokumen ini adalah acuan standar baku pengembangan antarmuka (UI/UX), komponen styling, struktur tabel data, modal, dan tata letak Blade pada project **Larashiz**. Setiap penambahan atau perubahan fitur frontend **WAJIB** mematuhi aturan di bawah ini.

---

## 1. Standar Prioritas Komponen Bawaan Template (Template-First Principle)

1. **Wajib Cek Komponen Template Terlebih Dahulu**:
   - Setiap ingin membuat elemen antarmuka (seperti badge, alert, tombol, modal, pagination, card, list-group, breadcrumb, dropdown, dll), **WAJIB** mengecek terlebih dahulu apakah komponen tersebut sudah disediakan oleh template bawaan (Shizuefi / Stisla).
   - Contoh pada Badge: Gunakan class murni bawaan template seperti:
     - Primary: `<span class="badge badge-primary">`
     - Success: `<span class="badge badge-success">`
     - Danger: `<span class="badge badge-danger">`
     - Warning: `<span class="badge badge-warning">`
     - Info: `<span class="badge badge-info">`
     - Secondary: `<span class="badge badge-secondary">`
     - Light: `<span class="badge badge-light border">`
2. **Dilarang Menambahkan Class Ad-Hoc / Custom pada Komponen Bawaan**:
   - Dilarang menambahkan modifier arbitrary/ad-hoc (seperti menambahkan `text-dark` pada `badge-warning`). Gunakan tampilan murni template.
   - Dilarang membuat class custom baru (misal `.custom-badge`, `.log-badge-soft-*`) untuk elemen yang sudah ada bawaannya di template.
3. **Persetujuan Wajib untuk Komponen Kustom Baru**:
   - Jika suatu komponen memang **belum ada** di template bawaan dan benar-benar memerlukan custom component CSS baru, **WAJIB** meminta persetujuan terlebih dahulu kepada pengguna sebelum membuatnya.

---

## 2. Larangan Utility Class Ad-Hoc di Blade (Component-First CSS)

1. **Dilarang Menuliskan Utility Arbitrary Tailwind di Blade**:
   - ❌ Jangan gunakan: `text-[11px]`, `text-[#1e293b]`, `px-[25px]`, `bg-[#f8fafc]`, inline `style="..."`.
   - ✅ Gunakan class komponen semantik terdaftar (contoh: `.filter-chip-item`, `.log-list-tile`).
2. **Alur Pembuatan Style Baru**:
   - Definisikan class komponen baru di `public/assets/css/components-tailwind.css` menggunakan sintaks `@apply` Tailwind.
   - Jalankan kompilasi: `npm run build:shizuefi`.
   - Panggil nama class komponen semantik tersebut di file Blade.

---

## 3. Standar Struktur Header Halaman & Section Title (Page Header Standard)

1. **Header Slot & Breadcrumb Baku (`<x-slot name="header">`)**:
   - Setiap halaman Blade / Livewire wajib mendefinisikan `<x-slot name="header">` yang berisi judul halaman `<h1>` dan breadcrumb navigasi:
     ```blade
     <x-slot name="header">
         <h1>Kelola Posts</h1>
         <div class="section-header-breadcrumb">
             <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
             <div class="breadcrumb-item">Posts</div>
         </div>
     </x-slot>
     ```
2. **Section Title & Section Lead Wajib**:
   - Tepat di bawah slot header, setiap halaman **wajib** menyertakan judul section dan deskripsi ringkas:
     ```blade
     <h2 class="section-title">Postingan</h2>
     <p class="section-lead mb-3">
         Kelola seluruh postingan artikel, berita, dan publikasi konten Anda di halaman ini.
     </p>
     ```
3. **Dilarang Menuliskan Ulang Tag `<section>` dan `<div class="section-body">`**:
   - Layout utama [`layouts/app.blade.php`](file:///var/www/projects/laravel/larashiz/resources/views/layouts/app.blade.php) sudah secara otomatis membungkus `$slot` dengan `<section class="section custom-section">` dan `<div class="section-body">`.
   - **DILARANG KERAS** menambahkan wrapper `<section>` atau `<div class="section-body">` di dalam file view karena akan menciptakan nesting ganda (*redundant wrapper*) dan merusak kalkulasi margin pada mode mobile.

---

## 4. Standar Desain Tabel Data (Table Component Standard)

Semua halaman yang menampilkan tabel data (seperti pada modul **Posts** dan **Logs**) wajib mengikuti pola struktur HTML & class komponen baku berikut:

### Struktur Hierarki Tabel
```html
<div class="card">
    <!-- 1. Card Header -->
    <div class="card-header">
        <h4>Judul Tabel</h4>
    </div>

    <div class="card-body p-0">
        <!-- 2. Controls: Show Entries & Search Bar -->
        <div class="dt-control-wrapper">
            <div class="dt-show-entries">
                Show
                <select wire:model.live="perPage" class="dt-select-entries">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                Entries
            </div>

            <div class="dt-search-wrapper">
                <span class="mr-1">Search:</span>
                <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Search here...">
            </div>
        </div>

        <!-- 3. Table Responsive Container (Smooth Horizontal Scroll) -->
        <div class="table-responsive">
            <table class="table table-clean">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="text-center text-nowrap" style="width: 60px;">NO</th>
                        <th class="text-nowrap">KOLOM UTAMA</th>
                        <th class="text-nowrap" style="width: 160px;">KATEGORI</th>
                        <th class="text-nowrap" style="width: 140px;">STATUS</th>
                        <th class="text-center text-nowrap" style="width: 110px;"><i class="fas fa-cog text-muted" title="Aksi"></i></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $index => $item)
                        <tr wire:key="item-{{ $item->id }}">
                            <td class="text-center font-weight-600 text-muted align-middle">
                                {{ $items->firstItem() + $index }}
                            </td>
                            <!-- Kolom Konten Utama -->
                            <td class="align-middle">...</td>
                            <!-- Kolom Aksi -->
                            <td class="text-center align-middle text-nowrap">
                                <button type="button" class="btn btn-primary btn-action mr-1" title="Edit">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-action" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="fas fa-folder-open text-muted mb-2" style="font-size: 32px;"></i>
                                    <div class="font-weight-600 mt-2">Tidak ada data ditemukan</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 4. Footer: Info & Pagination -->
        <div class="dt-footer-wrapper">
            <div>
                Showing {{ $items->total() > 0 ? $items->firstItem() : 0 }} to {{ $items->total() > 0 ? $items->lastItem() : 0 }} of {{ $items->total() }} entries
            </div>
            <div>
                {{ $items->links('livewire.custom-pagination') }}
            </div>
        </div>
    </div>
</div>
```

### Aturan Wajib Tabel:
1. **Header Anti-Wrap (`text-nowrap`)**: Semua tag `<th>` pada `<thead>` **wajib** menggunakan class `text-nowrap` agar judul kolom tidak terlipat/patah ke bawah.
2. **Auto-Width untuk Kolom Utama**: Berikan ukuran width pasti pada kolom metadata ringkas (`NO`, `STATUS`, `AKSI`), dan biarkan kolom teks utama tanpa width tetap agar leluasa beradaptasi dengan lebar layar.
3. **Penomoran Konsisten**: Selalu gunakan formula `{{ $items->firstItem() + $index }}` agar nomor halaman pagination berlanjut dengan benar.
4. **Wire Key Wajib**: Setiap row `<tr>` dalam loop wajib memiliki `wire:key="item-{{ $item->id }}"`.
5. **Pagination Custom**: Gunakan view pagination kustom `$items->links('livewire.custom-pagination')`.

---

## 4. Standar Modal & Form Interactivity

1. **Header & Footer Standar Bawaan**:
   - Header: `<div class="modal-header">` polos (dilarang menambahkan background color custom seperti `bg-light`).
   - Footer: `<div class="modal-footer bg-whitesmoke br">` (mengikuti standar modal template Stisla).
2. **Layout Detail Modal (Mobile / Flutter-Style ListTile)**:
   - Menampilkan header status banner terpadu (`.log-detail-banner`).
   - Menggunakan grup list-tile bergaya mobile card (`.log-list-tile-group` dan `.log-list-tile`) untuk menyajikan metadata (User, IP, User-Agent, Request ID) daripada tabel HTML biasa.
3. **Form & Filter Real-Time**:
   - Pada filter/modal Livewire yang sudah berjalan reaktif secara *real-time*, **dilarang** menambahkan tombol "Terapkan Filter / Submit" ganda di modal kecuali jika form tersebut memerlukan submit manual.
4. **Standar Select2 di Dalam Bootstrap Modal & Pencegahan Focus Trap**:
   - **Hapus `tabindex="-1"` pada Modal**: Atribut `tabindex="-1"` pada modal mengaktifkan *Focus Trap* bawaan Bootstrap yang menyebabkan kotak pencarian (search box) Select2 tidak bisa diketik / kehilangan fokus. Hapus atribut `tabindex="-1"` pada elemen modal pembungkus.
   - **Wajib `dropdownParent`**: Selalu konfigurasi `dropdownParent: $('#modalId')` saat inisialisasi Select2 di dalam modal agar dropdown terpasang di konteks modal dan tidak tertutup backdrop.
   - **Override Enforce Focus di JavaScript**:
     ```javascript
     if ($.fn.modal && $.fn.modal.Constructor) {
         $.fn.modal.Constructor.prototype._enforceFocus = function() {};
     }
     ```
   - **Wajib `wire:ignore` pada Wrapper Select**: Selalu bungkus form-group Select2 dengan `wire:ignore` agar Livewire DOM morphing tidak mereset atau merusak instance Select2 saat komponen melakukan re-render.
   - **Sinkronisasi Dua Arah (`@this.set()`)**: Ikat event `change` Select2 ke state Livewire via `@this.set('form.field', value)` dan trigger update value Select2 saat modal dibuka (`open-modal`).

---

## 5. Standar Warna Primary & Konsistensi Tema Proyek

1. **Warna Primary Resmi Proyek (`#0b52aa`)**:
   - Seluruh elemen yang merepresentasikan warna utama/primary (tombol `.btn-primary`, tombol melayang / FAB `.btn-floating-filter`, active chip filters, active borders, SweetAlert confirm button) **WAJIB** menggunakan warna primary resmi proyek yaitu `#0b52aa` (atau class utility bawaan tema `.btn-primary` / `.bg-primary` / `var(--primary)`).
   - **DILARANG** menggunakan warna default Stisla lama ungu/indigo (`#6777ef`) pada styling baru, komponen baru, ataupun konfigurasi script dialog.
2. **Tombol Melayang (Floating Action Button / FAB)**:
   - Menggunakan background color primary `#0b52aa` dengan efek hover `#09458f` dan shadow lembut yang serasi (`rgba(11,82,170,0.4)`).

---

## 6. Standar Global Card & Spacing

1. **Tingkat Ketebalan Shadow**:
   - Seluruh kartu (`.card`, `.card-statistic-1`, `.shadow-sm`) menggunakan bayangan medium-soft yang seimbang:
     `box-shadow: 0 2px 6px 0 rgba(0, 0, 0, 0.06), 0 1px 2px 0 rgba(0, 0, 0, 0.04)` dengan border halus `border: 1px solid #e9edf2`.
   - Dilarang menggunakan shadow hitam pekat/tebal atau shadow yang terlalu transparan/mati.
2. **Spacing Mobile Responsive**:
   - Pada layar mobile (`max-width: 767.98px`), margin bawah card diset `margin-bottom: 14px !important` agar jarak vertikal antar card tetap rapat dan proporsional.
   - Hindari menambahkan class `mb-4` ganda pada elemen kolom pembungkus card.

---

## 7. Alur Kompilasi Aset Frontend

Setiap perubahan CSS di `public/assets/css/components-tailwind.css` atau `style-tailwind.css` **wajib** dikompilasi ulang:
```bash
npm run build:shizuefi
```

---

## 8. Standar Notifikasi & Konfirmasi SweetAlert2 (Mandatory SweetAlert2 Rules)

1. **Wajib Menggunakan SweetAlert2 Modal Pop-up untuk Semua Notifikasi**:
   - Seluruh pesan feedback/notifikasi (berhasil, gagal, peringatan, info) **WAJIB** menggunakan **SweetAlert2 Modal Pop-up** (`swal:alert` di tengah layar dengan judul, pesan, dan tombol OK):
     ```php
     // Notifikasi Sukses
     $this->dispatch('swal:alert', [
         'type' => 'success',
         'title' => 'Berhasil!',
         'message' => 'Status akun pengguna berhasil diaktifkan.',
     ]);

     // Notifikasi Error / Gagal
     $this->dispatch('swal:alert', [
         'type' => 'error',
         'title' => 'Gagal!',
         'message' => 'Terjadi kesalahan sistem saat memproses data.',
     ]);
     ```
   - **Dilarang** menggunakan toastr/toast pojok atau alert banner statis di dalam blade. Seluruh interaksi notifikasi wajib tampil terpusat dan konsisten menggunakan modal pop-up SweetAlert2.

2. **Wajib Menggunakan SweetAlert2 Konfirmasi untuk Aksi Berdampak Signifikan**:
   - Tindakan destruktif (seperti menghapus data/pengguna) dan tindakan perubahan status (seperti aktifkan / nonaktifkan akun pengguna) **WAJIB** memicu dialog konfirmasi SweetAlert2 terlebih dahulu (`swal:confirm` atau `swal:confirm-delete`) sebelum aksi dieksekusi di backend.

3. **Wajib Meminta Konfirmasi Pengguna Sebelum Menerapkan SweetAlert2 Konfirmasi Baru**:
   - Jika saat merancang fitur baru ditemukan aksi yang berpotensi memerlukan dialog konfirmasi SweetAlert2, AI / developer **WAJIB meminta konfirmasi dan persetujuan pengguna (USER) terlebih dahulu** sebelum menambahkan flow konfirmasi tersebut.

---

## 9. Standar Kode Bersih Blade (Clean Blade & Presentation Logic Standard)

1. **Dilarang Menuliskan Logic / Komputasi di File Blade**:
   - **DILARANG KERAS** menuliskan blok `@php ... @endphp` untuk komputasi data, percabangan `match()`, `switch()`, manipulasi string kompleks, atau penentuan class CSS kondisional di dalam file view Blade.
   - File Blade harus **langsung siap pakai (ready-to-render)** dan fokus hanya pada penyajian antarmuka HTML.
2. **Gunakan Eloquent Accessor / Model Attribute**:
   - Semua pemetaan representasi tampilan (seperti penentuan class badge, label status, format warna) **WAJIB** dipindahkan ke **Model Eloquent** sebagai Attribute Accessor (contoh: `$log->level_badge_class` pada Model `ActivityLog`).
   - Panggil langsung atribut tersebut di Blade:
     ```blade
     <!-- Bersih & Langsung Siap Pakai -->
     <span class="{{ $log->level_badge_class }}">
         {{ strtoupper($log->level) }}
     </span>
     ```
3. **Wajib Konsistensi Section Title & Lead**:
   - Setiap halaman utama aplikasi **WAJIB** menyertakan `<h2 class="section-title">` dan `<p class="section-lead mb-3">` tepat di bawah `<x-slot name="header">` untuk menjaga konsistensi visual layout template.

---

## 10. Standar Loading State & Animasi Spinner Livewire (Wire Loading Standard)

1. **Wajib `wire:loading.attr="disabled"` pada Seluruh Tombol Aksi**:
   - Setiap tombol interaktif yang memicu aksi Livewire (tombol submit form, tombol edit modal, tombol hapus, tombol toggle status, tombol reset filter, tombol floating FAB) **WAJIB** memiliki atribut `wire:loading.attr="disabled"` dan `wire:target="..."` spesifik untuk mencegah klik berulang (*double-submit / spamming*).
2. **Wajib Indikator Spinner Loading**:
   - Setiap tombol aksi wajib menyertakan icon animasi spinner FontAwesome (`<i class="fas fa-spinner fa-spin"></i>`) yang tampil secara dinamis saat request Livewire sedang diproses:
     ```blade
     <!-- 1. Contoh pada Tombol Aksi Tabel / Icon Button -->
     <button
         type="button"
         wire:click="edit({{ $item->id }})"
         wire:loading.attr="disabled"
         wire:target="edit({{ $item->id }})"
         class="btn btn-primary btn-action"
         title="Edit Data"
     >
         <i wire:loading wire:target="edit({{ $item->id }})" class="fas fa-spinner fa-spin"></i>
         <i wire:loading.remove wire:target="edit({{ $item->id }})" class="fas fa-pencil-alt"></i>
     </button>

     <!-- 2. Contoh pada Tombol Submit Form Modal -->
     <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="store">
         <i wire:loading wire:target="store" class="fas fa-spinner fa-spin mr-1"></i>
         <i wire:loading.remove wire:target="store" class="fas fa-save mr-1"></i>
         Simpan Data
     </button>

     <!-- 3. Contoh pada Floating Action Button (FAB) -->
     <button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create" class="btn-floating-add" title="Tambah Data" aria-label="Tambah Data">
         <i wire:loading wire:target="create" class="fas fa-spinner fa-spin"></i>
         <i wire:loading.remove wire:target="create" class="fas fa-plus"></i>
     </button>
     ```

---

## 11. Standar Format Tag HTML Satu Baris (Single-Line Tag & Element Standard)

1. **Tag Input, Select, Textarea, dan Tombol Wajib Satu Baris**:
   - **DILARANG** memecah/wrap atribut tag HTML (`<input>`, `<select>`, `<textarea>`, `<button>`) menjadi banyak baris ke bawah (*multi-line attribute wrapping*).
   - Tuliskan seluruh atribut tag HTML secara ringkas dan bersih dalam **satu baris (one line)**:
     ```blade
     <!-- ✅ BENAR (Satu Baris Bersih) -->
     <input type="text" id="title" wire:model="form.title" class="form-control @error('form.title') is-invalid @enderror" placeholder="Masukkan judul post..." autocomplete="off">
     <button type="button" wire:click="edit({{ $item->id }})" wire:loading.attr="disabled" wire:target="edit({{ $item->id }})" class="btn btn-primary btn-action" data-toggle="tooltip" title="Edit Data">

     <!-- ❌ SALAH (Dilarang dibungkus baris panjang ke bawah) -->
     <input
         type="text"
         id="title"
         wire:model="form.title"
         class="form-control @error('form.title') is-invalid @enderror"
         placeholder="Masukkan judul post..."
         autocomplete="off"
     >
     ```

2. **Tag Table Header (`<th>`) Wajib Ditulis Satu Baris Penuh**:
   - Semua tag `<th>` pada `<thead>` wajib ditulis dalam **satu baris penuh (one line)** bersama isi labelnya:
     ```blade
     <!-- ✅ BENAR -->
     <th class="text-center text-nowrap" style="width: 50px;">NO</th>
     <th class="text-nowrap" style="width: 125px;">MODUL & AKSI</th>
     <th class="text-nowrap">PESAN LOG</th>

     <!-- ❌ SALAH -->
     <th class="text-nowrap" style="width: 125px;">
         MODUL & AKSI
     </th>
     ```

3. **Pengecualian untuk Kolom Data Dinamis (`<td>`)**:
   - Kolom data tabel (`<td>`) yang berisi sintaks Blade dinamis `{{ ... }}`, badge, wrapper div, atau manipulasi format tanggal diperbolehkan ditulis multi-line agar kode tetap terstruktur dan rapi.

---

## 12. Standar Anti-Wrapping Teks & Label (Zero-Wrap UI Standard)

1. **Dilarang Ada Teks/Label/Badge/Nav Pills Terpotong atau Ter-wrap ke Baris Baru**:
   - Seluruh label input form, nav-pills, badges, tombol aksi, dan header tabel **DILARANG** ter-wrap secara canggung (*awkward line breaks*) ke bawah, baik pada tampilan desktop maupun mobile viewport.
2. **Penulisan Label Ringkas & Padat**:
   - Gunakan nama label yang ringkas, jelas, dan lugas (contoh: `Total Kasbon`, `Kepegawaian & Gaji`, `Pribadi & Bank`).
   - Hindari menulis teks keterangan panjang di dalam label (seperti keterangan di dalam kurung) yang memicu pemecahan baris.
3. **Pills & Tabs Responsif**:
   - Pada modal atau form yang memiliki nav-pills/tabs, wajib selalu mengunci tata letak dalam 1 baris horizontal (`.nav-fill.flex-row.flex-nowrap`, `style="flex: 1 1 50%;"`, dan `.text-nowrap`) agar tidak pernah menumpuk ke bawah di layar mobile.

---

## 13. Standar Shadow & Border Radius Card (Card Shadow Standard)

1. **Wajib Menggunakan Standar Card Shadow & Border Radius Larashiz**:
   - Seluruh elemen card di aplikasi (termasuk `.card`, card Filter Log, card Riwayat Aktivitas, card Data Karyawan, widget statistik, serta `.activity-detail` pada timeline) **WAJIB TANPA KECUALI** menggunakan spesifikasi baku:
     - **Shadow**: `box-shadow: 0 2px 6px 0 rgba(0, 0, 0, 0.06), 0 1px 2px 0 rgba(0, 0, 0, 0.04);` (`shadow-[0_2px_6px_0_rgba(0,0,0,0.06),0_1px_2px_0_rgba(0,0,0,0.04)]`)
     - **Border**: `1px solid #e9edf2` (`border border-[#e9edf2]`)
     - **Border Radius**: `12px` (`rounded-[12px]`)
     - **Background**: `bg-white` (`#ffffff`)
   - ✅ **BENAR**: Semua card (baik container utama maupun item activities timeline) memiliki shadow yang sangat bersih, crisp, modern depth, dan memiliki border halus yang 100% identik dengan Card Filter Log.
2. **Dilarang Mengubah Ukuran Shadow / Radius Card**:
   - ❌ **DILARANG**: Mengubah atau membedakan nilai shadow dan radius pada card apapun di seluruh sistem (jangan gunakan shadow tebal seperti `0 8px 20px rgba(0,0,0,0.08)`).

