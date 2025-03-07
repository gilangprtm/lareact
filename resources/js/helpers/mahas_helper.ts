export const formatNumber = (value: number | string | undefined, allowZero = false): string => {
    if (value === undefined || value === null || value === '') return '';
    if (value === 0 && !allowZero) return '';

    const num = typeof value === 'string' ? parseFloat(value) : value;
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
};

export const formatToNumber = (value: string): number => {
    if (!value) return 0;
    return parseFloat(value.replace(/,/g, ''));
};
