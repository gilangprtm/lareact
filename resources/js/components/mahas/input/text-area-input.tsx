import { Textarea } from '@/components/ui/textarea';
import * as React from 'react';
import { TextareaInputProps } from './types';

export const TextareaInput = React.forwardRef<HTMLTextAreaElement, TextareaInputProps>((props, ref) => {
    const { value, onChange, disabled, readOnly, required, className, placeholder, rows, cols, maxLength, autoGrow, ...rest } = props;

    const handleChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
        onChange?.(e.target.value);
    };

    return (
        <Textarea
            ref={ref}
            value={value ?? ''}
            onChange={handleChange}
            disabled={disabled}
            readOnly={readOnly}
            required={required}
            className={className}
            placeholder={placeholder}
            rows={rows}
            cols={cols}
            maxLength={maxLength}
            {...rest}
        />
    );
});

TextareaInput.displayName = 'TextareaInput';
