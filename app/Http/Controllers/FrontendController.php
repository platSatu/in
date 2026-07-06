<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\FormQuestion;
use App\Models\FormQuestionOption;
use App\Models\FormSubmission;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\University;
use App\Models\UniversityProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FrontendController extends Controller
{
    public function index()
    {
        return view('frontend.index');
    }

    /**
     * Show University Profile Page
     */
    public function universityProfile($id)
    {
        $university = University::findOrFail($id);
        $profile = UniversityProfile::where('university_id', $id)
            ->where('status', 'active')
            ->firstOrFail();

        return view('frontend.university-profile', compact('university', 'profile'));
    }

    /**
     * Form Wizard - Show form selection or direct to wizard
     */
    public function formWizard(Request $request)
    {
        $formId = $request->query('form_id');

        // Get all available forms
        $forms = Form::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        // If specific form selected, get its questions
        $selectedForm = null;
        $questions = [];

        if ($formId) {
            $selectedForm = Form::find($formId);
            if ($selectedForm) {
                $questions = FormQuestion::where('form_id', $formId)
                    ->where('status', 'active')
                    ->with('options')
                    ->orderBy('order')
                    ->get();
            }
        }

        return view('frontend.form-wizard', compact('forms', 'selectedForm', 'questions'));
    }

    /**
     * Form Wizard - Submit form submission
     */
    public function formWizardSubmit(Request $request)
    {
        $validated = $request->validate([
            'form_id' => 'required|exists:forms,id',
            'name' => 'required|string|max:255',
            'handphone' => 'required|string|max:20',
        ]);

        // Create or find user by phone (for tracking)
        $user = User::where('handphone', $validated['handphone'])->first();

        if (!$user) {
            // Create temporary user for this submission
            // User model has booted() method that auto-generates UUID
            $user = User::create([
                'name' => $validated['name'],
                'handphone' => $validated['handphone'],
                'email' => $validated['handphone'] . '@temp.local',
                'password' => Hash::make(Str::random(16)),
            ]);
        }

        // Get form for message building
        $form = Form::find($validated['form_id']);

        // Create submission record
        // FormSubmission model uses HasUuids trait, so UUID will be auto-generated
        $submission = FormSubmission::create([
            'user_id' => $user->id,
            'form_id' => $validated['form_id'],
            'status' => 'active',
        ]);

        // Get form questions
        $questions = FormQuestion::where('form_id', $validated['form_id'])
            ->where('status', 'active')
            ->with('options')
            ->orderBy('order')
            ->get();

        // Build answers summary for WhatsApp message
        $answersSummary = [];
        $questionNumber = 1;

        // Process each question answer
        foreach ($questions as $question) {
            $questionKey = 'question_' . $question->id;
            $answerValue = null;

            if ($question->type === 'text' || $question->type === 'number') {
                $answerText = $request->input($questionKey);
                $answerValue = $answerText ?: '-';

                if ($answerText) {
                    FormAnswer::create([
                        'user_id' => $user->id,
                        'submission_id' => $submission->id,
                        'question_id' => $question->id,
                        'option_id' => null,
                        'answer_text' => $answerText,
                        'status' => 'active',
                    ]);
                }
            } elseif ($question->type === 'single_choice') {
                $optionId = $request->input($questionKey);

                if ($optionId) {
                    $option = FormQuestionOption::find($optionId);
                    $answerValue = $option ? $option->option_text : '-';

                    FormAnswer::create([
                        'user_id' => $user->id,
                        'submission_id' => $submission->id,
                        'question_id' => $question->id,
                        'option_id' => $optionId,
                        'answer_text' => null,
                        'status' => 'active',
                    ]);
                } else {
                    $answerValue = '-';
                }
            } elseif ($question->type === 'multiple_choice') {
                $optionIds = $request->input($questionKey, []);
                $selectedOptions = [];

                foreach ($optionIds as $optionId) {
                    $option = FormQuestionOption::find($optionId);
                    if ($option) {
                        $selectedOptions[] = $option->option_text;

                        FormAnswer::create([
                            'user_id' => $user->id,
                            'submission_id' => $submission->id,
                            'question_id' => $question->id,
                            'option_id' => $optionId,
                            'answer_text' => null,
                            'status' => 'active',
                        ]);
                    }
                }

                $answerValue = !empty($selectedOptions) ? implode(', ', $selectedOptions) : '-';
            }

            $answersSummary[] = "{$questionNumber}. {$question->question_text}\n   Jawaban: {$answerValue}";
            $questionNumber++;
        }

        // Build WhatsApp message
        $message = "Halo {$user->name},\n\n";
        $message .= "Terima kasih telah mengisi formulir \"{$form->name}\".\n\n";
        $message .= "*Ringkasan Jawaban:*\n";
        $message .= implode("\n", $answersSummary) . "\n\n";
$message .= "Hasil Anda sudah kami terima. Terima kasih! 😊";

        // Save user preferences from answers
        $userAnswers = FormAnswer::where('submission_id', $submission->id)
            ->with(['question', 'option'])
            ->get();
        $preferences = $this->saveUserPreferences($user, $userAnswers);

        // Get university recommendations
        $recommendedUniversities = $this->matchUniversities($preferences, 5);

        // Build university recommendations message
        $recommendationsMessage = $this->buildRecommendationsMessage($recommendedUniversities, $preferences);

        // Append recommendations to message
        $message .= $recommendationsMessage;

        // Send WhatsApp message
        $this->sendWhatsapp($user->handphone, $message);

        return redirect()
            ->route('frontend.form.wizard')
            ->with('success', 'Terima kasih! Formulir berhasil disubmit.');
    }

    /**
     * Send WhatsApp message using Wablas API
     */
    private function sendWhatsapp($phone, $message)
    {
        try {
            // Clean phone number (remove all non-digits except +)
            $phone = preg_replace('/[^0-9+]/', '', $phone);

            // If phone starts with +62, replace with 62
            if (str_starts_with($phone, '+62')) {
                $phone = '62' . substr($phone, 3);
            } elseif (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }

            $response = Http::withHeaders([
                'Authorization' => env('WABLAS_TOKEN') . '.' . env('WABLAS_SECRET'),
                'Content-Type' => 'application/json',
            ])->post('https://smg.wablas.com/api/v2/send-message', [
                'data' => [
                    [
                        'phone' => $phone,
                        'message' => $message,
                    ]
                ]
            ]);

            // Log response
            Log::info('Wablas Response - Form Wizard', [
                'phone' => $phone,
                'body' => $response->json(),
            ]);

            return $response->json();

} catch (\Exception $e) {
            Log::error('Wablas Error - Form Wizard', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Save user preferences from quiz answers
     */
    private function saveUserPreferences($user, $answers)
    {
        $preferences = [
            'user_id' => $user->id,
            'status' => 'active',
        ];

        // Map question IDs to preference fields (you can adjust these IDs based on your form structure)
        $questionMapping = [
            'field_of_study' => [], // Multiple choice question IDs for field
            'min_budget' => [],     // Number question IDs for min budget
            'max_budget' => [],     // Number question IDs for max budget
            'preferred_language' => [], // Single choice question IDs for language
            'scholarship_needed' => [], // Single choice question IDs for scholarship
            'country' => [],        // Single choice question IDs for country
        ];

// Default keywords to match with question text
        $keywordMapping = [
            'interest' => 'field_of_study',
            'bidang' => 'field_of_study',
            'jurusan' => 'field_of_study',
            'major' => 'field_of_study',
            'study' => 'field_of_study',
            'budget' => 'min_budget',
            'anggaran' => 'min_budget',
            'biaya' => 'min_budget',
            'prioritas' => 'scholarship_needed',
            'beasiswa' => 'scholarship_needed',
            'scholarship' => 'scholarship_needed',
            'bahasa' => 'preferred_language',
            'language' => 'preferred_language',
            'mandarin' => 'preferred_language',
            'negara' => 'country',
            'country' => 'country',
            'sertifikat' => 'has_certificate',
            'certificate' => 'has_certificate',
        ];

// Process each answer and map to preferences
        foreach ($answers as $answer) {
            $question = $answer->question;
            if (!$question) continue;

            $questionText = strtolower($question->question_text);
            
// Find matching field based on keywords
            foreach ($keywordMapping as $keyword => $field) {
                if (strpos($questionText, $keyword) !== false) {
                    if ($question->type === 'single_choice' && $answer->option) {
                        $optionText = $answer->option->option_text;
                        $preferences[$field] = $optionText;
                        
                        // Convert scholarship to boolean
                        if ($field === 'scholarship_needed') {
                            $preferences[$field] = stripos($optionText, 'beasiswa') !== false || 
                                               stripos($optionText, 'ya') !== false ||
                                               stripos($optionText, 'yes') !== false;
                        }
                    } elseif ($question->type === 'multiple_choice' && $answer->answer_text) {
                        $preferences[$field] = $answer->answer_text;
                    } elseif (in_array($question->type, ['text', 'number']) && $answer->answer_text) {
                        // For budget fields, handle range or single value
                        if (in_array($field, ['min_budget', 'max_budget'])) {
                            $answerText = $answer->answer_text;
                            
                            // Check if it's a range like "10 - 20" or "10-20"
                            if (preg_match('/(\d+)\s*[-–]\s*(\d+)/', $answerText, $matches)) {
                                // It's a range
                                if ($field === 'min_budget') {
                                    $preferences['min_budget'] = (int) $matches[1];
                                    $preferences['max_budget'] = (int) $matches[2];
                                }
                            } else {
                                // Try to extract just numbers
                                $numbers = preg_replace('/[^0-9]/', '', $answerText);
                                $preferences[$field] = !empty($numbers) ? (int) $numbers : null;
                            }
                        } else {
                            $preferences[$field] = $answer->answer_text;
                        }
                    }
                    break;
                }
            }
        }

// Ensure numeric fields are properly cast to integers
        if (isset($preferences['min_budget']) && !is_int($preferences['min_budget'])) {
            $preferences['min_budget'] = is_numeric($preferences['min_budget']) ? (int) $preferences['min_budget'] : null;
        }
        if (isset($preferences['max_budget']) && !is_int($preferences['max_budget'])) {
            $preferences['max_budget'] = is_numeric($preferences['max_budget']) ? (int) $preferences['max_budget'] : null;
        }

        // Create preferences
        return UserPreference::create($preferences);
    }

/**
     * Match universities based on user preferences
     */
    private function matchUniversities($preferences, $limit = 5)
    {
        // Log preferences for debugging
        Log::info('User Preferences for matching', [
            'field_of_study' => $preferences->field_of_study,
            'min_budget' => $preferences->min_budget,
            'max_budget' => $preferences->max_budget,
            'preferred_language' => $preferences->preferred_language,
            'scholarship_needed' => $preferences->scholarship_needed,
            'country' => $preferences->country,
        ]);

// Get all active universities first (flexible matching)
        $allUniversities = UniversityProfile::with('university')
            ->where('status', 'active')
            ->get();

        Log::info('Total universities in database', [
            'count' => $allUniversities->count(),
            'sample_fields' => $allUniversities->take(3)->pluck('field')->toArray(),
            'sample_budgets' => $allUniversities->take(3)->map(function($u) {
                return ['min' => $u->min_budget, 'max' => $u->max_budget];
            })->toArray(),
        ]);

        // If no universities at all, return empty
        if ($allUniversities->isEmpty()) {
            return [];
        }

        // Calculate match percentage and sort
        $results = [];
        foreach ($allUniversities as $profile) {
            $score = $this->calculateMatchScore($preferences, $profile);
            
            $results[] = [
                'profile' => $profile,
                'university' => $profile->university,
                'match_percentage' => $score,
                'field' => $profile->field,
                'budget_range' => $this->formatBudgetRange($profile),
                'language' => $profile->language,
                'scholarship' => $profile->scholarship_available ? 'Ya' : 'Tidak',
                'min_budget' => $profile->min_budget,
                'max_budget' => $profile->max_budget,
            ];
        }

        // Sort by match percentage (highest first)
        usort($results, function ($a, $b) {
            return $b['match_percentage'] - $a['match_percentage'];
        });

// Return top matches with score > 0, or at least some results
        $matchedResults = array_filter($results, function ($r) {
            return $r['match_percentage'] > 0;
        });

        // If still empty or no matches, return all universities (for demo purposes)
        // This ensures we always show some results if universities exist
        if (empty($matchedResults) && count($results) > 0) {
            Log::info('No matches found, returning all universities');
            $matchedResults = $results;
        }

        return array_slice(array_values($matchedResults), 0, $limit);
    }

/**
     * Calculate match score between user preference and university profile
     */
    private function calculateMatchScore($preferences, $profile)
    {
        $score = 0;
        $totalWeight = 0;
        
        // Get preference values
        $prefField = $preferences->field_of_study ?? '';
        $prefMinBudget = $preferences->min_budget ?? 0;
        $prefMaxBudget = $preferences->max_budget ?? 0;
        $prefLanguage = $preferences->preferred_language ?? '';
        $prefScholarship = $preferences->scholarship_needed ?? false;

        // Field match (40%)
        if (!empty($prefField)) {
            $profileField = strtolower($profile->field ?? '');
            $searchField = strtolower($prefField);
            
            // Check for partial match
            if (stripos($profileField, $searchField) !== false || 
                stripos($searchField, $profileField) !== false) {
                $score += 40;
            }
            // Also check if field contains keywords like "engineering"
            $engineeringKeywords = ['engineering', 'teknik', 'tech', 'science', 'informatics', 'computer'];
            foreach ($engineeringKeywords as $keyword) {
                if (stripos($profileField, $keyword) !== false && stripos($searchField, $keyword) !== false) {
                    $score += 40;
                    break;
                }
            }
        } else {
            // No field preference, give default points
            $score += 40;
        }
        $totalWeight += 40;

        // Budget match (30%)
        if ($prefMinBudget > 0 || $prefMaxBudget > 0) {
            $profileMin = $profile->min_budget ?? 0;
            $profileMax = $profile->max_budget ?? 0;
            
            // Check if ranges overlap
            if ($profileMin <= $prefMaxBudget && $profileMax >= $prefMinBudget) {
                $score += 30;
            }
            // Handle case where profile has no budget data - give partial point
            if ($profileMin == 0 && $profileMax == 0) {
                $score += 15;
            }
        } else {
            // No budget preference
            $score += 30;
        }
        $totalWeight += 30;

        // Language match (15%)
        if (!empty($prefLanguage)) {
            $profileLanguage = strtolower($profile->language ?? '');
            $searchLanguage = strtolower($prefLanguage);
            
            if (stripos($profileLanguage, $searchLanguage) !== false || 
                stripos($searchLanguage, $profileLanguage) !== false) {
                $score += 15;
            }
            // Special case: "belum bisa" or "tidak bisa" means user doesn't need specific language
            if (stripos($searchLanguage, 'belum') !== false || stripos($searchLanguage, 'tidak') !== false) {
                $score += 15; // Give points as any language is acceptable
            }
        } else {
            $score += 15;
        }
        $totalWeight += 15;

        // Scholarship match (15%)
        if ($prefScholarship) {
            // User needs scholarship - only match if profile has scholarship
            if ($profile->scholarship_available) {
                $score += 15;
            }
        } else {
            // User doesn't specify scholarship need, any is fine
            $score += 15;
        }
        $totalWeight += 15;

        return $score;
    }

    /**
     * Format budget range for display
     */
    private function formatBudgetRange($profile)
    {
        $min = $profile->min_budget ?? 0;
        $max = $profile->max_budget ?? 0;
        
        if ($min > 0 && $max > 0) {
            return 'Rp ' . number_format($min, 0, ',', '.') . ' - Rp ' . number_format($max, 0, ',', '.');
        } elseif ($min > 0) {
            return 'Rp ' . number_format($min, 0, ',', '.') . '+';
        } elseif ($max > 0) {
            return 'Up to Rp ' . number_format($max, 0, ',', '.');
        }
        
        return 'Hubungi kami';
    }

    /**
     * Build university recommendations message for WhatsApp
     */
    private function buildRecommendationsMessage($recommendedUniversities, $preferences)
    {
        if (empty($recommendedUniversities)) {
            return "\n\n📚 *Rekomendasi Kampus:*\n" .
                   "Maaf, tidak ada kampus yang cocok dengan kriteria Anda saat ini.\n" .
                   "Tim kami akan menghubungi Anda untuk konsultasi lebih lanjut.";
        }

        $message = "\n\n🎓 *Rekomendasi Kampus:*\n";
        $message .= "Berdasarkan preferensi Anda, berikut kampus yang cocok:\n";
        
        foreach ($recommendedUniversities as $index => $uni) {
            $no = $index + 1;
            $name = $uni['university']->name ?? 'Unknown';
            $country = $uni['university']->country ?? '';
            $match = $uni['match_percentage'];
            $field = $uni['field'];
            $budget = $uni['budget_range'];
            $scholarship = $uni['scholarship'];
            
            $message .= "\n{$no}. {$name} ({$country})";
            $message .= "\n   📊 Kecocokan: {$match}%";
            $message .= "\n   📚 Bidang: {$field}";
            $message .= "\n   💰 Budget: {$budget}";
            $message .= "\n   🎁 Beasiswa: {$scholarship}";
            
            // Add profile link if available
            if (isset($uni['university']->id)) {
                $message .= "\n   🔗 Lihat profil: " . route('frontend.university.profile', $uni['university']->id);
            }
        }

        $message .= "\n\n💬 Klik link di atas untuk informasi lebih lengkap!";
        
        return $message;
    }
}
