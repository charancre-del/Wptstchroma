const { test, expect } = require('@playwright/test');
const path = require('path');

test.describe('Report Wizard Flow (Phase 2 Logic)', () => {

    // Mock Data
    const mockSchools = {
        data: [
            { id: 101, name: 'Springfield Elementary', region: 'North', tier: 1 },
            { id: 102, name: 'Shelbyville High', region: 'South', tier: 2 }
        ],
        meta: { total_pages: 1 }
    };

    const mockLatestReport = {
        data: [
            { id: 500, school_id: 101, inspection_date: '2025-12-01', status: 'approved' }
        ]
    };

    const mockChecklist = {
        title: 'Tier 1 Checklist',
        sections: [
            {
                id: 'safety',
                title: 'Safety',
                items: [
                    { id: 'fire_exits', label: 'Fire Exits Clear', description: 'Ensure no blockages.' }
                ]
            }
        ]
    };

    test.beforeEach(async ({ page }) => {
        // --- 1. NETWORK INTERCEPTION (MOCKING) ---

        // Mock User
        await page.route('**/me', async route => {
            await route.fulfill({ json: { id: 1, name: 'Tester', capabilities: ['manage_options'] } });
        });

        // Mock Schools Search
        await page.route('**/schools*', async route => {
            await route.fulfill({ json: mockSchools });
        });

        // Mock Reports Fetch (Linking Logic)
        await page.route('**/reports?*school_id=101*', async route => {
            // Check if asking for latest approved
            if (route.request().url().includes('status=approved')) {
                await route.fulfill({ json: mockLatestReport });
            } else {
                await route.fulfill({ json: { data: [] } });
            }
        });

        // Mock API Checklist
        await page.route('**/checklists/*', async route => {
            await route.fulfill({ json: mockChecklist });
        });

        // Mock Report Creation (POST)
        await page.route('**/reports', async route => {
            if (route.request().method() === 'POST') {
                const postData = route.request().postDataJSON();
                // GUARDRAIL CHECK: Ensure school_id is present
                if (!postData.school_id) {
                    await route.abort('failed');
                    return;
                }

                await route.fulfill({
                    json: {
                        success: true,
                        data: { id: 999, ...postData, status: 'draft', updated_at: new Date().toISOString() }
                    }
                });
            } else {
                // GET reports list
                await route.fulfill({ json: { data: [], meta: { total_pages: 0 } } });
            }
        });

        // Mock Report Update (PUT) & Autosave
        await page.route('**/reports/999', async route => {
            if (route.request().method() === 'PUT') {
                // Simulate CONFLICT if a specific header is missing (Optional test)
                await route.fulfill({ json: { success: true, data: { updated_at: new Date().toISOString() } } });
            }
        });

        // --- 2. LOAD APP ---
        // Load the local HTML file that mounts React
        await page.goto(`file://${path.join(__dirname, '../../mock-admin.html')}`);
    });

    test('Full Wizard Happy Path: Select School -> Link -> Draft', async ({ page }) => {
        // 1. Dashboard Load
        await expect(page.locator('h1')).toContainText('Dashboard'); // Assuming Dashboard placeholder

        // 2. Navigate to Create
        await page.click('text=New Report'); // Or Navigate via sidebar
        // Since sidebar is simple, URL change might be easier to force if links broken
        // But let's assume "New Report" button exists on Dashboard or Schools list
        // If Dashboard is empty placeholder, user might need to go to Schools List first?
        // Let's force navigation via hash
        await page.evaluate(() => window.location.hash = '#/create');

        // 3. Step 1: School Selection
        await expect(page.locator('text=Select School')).toBeVisible();
        await page.fill('input[placeholder="Search schools..."]', 'Springfield');
        await expect(page.locator('text=Springfield Elementary')).toBeVisible();
        await page.click('text=Springfield Elementary');

        // 4. Verify Explicit Linking (Audit Requirement)
        // Should fetch latest report #500
        await expect(page.locator('text=Compare with Previous Report')).toBeVisible();
        await expect(page.locator('text=ID: 500')).toBeVisible();

        // Proceed
        await page.click('button:has-text("Next Step")');

        // 5. Step 2: Metadata
        await expect(page.locator('text=Report Details')).toBeVisible();
        // Verify Summary shows Linked
        await expect(page.locator('text=Linked to Report #500')).toBeVisible();
        await page.click('button:has-text("Next Step")');

        // 6. Step 3: Checklist
        await expect(page.locator('text=Tier 1 Checklist')).toBeVisible();
        await expect(page.locator('text=Fire Exits Clear')).toBeVisible();
        // Rate item
        await page.click('button:has-text("Meets")');
        await page.click('button:has-text("Next Step")');

        // 7. Step 4: Photos
        await expect(page.locator('text=Photos & Evidence')).toBeVisible();
        // Skip upload for now
        await page.click('button:has-text("Next Step")');

        // 8. Step 5: Review (Placeholder)
        await expect(page.locator('text=Submit Report')).toBeVisible();
    });

    test('Guardrail: Creation Blocked without School', async ({ page }) => {
        await page.evaluate(() => window.location.hash = '#/create');
        await expect(page.locator('text=Select School')).toBeVisible();

        // Try to click Next without selection
        const nextButton = page.locator('button:has-text("Next Step")');
        // Check if disabled or if clicking shows toast
        // Implementation disabled it
        await expect(nextButton).toBeDisabled();
    });

});
