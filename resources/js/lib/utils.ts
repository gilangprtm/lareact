import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/**
 * Interface untuk FlashMessage
 */
interface FlashMessage {
    type: 'success' | 'error' | 'info' | 'warning';
    message: string;
}

/**
 * Mengekstrak flash message dari response Inertia
 * @param data - Data dari response Inertia
 * @returns Flash message yang diekstrak atau null jika tidak ada
 */
export function flashMessage(data: any): FlashMessage | null {
    if (!data || typeof data !== 'object') {
        return null;
    }

    // Cek flash message dari session
    const flash = data.flash || data.props?.flash || {};

    // Deteksi tipe flash message
    if (flash.success) {
        return { type: 'success', message: flash.success };
    }

    if (flash.error) {
        return { type: 'error', message: flash.error };
    }

    if (flash.info) {
        return { type: 'info', message: flash.info };
    }

    if (flash.warning) {
        return { type: 'warning', message: flash.warning };
    }

    // Cek custom message formats
    if (flash.message && flash.type) {
        return { type: flash.type, message: flash.message };
    }

    // Fallback jika tidak ada flash message yang terdeteksi
    return null;
}
