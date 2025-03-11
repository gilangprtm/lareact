import { Checkbox } from '@/components/ui/checkbox';
import * as React from 'react';
import { CheckboxInputProps } from './types';

export const CheckboxInput = React.forwardRef<HTMLButtonElement, CheckboxInputProps>((props, ref) => {
    const { checked, onChange, disabled, readOnly, required, className, type, ...rest } = props;

    const handleChange = (checked: boolean) => {
        onChange?.(checked);
    };

    return (
        <Checkbox
            ref={ref}
            checked={checked}
            onCheckedChange={handleChange}
            disabled={disabled || readOnly}
            required={required}
            className={className}
            {...rest}
        />
    );
});

CheckboxInput.displayName = 'CheckboxInput';
