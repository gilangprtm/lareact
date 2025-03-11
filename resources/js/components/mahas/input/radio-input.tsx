import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import * as React from 'react';
import { RadioInputProps } from './types';

export const RadioInput = React.forwardRef<HTMLInputElement, RadioInputProps>((props, ref) => {
    const { value, onChange, disabled, readOnly, required, className, options, type, ...rest } = props;

    const handleChange = (newValue: string) => {
        onChange?.(newValue);
    };

    return (
        <RadioGroup
            value={value?.toString()}
            onValueChange={handleChange}
            disabled={disabled || readOnly}
            required={required}
            className={className}
            {...rest}
        >
            {options.map((option) => (
                <div key={option.value} className="flex items-center space-x-2">
                    <RadioGroupItem value={option.value.toString()} />
                    <label className="text-sm leading-none font-medium">{option.label}</label>
                </div>
            ))}
        </RadioGroup>
    );
});

RadioInput.displayName = 'RadioInput';
