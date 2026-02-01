<?php
/**
 * AI Executive Summary Generator
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\AI;

use ChromaQA\Models\Report;
use ChromaQA\Models\Checklist_Response;
use ChromaQA\Checklists\Checklist_Manager;

/**
 * Generates AI-powered executive summaries for QA reports.
 */
class Executive_Summary
{
    /**
     * Generate executive summary for a report.
     *
     * @param Report $report The report to summarize.
     * @return array|WP_Error
     */
    public function generate(Report $report)
    {
        if (!Gemini_Service::is_configured()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[CQA DEBUG] AI Summary failed: Gemini Service is not configured.');
            }
            return new \WP_Error(
                'not_configured',
                __('AI features are not configured. Please enter your Gemini API key in Settings.', 'chroma-qa-reports'),
                ['status' => 400]
            );
        }

        $school = $report->get_school();
        $responses = Checklist_Response::get_by_report_grouped($report->id);
        $previous_report = $report->get_previous_report();
        $checklist = Checklist_Manager::get_checklist_for_type($report->report_type);
        $stats = Checklist_Manager::get_progress_stats($report->id, $report->report_type);
        $photos = $report->get_photos();

        // Build context data once (shared between both prompts)
        $context = $this->build_context($school, $report, $responses, $previous_report, $checklist, $stats, $photos);

        // Call 1: Generate Executive Summary (narrative + analysis)
        $executive_result = $this->generate_executive_part($context, $previous_report);

        // Call 2: Generate Plan of Improvement (action items)
        $poi_result = $this->generate_poi_part($context, $previous_report);

        // Handle errors
        $errors = [];
        if (\is_wp_error($executive_result)) {
            $errors[] = 'Executive: ' . $executive_result->get_error_message();
        }
        if (\is_wp_error($poi_result)) {
            $errors[] = 'POI: ' . $poi_result->get_error_message();
        }

        // If both failed, return error
        if (count($errors) === 2) {
            return new \WP_Error('ai_generation_failed', implode(' | ', $errors));
        }

        // Merge results (use empty arrays for failed parts)
        $result = array_merge(
            is_array($executive_result) ? $executive_result : [],
            is_array($poi_result) ? $poi_result : []
        );

        // Save the merged summary to database
        $this->save_summary($report->id, $result);

        return $result;
    }

    /**
     * Generate the executive summary part (narrative + analysis).
     *
     * @param array $context Shared context data.
     * @param Report|null $previous_report Previous report for comparison.
     * @return array|WP_Error
     */
    private function generate_executive_part($context, $previous_report)
    {
        $prompt = $context['base_prompt'];

        $prompt .= "## Instructions\n";
        $prompt .= "Generate a focused JSON response for the EXECUTIVE SUMMARY portion only.\n\n";
        $prompt .= "{\n";
        $prompt .= '  "executive_summary": "A comprehensive 3-4 paragraph professional summary. Start by celebrating specific successes. Then provide analysis of compliance status. Finally, bridge into growth areas with supportive coaching language.",';
        $prompt .= "\n";
        $prompt .= '  "strengths": ["List 5-8 specific strengths observed, referencing actual checklist items"],';
        $prompt .= "\n";
        $prompt .= '  "areas_of_concern": [';
        $prompt .= "\n";
        $prompt .= '    { "severity": "high|medium|low", "section": "section name", "item": "specific item", "observation": "what was observed", "coaching_note": "supportive improvement guidance" }';
        $prompt .= "\n";
        $prompt .= "  ],\n";
        $prompt .= '  "suggested_rating": "exceeds|meets|needs_improvement",';
        $prompt .= "\n";
        $prompt .= '  "key_findings": ["3-5 most important takeaways"]';
        $prompt .= "\n";
        $prompt .= "}\n\n";

        $prompt .= "IMPORTANT: Be thorough but focused. Reference specific checklist items. Celebrate wins first.\n";

        return Gemini_Service::generate_json($prompt, [
            'temperature' => 0.4,
            'maxTokens' => 3000,  // Executive summary is narrative, needs less tokens
        ]);
    }

    /**
     * Generate the Plan of Improvement part (action items).
     *
     * @param array $context Shared context data.
     * @param Report|null $previous_report Previous report for comparison.
     * @return array|WP_Error
     */
    private function generate_poi_part($context, $previous_report)
    {
        $prompt = $context['base_prompt'];

        $prompt .= "## Instructions\n";
        $prompt .= "Generate a focused JSON response for the PLAN OF IMPROVEMENT only.\n";
        $prompt .= "Focus on actionable items based on areas that need work (SOMETIMES or NO ratings).\n\n";
        $prompt .= "{\n";
        $prompt .= '  "plan_of_improvement": [';
        $prompt .= "\n";
        $prompt .= '    { "priority": 1, "area": "specific section/area", "current_status": "what needs improvement", "action_steps": ["step 1", "step 2", "step 3"], "timeline": "specific timeframe (e.g., Within 7 days)", "success_criteria": "how we will know this is resolved", "support_offered": "resources or help available" }';
        $prompt .= "\n";
        $prompt .= "  ],\n";

        if ($previous_report) {
            $prompt .= '  "comparison": {';
            $prompt .= "\n";
            $prompt .= '    "celebrations": ["list of improved items since last visit"],';
            $prompt .= "\n";
            $prompt .= '    "regressions": ["items that have declined, if any"],';
            $prompt .= "\n";
            $prompt .= '    "focus_areas": ["recurring items needing continued focus"]';
            $prompt .= "\n";
            $prompt .= "  },\n";
        }

        $prompt .= '  "recommendations": ["3-5 specific recommendations for the Director"]';
        $prompt .= "\n";
        $prompt .= "}\n\n";

        $prompt .= "IMPORTANT:\n";
        $prompt .= "1. Each POI item must have 2-4 concrete, actionable steps.\n";
        $prompt .= "2. Safety/health issues (NO ratings) are HIGH priority and need immediate action.\n";
        $prompt .= "3. Timelines should be realistic and specific.\n";
        $prompt .= "4. Maintain a supportive, coaching tone - we are partners in success.\n";

        return Gemini_Service::generate_json($prompt, [
            'temperature' => 0.4,
            'maxTokens' => 5000,  // POI needs more tokens for action steps, timelines, etc.
        ]);
    }

    /**
     * Build the shared context for both prompts.
     *
     * @param object $school School object.
     * @param Report $report Report object.
     * @param array  $responses Checklist responses.
     * @param Report|null $previous_report Previous report for comparison.
     * @param array  $checklist Checklist definition.
     * @param array  $stats Stats summary.
     * @param array  $photos Photo objects with captions.
     * @return array Context with base_prompt.
     */
    private function build_context($school, $report, $responses, $previous_report, $checklist, $stats, $photos = [])
    {
        $school_name = $school ? $school->name : 'Unknown School';
        $report_type = $report->get_type_label();
        $date = \date_i18n('F j, Y', \strtotime($report->inspection_date));

        $prompt = "You are a Supportive QA Coach and Mentor for Chroma Early Learning Academy, a childcare organization. ";
        $prompt .= "Your goal is to provide constructive, encouraging feedback that empowers School Directors.\n\n";

        $prompt .= "## Report Information\n";
        $prompt .= "- School: {$school_name}\n";
        $prompt .= "- Report Type: {$report_type}\n";
        $prompt .= "- Inspection Date: {$date}\n\n";

        $prompt .= "## Summary Statistics\n";
        $prompt .= "- Total Items: {$stats['total']}\n";
        $prompt .= "- Completed: {$stats['completed']} ({$stats['percentage']}%)\n";
        $prompt .= "- Yes/Compliant: {$stats['yes']}\n";
        $prompt .= "- Needs Work: {$stats['sometimes']}\n";
        $prompt .= "- Non-Compliant: {$stats['no']}\n\n";

        $prompt .= "## Checklist Responses\n\n";

        foreach ($checklist['sections'] as $section) {
            $section_responses = $responses[$section['key']] ?? [];

            if (empty($section_responses)) {
                continue;
            }

            $prompt .= "### {$section['name']}\n";

            foreach ($section['items'] as $item) {
                if (isset($section_responses[$item['key']])) {
                    $response = $section_responses[$item['key']];
                    $rating = strtoupper($response->rating);
                    $notes = $response->notes ? " - Notes: {$response->notes}" : '';
                    $prompt .= "- [{$rating}] {$item['label']}{$notes}\n";
                }
            }

            $prompt .= "\n";
        }

        // Add photo comments if any
        if (!empty($photos)) {
            $prompt .= "## Photo Evidence & Comments\n";
            foreach ($photos as $photo) {
                $caption = $photo->caption ?? '';
                $section = $photo->section_key ?? 'general';
                if (!empty($caption)) {
                    $prompt .= "- [{$section}] {$caption}\n";
                }
            }
            $prompt .= "\n";
        }

        if ($previous_report) {
            $prompt .= "## Comparison with Previous Report\n";
            $prompt .= "This report is compared to a previous inspection from {$previous_report->inspection_date}.\n\n";
        }

        return [
            'base_prompt' => $prompt,
        ];
    }

    /**
     * Build the AI prompt (legacy - kept for compatibility).
     *
     * @param object $school School object.
     * @param Report $report Report object.
     * @param array  $responses Checklist responses.
     * @param Report|null $previous_report Previous report for comparison.
     * @param array  $checklist Checklist definition.
     * @param array  $stats Stats summary.
     * @param array  $photos Photo objects with captions.
     * @param bool   $concise Whether to request a more concise response.
     * @return string
     */
    private function build_prompt($school, $report, $responses, $previous_report, $checklist, $stats, $photos = [], $concise = false)
    {
        $context = $this->build_context($school, $report, $responses, $previous_report, $checklist, $stats, $photos);
        return $context['base_prompt'];
    }


    /**
     * Save the summary to database.
     *
     * @param int   $report_id Report ID.
     * @param array $summary Summary data.
     */
    public function save_summary($report_id, $summary)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cqa_ai_summaries';

        // Start transaction for atomicity
        $wpdb->query('START TRANSACTION');

        try {
            // Delete existing summary if any
            $deleted = $wpdb->delete($table, ['report_id' => $report_id], ['%d']);

            if ($deleted === false) {
                throw new \Exception('Failed to clear existing summary.');
            }

            // Insert new summary
            $inserted = $wpdb->insert(
                $table,
                [
                    'report_id' => $report_id,
                    'executive_summary' => $summary['executive_summary'] ?? '',
                    'issues_json' => \wp_json_encode($summary['issues'] ?? $summary['growth_opportunities'] ?? []),
                    'poi_json' => \wp_json_encode($summary['poi'] ?? $summary['support_and_growth_plan'] ?? $summary['plan_of_improvement'] ?? []),
                    'comparison_json' => \wp_json_encode($summary['comparison'] ?? []),
                    'generated_at' => \current_time('mysql'),
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s']
            );

            if ($inserted === false) {
                throw new \Exception('Failed to insert new summary.');
            }

            $wpdb->query('COMMIT');
            return true;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Executive_Summary::save_summary failure: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate smart note suggestion based on rating.
     *
     * @param string $section_key Section key.
     * @param string $item_key Item key.
     * @param string $item_label Item human-readable label.
     * @param string $rating Selected rating (no, sometimes).
     * @return array Suggestion data.
     */
    public static function get_note_suggestion($section_key, $item_key, $item_label, $rating)
    {
        // Common suggestions based on patterns
        $suggestions = self::get_predefined_suggestions();

        // Check for exact match
        $key = $section_key . '/' . $item_key;
        if (isset($suggestions[$key]) && isset($suggestions[$key][$rating])) {
            return [
                'suggestion' => $suggestions[$key][$rating],
                'source' => 'predefined',
            ];
        }

        // Try AI-powered suggestion
        if (Gemini_Service::is_configured()) {
            return self::generate_ai_suggestion($item_label, $rating, $section_key);
        }

        // Fallback generic suggestions
        return self::get_generic_suggestion($item_label, $rating);
    }

    /**
     * Get predefined suggestions for common items.
     *
     * @return array Suggestions map.
     */
    private static function get_predefined_suggestions()
    {
        return [
            'health_safety/fire_extinguisher' => [
                'no' => 'Fire extinguisher needs to be serviced/replaced. Schedule inspection by [DATE]. Ensure annual maintenance tag is current.',
                'sometimes' => 'Fire extinguisher present but needs attention. Check expiration date and ensure monthly visual inspection log is maintained.',
            ],
            'health_safety/fire_drills' => [
                'no' => 'Fire drills not conducted as required. Schedule monthly fire drills and ensure one nap time drill per year. Maintain drill log with dates and times.',
                'sometimes' => 'Fire drills being conducted but not consistently documented. Ensure drill log captures all required information.',
            ],
            'health_safety/allergy_list' => [
                'no' => 'Allergy list not posted or not current. Update immediately and post in all classrooms and kitchen. Ensure all staff are aware.',
                'sometimes' => 'Allergy list present but needs updating. Verify all enrolled children with allergies are listed with current information.',
            ],
            'classroom/handwashing' => [
                'no' => 'Proper handwashing procedures not being followed. Retrain staff on proper technique. Post handwashing posters at all sinks.',
                'sometimes' => 'Handwashing procedures inconsistent. Observe and coach staff on key times (before meals, after diaper changes, etc.).',
            ],
            'classroom/diapering' => [
                'no' => 'Diapering procedures not being followed correctly. Review proper steps with staff. Ensure gloves and sanitizer are accessible at changing tables.',
                'sometimes' => 'Diapering procedure needs improvement. Observe staff and provide coaching on proper sequence and sanitization.',
            ],
            'playground/fall_zone' => [
                'no' => 'Fall zone does not meet requirements. Add mulch to achieve required depth (9" wood mulch or 6" rubber). Extend fall zone perimeter.',
                'sometimes' => 'Fall zone needs attention in some areas. Check depth and coverage, particularly under high-use equipment.',
            ],
            'playground/equipment_condition' => [
                'no' => 'Equipment has safety concerns (rust, sharp edges, loose parts). Remove from use until repaired or replaced.',
                'sometimes' => 'Some equipment needs maintenance. Schedule repairs and document completion.',
            ],
            'kitchen/food_storage' => [
                'no' => 'Food storage not meeting standards. Label all items with date and contents. Ensure FIFO rotation. Check refrigerator temperatures daily.',
                'sometimes' => 'Food storage mostly compliant but some items need proper labeling or dating.',
            ],
            'building/cleanliness' => [
                'no' => 'Facility cleanliness below standards. Deep clean required. Review and strengthen cleaning checklists and routines.',
                'sometimes' => 'Some areas need additional cleaning attention. Focus on high-touch surfaces and bathroom areas.',
            ],
            'lobby/front_desk' => [
                'no' => 'Front desk not consistently staffed. Ensure coverage during all operating hours for security and customer service.',
                'sometimes' => 'Front desk coverage intermittent. Review schedule and provide backup coverage plan.',
            ],
        ];
    }

    /**
     * Generate AI-powered suggestion.
     *
     * @param string $item_label Item label.
     * @param string $rating Rating.
     * @param string $section_key Section key.
     * @return array Suggestion.
     */
    private static function generate_ai_suggestion($item_label, $rating, $section_key)
    {
        $prompt = sprintf(
            'You are a supportive QA Coach for a childcare facility. A checklist item "%s" in the "%s" section was marked as "%s". ' .
            'Generate a brief, professional, and encouraging coaching note (1-2 sentences) for the School Director. ' .
            'Explain the growth opportunity and recommend a helpful action step. ' .
            'The tone should be supportive, not punitive. Return only the note text, no other formatting.',
            $item_label,
            str_replace('_', ' ', $section_key),
            $rating === 'no' ? 'needs focus/No' : 'partially meeting standards/Sometimes'
        );

        $result = Gemini_Service::generate($prompt, ['temperature' => 0.3, 'maxTokens' => 200]);

        if (is_wp_error($result)) {
            return self::get_generic_suggestion($item_label, $rating);
        }

        return [
            'suggestion' => trim($result['text'] ?? ''),
            'source' => 'ai',
        ];
    }

    /**
     * Get generic suggestion.
     *
     * @param string $item_label Item label.
     * @param string $rating Rating.
     * @return array Suggestion.
     */
    private static function get_generic_suggestion($item_label, $rating)
    {
        if ($rating === 'no') {
            $template = 'Item "%s" is not meeting standards. Immediate action required to bring into compliance. Follow up within [X] days.';
        } else {
            $template = 'Item "%s" needs improvement. While partially meeting standards, additional attention is recommended.';
        }

        return [
            'suggestion' => sprintf($template, $item_label),
            'source' => 'generic',
        ];
    }
}
