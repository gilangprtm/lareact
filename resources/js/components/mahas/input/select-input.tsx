import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import * as React from 'react';
import { SelectInputProps } from './types';

export const SelectInput = React.forwardRef<HTMLButtonElement, SelectInputProps>((props, ref) => {
    const { value, onChange, disabled, readOnly, required, className, placeholder, options, multiple, ...rest } = props;

    const handleChange = (newValue: string) => {
        onChange?.(newValue);
    };

    return (
        <Select value={value?.toString()} onValueChange={handleChange} disabled={disabled || readOnly}>
            <SelectTrigger ref={ref} className={className}>
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value.toString()}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
});

SelectInput.displayName = 'SelectInput';
