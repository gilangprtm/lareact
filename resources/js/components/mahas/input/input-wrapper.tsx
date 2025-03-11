import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface InputWrapperProps {
    id: string;
    label?: string;
    error?: string;
    description?: string;
    helpText?: string;
    required?: boolean;
    disabled?: boolean;
    isLoading?: boolean;
    labelClassName?: string;
    containerClassName?: string;
    children: ReactNode;
    showLabel?: boolean;
}

export function InputWrapper({
    id,
    label,
    error,
    description,
    helpText,
    required,
    disabled,
    isLoading,
    labelClassName,
    containerClassName,
    children,
    showLabel = true,
}: InputWrapperProps) {
    const descriptionId = `${id}-description`;
    const helpTextId = `${id}-help`;
    const errorId = `${id}-error`;

    return (
        <div className={cn('space-y-2', containerClassName)}>
            {label && showLabel && (
                <Label htmlFor={id} className={cn(disabled && 'opacity-70', isLoading && 'animate-pulse', labelClassName)}>
                    {label}
                    {required && <span className="text-destructive">*</span>}
                </Label>
            )}
            {children}
            {description && (
                <p id={descriptionId} className={cn('text-muted-foreground text-sm', disabled && 'opacity-70', isLoading && 'animate-pulse')}>
                    {description}
                </p>
            )}
            {helpText && (
                <p id={helpTextId} className={cn('text-muted-foreground text-sm italic', isLoading && 'animate-pulse')}>
                    {helpText}
                </p>
            )}
            {error && (
                <p id={errorId} className={cn('text-destructive text-sm', isLoading && 'animate-pulse')}>
                    {error}
                </p>
            )}
        </div>
    );
}
