describe('Version History - Comparison Rendering', () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = '<div id="test-root"></div>';
        delete window.CQAVersionHistory;
    });

    test('compareResponses detects note-only changes', () => {
        require('../../admin/js/version-history.js');

        const VersionHistory = window.CQAVersionHistory;
        const changes = VersionHistory.compareResponses(
            {
                classroom: {
                    safety: {
                        rating: 'yes',
                        notes: 'Old note'
                    }
                }
            },
            {
                classroom: {
                    safety: {
                        rating: 'yes',
                        notes: 'Updated note'
                    }
                }
            }
        );

        expect(changes).toHaveLength(1);
        expect(changes[0].differences).toHaveLength(1);
        expect(changes[0].differences[0].key).toBe('notes');
    });

    test('renderResponseChanges shows the full response change set', () => {
        require('../../admin/js/version-history.js');

        const VersionHistory = window.CQAVersionHistory;
        const changes = Array.from({ length: 30 }, (_, index) => ({
            section: 'section_' + (index + 1),
            item: 'item_' + (index + 1),
            sectionLabel: 'Section ' + (index + 1),
            itemLabel: 'Item ' + (index + 1),
            differences: [
                {
                    key: 'rating',
                    label: 'Rating',
                    old: 'no',
                    new: 'yes'
                }
            ]
        }));

        const html = VersionHistory.renderResponseChanges(changes, 7);
        document.getElementById('test-root').innerHTML = html;

        expect(document.querySelectorAll('.cqa-change-list li')).toHaveLength(30);
        expect(document.querySelector('.cqa-more-changes')).toBeNull();
        expect(document.body.textContent).toContain('Section 30 / Item 30');
    });
});
