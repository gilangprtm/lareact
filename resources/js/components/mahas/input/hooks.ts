import { useCallback, useRef, useState } from 'react';

export const useDebounce = (callback: (value: string) => void, delay: number) => {
    const timeoutRef = useRef<ReturnType<typeof setTimeout>>(null);

    const debouncedCallback = useCallback(
        (value: string) => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
            timeoutRef.current = setTimeout(() => {
                callback(value);
            }, delay);
        },
        [callback, delay],
    );

    return debouncedCallback;
};

export const useFilePreview = () => {
    const [previews, setPreviews] = useState<string[]>([]);

    const updatePreviews = useCallback(
        (files: FileList | null) => {
            // Clear old previews
            previews.forEach((preview) => URL.revokeObjectURL(preview));

            if (!files) {
                setPreviews([]);
                return;
            }

            const newPreviews = Array.from(files)
                .filter((file) => file.type.startsWith('image/'))
                .map((file) => URL.createObjectURL(file));

            setPreviews(newPreviews);
        },
        [previews],
    );

    return { previews, updatePreviews };
};

export const useValidation = (validate?: (value: any) => string | undefined) => {
    const [error, setError] = useState<string>();

    const validateValue = useCallback(
        (value: any) => {
            if (validate) {
                const customError = validate(value);
                if (customError) {
                    setError(customError);
                    return false;
                }
            }
            setError(undefined);
            return true;
        },
        [validate],
    );

    return { error, validateValue };
};
