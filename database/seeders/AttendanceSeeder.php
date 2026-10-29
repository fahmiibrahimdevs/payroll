<?php

namespace Database\Seeders;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $backupPath = database_path('seeders/attendance_backup.json');
        if (! file_exists($backupPath)) {
            return;
        }

        $records = json_decode(file_get_contents($backupPath), true);
        if (empty($records)) {
            return;
        }

        $formatted = array_map(function ($row) {
            unset($row['id']);
            $row['date'] = Carbon::parse($row['date'])->toDateString();
            $row['created_at'] = ! empty($row['created_at']) ? Carbon::parse($row['created_at'])->toDateTimeString() : now()->toDateTimeString();
            $row['updated_at'] = ! empty($row['updated_at']) ? Carbon::parse($row['updated_at'])->toDateTimeString() : now()->toDateTimeString();

            return $row;
        }, $records);

        foreach (array_chunk($formatted, 100) as $chunk) {
            Attendance::insert($chunk);
        }
    }
}
