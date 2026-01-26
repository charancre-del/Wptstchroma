/**
 * IndexedDB Draft Storage Helper
 * 
 * Manages local draft persistence with enforced limits:
 * - Max 10 drafts
 * - 30-day TTL auto-cleanup
 * - 200MB photo storage cap with LRU eviction
 */

import { get, set, del, keys, createStore } from 'idb-keyval';

const DB_NAME = 'cqa-drafts';
const STORE_NAME = 'drafts';
const MAX_DRAFTS = 10;
const MAX_AGE_MS = 30 * 24 * 60 * 60 * 1000; // 30 days
const MAX_PHOTO_BYTES = 200 * 1024 * 1024; // 200MB

// Create dedicated store
const store = createStore(DB_NAME, STORE_NAME);

/**
 * Draft Storage API
 */
export const draftStorage = {
    /**
     * Save a draft (auto-triggers cleanup)
     */
    async save(draftId, data) {
        const draft = {
            ...data,
            id: draftId,
            lastModified: Date.now(),
        };
        await set(draftId, draft, store);
        await this.cleanup();
        return draft;
    },

    /**
     * Get a draft by ID
     */
    async get(draftId) {
        return get(draftId, store);
    },

    /**
     * Delete a draft by ID
     */
    async delete(draftId) {
        await del(draftId, store);
    },

    /**
     * Get all drafts
     */
    async getAll() {
        const allKeys = await keys(store);
        const drafts = await Promise.all(
            allKeys.map(k => get(k, store))
        );
        return drafts.filter(Boolean);
    },

    /**
     * Check if a draft exists
     */
    async exists(draftId) {
        const draft = await get(draftId, store);
        return !!draft;
    },

    /**
     * Cleanup: enforce TTL, max count, and photo cap
     */
    async cleanup() {
        try {
            await this._deleteExpiredDrafts();
            await this._enforceMaxDrafts();
            await this._evictPhotosIfNeeded();
        } catch (error) {
            console.error('[DraftStorage] Cleanup error:', error);
        }
    },

    /**
     * Delete drafts older than 30 days
     */
    async _deleteExpiredDrafts() {
        const drafts = await this.getAll();
        const cutoff = Date.now() - MAX_AGE_MS;

        const expired = drafts.filter(d => d.lastModified < cutoff);
        for (const draft of expired) {
            console.log(`[DraftStorage] Deleting expired draft: ${draft.id}`);
            await this.delete(draft.id);
        }
    },

    /**
     * Keep only newest MAX_DRAFTS
     */
    async _enforceMaxDrafts() {
        const drafts = await this.getAll();
        if (drafts.length <= MAX_DRAFTS) return;

        // Sort by lastModified descending (newest first)
        const sorted = drafts.sort((a, b) => b.lastModified - a.lastModified);
        const toDelete = sorted.slice(MAX_DRAFTS);

        for (const draft of toDelete) {
            console.log(`[DraftStorage] Evicting old draft: ${draft.id}`);
            await this.delete(draft.id);
        }
    },

    /**
     * LRU eviction if photo storage exceeds cap
     */
    async _evictPhotosIfNeeded() {
        const drafts = await this.getAll();
        let totalSize = 0;

        for (const draft of drafts) {
            totalSize += this._estimatePhotoSize(draft.photos || []);
        }

        if (totalSize <= MAX_PHOTO_BYTES) return;

        // Sort by lastModified ascending (oldest first)
        const sorted = drafts
            .filter(d => d.photos?.length > 0)
            .sort((a, b) => a.lastModified - b.lastModified);

        // Remove photos from oldest draft until under limit
        for (const draft of sorted) {
            if (totalSize <= MAX_PHOTO_BYTES) break;

            const photoSize = this._estimatePhotoSize(draft.photos);
            console.log(`[DraftStorage] Evicting photos from draft: ${draft.id} (${photoSize} bytes)`);

            draft.photos = [];
            await this.save(draft.id, draft);
            totalSize -= photoSize;
        }
    },

    /**
     * Estimate photo size from base64 previews
     */
    _estimatePhotoSize(photos) {
        if (!photos || !Array.isArray(photos)) return 0;
        return photos.reduce((sum, p) => {
            // base64 preview length is ~4/3 of actual size
            const previewSize = p.preview?.length || 0;
            return sum + Math.ceil(previewSize * 0.75);
        }, 0);
    },

    /**
     * Get storage stats
     */
    async getStats() {
        const drafts = await this.getAll();
        let totalPhotoBytes = 0;

        for (const draft of drafts) {
            totalPhotoBytes += this._estimatePhotoSize(draft.photos);
        }

        return {
            draftCount: drafts.length,
            maxDrafts: MAX_DRAFTS,
            photoBytes: totalPhotoBytes,
            maxPhotoBytes: MAX_PHOTO_BYTES,
            photoUsagePercent: Math.round((totalPhotoBytes / MAX_PHOTO_BYTES) * 100),
        };
    },

    /**
     * Clear all drafts (for testing/reset)
     */
    async clear() {
        const allKeys = await keys(store);
        for (const key of allKeys) {
            await del(key, store);
        }
    },
};

export default draftStorage;
