import { router } from '@inertiajs/react';
import { debounce, isEqual, pickBy } from 'lodash';
import { useCallback, useEffect, useRef } from 'react';

interface UseFilterProps {
    route: string;
    values: Record<string, any>;
    only?: string[];
    wait?: number;
}

/**
 * UseFilter hook untuk menangani filter dan pencarian otomatis
 * @param param0 - Parameter filter
 * @returns Object values yang telah difilter
 */
export function UseFilter({ route, values, only, wait = 300 }: UseFilterProps) {
    const prevValuesRef = useRef<Record<string, any>>(values);

    const reload = useCallback(
        debounce((query) => {
            router.get(route, pickBy(query), {
                only: only,
                preserveState: true,
                preserveScroll: true,
            });
        }, wait),
        [route, only, wait],
    );

    // Effect untuk memanggil reload hanya ketika values benar-benar berubah
    useEffect(() => {
        // Menggunakan lodash isEqual untuk deep comparison
        if (!isEqual(prevValuesRef.current, values)) {
            reload(values);
            prevValuesRef.current = values;
        }
    }, [values, reload]);

    return { values };
}
