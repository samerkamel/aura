<?php

namespace Modules\Attendance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Attendance\Models\PublicHoliday;

/**
 * Egypt Public Holiday Seeder
 *
 * Seeds the public holidays for Egypt for 2025 and 2026.
 * Islamic holiday dates are based on expected lunar calendar dates
 * and may vary slightly based on official moon sighting announcements.
 *
 * @author Dev Agent
 */
class EgyptPublicHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays = array_merge(
            $this->getHolidays2025(),
            $this->getHolidays2026()
        );

        foreach ($holidays as $holiday) {
            PublicHoliday::updateOrCreate(
                ['date' => $holiday['date']],
                ['name' => $holiday['name']]
            );
        }

        $this->command->info('Egypt public holidays for 2025 and 2026 have been seeded successfully!');
    }

    /**
     * Get Egypt public holidays for 2025
     */
    private function getHolidays2025(): array
    {
        return [
            // Fixed Holidays
            ['date' => '2025-01-07', 'name' => 'Coptic Christmas Day'],
            ['date' => '2025-01-25', 'name' => 'Revolution Day (January 25)'],
            ['date' => '2025-04-21', 'name' => 'Sham El-Nessim (Spring Festival)'],
            ['date' => '2025-04-24', 'name' => 'Sinai Liberation Day'],
            ['date' => '2025-04-25', 'name' => 'Sinai Liberation Day (Day 2)'],
            ['date' => '2025-05-01', 'name' => 'Labour Day'],
            ['date' => '2025-06-30', 'name' => 'June 30 Revolution Day'],
            ['date' => '2025-07-03', 'name' => 'June 30 Revolution (Day Off)'],
            ['date' => '2025-07-23', 'name' => 'Revolution Day (July 23)'],
            ['date' => '2025-07-24', 'name' => 'Revolution Day (Day Off)'],
            ['date' => '2025-10-06', 'name' => 'Armed Forces Day'],
            ['date' => '2025-10-09', 'name' => 'Armed Forces Day (Day Off)'],

            // Islamic Holidays 2025 (dates may vary based on moon sighting)
            // Eid al-Fitr (End of Ramadan) - Expected March 29 - April 2
            ['date' => '2025-03-29', 'name' => 'Eid al-Fitr (Day 1)'],
            ['date' => '2025-03-30', 'name' => 'Eid al-Fitr (Day 2)'],
            ['date' => '2025-03-31', 'name' => 'Eid al-Fitr (Day 3)'],
            ['date' => '2025-04-01', 'name' => 'Eid al-Fitr (Day 4)'],
            ['date' => '2025-04-02', 'name' => 'Eid al-Fitr (Day 5)'],

            // Eid al-Adha (Feast of Sacrifice) - Expected June 5-9
            ['date' => '2025-06-05', 'name' => 'Waqfat Arafat (Day of Arafah)'],
            ['date' => '2025-06-06', 'name' => 'Eid al-Adha (Day 1)'],
            ['date' => '2025-06-07', 'name' => 'Eid al-Adha (Day 2)'],
            ['date' => '2025-06-08', 'name' => 'Eid al-Adha (Day 3)'],
            ['date' => '2025-06-09', 'name' => 'Eid al-Adha (Day 4)'],

            // Islamic New Year (Hijri New Year) - Expected June 26
            ['date' => '2025-06-26', 'name' => 'Islamic New Year (Hijri 1447)'],

            // Prophet Muhammad\'s Birthday - Expected September 4
            ['date' => '2025-09-04', 'name' => 'Prophet Muhammad\'s Birthday (Mawlid)'],
        ];
    }

    /**
     * Get Egypt public holidays for 2026
     */
    private function getHolidays2026(): array
    {
        return [
            // Fixed Holidays
            ['date' => '2026-01-07', 'name' => 'Coptic Christmas Day'],
            ['date' => '2026-01-25', 'name' => 'Revolution Day (January 25)'],
            ['date' => '2026-01-29', 'name' => 'Revolution Day (Day Off)'],
            ['date' => '2026-04-13', 'name' => 'Sham El-Nessim (Spring Festival)'],
            ['date' => '2026-04-25', 'name' => 'Sinai Liberation Day'],
            ['date' => '2026-05-01', 'name' => 'Labour Day'],
            ['date' => '2026-06-30', 'name' => 'June 30 Revolution Day'],
            ['date' => '2026-07-02', 'name' => 'June 30 Revolution (Day Off)'],
            ['date' => '2026-07-23', 'name' => 'Revolution Day (July 23)'],
            ['date' => '2026-10-06', 'name' => 'Armed Forces Day'],
            ['date' => '2026-10-08', 'name' => 'Armed Forces Day (Day Off)'],

            // Islamic Holidays 2026 (dates may vary based on moon sighting)
            // Eid al-Fitr (End of Ramadan) - Expected March 21-23
            ['date' => '2026-03-21', 'name' => 'Eid al-Fitr (Day 1)'],
            ['date' => '2026-03-22', 'name' => 'Eid al-Fitr (Day 2)'],
            ['date' => '2026-03-23', 'name' => 'Eid al-Fitr (Day 3)'],

            // Eid al-Adha (Feast of Sacrifice) - Expected May 26-29
            ['date' => '2026-05-26', 'name' => 'Waqfat Arafat (Day of Arafah)'],
            ['date' => '2026-05-27', 'name' => 'Eid al-Adha (Day 1)'],
            ['date' => '2026-05-28', 'name' => 'Eid al-Adha (Day 2)'],
            ['date' => '2026-05-29', 'name' => 'Eid al-Adha (Day 3)'],

            // Islamic New Year (Hijri New Year) - Expected June 17
            ['date' => '2026-06-17', 'name' => 'Islamic New Year (Hijri 1448)'],

            // Prophet Muhammad\'s Birthday - Expected August 26
            ['date' => '2026-08-26', 'name' => 'Prophet Muhammad\'s Birthday (Mawlid)'],
        ];
    }
}
