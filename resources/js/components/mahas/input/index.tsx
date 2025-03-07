import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import * as React from 'react';
import { CheckboxInput } from './CheckboxInput';
import { DateTimeInput } from './DateTimeInput';
import { FileInput } from './FileInput';
import { RadioInput } from './RadioInput';
import { SelectInput } from './SelectInput';
import { TextInput } from './TextInput';
import { TextareaInput } from './TextareaInput';
import { MahasInputProps, isCheckboxInput, isDateTimeInput, isFileInput, isRadioInput, isSelectInput, isTextInput, isTextareaInput } from './types';

export const MahasInput = React.forwardRef<HTMLElement, MahasInputProps>((props, ref) => {
    const { label, description, error, className } = props;

    const renderInput = () => {
        if (isTextInput(props)) return <TextInput {...props} ref={ref as React.ForwardedRef<HTMLInputElement>} />;
        if (isTextareaInput(props)) return <TextareaInput {...props} ref={ref as React.ForwardedRef<HTMLTextAreaElement>} />;
        if (isSelectInput(props)) return <SelectInput {...props} ref={ref as React.ForwardedRef<HTMLButtonElement>} />;
        if (isCheckboxInput(props)) return <CheckboxInput {...props} ref={ref as React.ForwardedRef<HTMLButtonElement>} />;
        if (isRadioInput(props)) return <RadioInput {...props} ref={ref as React.ForwardedRef<HTMLInputElement>} />;
        if (isDateTimeInput(props)) return <DateTimeInput {...props} ref={ref as React.ForwardedRef<HTMLInputElement>} />;
        if (isFileInput(props)) return <FileInput {...props} ref={ref as React.ForwardedRef<HTMLInputElement>} />;
        return null;
    };

    return (
        <div className={cn('space-y-2', className)}>
            {label && (
                <Label htmlFor={props.name} className={cn(error && 'text-destructive')}>
                    {label}
                    {props.required && <span className="text-destructive ml-1">*</span>}
                </Label>
            )}
            {renderInput()}
            {description && <p className="text-muted-foreground text-sm">{description}</p>}
            {error && <p className="text-destructive text-sm">{error}</p>}
        </div>
    );
});

MahasInput.displayName = 'MahasInput';

export type { MahasInputProps };
export default MahasInput;
