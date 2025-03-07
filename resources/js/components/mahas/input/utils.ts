export const formatNumber = (
    value: string | number,
    locale: string,
    options?: {
        isCurrency?: boolean;
        decimalPlaces?: number;
    },
) => {
    if (!value) return '';

    const numValue = typeof value === 'string' ? parseFloat(value) : value;
    if (isNaN(numValue)) return value;

    if (options?.isCurrency) {
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: options.decimalPlaces ?? 2,
            maximumFractionDigits: options.decimalPlaces ?? 2,
        }).format(numValue);
    }

    return new Intl.NumberFormat(locale, {
        minimumFractionDigits: options?.decimalPlaces ?? 2,
        maximumFractionDigits: options?.decimalPlaces ?? 2,
    }).format(numValue);
};

export const validateFiles = (
    files: FileList,
    options?: {
        maxFileSize?: number;
        allowedFileTypes?: string[];
    },
): { isValid: boolean; error?: string } => {
    if (options?.maxFileSize) {
        const isValidSize = Array.from(files).every((file) => file.size <= options.maxFileSize!);
        if (!isValidSize) {
            return {
                isValid: false,
                error: `File size should not exceed ${options.maxFileSize! / (1024 * 1024)}MB`,
            };
        }
    }

    if (options?.allowedFileTypes?.length) {
        const isValidType = Array.from(files).every((file) => options.allowedFileTypes?.some((type) => file.type.startsWith(type)));
        if (!isValidType) {
            return {
                isValid: false,
                error: `Only ${options.allowedFileTypes.join(', ')} files are allowed`,
            };
        }
    }

    return { isValid: true };
};

export const generateFilePreview = (files: FileList): string[] => {
    return Array.from(files)
        .filter((file) => file.type.startsWith('image/'))
        .map((file) => URL.createObjectURL(file));
};

export const sizeClasses = {
    sm: 'h-8 text-sm',
    md: 'h-10 text-base',
    lg: 'h-12 text-lg',
};
