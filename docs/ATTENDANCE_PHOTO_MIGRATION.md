# Pemindahan foto absensi lama

Foto selfie yang dibuat sebelum perubahan penyimpanan masih berada di `storage/app/public/attendances` dan dapat dibuka langsung melalui tautan publik selama file itu belum dipindahkan. Endpoint foto baru memeriksa hak akses, tetapi tidak dapat menutup tautan langsung ke file lama.

1. Cadangkan database dan folder `storage/app/public/attendances`. Pastikan kode baru sudah terpasang dan disk `local` mengarah ke `storage/app/private` dengan akses tulis untuk aplikasi.
2. Periksa jumlah foto lama: `php artisan attendances:move-photos-private --dry-run --no-interaction`.
3. Pada waktu operasional yang disepakati, jalankan `php artisan attendances:move-photos-private --no-interaction`. Perintah menyalin dan memverifikasi isi file sebelum menghapus salinan publik. Jika ada kegagalan, file publik yang gagal tetap dipertahankan dan perintah mengembalikan status gagal; perbaiki penyebabnya lalu jalankan ulang.
4. Jalankan lagi mode `--dry-run`. Hasilnya harus **0**. Periksa bahwa foto absensi masih tampil bagi pemilik dan Admin/Direktur, dan pengguna lain mendapat penolakan akses.
5. Jika ada CDN atau cache web di depan `/storage/attendances`, hapus salinan yang tersimpan di sana. Tinjau juga cadangan publik lama sesuai kebijakan retensi organisasi.

Perintah ini memindahkan **semua file** di subfolder publik `attendances`, termasuk file yang tidak lagi dirujuk database. Tidak ada perubahan nilai path di database. Jangan menganggap foto lama sudah privat sampai langkah 3–5 selesai dan jumlah file publik benar-benar nol.
