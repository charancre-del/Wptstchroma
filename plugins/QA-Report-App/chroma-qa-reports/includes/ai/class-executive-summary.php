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

        if (\is_wp_error($executive_result)) {
            return new \WP_Error(
                'ai_executive_failed',
                __('AI executive summary generation failed. Please try again.', 'chroma-qa-reports'),
                ['status' => 422]
            );
        }

        $executive_text = is_array($executive_result) ? trim((string) ($executive_result['executive_summary'] ?? '')) : '';
        if ($executive_text === '') {
            return new \WP_Error(
                'ai_executive_empty',
                __('AI executive summary returned an empty response. Please try again.', 'chroma-qa-reports'),
                ['status' => 422]
            );
        }

        if (\is_wp_error($poi_result)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[CQA DEBUG] POI generation failed: ' . $poi_result->get_error_message());
            }
            return new \WP_Error(
                'ai_poi_failed',
                __('AI plan of improvement generation failed. Please try again.', 'chroma-qa-reports'),
                ['status' => 422]
            );
        }

        $poi_items = [];
        if (is_array($poi_result)) {
            $poi_items = $poi_result['plan_of_improvement'] ?? $poi_result['poi'] ?? $poi_result['support_and_growth_plan'] ?? [];
        }

        $poi_validation_error = '';
        $poi_items = $this->normalize_poi_items($poi_items, $poi_validation_error);
        if (empty($poi_items)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[CQA DEBUG] POI validation produced zero valid items: ' . $poi_validation_error);
            }
            return new \WP_Error(
                'ai_poi_empty',
                __('AI plan of improvement returned no items. Please try again.', 'chroma-qa-reports'),
                ['status' => 422]
            );
        }

        // Normalize POI keys for downstream consumers/storage compatibility.
        $poi_result['plan_of_improvement'] = $poi_items;
        $poi_result['poi'] = $poi_items;

        // Merge results (use empty arrays for failed parts)
        $result = array_merge(
            is_array($executive_result) ? $executive_result : [],
            is_array($poi_result) ? $poi_result : []
        );

        // Save the merged summary to database
        $this->save_summary($report->id, $result);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[CQA DEBUG] AI summary generated report_id=%d poi_items=%d executive_chars=%d',
                (int) $report->id,
                count($poi_items),
                strlen($executive_text)
            ));
        }

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
            'schema' => $this->get_executive_schema(),
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
        $prompt .= "Generate a focused, highly detailed JSON response for the PLAN OF IMPROVEMENT only.\n";
        $prompt .= "Focus exclusively on items with 'SOMETIMES' or 'NO' ratings. Be specific to the inspection observations.\n\n";

        $prompt .= "{\n";
        $prompt .= '  "plan_of_improvement": [';
        $prompt .= "\n";
        $prompt .= '    { ';
        $prompt .= '"priority": 1, '; // Priority 1 = Critical/Safety, 2 = Important, 3 = Improvement
        $prompt .= '"area": "Specific Item or Category Name", ';
        $prompt .= '"current_status": "Detailed explanation of what was observed (e.g., \"Found expired milk in the fridge and missing temperature logs for the last 3 days\")", ';
        $prompt .= '"action_steps": ["Specific step 1", "Specific step 2", "Specific step 3"], ';
        $prompt .= '"timeline": "Within 24 hours|Within 7 days|By next visit", ';
        $prompt .= '"success_criteria": "Clear measurement of success", ';
        $prompt .= '"support_offered": "Specific resources or training provided" ';
        $prompt .= "}\n";
        $prompt .= "  ],\n";

        if ($previous_report) {
            $prompt .= '  "comparison": {';
            $prompt .= "\n";
            $prompt .= '    "celebrations": ["1-3 items improved since last visit"],';
            $prompt .= "\n";
            $prompt .= '    "regressions": ["items that have declined, if any"],';
            $prompt .= "\n";
            $prompt .= '    "focus_areas": ["recurring items needing continued focus"]';
            $prompt .= "\n";
            $prompt .= "  },\n";
        }

        $prompt .= '  "recommendations": ["3-5 high-level strategic recommendations for the Director"]';
        $prompt .= "\n";
        $prompt .= "}\n\n";

        $prompt .= "CRITICAL REQUIREMENTS:\n";
        $prompt .= "1. 'current_status' MUST describe the specific observation from the notes/checklist.\n";
        $prompt .= "2. 'action_steps' MUST be a list of 2-4 concrete, numbered steps the director should take.\n";
        $prompt .= "3. DO NOT use vague categories like 'Safety Protocols' alone; use specific areas like 'Medication Storage' or 'Staff Files'.\n";
        $prompt .= "4. PRIORITIZE safety and health issues (NO ratings) as Priority 1.\n";
        $prompt .= "5. Maintain a supportive, coaching tone - use 'we' and 'our' where appropriate.\n";

        return Gemini_Service::generate_json($prompt, [
            'temperature' => 0.4,
            'maxTokens' => 10000,
            'schema' => $this->get_poi_schema((bool) $previous_report),
        ]);
    }

    /**
     * Lightweight schema for executive summary generation.
     *
     * @return array
     */
    private function get_executive_schema()
    {
        return [
            'type' => 'object',
            'required' => ['executive_summary'],
            'properties' => [
                'executive_summary' => ['type' => 'string', 'minLength' => 20],
                'strengths' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'areas_of_concern' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'severity' => ['type' => 'string'],
                            'section' => ['type' => 'string'],
                            'item' => ['type' => 'string'],
                            'observation' => ['type' => 'string'],
                            'coaching_note' => ['type' => 'string'],
                        ],
                    ],
                ],
                'suggested_rating' => ['type' => 'string'],
                'key_findings' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];
    }

    /**
     * Lightweight schema for POI generation.
     *
     * @param bool $include_comparison Whether comparison object is expected.
     * @return array
     */
    private function get_poi_schema($include_comparison = false)
    {
        $schema = [
            'type' => 'object',
            'required' => ['plan_of_improvement'],
            'properties' => [
                'plan_of_improvement' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'items' => [
                        'type' => 'object',
                        'required' => ['area'],
                        'properties' => [
                            'priority' => ['type' => 'integer'],
                            'area' => ['type' => 'string', 'minLength' => 2],
                            'current_status' => ['type' => 'string'],
                            'action_steps' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                            'timeline' => ['type' => 'string'],
                            'success_criteria' => ['type' => 'string'],
                            'support_offered' => ['type' => 'string'],
                        ],
                    ],
                ],
                'recommendations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];

        if ($include_comparison) {
            $schema['properties']['comparison'] = [
                'type' => 'object',
                'properties' => [
                    'celebrations' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'regressions' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'focus_areas' => ['type' => 'array', 'items' => ['type' => 'string']],
                ],
            ];
        }

        return $schema;
    }

    /**
     * Normalize POI items to a stable structure and filter invalid entries.
     *
     * @param mixed  $items Raw POI items.
     * @param string $error Validation details for debug logs.
     * @return array
     */
    private function normalize_poi_items($items, &$error = '')
    {
        $error = '';
        if (!is_array($items)) {
            $error = 'POI payload is not an array.';
            return [];
        }

        $normalized = [];
        $skipped = 0;

        foreach ($items as $idx => $item) {
            // Accept string POI entries from model fallbacks.
            if (is_string($item)) {
                $text_item = \sanitize_textarea_field($item);
                if ($text_item === '') {
                    $skipped++;
                    continue;
                }
                $normalized[] = [
                    'priority' => 3,
                    'area' => 'General Improvement',
                    'current_status' => '',
                    'action_steps' => [$text_item],
                    'timeline' => '',
                    'success_criteria' => '',
                    'support_offered' => '',
                    'action' => $text_item,
                ];
                continue;
            }

            if (!is_array($item)) {
                $skipped++;
                continue;
            }

            $area = \sanitize_text_field((string) ($item['area'] ?? $item['section'] ?? $item['item'] ?? $item['title'] ?? $item['category'] ?? ''));
            $current_status = \sanitize_textarea_field((string) ($item['current_status'] ?? $item['observation'] ?? ''));
            $timeline = \sanitize_text_field((string) ($item['timeline'] ?? ''));
            $success_criteria = \sanitize_textarea_field((string) ($item['success_criteria'] ?? ''));
            $support_offered = \sanitize_textarea_field((string) ($item['support_offered'] ?? ''));

            $priority_raw = $item['priority'] ?? 3;
            $priority = is_numeric($priority_raw) ? (int) $priority_raw : \sanitize_text_field((string) $priority_raw);

            $action_steps = [];
            if (!empty($item['action_steps']) && is_array($item['action_steps'])) {
                foreach ($item['action_steps'] as $step) {
                    $step_text = \sanitize_textarea_field((string) $step);
                    if ($step_text !== '') {
                        $action_steps[] = $step_text;
                    }
                }
            } elseif (!empty($item['steps']) && is_array($item['steps'])) {
                foreach ($item['steps'] as $step) {
                    $step_text = \sanitize_textarea_field((string) $step);
                    if ($step_text !== '') {
                        $action_steps[] = $step_text;
                    }
                }
            } elseif (!empty($item['recommendations']) && is_array($item['recommendations'])) {
                foreach ($item['recommendations'] as $step) {
                    $step_text = \sanitize_textarea_field((string) $step);
                    if ($step_text !== '') {
                        $action_steps[] = $step_text;
                    }
                }
            } elseif (!empty($item['action']) && is_string($item['action'])) {
                $fallback_action = \sanitize_textarea_field($item['action']);
                if ($fallback_action !== '') {
                    $action_steps[] = $fallback_action;
                }
            } elseif (!empty($item['recommendation']) && is_string($item['recommendation'])) {
                $fallback_action = \sanitize_textarea_field($item['recommendation']);
                if ($fallback_action !== '') {
                    $action_steps[] = $fallback_action;
                }
            }

            // Final fallback: if model gave useful narrative but no explicit steps, keep item.
            if (empty($action_steps)) {
                $fallback_step = '';
                if ($current_status !== '') {
                    $fallback_step = $current_status;
                } elseif ($success_criteria !== '') {
                    $fallback_step = $success_criteria;
                } elseif ($support_offered !== '') {
                    $fallback_step = $support_offered;
                }

                if ($fallback_step !== '') {
                    $action_steps[] = $fallback_step;
                }
            }

            if ($area === '') {
                $area = 'General Improvement';
            }

            if (empty($action_steps)) {
                $skipped++;
                continue;
            }

            $normalized[] = [
                'priority' => $priority,
                'area' => $area,
                'current_status' => $current_status,
                'action_steps' => $action_steps,
                'timeline' => $timeline,
                'success_criteria' => $success_criteria,
                'support_offered' => $support_offered,
                // Backward-compatible single action field for older templates.
                'action' => $action_steps[0],
            ];
        }

        if ($skipped > 0) {
            $error = sprintf('Skipped %d invalid POI item(s).', (int) $skipped);
        }

        return $normalized;
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
