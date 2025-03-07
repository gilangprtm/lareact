import { InputHTMLAttributes, ReactNode } from 'react';

type HTMLInputProps = Pick<
    InputHTMLAttributes<HTMLInputElement>,
    'min' | 'max' | 'minLength' | 'maxLength' | 'pattern' | 'autoComplete' | 'placeholder'
>;

export interface BaseMahasInputProps {
    name: string;
    label?: string;
    description?: string;
    error?: string;
    disabled?: boolean;
    readOnly?: boolean;
    required?: boolean;
    className?: string;
}

export interface TextInputProps extends BaseMahasInputProps {
    type: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search' | 'color';
    value?: string | number;
    onChange?: (value: string) => void;
    placeholder?: string;
    min?: number;
    max?: number;
    step?: number;
    pattern?: string;
    autoComplete?: string;
}

export interface TextareaInputProps extends BaseMahasInputProps {
    type: 'textarea';
    value?: string;
    onChange?: (value: string) => void;
    placeholder?: string;
    rows?: number;
    cols?: number;
    maxLength?: number;
    autoGrow?: boolean;
}

export interface SelectOption {
    label: string;
    value: string;
}

export interface SelectPaginationData {
    data: SelectOption[];
    meta: {
        current_page: number;
        last_page: number;
        total: number;
    };
}

export interface SelectInputProps extends BaseMahasInputProps {
    type: 'select';
    value?: string | number;
    onChange?: (value: string) => void;
    options: Array<{ value: string | number; label: string }>;
    placeholder?: string;
    multiple?: boolean;
}

export interface CheckboxInputProps extends BaseMahasInputProps {
    type: 'checkbox';
    checked?: boolean;
    onChange?: (checked: boolean) => void;
}

export interface RadioInputProps extends BaseMahasInputProps {
    type: 'radio';
    value?: string | number;
    onChange?: (value: string) => void;
    options: Array<{ value: string | number; label: string }>;
}

export interface DateTimeInputProps extends BaseMahasInputProps {
    type: 'date' | 'time' | 'datetime';
    value?: Date | string;
    onChange?: (value: Date | null) => void;
    min?: Date;
    max?: Date;
    disabledDates?: Date[];
    disabledDays?: number[];
    showClearButton?: boolean;
}

export interface FileInputProps extends BaseMahasInputProps {
    type: 'file';
    value?: File | File[];
    onChange?: (files: File[]) => void;
    multiple?: boolean;
    accept?: string;
    maxSize?: number;
    maxFiles?: number;
    showPreview?: boolean;
    dragAndDrop?: boolean;
}

export interface PhoneInputProps extends BaseMahasInputProps {
    type: 'phone';
    value: string;
    onChange: (value: string) => void;
    defaultCountry?: string;
    onlyCountries?: string[];
    preferredCountries?: string[];
    showFlags?: boolean;
}

export interface ColorInputProps extends BaseMahasInputProps {
    type: 'color';
    value: string;
    onChange: (value: string) => void;
    presetColors?: string[];
    showPreview?: boolean;
    format?: 'hex' | 'rgb' | 'hsl';
}

export interface RatingInputProps extends BaseMahasInputProps {
    type: 'rating';
    value: number;
    onChange: (value: number) => void;
    max?: number;
    allowHalf?: boolean;
    showCount?: boolean;
    character?: ReactNode;
}

export interface TagInputProps extends BaseMahasInputProps {
    type: 'tags';
    value: string[];
    onChange: (value: string[]) => void;
    suggestions?: string[];
    maxTags?: number;
    allowDuplicates?: boolean;
    validateTag?: (tag: string) => boolean;
    transform?: (tag: string) => string;
}

export interface MaskInputProps extends BaseMahasInputProps {
    type: 'mask';
    value: string;
    onChange: (value: string) => void;
    mask: string; // e.g. '99/99/9999' or '(999) 999-9999'
    maskChar?: string;
    formatChars?: Record<string, string>;
    alwaysShowMask?: boolean;
}

export type MahasInputProps =
    | TextInputProps
    | TextareaInputProps
    | SelectInputProps
    | CheckboxInputProps
    | RadioInputProps
    | DateTimeInputProps
    | FileInputProps
    | PhoneInputProps
    | ColorInputProps
    | RatingInputProps
    | TagInputProps
    | MaskInputProps;

export const isCheckboxInput = (props: MahasInputProps): props is CheckboxInputProps => props.type === 'checkbox';
export const isSelectInput = (props: MahasInputProps): props is SelectInputProps => props.type === 'select';
export const isRadioInput = (props: MahasInputProps): props is RadioInputProps => props.type === 'radio';
export const isTextareaInput = (props: MahasInputProps): props is TextareaInputProps => props.type === 'textarea';
export const isTextInput = (props: MahasInputProps): props is TextInputProps =>
    !isCheckboxInput(props) &&
    !isSelectInput(props) &&
    !isRadioInput(props) &&
    !isTextareaInput(props) &&
    !isDateTimeInput(props) &&
    !isFileInput(props);
export const isDateTimeInput = (props: MahasInputProps): props is DateTimeInputProps => ['date', 'time', 'datetime'].includes(props.type as string);
export const isFileInput = (props: MahasInputProps): props is FileInputProps => props.type === 'file';
