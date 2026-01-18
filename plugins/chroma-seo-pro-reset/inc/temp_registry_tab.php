    /**
     * Render Registry & Maintenance Tab (Consolidated)
     */
    public function render_registry_tab()
    {
        // Get Registry Status
        $registered = [];
        $blocked = [];
        if (class_exists('Chroma_Schema_Registry')) {
            $registered = Chroma_Schema_Registry::get_all();
            $blocked = Chroma_Schema_Registry::get_blocked();
        }
        ?>
        <div class="chroma-seo-card">
            <h2>📊 Schema Registry Status</h2>
            <p>This table shows what the <strong>Chroma Schema Registry</strong> is currently outputting to the frontend for this page.</p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <!-- Active Schemas -->
                <div style="background: #f0f6fc; padding: 15px; border: 1px solid #c2dbff; border-radius: 5px;">
                    <h3 style="margin-top: 0; color: #005a9c;">✅ Active Output (<?php echo count($registered); ?>)</h3>
                    <table class="widefat striped">
                        <thead><tr><th>Type</th><th>Source</th></tr></thead>
                        <tbody>
                            <?php if (empty($registered)): ?>
                                <tr><td colspan="2">No schemas registered yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($registered as $item): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($item['type']); ?></strong></td>
                                        <td><?php echo esc_html($item['source']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Blocked Schemas -->
                <div style="background: #fff5f5; padding: 15px; border: 1px solid #fcb3b3; border-radius: 5px;">
                    <h3 style="margin-top: 0; color: #d63638;">🚫 Blocked / Invalid (<?php echo count($blocked); ?>)</h3>
                    <table class="widefat striped">
                        <thead><tr><th>Type</th><th>Reason</th></tr></thead>
                        <tbody>
                            <?php if (empty($blocked)): ?>
                                <tr><td colspan="2">No schemas blocked.</td></tr>
                            <?php else: ?>
                                <?php foreach ($blocked as $item): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($item['type']); ?></strong></td>
                                        <td><?php echo esc_html($item['reason']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <hr style="margin: 30px 0;">

        <!-- Consolidated Tools Section -->
        <div class="chroma-seo-card">
            <h2>🛠️ Maintenance Tools</h2>
            
            <!-- Tab Navigation for Tools -->
            <h3 class="nav-tab-wrapper" style="margin-bottom: 15px;">
                <a href="#tool-cleanup" class="nav-tab nav-tab-active" onclick="switchToolTab(event, 'tool-cleanup')">Schema Cleanup</a>
                <a href="#tool-bulk" class="nav-tab" onclick="switchToolTab(event, 'tool-bulk')">Bulk Actions</a>
            </h3>

            <!-- Tool: Schema Cleanup -->
            <div id="tool-cleanup" class="tool-content">
                <?php $this->render_cleanup_tab_content(); ?>
            </div>

            <!-- Tool: Bulk Actions -->
            <div id="tool-bulk" class="tool-content" style="display: none;">
                <?php $this->render_bulk_ops_tab_content(); ?>
            </div>
        </div>

        <script>
        function switchToolTab(e, id) {
            e.preventDefault();
            jQuery('.tool-content').hide();
            jQuery('#' + id).show();
            jQuery('.nav-tab').removeClass('nav-tab-active');
            jQuery(e.target).addClass('nav-tab-active');
        }
        </script>
        <?php
    }
