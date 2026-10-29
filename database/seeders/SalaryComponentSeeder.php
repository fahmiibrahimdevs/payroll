<?php

namespace Database\Seeders;

use App\Enums\SalaryCalculationType;
use App\Enums\SalaryComponentCategory;
use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalaryComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $components = [
            // ==================== PENDAPATAN TETAP ====================
            [
                'code' => 'GAJI_POKOK',
                'name' => 'Gaji Pokok',
                'category' => SalaryComponentCategory::FIXED_ALLOWANCE,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => true,
                'is_system' => true,
                'is_taxable' => true,
                'sort_order' => 1,
                'description' => 'Upah dasar pokok karyawan sesuai perjanjian kerja/posisi.',
            ],
            [
                'code' => 'TUNJ_JABATAN',
                'name' => 'Tunjangan Jabatan',
                'category' => SalaryComponentCategory::FIXED_ALLOWANCE,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => true,
                'is_system' => false,
                'is_taxable' => true,
                'sort_order' => 2,
                'description' => 'Tunjangan fungsional struktural atas wewenang dan tanggung jawab jabatan.',
            ],
            [
                'code' => 'TUNJ_KEAHLIAN',
                'name' => 'Tunjangan Keahlian',
                'category' => SalaryComponentCategory::FIXED_ALLOWANCE,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => false,
                'is_system' => false,
                'is_taxable' => true,
                'sort_order' => 3,
                'description' => 'Tunjangan kompetensi spesialis atau sertifikasi keahlian khusus.',
            ],
            [
                'code' => 'TUNJ_MASA_KERJA',
                'name' => 'Tunjangan Masa Kerja',
                'category' => SalaryComponentCategory::FIXED_ALLOWANCE,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => false,
                'is_system' => false,
                'is_taxable' => true,
                'sort_order' => 4,
                'description' => 'Penghargaan loyalitas dan masa pengabdian kerja karyawan.',
            ],
            [
                'code' => 'TUNJ_KELUARGA',
                'name' => 'Tunjangan Keluarga',
                'category' => SalaryComponentCategory::FIXED_ALLOWANCE,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => false,
                'is_system' => false,
                'is_taxable' => true,
                'sort_order' => 5,
                'description' => 'Bantuan tunjangan pasangan sah dan tanggungan anak.',
            ],

            // ==================== TUNJANGAN TIDAK TETAP ====================
            [
                'code' => 'TUNJ_MAKAN',
                'name' => 'Tunjangan Makan',
                'category' => SalaryComponentCategory::VARIABLE_ALLOWANCE,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => true,
                'is_system' => true,
                'is_taxable' => true,
                'sort_order' => 6,
                'description' => 'Uang makan operasional kehadiran harian atau bulanan.',
            ],
            [
                'code' => 'TUNJ_TRANSPORT',
                'name' => 'Tunjangan Transport',
                'category' => SalaryComponentCategory::VARIABLE_ALLOWANCE,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => true,
                'is_system' => false,
                'is_taxable' => true,
                'sort_order' => 7,
                'description' => 'Bantuan akomodasi dan transportasi perjalanan kerja operasional.',
            ],
            [
                'code' => 'TUNJ_KOMUNIKASI',
                'name' => 'Tunjangan Komunikasi',
                'category' => SalaryComponentCategory::VARIABLE_ALLOWANCE,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => false,
                'is_system' => false,
                'is_taxable' => true,
                'sort_order' => 8,
                'description' => 'Penggantian kuota internet dan pulsa komunikasi koordinasi kerja.',
            ],
            [
                'code' => 'LEMBUR',
                'name' => 'Uang Lembur',
                'category' => SalaryComponentCategory::VARIABLE_ALLOWANCE,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => true,
                'is_system' => true,
                'is_taxable' => true,
                'sort_order' => 9,
                'description' => 'Upah jam kerja lembur di luar jam kerja reguler yang disetujui.',
            ],
            [
                'code' => 'BONUS',
                'name' => 'Bonus / Insentif',
                'category' => SalaryComponentCategory::VARIABLE_ALLOWANCE,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => false,
                'is_system' => false,
                'is_taxable' => true,
                'sort_order' => 10,
                'description' => 'Bonus pencapaian target, performa KPI, atau insentif proyek.',
            ],
            [
                'code' => 'THR',
                'name' => 'Tunjangan Hari Raya',
                'category' => SalaryComponentCategory::VARIABLE_ALLOWANCE,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => false,
                'is_system' => false,
                'is_taxable' => true,
                'sort_order' => 11,
                'description' => 'Tunjangan keagamaan tahunan sesuai ketentuan ketenagakerjaan.',
            ],
            [
                'code' => 'REIMBURSE',
                'name' => 'Reimburse',
                'category' => SalaryComponentCategory::VARIABLE_ALLOWANCE,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => false,
                'is_system' => false,
                'is_taxable' => true,
                'sort_order' => 12,
                'description' => 'Klaim pengeluaran operasional perusahaan atas nama karyawan.',
            ],

            // ==================== POTONGAN ====================
            [
                'code' => 'BPJS_KES',
                'name' => 'BPJS Kesehatan (1%)',
                'category' => SalaryComponentCategory::DEDUCTION,
                'calculation_type' => SalaryCalculationType::PERCENTAGE_OF_BASIC_SALARY,
                'default_value' => 1.00,
                'is_active' => true,
                'is_system' => false,
                'is_taxable' => false,
                'sort_order' => 13,
                'description' => 'Iuran BPJS Kesehatan potongan karyawan 1% dari upah.',
            ],
            [
                'code' => 'BPJS_JHT',
                'name' => 'BPJS JHT (2%)',
                'category' => SalaryComponentCategory::DEDUCTION,
                'calculation_type' => SalaryCalculationType::PERCENTAGE_OF_BASIC_SALARY,
                'default_value' => 2.00,
                'is_active' => true,
                'is_system' => false,
                'is_taxable' => false,
                'sort_order' => 14,
                'description' => 'Iuran Jaminan Hari Tua (JHT) potongan karyawan 2% dari upah.',
            ],
            [
                'code' => 'BPJS_JP',
                'name' => 'BPJS Pensiun (1%)',
                'category' => SalaryComponentCategory::DEDUCTION,
                'calculation_type' => SalaryCalculationType::PERCENTAGE_OF_BASIC_SALARY,
                'default_value' => 1.00,
                'is_active' => true,
                'is_system' => false,
                'is_taxable' => false,
                'sort_order' => 15,
                'description' => 'Iuran Jaminan Pensiun (JP) potongan karyawan 1% dari upah.',
            ],
            [
                'code' => 'PPH21',
                'name' => 'PPh 21',
                'category' => SalaryComponentCategory::DEDUCTION,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => false,
                'is_system' => false,
                'is_taxable' => false,
                'sort_order' => 16,
                'description' => 'Pajak penghasilan pasal 21 atas penghasilan bruto karyawan.',
            ],
            [
                'code' => 'KASBON',
                'name' => 'Potongan Kasbon',
                'category' => SalaryComponentCategory::DEDUCTION,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => true,
                'is_system' => true,
                'is_taxable' => false,
                'sort_order' => 17,
                'description' => 'Cicilan pelunasan kasbon atau pinjaman internal karyawan.',
            ],
            [
                'code' => 'POT_TERLAMBAT',
                'name' => 'Potongan Keterlambatan',
                'category' => SalaryComponentCategory::DEDUCTION,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => false,
                'is_system' => false,
                'is_taxable' => false,
                'sort_order' => 18,
                'description' => 'Penalti atau potongan kedisiplinan atas keterlambatan hadir.',
            ],
            [
                'code' => 'POT_ALPA',
                'name' => 'Potongan Alpa',
                'category' => SalaryComponentCategory::DEDUCTION,
                'calculation_type' => SalaryCalculationType::NOMINAL,
                'default_value' => null,
                'is_active' => false,
                'is_system' => false,
                'is_taxable' => false,
                'sort_order' => 19,
                'description' => 'Pemotongan upah proporsional atas ketidakhadiran tanpa izin resmi.',
            ],
        ];

        foreach ($components as $data) {
            $data['type'] = $data['category']->type();
            SalaryComponent::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        // Clean up obsolete dummy/legacy components that are not in the master seed
        $seedCodes = array_column($components, 'code');
        $obsoleteComponents = SalaryComponent::withTrashed()
            ->whereNotIn('code', $seedCodes)
            ->get();

        if ($obsoleteComponents->isNotEmpty()) {
            $bonusComp = SalaryComponent::where('code', 'BONUS')->first();
            $kasbonComp = SalaryComponent::where('code', 'KASBON')->first();
            $pph21Comp = SalaryComponent::where('code', 'PPH21')->first();

            foreach ($obsoleteComponents as $obsolete) {
                $replacementId = match ($obsolete->code) {
                    'TJ-BONUS' => $bonusComp?->id,
                    'POT-KASBON' => $kasbonComp?->id,
                    'POT-PPH21' => $pph21Comp?->id,
                    default => null,
                };

                DB::table('payroll_item_lines')
                    ->where('salary_component_id', $obsolete->id)
                    ->update(['salary_component_id' => $replacementId]);

                $obsolete->forceDelete();
            }
        }
    }
}
