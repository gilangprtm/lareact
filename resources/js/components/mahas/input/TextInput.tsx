import { Input } from '@/components/ui/input';
import * as React from 'react';
import { TextInputProps } from './types';

export const TextInput = React.forwardRef<HTMLInputElement, TextInputProps>((props, ref) => {
    const { type, value, onChange, disabled, readOnly, required, className, placeholder, min, max, step, pattern, autoComplete, ...rest } = props;

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        onChange?.(e.target.value);
    };

    return (
        <Input
            ref={ref}
            type={type}
            value={value ?? ''}
            onChange={handleChange}
            disabled={disabled}
            readOnly={readOnly}
            required={required}
            className={className}
            placeholder={placeholder}
            min={min}
            max={max}
            step={step}
            pattern={pattern}
            autoComplete={autoComplete}
            {...rest}
        />
    );
});

TextInput.displayName = 'TextInput';
