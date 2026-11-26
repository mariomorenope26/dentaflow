import type {
    Appointment,
    OdontogramCondition,
    OdontogramFinding,
    Patient,
    TreatmentCatalogItem,
} from '@/types';

const DB_NAME = 'dentaflow_offline_db';
const DB_VERSION = 1;

export type OfflineMutationType =
    | 'odontogram.sync'
    | 'clinical_evolution.create'
    | 'appointment.status';

export type OfflineMutation = {
    id: string;
    type: OfflineMutationType;
    label: string;
    created_at: string;
    force?: boolean;
    payload: Record<string, unknown>;
};

export type OfflineConflict = {
    id: string;
    type: OfflineMutationType;
    label: string;
    server_version: number;
    server_updated_at?: string;
    server_findings?: OdontogramFinding[];
    local_findings?: OdontogramFinding[];
    server_status?: string;
    local_status?: string;
};

export type OfflineSnapshot = {
    synced_at: string;
    appointments: Appointment[];
    patients: Patient[];
    treatment_catalog: TreatmentCatalogItem[];
    odontogram_conditions: OdontogramCondition[];
};

function openDb(): Promise<IDBDatabase> {
    return new Promise((resolve, reject) => {
        if (typeof indexedDB === 'undefined') {
            reject(new Error('IndexedDB unavailable'));
            return;
        }

        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains('snapshots')) {
                db.createObjectStore('snapshots');
            }
            if (!db.objectStoreNames.contains('mutations')) {
                db.createObjectStore('mutations', { keyPath: 'id' });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

export async function saveOfflineSnapshot(
    snapshot: OfflineSnapshot,
): Promise<void> {
    try {
        const db = await openDb();
        await new Promise<void>((resolve, reject) => {
            const tx = db.transaction('snapshots', 'readwrite');
            tx.objectStore('snapshots').put(snapshot, 'latest');
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    } catch {
        // Ignore IndexedDB quota/private-mode errors gracefully
    }
}

export async function getOfflineSnapshot(): Promise<OfflineSnapshot | null> {
    try {
        const db = await openDb();
        return await new Promise<OfflineSnapshot | null>((resolve, reject) => {
            const tx = db.transaction('snapshots', 'readonly');
            const req = tx.objectStore('snapshots').get('latest');
            req.onsuccess = () =>
                resolve((req.result as OfflineSnapshot | undefined) ?? null);
            req.onerror = () => reject(req.error);
        });
    } catch {
        return null;
    }
}

export async function searchOfflinePatients(query: string): Promise<Patient[]> {
    const snap = await getOfflineSnapshot();
    if (!snap) return [];

    const needle = query.toLowerCase();
    return snap.patients.filter(
        (p) =>
            p.full_name.toLowerCase().includes(needle) ||
            (p.document_number ?? '').toLowerCase().includes(needle) ||
            p.history_number.toLowerCase().includes(needle),
    );
}

export async function queueOfflineMutation(
    type: OfflineMutationType,
    label: string,
    payload: Record<string, unknown>,
): Promise<OfflineMutation> {
    const mutation: OfflineMutation = {
        id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        type,
        label,
        created_at: new Date().toISOString(),
        payload,
    };

    const db = await openDb();
    await new Promise<void>((resolve, reject) => {
        const tx = db.transaction('mutations', 'readwrite');
        tx.objectStore('mutations').put(mutation);
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });

    window.dispatchEvent(new CustomEvent('dentaflow:offline-queue-updated'));
    return mutation;
}

export async function listOfflineMutations(): Promise<OfflineMutation[]> {
    try {
        const db = await openDb();
        return await new Promise<OfflineMutation[]>((resolve, reject) => {
            const tx = db.transaction('mutations', 'readonly');
            const req = tx.objectStore('mutations').getAll();
            req.onsuccess = () =>
                resolve((req.result as OfflineMutation[]) ?? []);
            req.onerror = () => reject(req.error);
        });
    } catch {
        return [];
    }
}

export async function removeOfflineMutation(id: string): Promise<void> {
    try {
        const db = await openDb();
        await new Promise<void>((resolve, reject) => {
            const tx = db.transaction('mutations', 'readwrite');
            tx.objectStore('mutations').delete(id);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
        window.dispatchEvent(
            new CustomEvent('dentaflow:offline-queue-updated'),
        );
    } catch {
        // noop
    }
}
