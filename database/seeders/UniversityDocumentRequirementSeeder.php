<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\University;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Isi awal Dokumen Persyaratan per kampus dari Google Sheet "Requirements University" (12 November 2026).
 * Jalankan: php artisan db:seed --class=UniversityDocumentRequirementSeeder
 *
 * Aman diulang: kampus yang sudah punya persyaratan (sudah diatur/diubah admin) dilewati.
 * Nama kampus dicocokkan tanpa huruf besar/kecil & tanda baca; yang tidak ketemu dilaporkan
 * supaya admin bisa mengaturnya sendiri di halaman edit University.
 *
 * Format baris: code => [wajib?, catatan, jenjang (null/bachelor/postgraduate), bahasa (null/english/chinese)].
 */
class UniversityDocumentRequirementSeeder extends Seeder
{
    /** Daftar umum (Zhejiang University), dipakai juga untuk kampus di tab 2 yang tidak punya daftar rinci. */
    private function standard(string $english = 'IELTS 6.5+ / TOEFL 80+ / Duolingo 120+', string $hsk = 'HSK 5 with a score of 200+', string $bank = '20,000'): array
    {
        return [
            'passport' => [true, 'Photo & signature page.'],
            'ijazah_translate' => [false, 'Certificate from your highest degree. Not graduated yet? Upload the Pre-Graduation Certificate (Graduation Letter Expected) instead.'],
            'graduation_letter_expected' => [false, 'Only if you have not graduated yet, as a replacement for the certificate.'],
            'transcript' => [true, 'Report card / transcript from your highest degree.'],
            'skck' => [true, 'Certificate of Good Conduct (SKCK).'],
            'medical_checkup' => [true, 'General health examination record.'],
            'personal_statement' => [true, 'At least 800 words: introduce yourself, tell us why you are applying, and what makes you stand out.'],
            'certificate_ielts_toefl_duolingo' => [true, $english.' (for English-taught programs).', null, 'english'],
            'certificate_hsk' => [true, $hsk.' (for Chinese-taught programs).', null, 'chinese'],
            'standardized_test' => [false, 'If you have one: SAT / IB / A-Level / national entrance exam results.'],
            'bank_statement' => [true, 'Financial guarantee letter or bank statement showing at least RMB '.$bank.'.'],
            'guardian_letter' => [false, 'Required if you are under 18 years old.'],
            'recommendation_letter' => [true, '2 letters from your high school teacher or university professor. Please combine them into 1 PDF.'],
            'any_certificate' => [false, 'Recommended: art, language, or sports certificates.'],
            'csca' => [true, 'CSCA report, for Bachelor programs.', 'bachelor'],
            'supervisor_acceptance_letter' => [true, 'Acceptance letter from your supervisor, for Master/PhD programs.', 'postgraduate'],
        ];
    }

    private function campuses(): array
    {
        $standard = $this->standard();
        $without = fn (array $list, array $codes) => array_diff_key($list, array_flip($codes));
        $bank30 = ['bank_statement' => [true, 'Financial guarantee letter or bank statement showing at least RMB 30,000.']];

        return [
            'Zhejiang University' => $standard,
            'Zhejiang Normal University' => [
                'passport' => [true, 'Photo & signature page.'],
                'ijazah_translate' => [false, 'Notarized high school diploma. Not graduated yet? Upload the notarized Pre-Graduation Certificate instead.'],
                'graduation_letter_expected' => [false, 'Notarized, only if you have not graduated yet.'],
                'transcript' => [true, 'Notarized academic transcripts.'],
                'csca' => [true, 'CSCA transcript (China Scholastic Competency Assessment).'],
                'certificate_hsk' => [true, 'HSK 4 with a score of 210+.'],
                'medical_checkup' => [true, 'Foreigner Physical Examination Form.'],
                'skck' => [true, 'No-criminal record certificate.'],
                'no_drug_statement' => [true, 'A signed statement that you are free from drug addiction.'],
                'mental_health_survey' => [true, null],
                'study_plan' => [true, 'Written in your program\'s language of instruction, at least 1,000 words.'],
                'any_certificate' => [false, 'Other supporting documents, if any.'],
                'supervisor_acceptance_letter' => [true, 'Acceptance letter from your supervisor, for Master/PhD programs.', 'postgraduate'],
            ] + $bank30,
            'Shanghai University' => ['self_intro_video' => [true, 'A 60-second video introducing yourself.']]
                + $without($this->standard('IELTS 6.5+ / TOEFL 90+', 'HSK 5 with a score of 210+'), ['standardized_test']),
            'South China University of Technology' => $without($this->standard('IELTS 6.5+ / TOEFL 90+ / Duolingo 100+', 'HSK 4 or 5 with a score of 180+'), ['standardized_test']),
            'Qingdao City University' => [
                'passport' => [true, 'Photo page.'],
                'pass_photo' => [true, '3.3 × 4.8 cm with a white background.'],
                'ijazah_translate' => [false, 'Academic certificate, original and English version. Not graduated yet? Upload the Pre-Graduation Certificate instead.'],
                'graduation_letter_expected' => [false, 'Only if you have not graduated yet, original and English version.'],
                'transcript' => [true, 'Academic transcript, original and English version.'],
                'medical_checkup' => [true, 'Medical check-up report.'],
                'personal_statement' => [true, null],
            ],
            'Nanjing University of Information Science and Technology' => array_intersect_key(
                $this->standard('IELTS 6.0+ / TOEFL 80+ / Duolingo 100+', 'HSK 4 with a score of 180+'),
                array_flip(['passport', 'ijazah_translate', 'graduation_letter_expected', 'transcript', 'skck', 'medical_checkup', 'personal_statement', 'certificate_ielts_toefl_duolingo', 'certificate_hsk', 'bank_statement', 'supervisor_acceptance_letter'])
            ),
            'Nanjing University of Aeronautics and Astronautics' => $standard,
            'Nanjing Tech University' => $bank30 + $standard,
            'Nanjing Normal University' => $standard,
            'Nanjing Medical University' => $this->standard(hsk: 'HSK 4 with a score of 180+', bank: '30,000'),
            'Harbin Institute of Technology Shenzhen' => $bank30 + $standard,
            'East China Normal University' => $standard,
            'The Chinese University of Hong Kong Shenzhen' => $standard,
            'China Pharmaceutical University' => $this->standard(hsk: 'HSK 4 with a score of 180+'),
        ];
    }

    /** Nama di sheet kadang beda tulis dengan data kampus (typo, singkatan, "&"). */
    private const ALIASES = [
        'qingdaocityuniversity' => ['qindaocityuniversity'],
        'harbininstituteoftechnologyshenzhen' => ['harbininstitutetechnologyshenzhen', 'hitshenzhen'],
        'thechineseuniversityofhongkongshenzhen' => ['chineseuniversityofhongkongshenzhen', 'cuhkshenzhen', 'cuhksz'],
        'nanjinguniversityofinformationscienceandtechnology' => ['nuist'],
    ];

    private static function key(string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', str_replace('&', 'and', Str::lower($name)));
    }

    public function run(): void
    {
        $types = DocumentType::pluck('id', 'code');
        $universities = University::all()->keyBy(fn (University $university) => self::key($university->name));
        // Pembayaran tetap tampil di semua kampus (permintaan user).
        $always = ['registration_fee_payment' => [false, null], 'deposit_fee_china' => [false, null]];

        foreach ($this->campuses() as $name => $rows) {
            $key = self::key($name);
            $university = collect([$key, ...(self::ALIASES[$key] ?? [])])->map(fn ($k) => $universities->get($k))->filter()->first();

            if (! $university) {
                $this->command?->warn("Belum ketemu di data kampus: {$name} (atur manual di halaman edit University).");

                continue;
            }

            if ($university->documentRequirements()->exists()) {
                $this->command?->line("Dilewati, sudah diatur: {$university->name}");

                continue;
            }

            foreach ($rows + $always as $code => $row) {
                if (! $types->has($code)) {
                    continue;
                }

                $university->documentRequirements()->create([
                    'document_type_id' => $types[$code],
                    'is_required' => $row[0],
                    'note' => $row[1],
                    'degree_scope' => $row[2] ?? null,
                    'language_scope' => $row[3] ?? null,
                ]);
            }

            $this->command?->info("Diatur: {$university->name} (".count($rows + $always).' dokumen)');
        }
    }
}
