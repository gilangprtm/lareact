import { Input } from '@/components/ui/input';
import * as React from 'react';
import { DateTimeInputProps } from './types';

export const DateTimeInput = React.forwardRef<HTMLInputElement, DateTimeInputProps>((props, ref) => {
    const { value, onChange, disabled, readOnly, required, className, type, min, max, disabledDates, disabledDays, showClearButton, ...rest } = props;

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const date = e.target.value ? new Date(e.target.value) : null;
        onChange?.(date);
    };

    const formatValue = () => {
        if (!value) return '';
        const date = value instanceof Date ? value : new Date(value);
        if (type === 'date') {
            return date.toISOString().split('T')[0];
        }
        if (type === 'time') {
            return date.toTimeString().split(' ')[0];
        }
        return date.toISOString().slice(0, 16);
    };

    const inputType = type === 'datetime' ? 'datetime-local' : type;

    return (
        <div className="relative">
            <Input
                ref={ref}
                type={inputType}
                value={formatValue()}
                onChange={handleChange}
                disabled={disabled}
                readOnly={readOnly}
                required={required}
                className={className}
                min={min?.toISOString().slice(0, 16)}
                max={max?.toISOString().slice(0, 16)}
                {...rest}
            />
            {showClearButton && value && !disabled && !readOnly && (
                <button
                    type="button"
                    className="text-muted-foreground hover:text-foreground absolute top-1/2 right-2 -translate-y-1/2"
                    onClick={() => onChange?.(null)}
                >
                    ✕
                </button>
            )}
        </div>
    );
});

DateTimeInput.displayName = 'DateTimeInput';
