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
        $checklist_lookup = $this->build_checklist_lookup($checklist);
        $warnings = [];

        // Build context data once (shared between both prompts)
        $context = $this->build_context($school, $report, $responses, $previous_report, $checklist, $stats, $photos);

        // Call 1: Generate Executive Summary (narrative + analysis)
        $executive_result = $this->generate_executive_part($context, $previous_report);

        if (\is_wp_error($executive_result)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[CQA DEBUG] Executive summary generation failed, using fallback: ' . $executive_result->get_error_message());
            }
            $warnings[] = __('The executive summary was completed using checklist-based fallback guidance because the AI response could not be parsed cleanly.', 'chroma-qa-reports');
            $executive_result = $this->build_fallback_executive_result($school, $report, $responses, $stats, $previous_report, $checklist_lookup);
        }

        $executive_text = is_array($executive_result) ? trim((string) ($executive_result['executive_summary'] ?? '')) : '';
        if ($executive_text === '') {
            $warnings[] = __('The executive summary was completed using checklist-based fallback guidance because the AI response came back empty.', 'chroma-qa-reports');
            $executive_result = $this->build_fallback_executive_result($school, $report, $responses, $stats, $previous_report, $checklist_lookup);
            $executive_text = is_array($executive_result) ? trim((string) ($executive_result['executive_summary'] ?? '')) : '';
        }

        if ($executive_text === '') {
            return new \WP_Error(
                'ai_executive_failed',
                __('AI executive summary generation failed and no fallback summary could be prepared.', 'chroma-qa-reports'),
                ['status' => 422]
            );
        }

        // Call 2: Generate Plan of Improvement (action items)
        $poi_result = $this->generate_poi_part($school, $report, $responses, $stats, $previous_report, $checklist_lookup);

        if (\is_wp_error($poi_result)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[CQA DEBUG] POI generation failed, using fallback: ' . $poi_result->get_error_message());
            }
            $warnings[] = __('The plan of improvement was prepared from checklist responses because the AI action plan response was malformed.', 'chroma-qa-reports');
            $poi_result = $this->build_fallback_poi_result($responses, $stats, $previous_report, $checklist_lookup);
        }

        $poi_items = [];
        if (is_array($poi_result)) {
            $poi_items = $poi_result['plan_of_improvement'] ?? $poi_result['poi'] ?? $poi_result['support_and_growth_plan'] ?? [];
        }

        $poi_validation_error = '';
        $poi_items = $this->normalize_poi_items($poi_items, $poi_validation_error);
        $needs_growth_plan = ((int) ($stats['sometimes'] ?? 0) + (int) ($stats['no'] ?? 0)) > 0;
        if (empty($poi_items) && $needs_growth_plan) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[CQA DEBUG] POI validation produced zero valid items, using fallback: ' . $poi_validation_error);
            }
            $warnings[] = __('The plan of improvement was rebuilt from the checklist because the AI action items were incomplete.', 'chroma-qa-reports');
            $poi_result = $this->build_fallback_poi_result($responses, $stats, $previous_report, $checklist_lookup);
            $poi_items = $this->normalize_poi_items($poi_result['plan_of_improvement'] ?? [], $poi_validation_error);
        }

        // Normalize POI keys for downstream consumers/storage compatibility.
        if (!is_array($poi_result)) {
            $poi_result = [];
        }
        $warnings = array_merge($warnings, $this->extract_generation_warnings($executive_result), $this->extract_generation_warnings($poi_result));
        $poi_result['plan_of_improvement'] = $poi_items;
        $poi_result['poi'] = $poi_items;
        if (empty($poi_result['recommendations']) || !is_array($poi_result['recommendations'])) {
            $poi_result['recommendations'] = $this->build_fallback_recommendations($poi_items, $stats);
        }
        if ($previous_report && (empty($poi_result['comparison']) || !is_array($poi_result['comparison']))) {
            $poi_result['comparison'] = $this->build_fallback_comparison($responses, $previous_report, $checklist_lookup);
        }

        // Merge results (use empty arrays for failed parts)
        $result = array_merge(
            is_array($executive_result) ? $executive_result : [],
            is_array($poi_result) ? $poi_result : []
        );
        if (!empty($warnings)) {
            $result['generation_warnings'] = array_values(array_unique($warnings));
        }

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
     * @param object|null $school School model.
     * @param Report $report Current report.
     * @param array $responses Grouped checklist responses.
     * @param array $stats Checklist stats.
     * @param Report|null $previous_report Previous report for comparison.
     * @param array $checklist_lookup Checklist lookup metadata.
     * @return array|WP_Error
     */
    private function generate_poi_part($school, $report, $responses, $stats, $previous_report, $checklist_lookup)
    {
        $findings = array_values(array_filter(
            $this->collect_response_findings($responses, $checklist_lookup),
            function ($finding) {
                return in_array($finding['rating'], ['no', 'sometimes'], true);
            }
        ));

        if (empty($findings)) {
            return $this->build_fallback_poi_result($responses, $stats, $previous_report, $checklist_lookup);
        }

        $section_groups = $this->group_findings_by_section($findings);
        $section_items = [];
        $section_warnings = [];

        foreach ($section_groups as $section_group) {
            $warning = '';
            $items = $this->generate_section_poi_items(
                $school,
                $report,
                $section_group,
                $stats,
                $previous_report,
                $warning
            );

            if (!empty($warning)) {
                $section_warnings[] = $warning;
            }

            if (!empty($items)) {
                $section_items[$section_group['section_key']] = $items;
            }
        }

        $merged_items = $this->merge_section_poi_items($section_items, $findings);

        if (empty($merged_items)) {
            $fallback = $this->build_fallback_poi_result($responses, $stats, $previous_report, $checklist_lookup);
            if (!empty($section_warnings)) {
                $fallback['generation_warnings'] = array_values(array_unique(array_merge(
                    $fallback['generation_warnings'] ?? [],
                    $section_warnings,
                    [__('The section-based POI pipeline fell back to checklist-derived action items because no valid AI section plans were returned.', 'chroma-qa-reports')]
                )));
            }
            return $fallback;
        }

        $result = [
            'plan_of_improvement' => $merged_items,
            'poi' => $merged_items,
            'recommendations' => $this->build_fallback_recommendations($merged_items, $stats),
            'comparison' => $previous_report ? $this->build_fallback_comparison($responses, $previous_report, $checklist_lookup) : [],
        ];

        if (!empty($section_warnings)) {
            $result['generation_warnings'] = array_values(array_unique($section_warnings));
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[CQA DEBUG] POI generated by section report_id=%d sections=%d merged_items=%d',
                (int) $report->id,
                count($section_groups),
                count($merged_items)
            ));
        }

        return $result;
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
    private function get_poi_schema($include_comparison = false, $max_items = null, $require_traceability = false)
    {
        $required_fields = ['priority', 'area', 'current_status', 'action_steps', 'timeline', 'success_criteria', 'support_offered'];
        if ($require_traceability) {
            array_unshift($required_fields, 'section_key', 'item_key');
        }

        $schema = [
            'type' => 'object',
            'required' => ['plan_of_improvement'],
            'properties' => [
                'plan_of_improvement' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => $required_fields,
                        'properties' => [
                            'priority' => ['type' => 'integer'],
                            'section_key' => ['type' => 'string', 'minLength' => 2],
                            'item_key' => ['type' => 'string', 'minLength' => 2],
                            'root_key' => ['type' => 'string'],
                            'area' => ['type' => 'string', 'minLength' => 2],
                            'current_status' => ['type' => 'string', 'minLength' => 10],
                            'action_steps' => [
                                'type' => 'array',
                                'minItems' => 1,
                                'maxItems' => 3,
                                'items' => ['type' => 'string'],
                            ],
                            'timeline' => ['type' => 'string', 'minLength' => 3],
                            'success_criteria' => ['type' => 'string', 'minLength' => 10],
                            'support_offered' => ['type' => 'string', 'minLength' => 10],
                            'source_sections' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                            'source_items' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
                'recommendations' => [
                    'type' => 'array',
                    'maxItems' => 3,
                    'items' => ['type' => 'string'],
                ],
            ],
        ];

        if ($include_comparison) {
            $schema['properties']['comparison'] = [
                'type' => 'object',
                'properties' => [
                    'celebrations' => ['type' => 'array', 'maxItems' => 2, 'items' => ['type' => 'string']],
                    'regressions' => ['type' => 'array', 'maxItems' => 2, 'items' => ['type' => 'string']],
                    'focus_areas' => ['type' => 'array', 'maxItems' => 2, 'items' => ['type' => 'string']],
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
                'section_key' => \sanitize_text_field((string) ($item['section_key'] ?? '')),
                'item_key' => \sanitize_text_field((string) ($item['item_key'] ?? '')),
                'root_key' => \sanitize_text_field((string) ($item['root_key'] ?? '')),
                'area' => $area,
                'current_status' => $current_status,
                'action_steps' => $action_steps,
                'timeline' => $timeline,
                'success_criteria' => $success_criteria,
                'support_offered' => $support_offered,
                'source_sections' => $this->normalize_traceability_list($item['source_sections'] ?? []),
                'source_items' => $this->normalize_traceability_list($item['source_items'] ?? []),
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
     * Build a quick lookup table for checklist metadata.
     *
     * @param array $checklist Checklist definition.
     * @return array
     */
    private function build_checklist_lookup($checklist)
    {
        $lookup = [];

        if (!is_array($checklist) || empty($checklist['sections']) || !is_array($checklist['sections'])) {
            return $lookup;
        }

        foreach ($checklist['sections'] as $section) {
            $section_key = (string) ($section['key'] ?? '');
            if ($section_key === '') {
                continue;
            }

            $section_name = (string) ($section['name'] ?? $this->humanize_key($section_key));
            $items = isset($section['items']) && is_array($section['items']) ? $section['items'] : [];

            foreach ($items as $item) {
                $item_key = (string) ($item['key'] ?? '');
                if ($item_key === '') {
                    continue;
                }

                $lookup[$section_key . '/' . $item_key] = [
                    'section_key' => $section_key,
                    'section_name' => $section_name,
                    'item_key' => $item_key,
                    'item_label' => (string) ($item['label'] ?? $this->humanize_key($item_key)),
                    'tier' => (int) ($item['tier'] ?? $section['tier'] ?? 1),
                    'entry_mode' => (string) ($item['entry_mode'] ?? 'standalone'),
                    'root_key' => (string) ($item['root_key'] ?? ($section_key . '/' . $item_key)),
                    'shared_with' => !empty($item['shared_with']) && is_array($item['shared_with'])
                        ? $item['shared_with']
                        : null,
                ];
            }
        }

        return $lookup;
    }

    /**
     * Collect normalized findings from report responses.
     *
     * @param array $responses Grouped responses.
     * @param array $checklist_lookup Checklist lookup metadata.
     * @return array
     */
    private function collect_response_findings($responses, $checklist_lookup)
    {
        $findings = [];
        $exact_duplicate_index = [];

        if (!is_array($responses)) {
            return $findings;
        }

        foreach ($responses as $section_key => $items) {
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $item_key => $response) {
                $rating = strtolower(trim((string) (is_object($response) ? ($response->rating ?? '') : ($response['rating'] ?? ''))));
                if ($rating === '' || $rating === 'na') {
                    continue;
                }

                $notes = trim((string) (is_object($response) ? ($response->notes ?? '') : ($response['notes'] ?? '')));
                $previous_rating = strtolower(trim((string) (is_object($response) ? ($response->previous_rating ?? '') : ($response['previous_rating'] ?? ''))));
                $previous_notes = trim((string) (is_object($response) ? ($response->previous_notes ?? '') : ($response['previous_notes'] ?? '')));
                $meta = $checklist_lookup[$section_key . '/' . $item_key] ?? [
                    'section_key' => $section_key,
                    'section_name' => $this->humanize_key($section_key),
                    'item_key' => $item_key,
                    'item_label' => $this->humanize_key($item_key),
                    'tier' => 1,
                    'entry_mode' => 'standalone',
                    'root_key' => $section_key . '/' . $item_key,
                    'shared_with' => null,
                ];

                $finding = [
                    'section_key' => $section_key,
                    'section_name' => $meta['section_name'],
                    'item_key' => $item_key,
                    'item_label' => $meta['item_label'],
                    'rating' => $rating,
                    'notes' => $notes,
                    'previous_rating' => $previous_rating,
                    'previous_notes' => $previous_notes,
                    'priority' => $this->determine_priority($section_key, $rating),
                    'tier' => (int) ($meta['tier'] ?? 1),
                    'entry_mode' => (string) ($meta['entry_mode'] ?? 'standalone'),
                    'root_key' => (string) ($meta['root_key'] ?? ($section_key . '/' . $item_key)),
                    'shared_with' => $meta['shared_with'] ?? null,
                ];

                if ($finding['entry_mode'] === 'shared_exact') {
                    $dedupe_key = $finding['root_key'];
                    if (isset($exact_duplicate_index[$dedupe_key])) {
                        $existing_index = $exact_duplicate_index[$dedupe_key];
                        $existing = $findings[$existing_index];

                        if (strlen($finding['notes']) > strlen($existing['notes'])) {
                            $findings[$existing_index]['notes'] = $finding['notes'];
                        }

                        if (strlen($finding['previous_notes']) > strlen($existing['previous_notes'])) {
                            $findings[$existing_index]['previous_notes'] = $finding['previous_notes'];
                        }

                        $findings[$existing_index]['priority'] = min(
                            (int) $existing['priority'],
                            (int) $finding['priority']
                        );
                        continue;
                    }

                    $exact_duplicate_index[$dedupe_key] = count($findings);
                }

                $findings[] = $finding;
            }
        }

        usort($findings, function ($left, $right) {
            if ($left['priority'] !== $right['priority']) {
                return $left['priority'] <=> $right['priority'];
            }

            if ($left['rating'] !== $right['rating']) {
                return $this->rating_weight($left['rating']) <=> $this->rating_weight($right['rating']);
            }

            return strcmp($left['item_label'], $right['item_label']);
        });

        return $findings;
    }

    /**
     * Build a compact POI-only prompt from non-compliant findings.
     *
     * @param object|null $school School model.
     * @param Report $report Current report.
     * @param array $findings Non-compliant findings only.
     * @param array $stats Checklist stats.
     * @param Report|null $previous_report Previous report model.
     * @return string
     */
    private function build_section_poi_prompt($school, $report, $section_group, $findings, $stats, $previous_report)
    {
        $school_name = $school ? $school->name : 'Unknown School';
        $report_type = $report->get_type_label();
        $date = \date_i18n('F j, Y', \strtotime($report->inspection_date));
        $section_name = $section_group['section_name'];
        $section_key = $section_group['section_key'];

        $prompt = "You are a supportive QA Coach for a childcare organization.\n";
        $prompt .= "Return ONLY valid JSON for a concise Plan of Improvement for ONE report section.\n\n";
        $prompt .= "## Visit Snapshot\n";
        $prompt .= "- School: {$school_name}\n";
        $prompt .= "- Report Type: {$report_type}\n";
        $prompt .= "- Inspection Date: {$date}\n";
        $prompt .= "- Focus Section: {$section_name} ({$section_key})\n";
        $prompt .= '- Items marked SOMETIMES: ' . (int) ($stats['sometimes'] ?? 0) . "\n";
        $prompt .= '- Items marked NO: ' . (int) ($stats['no'] ?? 0) . "\n\n";
        $prompt .= "## Section Findings Requiring Action\n";

        foreach ($findings as $index => $finding) {
            $observation = $finding['notes'] !== ''
                ? $finding['notes']
                : sprintf('%s was marked as %s.', $finding['item_label'], strtoupper($finding['rating']));

            $prompt .= sprintf(
                "%d. Priority %d | [%s] %s > %s (%s/%s)\n",
                $index + 1,
                (int) $finding['priority'],
                strtoupper($finding['rating']),
                $finding['section_name'],
                $finding['item_label'],
                $finding['section_key'],
                $finding['item_key']
            );
            $prompt .= '   Observation: ' . $observation . "\n";

            if (($finding['entry_mode'] ?? '') === 'linked_refinement' && !empty($finding['shared_with']['label'])) {
                $prompt .= '   Tier 2 refinement of: ' . $finding['shared_with']['label'] . "\n";
            }

            if (!empty($finding['previous_rating'])) {
                $prompt .= '   Previous visit rating: ' . strtoupper($finding['previous_rating']) . "\n";
            }
        }

        $prompt .= "\n## Output Requirements\n";
        $prompt .= "- Create one plan_of_improvement item for every actionable finding below unless two findings are clearly the same operational issue.\n";
        $prompt .= "- Stay inside the focus section only. Do not mention other sections.\n";
        $prompt .= "- Every POI item must include section_key exactly as {$section_key}.\n";
        $prompt .= "- Every POI item must include item_key matching one of the findings above.\n";
        $prompt .= "- When a finding is marked as a Tier 2 refinement of a Tier 1 baseline, build on the deeper Tier 2 expectation instead of repeating the baseline wording.\n";
        $prompt .= "- current_status must be one concise sentence grounded only in the provided observations.\n";
        $prompt .= "- action_steps must contain 2 or 3 short, concrete steps.\n";
        $prompt .= "- timeline must be one of: Within 24 hours, Within 7 days, By next visit.\n";

        $prompt .= "- Keep wording supportive and specific. Do not add commentary outside JSON.\n\n";
        $prompt .= "{\n";
        $prompt .= '  "plan_of_improvement": [' . "\n";
        $prompt .= '    {' . "\n";
        $prompt .= '      "priority": 1,' . "\n";
        $prompt .= '      "section_key": "' . $section_key . '",' . "\n";
        $prompt .= '      "item_key": "specific_item_key",' . "\n";
        $prompt .= '      "area": "Specific item name",' . "\n";
        $prompt .= '      "current_status": "One concise sentence describing the current observation.",' . "\n";
        $prompt .= '      "action_steps": ["Concrete step 1", "Concrete step 2"],' . "\n";
        $prompt .= '      "timeline": "Within 24 hours",' . "\n";
        $prompt .= '      "success_criteria": "What success looks like at the next check.",' . "\n";
        $prompt .= '      "support_offered": "Coaching, tools, or follow-up support available."' . "\n";
        $prompt .= '    }' . "\n";
        $prompt .= '  ]' . "\n";
        $prompt .= "}\n";

        return $prompt;
    }

    /**
     * Build a deterministic executive summary when AI JSON fails.
     *
     * @param object|null $school School model.
     * @param Report      $report Current report.
     * @param array       $responses Grouped responses.
     * @param array       $stats Checklist stats.
     * @param Report|null $previous_report Previous report model.
     * @param array       $checklist_lookup Checklist lookup metadata.
     * @return array
     */
    private function build_fallback_executive_result($school, $report, $responses, $stats, $previous_report, $checklist_lookup)
    {
        $findings = $this->collect_response_findings($responses, $checklist_lookup);
        $strengths = [];
        $concerns = [];

        foreach ($findings as $finding) {
            if ($finding['rating'] === 'yes' && count($strengths) < 5) {
                $strengths[] = $finding['item_label'];
            }

            if (in_array($finding['rating'], ['no', 'sometimes'], true) && count($concerns) < 5) {
                $concerns[] = [
                    'severity' => $finding['priority'] <= 2 ? 'high' : 'medium',
                    'section' => $finding['section_name'],
                    'item' => $finding['item_label'],
                    'observation' => $finding['notes'] !== ''
                        ? $finding['notes']
                        : sprintf('%s was marked as %s during this visit.', $finding['item_label'], strtoupper($finding['rating'])),
                    'coaching_note' => sprintf(
                        __('Review expectations for %s with the team and confirm the correction is visible in daily practice.', 'chroma-qa-reports'),
                        $finding['item_label']
                    ),
                ];
            }
        }

        $school_name = $school ? $school->name : __('this school', 'chroma-qa-reports');
        $report_type = $report->get_type_label();
        $completed = (int) ($stats['completed'] ?? 0);
        $total = (int) ($stats['total'] ?? 0);
        $yes = (int) ($stats['yes'] ?? 0);
        $sometimes = (int) ($stats['sometimes'] ?? 0);
        $no = (int) ($stats['no'] ?? 0);

        $highlighted_strengths = $this->format_label_list($strengths, 3);
        $priority_labels = $this->format_label_list(array_map(function ($finding) {
            return $finding['item'];
        }, $concerns), 3);

        $paragraphs = [];
        $paragraphs[] = sprintf(
            __('%1$s completed a %2$s QA visit on %3$s. %4$d of %5$d checklist items were completed, with %6$d items meeting expectations. %7$s', 'chroma-qa-reports'),
            $school_name,
            $report_type,
            \date_i18n('F j, Y', \strtotime($report->inspection_date)),
            $completed,
            $total,
            $yes,
            $highlighted_strengths !== ''
                ? sprintf(__('Observed strengths included %s.', 'chroma-qa-reports'), $highlighted_strengths)
                : __('The visit showed several routines that are already supporting stable compliance.', 'chroma-qa-reports')
        );

        if ($sometimes > 0 || $no > 0) {
            $paragraphs[] = sprintf(
                __('The main coaching opportunities centered on %1$d items marked Sometimes and %2$d items marked No. Priority follow-up should focus on %3$s so corrections are visible, documented, and consistent from room to room.', 'chroma-qa-reports'),
                $sometimes,
                $no,
                $priority_labels !== '' ? $priority_labels : __('the items listed in the improvement plan', 'chroma-qa-reports')
            );
        } else {
            $paragraphs[] = __('No major compliance gaps were identified in the completed responses. The next step is to keep strong routines in place, maintain documentation, and continue internal spot-checks between visits.', 'chroma-qa-reports');
        }

        if ($previous_report) {
            $comparison = $this->build_fallback_comparison($responses, $previous_report, $checklist_lookup);
            $celebrations = $this->format_label_list($comparison['celebrations'] ?? [], 2);
            $focus_areas = $this->format_label_list($comparison['focus_areas'] ?? [], 2);

            if ($celebrations !== '' || $focus_areas !== '') {
                $paragraphs[] = sprintf(
                    __('Compared with the previous visit on %1$s, there are clear opportunities to build on progress%2$s%3$s.', 'chroma-qa-reports'),
                    \date_i18n('F j, Y', \strtotime($previous_report->inspection_date)),
                    $celebrations !== '' ? sprintf(__(' in areas such as %s', 'chroma-qa-reports'), $celebrations) : '',
                    $focus_areas !== '' ? sprintf(__(' while continuing close attention to %s', 'chroma-qa-reports'), $focus_areas) : ''
                );
            }
        }

        $paragraphs[] = __('With targeted follow-through, clear ownership, and supportive coaching around the items below, the site is well positioned to strengthen consistency before the next QA review.', 'chroma-qa-reports');

        return [
            'executive_summary' => implode("\n\n", array_filter($paragraphs)),
            'strengths' => $strengths,
            'areas_of_concern' => $concerns,
            'suggested_rating' => $this->derive_suggested_rating($report, $stats),
            'key_findings' => $this->build_fallback_key_findings($stats, $strengths, $concerns),
        ];
    }

    /**
     * Build deterministic POI content when AI JSON fails.
     *
     * @param array       $responses Grouped responses.
     * @param array       $stats Checklist stats.
     * @param Report|null $previous_report Previous report model.
     * @param array       $checklist_lookup Checklist lookup metadata.
     * @return array
     */
    private function build_fallback_poi_result($responses, $stats, $previous_report, $checklist_lookup)
    {
        $findings = array_values(array_filter(
            $this->collect_response_findings($responses, $checklist_lookup),
            function ($finding) {
                return in_array($finding['rating'], ['no', 'sometimes'], true);
            }
        ));

        $poi_items = [];
        foreach ($findings as $finding) {
            $poi_items[] = $this->build_poi_item_from_finding($finding);
        }

        if (empty($poi_items) && ((int) ($stats['sometimes'] ?? 0) + (int) ($stats['no'] ?? 0)) === 0) {
            return [
                'plan_of_improvement' => [],
                'poi' => [],
                'recommendations' => [
                    __('Continue current monitoring routines and keep documentation up to date for the next QA visit.', 'chroma-qa-reports'),
                ],
                'comparison' => $previous_report ? $this->build_fallback_comparison($responses, $previous_report, $checklist_lookup) : [],
            ];
        }

        return [
            'plan_of_improvement' => $poi_items,
            'poi' => $poi_items,
            'recommendations' => $this->build_fallback_recommendations($poi_items, $stats),
            'comparison' => $previous_report ? $this->build_fallback_comparison($responses, $previous_report, $checklist_lookup) : [],
        ];
    }

    /**
     * Extract generation warnings from a result payload.
     *
     * @param mixed $result Result payload.
     * @return array
     */
    private function extract_generation_warnings($result)
    {
        if (!is_array($result) || empty($result['generation_warnings']) || !is_array($result['generation_warnings'])) {
            return [];
        }

        return array_values(array_filter(array_map(function ($warning) {
            return trim((string) $warning);
        }, $result['generation_warnings'])));
    }

    /**
     * Group actionable findings by section so AI can work in smaller chunks.
     *
     * @param array $findings Actionable findings.
     * @return array
     */
    private function group_findings_by_section($findings)
    {
        $groups = [];

        foreach ($findings as $finding) {
            $section_key = (string) ($finding['section_key'] ?? 'general');
            if (!isset($groups[$section_key])) {
                $groups[$section_key] = [
                    'section_key' => $section_key,
                    'section_name' => (string) ($finding['section_name'] ?? $this->humanize_key($section_key)),
                    'findings' => [],
                    'top_priority' => 99,
                ];
            }

            $groups[$section_key]['findings'][] = $finding;
            $groups[$section_key]['top_priority'] = min($groups[$section_key]['top_priority'], (int) ($finding['priority'] ?? 4));
        }

        uasort($groups, function ($left, $right) {
            if ($left['top_priority'] !== $right['top_priority']) {
                return $left['top_priority'] <=> $right['top_priority'];
            }

            if (count($left['findings']) !== count($right['findings'])) {
                return count($right['findings']) <=> count($left['findings']);
            }

            return strcmp($left['section_name'], $right['section_name']);
        });

        return array_values($groups);
    }

    /**
     * Generate POI items for a single section with fallback protection.
     *
     * @param object|null $school School model.
     * @param Report $report Current report.
     * @param array $section_group Section findings bundle.
     * @param array $stats Checklist stats.
     * @param Report|null $previous_report Previous report.
     * @param string $warning Warning output parameter.
     * @return array
     */
    private function generate_section_poi_items($school, $report, $section_group, $stats, $previous_report, &$warning = '')
    {
        $warning = '';
        $section_findings = array_values($section_group['findings']);
        $finding_chunks = array_chunk($section_findings, 5);
        $items = [];
        $chunk_warnings = [];

        foreach ($finding_chunks as $chunk_index => $finding_chunk) {
            $prompt = $this->build_section_poi_prompt(
                $school,
                $report,
                $section_group,
                $finding_chunk,
                $stats,
                $previous_report
            );

            $response = Gemini_Service::generate_json($prompt, [
                'temperature' => 0.2,
                'maxTokens' => 2400,
                'schema' => $this->get_poi_schema(false, null, true),
            ]);

            if (\is_wp_error($response)) {
                $chunk_warnings[] = sprintf(
                    __('The %1$s section batch %2$d used checklist-derived fallback items because the section AI response could not be parsed cleanly.', 'chroma-qa-reports'),
                    $section_group['section_name'],
                    $chunk_index + 1
                );
                $items = array_merge($items, $this->build_section_fallback_poi_items($finding_chunk));
                continue;
            }

            $validation_error = '';
            $chunk_items = $this->normalize_poi_items($response['plan_of_improvement'] ?? $response['poi'] ?? [], $validation_error);
            $chunk_items = $this->enrich_section_poi_items($chunk_items, $section_group, $finding_chunk);

            if (empty($chunk_items)) {
                $chunk_warnings[] = sprintf(
                    __('The %1$s section batch %2$d used checklist-derived fallback items because the AI action items were incomplete.', 'chroma-qa-reports'),
                    $section_group['section_name'],
                    $chunk_index + 1
                );
                $items = array_merge($items, $this->build_section_fallback_poi_items($finding_chunk));
                continue;
            }

            $items = array_merge($items, $chunk_items);
        }

        if (!empty($chunk_warnings)) {
            $warning = implode(' ', array_unique($chunk_warnings));
        }

        return $this->merge_poi_item_collection($items);
    }

    /**
     * Build deterministic section fallback items.
     *
     * @param array $findings Section findings.
     * @param int $max_items Max items to return.
     * @return array
     */
    private function build_section_fallback_poi_items($findings)
    {
        $items = [];
        foreach ($findings as $finding) {
            $items[] = $this->build_poi_item_from_finding($finding);
        }

        return $items;
    }

    /**
     * Build one deterministic POI item from a single finding.
     *
     * @param array $finding Finding metadata.
     * @return array
     */
    private function build_poi_item_from_finding($finding)
    {
        $current_status = $finding['notes'] !== ''
            ? $finding['notes']
            : sprintf('%s was marked as %s during this visit.', $finding['item_label'], strtoupper($finding['rating']));

        $action_steps = [
            $finding['priority'] === 1
                ? sprintf(__('Correct the %s concern immediately and verify the area is safe and compliant today.', 'chroma-qa-reports'), $finding['item_label'])
                : sprintf(__('Review the expectation for %s with the team and align on the daily standard to be followed.', 'chroma-qa-reports'), $finding['item_label']),
            $finding['notes'] !== ''
                ? __('Address the observation noted during the visit and document the correction in the site follow-up log.', 'chroma-qa-reports')
                : sprintf(__('Observe %s in practice and close any gaps in consistency or documentation.', 'chroma-qa-reports'), $finding['item_label']),
            sprintf(__('Recheck %s during the next leadership review and confirm the improvement is sustained.', 'chroma-qa-reports'), $finding['item_label']),
        ];

        return [
            'priority' => (int) $finding['priority'],
            'section_key' => (string) ($finding['section_key'] ?? ''),
            'item_key' => (string) ($finding['item_key'] ?? ''),
            'root_key' => (string) ($finding['root_key'] ?? (($finding['section_key'] ?? '') . '/' . ($finding['item_key'] ?? ''))),
            'area' => (string) ($finding['item_label'] ?? __('General Improvement', 'chroma-qa-reports')),
            'current_status' => $current_status,
            'action_steps' => $action_steps,
            'timeline' => $this->priority_timeline($finding['priority']),
            'success_criteria' => sprintf(
                __('%s is consistently compliant in practice and any related logs, materials, or documentation are complete and current.', 'chroma-qa-reports'),
                $finding['item_label']
            ),
            'support_offered' => __('We can review the expectation together, provide coaching feedback, and revisit the item during the next QA follow-up.', 'chroma-qa-reports'),
            'source_sections' => [(string) ($finding['section_key'] ?? '')],
            'source_items' => [(string) ($finding['section_key'] ?? '') . '/' . (string) ($finding['item_key'] ?? '')],
            'action' => $action_steps[0],
        ];
    }

    /**
     * Enrich model-generated section items with checklist-backed traceability.
     *
     * @param array $items Section POI items.
     * @param array $section_group Section metadata.
     * @param array $section_findings Findings within the section.
     * @return array
     */
    private function enrich_section_poi_items($items, $section_group, $section_findings)
    {
        $enriched = [];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $matched_finding = $this->match_finding_for_poi_item($item, $section_findings, $index);
            if (empty($matched_finding)) {
                continue;
            }

            $fallback_item = $this->build_poi_item_from_finding($matched_finding);
            $section_key = (string) ($item['section_key'] ?? $section_group['section_key'] ?? $matched_finding['section_key'] ?? '');
            if ($section_key === '') {
                $section_key = (string) ($matched_finding['section_key'] ?? '');
            }

            $item_key = (string) ($item['item_key'] ?? $matched_finding['item_key'] ?? '');
            $area = trim((string) ($item['area'] ?? ''));
            $current_status = trim((string) ($item['current_status'] ?? ''));
            $timeline = trim((string) ($item['timeline'] ?? ''));
            $success_criteria = trim((string) ($item['success_criteria'] ?? ''));
            $support_offered = trim((string) ($item['support_offered'] ?? ''));
            $priority = isset($item['priority']) && is_numeric($item['priority'])
                ? max(1, min(5, (int) $item['priority']))
                : (int) ($matched_finding['priority'] ?? 3);

            $action_steps = [];
            if (!empty($item['action_steps']) && is_array($item['action_steps'])) {
                foreach ($item['action_steps'] as $step) {
                    $step_text = \sanitize_textarea_field((string) $step);
                    if ($step_text !== '') {
                        $action_steps[] = $step_text;
                    }
                }
            }

            if (empty($action_steps) && !empty($fallback_item['action_steps'])) {
                $action_steps = $fallback_item['action_steps'];
            }

            $source_sections = $this->normalize_traceability_list(array_merge(
                [$section_key],
                $item['source_sections'] ?? [],
                $fallback_item['source_sections'] ?? []
            ));
            $source_items = $this->normalize_traceability_list(array_merge(
                [$section_key . '/' . $item_key],
                $item['source_items'] ?? [],
                $fallback_item['source_items'] ?? []
            ));

            $enriched[] = [
                'priority' => $priority,
                'section_key' => $section_key,
                'item_key' => $item_key,
                'root_key' => (string) ($matched_finding['root_key'] ?? $fallback_item['root_key'] ?? ($section_key . '/' . $item_key)),
                'area' => $area !== '' ? $area : $fallback_item['area'],
                'current_status' => $current_status !== '' ? $current_status : $fallback_item['current_status'],
                'action_steps' => array_slice(array_values(array_unique($action_steps)), 0, 3),
                'timeline' => $timeline !== '' ? $timeline : $fallback_item['timeline'],
                'success_criteria' => $success_criteria !== '' ? $success_criteria : $fallback_item['success_criteria'],
                'support_offered' => $support_offered !== '' ? $support_offered : $fallback_item['support_offered'],
                'source_sections' => $source_sections,
                'source_items' => $source_items,
                'action' => !empty($action_steps) ? $action_steps[0] : $fallback_item['action'],
            ];
        }

        return $enriched;
    }

    /**
     * Match a POI item back to the checklist finding it likely came from.
     *
     * @param array $item POI item candidate.
     * @param array $section_findings Findings available in the section.
     * @param int   $default_index Fallback finding index.
     * @return array|null
     */
    private function match_finding_for_poi_item($item, $section_findings, $default_index = 0)
    {
        if (empty($section_findings)) {
            return null;
        }

        $item_key = trim((string) ($item['item_key'] ?? ''));
        if ($item_key !== '') {
            foreach ($section_findings as $finding) {
                if ((string) ($finding['item_key'] ?? '') === $item_key) {
                    return $finding;
                }
            }
        }

        $area = strtolower(trim((string) ($item['area'] ?? '')));
        if ($area !== '') {
            foreach ($section_findings as $finding) {
                $label = strtolower(trim((string) ($finding['item_label'] ?? '')));
                if ($label !== '' && ($area === $label || strpos($area, $label) !== false || strpos($label, $area) !== false)) {
                    return $finding;
                }
            }
        }

        $notes = strtolower(trim((string) ($item['current_status'] ?? '')));
        if ($notes !== '') {
            foreach ($section_findings as $finding) {
                $finding_notes = strtolower(trim((string) ($finding['notes'] ?? '')));
                if ($finding_notes !== '' && (strpos($notes, $finding_notes) !== false || strpos($finding_notes, $notes) !== false)) {
                    return $finding;
                }
            }
        }

        $fallback_index = max(0, min((int) $default_index, count($section_findings) - 1));
        return $section_findings[$fallback_index] ?? $section_findings[0];
    }

    /**
     * Merge section outputs into a balanced, de-duplicated final POI list.
     *
     * @param array $section_items POI items keyed by section.
     * @param int   $max_items Maximum final POI items.
     * @return array
     */
    private function merge_section_poi_items($section_items, $all_findings = [])
    {
        $merged_items = [];

        foreach ($section_items as $items) {
            if (!is_array($items)) {
                continue;
            }

            $merged_items = array_merge($merged_items, $items);
        }

        $merged_items = $this->merge_poi_item_collection($merged_items);
        $merged_items = $this->ensure_poi_coverage($merged_items, $all_findings);

        return $this->merge_poi_item_collection($merged_items);
    }

    /**
     * Merge duplicate POI items while keeping every distinct action item.
     *
     * @param array $items POI items.
     * @return array
     */
    private function merge_poi_item_collection($items)
    {
        $merged = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $item['source_sections'] = $this->normalize_traceability_list($item['source_sections'] ?? []);
            $item['source_items'] = $this->normalize_traceability_list($item['source_items'] ?? []);
            $key = $this->canonical_poi_key($item);

            if (!isset($merged[$key])) {
                $merged[$key] = $item;
                continue;
            }

            $merged[$key] = $this->merge_poi_item_pair($merged[$key], $item);
        }

        return $this->sort_poi_items(array_values($merged));
    }

    /**
     * Ensure that every actionable finding is represented in the final POI output.
     *
     * @param array $items Final POI items.
     * @param array $all_findings Actionable findings that should be covered.
     * @return array
     */
    private function ensure_poi_coverage($items, $all_findings)
    {
        if (empty($all_findings)) {
            return $items;
        }

        $covered = [];
        foreach ($items as $item) {
            $root_key = trim((string) ($item['root_key'] ?? ''));
            if ($root_key !== '') {
                $covered[$root_key] = true;
            }

            foreach ($item['source_items'] ?? [] as $source_item) {
                $covered[(string) $source_item] = true;
            }

            $section_key = trim((string) ($item['section_key'] ?? ''));
            $item_key = trim((string) ($item['item_key'] ?? ''));
            if ($section_key !== '' && $item_key !== '') {
                $covered[$section_key . '/' . $item_key] = true;
            }
        }

        foreach ($all_findings as $finding) {
            $root_key = (string) ($finding['root_key'] ?? (($finding['section_key'] ?? '') . '/' . ($finding['item_key'] ?? '')));
            if ($root_key !== '' && isset($covered[$root_key])) {
                continue;
            }

            $items[] = $this->build_poi_item_from_finding($finding);
            if ($root_key !== '') {
                $covered[$root_key] = true;
            }
        }

        return $items;
    }

    /**
     * Build a stable merge key for a POI item.
     *
     * @param array $item POI item.
     * @return string
     */
    private function canonical_poi_key($item)
    {
        $root_key = strtolower(trim((string) ($item['root_key'] ?? '')));
        if ($root_key !== '') {
            return 'root:' . $root_key;
        }

        $source_items = $this->normalize_traceability_list($item['source_items'] ?? []);
        if (!empty($source_items)) {
            sort($source_items);
            return 'source:' . implode('|', $source_items);
        }

        $section_key = strtolower(trim((string) ($item['section_key'] ?? '')));
        $item_key = strtolower(trim((string) ($item['item_key'] ?? '')));
        if ($section_key !== '' && $item_key !== '') {
            return 'item:' . $section_key . '/' . $item_key;
        }

        $area = strtolower(trim((string) ($item['area'] ?? '')));
        $area = preg_replace('/[^a-z0-9]+/i', '-', $area);
        $area = trim((string) $area, '-');

        if ($area !== '') {
            return 'area:' . $area;
        }

        return 'poi:' . md5(\wp_json_encode($item));
    }

    /**
     * Merge two POI items that represent the same operational issue.
     *
     * @param array $left Existing POI item.
     * @param array $right Incoming POI item.
     * @return array
     */
    private function merge_poi_item_pair($left, $right)
    {
        $left_steps = !empty($left['action_steps']) && is_array($left['action_steps']) ? $left['action_steps'] : [];
        $right_steps = !empty($right['action_steps']) && is_array($right['action_steps']) ? $right['action_steps'] : [];
        $merged_steps = array_slice(array_values(array_unique(array_filter(array_merge($left_steps, $right_steps)))), 0, 3);

        $current_status = trim((string) ($left['current_status'] ?? ''));
        $right_current_status = trim((string) ($right['current_status'] ?? ''));
        if (strlen($right_current_status) > strlen($current_status)) {
            $current_status = $right_current_status;
        }

        $success_criteria = trim((string) ($left['success_criteria'] ?? ''));
        $right_success_criteria = trim((string) ($right['success_criteria'] ?? ''));
        if (strlen($right_success_criteria) > strlen($success_criteria)) {
            $success_criteria = $right_success_criteria;
        }

        $support_offered = trim((string) ($left['support_offered'] ?? ''));
        $right_support_offered = trim((string) ($right['support_offered'] ?? ''));
        if (strlen($right_support_offered) > strlen($support_offered)) {
            $support_offered = $right_support_offered;
        }

        return [
            'priority' => min((int) ($left['priority'] ?? 3), (int) ($right['priority'] ?? 3)),
            'section_key' => (string) ($left['section_key'] ?? $right['section_key'] ?? ''),
            'item_key' => (string) ($left['item_key'] ?? $right['item_key'] ?? ''),
            'root_key' => (string) ($left['root_key'] ?? $right['root_key'] ?? ''),
            'area' => strlen((string) ($right['area'] ?? '')) > strlen((string) ($left['area'] ?? ''))
                ? (string) $right['area']
                : (string) ($left['area'] ?? $right['area'] ?? ''),
            'current_status' => $current_status,
            'action_steps' => $merged_steps,
            'timeline' => (string) ($left['timeline'] ?? $right['timeline'] ?? ''),
            'success_criteria' => $success_criteria,
            'support_offered' => $support_offered,
            'source_sections' => $this->normalize_traceability_list(array_merge($left['source_sections'] ?? [], $right['source_sections'] ?? [])),
            'source_items' => $this->normalize_traceability_list(array_merge($left['source_items'] ?? [], $right['source_items'] ?? [])),
            'action' => !empty($merged_steps) ? $merged_steps[0] : (string) ($left['action'] ?? $right['action'] ?? ''),
        ];
    }

    /**
     * Sort POI items consistently without truncating the list.
     *
     * @param array $items Candidate POI items.
     * @return array
     */
    private function sort_poi_items($items)
    {
        $items = array_values(array_filter($items, function ($item) {
            return is_array($item) && !empty($item['action_steps']) && !empty($item['area']);
        }));

        usort($items, function ($left, $right) {
            $left_priority = (int) ($left['priority'] ?? 3);
            $right_priority = (int) ($right['priority'] ?? 3);
            if ($left_priority !== $right_priority) {
                return $left_priority <=> $right_priority;
            }

            $left_sources = count($left['source_items'] ?? []);
            $right_sources = count($right['source_items'] ?? []);
            if ($left_sources !== $right_sources) {
                return $right_sources <=> $left_sources;
            }

            return strcmp((string) ($left['area'] ?? ''), (string) ($right['area'] ?? ''));
        });

        return array_values(array_map(function ($item) {
            $item['source_sections'] = $this->normalize_traceability_list($item['source_sections'] ?? []);
            $item['source_items'] = $this->normalize_traceability_list($item['source_items'] ?? []);
            $item['action_steps'] = array_slice(array_values(array_unique(array_filter($item['action_steps'] ?? []))), 0, 3);
            $item['action'] = !empty($item['action_steps']) ? $item['action_steps'][0] : (string) ($item['action'] ?? '');
            return $item;
        }, $items));
    }

    /**
     * Select final POI items while preserving section coverage where possible.
     *
     * @param array $items Candidate POI items.
     * @param int   $max_items Maximum items to return.
     * @return array
     */
    private function select_balanced_poi_items($items, $max_items)
    {
        if (empty($items)) {
            return [];
        }

        $max_items = max(1, (int) $max_items);
        $items = array_values(array_filter($items, function ($item) {
            return is_array($item) && !empty($item['action_steps']) && !empty($item['area']);
        }));

        usort($items, function ($left, $right) {
            $left_priority = (int) ($left['priority'] ?? 3);
            $right_priority = (int) ($right['priority'] ?? 3);
            if ($left_priority !== $right_priority) {
                return $left_priority <=> $right_priority;
            }

            $left_sources = count($left['source_items'] ?? []);
            $right_sources = count($right['source_items'] ?? []);
            if ($left_sources !== $right_sources) {
                return $right_sources <=> $left_sources;
            }

            return strcmp((string) ($left['area'] ?? ''), (string) ($right['area'] ?? ''));
        });

        $selected = [];
        $selected_keys = [];
        $covered_sections = [];

        foreach ($items as $item) {
            if (count($selected) >= $max_items) {
                break;
            }

            $primary_section = (string) ($item['section_key'] ?? '');
            if ($primary_section === '' && !empty($item['source_sections'][0])) {
                $primary_section = (string) $item['source_sections'][0];
            }

            if ($primary_section === '' || isset($covered_sections[$primary_section])) {
                continue;
            }

            $key = $this->canonical_poi_key($item);
            $selected[] = $item;
            $selected_keys[$key] = true;
            $covered_sections[$primary_section] = true;
        }

        foreach ($items as $item) {
            if (count($selected) >= $max_items) {
                break;
            }

            $key = $this->canonical_poi_key($item);
            if (isset($selected_keys[$key])) {
                continue;
            }

            $selected[] = $item;
            $selected_keys[$key] = true;
        }

        return array_values(array_map(function ($item) {
            $item['source_sections'] = $this->normalize_traceability_list($item['source_sections'] ?? []);
            $item['source_items'] = $this->normalize_traceability_list($item['source_items'] ?? []);
            $item['action_steps'] = array_slice(array_values(array_unique(array_filter($item['action_steps'] ?? []))), 0, 3);
            $item['action'] = !empty($item['action_steps']) ? $item['action_steps'][0] : (string) ($item['action'] ?? '');
            return $item;
        }, array_slice($selected, 0, $max_items)));
    }

    /**
     * Normalize traceability values into a clean unique string list.
     *
     * @param mixed $items Traceability value(s).
     * @return array
     */
    private function normalize_traceability_list($items)
    {
        if (!is_array($items)) {
            $items = [$items];
        }

        $normalized = [];
        foreach ($items as $item) {
            $value = \sanitize_text_field((string) $item);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Build deterministic recommendations from POI items.
     *
     * @param array $poi_items Plan of improvement items.
     * @param array $stats Checklist stats.
     * @return array
     */
    private function build_fallback_recommendations($poi_items, $stats)
    {
        if (empty($poi_items)) {
            return [
                __('Maintain the routines that are already working and continue internal spot-checks between visits.', 'chroma-qa-reports'),
            ];
        }

        $top_areas = [];
        foreach ($poi_items as $item) {
            if (!empty($item['area'])) {
                $top_areas[] = $item['area'];
            }
        }

        $top_areas = array_values(array_unique($top_areas));

        $recommendations = [
            __('Assign an owner and due date for each priority action item before the end of the week.', 'chroma-qa-reports'),
            $top_areas
                ? sprintf(__('Use short leadership spot-checks to monitor progress in %s until the corrections are consistent.', 'chroma-qa-reports'), $this->format_label_list($top_areas, 2))
                : __('Use short leadership spot-checks to monitor progress on the highest-priority action items until the corrections are consistent.', 'chroma-qa-reports'),
            __('Bring updated documentation, photos, or logs to the next QA follow-up so completed corrections are easy to verify.', 'chroma-qa-reports'),
        ];

        if ((int) ($stats['no'] ?? 0) > 0) {
            $recommendations[] = __('Address every No-rated item first, then move to Sometimes-rated items that need stronger daily consistency.', 'chroma-qa-reports');
        }

        return array_slice($recommendations, 0, 4);
    }

    /**
     * Build deterministic comparison data against the previous report.
     *
     * @param array  $responses Grouped current responses.
     * @param Report $previous_report Previous report model.
     * @param array  $checklist_lookup Checklist lookup metadata.
     * @return array
     */
    private function build_fallback_comparison($responses, $previous_report, $checklist_lookup)
    {
        $previous_responses = Checklist_Response::get_by_report_grouped($previous_report->id);
        $current_findings = $this->collect_response_findings($responses, $checklist_lookup);
        $previous_findings = $this->collect_response_findings($previous_responses, $checklist_lookup);
        $previous_map = [];

        foreach ($previous_findings as $finding) {
            $previous_map[$finding['section_key'] . '/' . $finding['item_key']] = $finding;
        }

        $celebrations = [];
        $regressions = [];
        $focus_areas = [];

        foreach ($current_findings as $finding) {
            $key = $finding['section_key'] . '/' . $finding['item_key'];
            if (empty($previous_map[$key])) {
                continue;
            }

            $prior = $previous_map[$key];
            $delta = $this->rating_score($finding['rating']) - $this->rating_score($prior['rating']);

            if ($delta > 0) {
                $celebrations[] = $finding['item_label'];
            } elseif ($delta < 0) {
                $regressions[] = $finding['item_label'];
            } elseif (in_array($finding['rating'], ['no', 'sometimes'], true)) {
                $focus_areas[] = $finding['item_label'];
            }
        }

        return [
            'celebrations' => array_slice(array_values(array_unique($celebrations)), 0, 3),
            'regressions' => array_slice(array_values(array_unique($regressions)), 0, 3),
            'focus_areas' => array_slice(array_values(array_unique($focus_areas)), 0, 3),
        ];
    }

    /**
     * Build a compact list of key findings for fallback summaries.
     *
     * @param array $stats Checklist stats.
     * @param array $strengths Strength labels.
     * @param array $concerns Concern data.
     * @return array
     */
    private function build_fallback_key_findings($stats, $strengths, $concerns)
    {
        $findings = [];
        $yes = (int) ($stats['yes'] ?? 0);
        $sometimes = (int) ($stats['sometimes'] ?? 0);
        $no = (int) ($stats['no'] ?? 0);

        $findings[] = sprintf(
            __('%d checklist items were marked compliant during the visit.', 'chroma-qa-reports'),
            $yes
        );

        if ($sometimes > 0) {
            $findings[] = sprintf(
                __('%d items need stronger day-to-day consistency.', 'chroma-qa-reports'),
                $sometimes
            );
        }

        if ($no > 0) {
            $findings[] = sprintf(
                __('%d items require prompt corrective action.', 'chroma-qa-reports'),
                $no
            );
        }

        if (!empty($strengths)) {
            $findings[] = sprintf(
                __('Strengths were observed in %s.', 'chroma-qa-reports'),
                $this->format_label_list($strengths, 2)
            );
        }

        if (!empty($concerns)) {
            $findings[] = sprintf(
                __('Priority coaching should focus on %s.', 'chroma-qa-reports'),
                $this->format_label_list(array_map(function ($concern) {
                    return $concern['item'];
                }, $concerns), 2)
            );
        }

        return array_slice(array_values(array_filter($findings)), 0, 5);
    }

    /**
     * Derive a suggested overall rating for fallback output.
     *
     * @param Report $report Current report.
     * @param array  $stats Checklist stats.
     * @return string
     */
    private function derive_suggested_rating($report, $stats)
    {
        if (!empty($report->overall_rating) && $report->overall_rating !== Report::RATING_PENDING) {
            return $report->overall_rating;
        }

        $completed = (int) ($stats['completed'] ?? 0);
        $yes = (int) ($stats['yes'] ?? 0);
        $sometimes = (int) ($stats['sometimes'] ?? 0);
        $no = (int) ($stats['no'] ?? 0);

        if ($completed > 0 && $yes === $completed && $sometimes === 0 && $no === 0) {
            return Report::RATING_EXCEEDS;
        }

        if ($no > 0 || $sometimes >= 5) {
            return Report::RATING_NEEDS_IMPROVEMENT;
        }

        return Report::RATING_MEETS;
    }

    /**
     * Convert a priority value into a standard timeline label.
     *
     * @param int|string $priority Priority value.
     * @return string
     */
    private function priority_timeline($priority)
    {
        $priority = (int) $priority;

        if ($priority <= 1) {
            return __('Within 24 hours', 'chroma-qa-reports');
        }

        if ($priority === 2) {
            return __('Within 7 days', 'chroma-qa-reports');
        }

        return __('By next visit', 'chroma-qa-reports');
    }

    /**
     * Determine an item priority from section and rating.
     *
     * @param string $section_key Section key.
     * @param string $rating Rating value.
     * @return int
     */
    private function determine_priority($section_key, $rating)
    {
        $section_key = strtolower((string) $section_key);
        $rating = strtolower((string) $rating);
        $is_high_risk = preg_match('/health|safety|medication|emergency|kitchen|food|playground|ratio|supervision/', $section_key) === 1;

        if ($rating === 'no') {
            return $is_high_risk ? 1 : 2;
        }

        if ($rating === 'sometimes') {
            return $is_high_risk ? 2 : 3;
        }

        return 4;
    }

    /**
     * Normalize a rating into a sortable score.
     *
     * @param string $rating Rating value.
     * @return int
     */
    private function rating_score($rating)
    {
        switch (strtolower((string) $rating)) {
            case 'yes':
                return 3;
            case 'sometimes':
                return 2;
            case 'no':
                return 1;
            default:
                return 0;
        }
    }

    /**
     * Rating weight for ordering concerns ahead of strengths.
     *
     * @param string $rating Rating value.
     * @return int
     */
    private function rating_weight($rating)
    {
        switch (strtolower((string) $rating)) {
            case 'no':
                return 0;
            case 'sometimes':
                return 1;
            case 'yes':
                return 2;
            default:
                return 3;
        }
    }

    /**
     * Format a list of labels as human-readable text.
     *
     * @param array $labels List of labels.
     * @param int   $limit  Max labels to include.
     * @return string
     */
    private function format_label_list($labels, $limit = 3)
    {
        $labels = array_values(array_unique(array_filter(array_map(function ($label) {
            return trim((string) $label);
        }, is_array($labels) ? $labels : []))));

        if (empty($labels)) {
            return '';
        }

        $labels = array_slice($labels, 0, max(1, (int) $limit));
        $count = count($labels);

        if ($count === 1) {
            return $labels[0];
        }

        if ($count === 2) {
            return $labels[0] . __(' and ', 'chroma-qa-reports') . $labels[1];
        }

        $last = array_pop($labels);
        return implode(', ', $labels) . __(', and ', 'chroma-qa-reports') . $last;
    }

    /**
     * Turn a machine key into a readable label.
     *
     * @param string $value Raw key.
     * @return string
     */
    private function humanize_key($value)
    {
        $value = str_replace(['_', '-'], ' ', (string) $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim(ucwords($value));
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
                if (($item['entry_mode'] ?? 'standalone') === 'shared_exact') {
                    continue;
                }

                if (isset($section_responses[$item['key']])) {
                    $response = $section_responses[$item['key']];
                    $rating = strtoupper($response->rating);
                    $notes = $response->notes ? " - Notes: {$response->notes}" : '';
                    $refinement_note = '';
                    if (($item['entry_mode'] ?? '') === 'linked_refinement' && !empty($item['shared_with']['label'])) {
                        $refinement_note = ' (Tier 2 refinement of ' . $item['shared_with']['label'] . ')';
                    }
                    $prompt .= "- [{$rating}] {$item['label']}{$refinement_note}{$notes}\n";
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
