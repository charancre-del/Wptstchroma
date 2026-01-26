import { openDB } from 'idb';

const DB_NAME = 'cqa-drafts-db';
const STORE_NAME = 'drafts';
const VERSION = 1;

export const initDB = async () => {
    return openDB(DB_NAME, VERSION, {
        upgrade(db) {
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                const store = db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                store.createIndex('updatedAt', 'updatedAt');
            }
        },
    });
};

export const saveLocalDraft = async (draft) => {
    const db = await initDB();
    const id = draft.id || 'new'; // Use 'new' for drafts not yet created on server
    await db.put(STORE_NAME, {
        ...draft,
        id, // Ensure ID is part of the object
        updatedAt: Date.now(),
    });
};

export const getLocalDraft = async (id) => {
    const db = await initDB();
    return db.get(STORE_NAME, id);
};

export const getAllLocalDrafts = async () => {
    const db = await initDB();
    return db.getAll(STORE_NAME);
};

export const deleteLocalDraft = async (id) => {
    const db = await initDB();
    await db.delete(STORE_NAME, id);
};

// Housekeeping: Delete drafts older than 30 days
export const cleanupOldDrafts = async () => {
    const db = await initDB();
    const tx = db.transaction(STORE_NAME, 'readwrite');
    const store = tx.objectStore(STORE_NAME);
    const index = store.index('updatedAt');

    const thirtyDaysAgo = Date.now() - (30 * 24 * 60 * 60 * 1000);
    const range = IDBKeyRange.upperBound(thirtyDaysAgo);

    let cursor = await index.openCursor(range);
    while (cursor) {
        await cursor.delete();
        cursor = await cursor.continue();
    }
    await tx.done;
};
