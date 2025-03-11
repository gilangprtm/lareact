import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import * as React from 'react';
import { useState } from 'react';
import { FileInputProps } from './types';

export const FileInput = React.forwardRef<HTMLInputElement, FileInputProps>((props, ref) => {
    const { value, onChange, disabled, readOnly, required, className, type, accept, maxSize, maxFiles, showPreview, dragAndDrop, ...rest } = props;

    const [isDragging, setIsDragging] = useState(false);

    const handleChange = (files: FileList | null) => {
        if (!files) {
            onChange?.([]);
            return;
        }

        const validFiles = Array.from(files).filter((file) => {
            if (maxSize && file.size > maxSize) return false;
            if (accept) {
                const acceptedTypes = accept.split(',').map((type) => type.trim());
                const fileType = file.type || '';
                const fileExtension = '.' + file.name.split('.').pop();
                return acceptedTypes.some((type) => {
                    if (type.startsWith('.')) {
                        return type === fileExtension;
                    }
                    if (type.endsWith('/*')) {
                        return fileType.startsWith(type.slice(0, -2));
                    }
                    return type === fileType;
                });
            }
            return true;
        });

        if (maxFiles && validFiles.length > maxFiles) {
            validFiles.splice(maxFiles);
        }

        onChange?.(validFiles);
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        if (!dragAndDrop || disabled || readOnly) return;
        setIsDragging(true);
    };

    const handleDragLeave = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        if (!dragAndDrop || disabled || readOnly) return;
        setIsDragging(false);
        handleChange(e.dataTransfer.files);
    };

    return (
        <div
            className={cn('relative', dragAndDrop && 'rounded-lg border-2 border-dashed p-4', isDragging && 'border-primary', className)}
            onDragOver={handleDragOver}
            onDragLeave={handleDragLeave}
            onDrop={handleDrop}
        >
            <Input
                ref={ref}
                type="file"
                onChange={(e) => handleChange(e.target.files)}
                disabled={disabled}
                readOnly={readOnly}
                required={required}
                accept={accept}
                multiple={maxFiles !== 1}
                {...rest}
            />
            {showPreview && value && (
                <div className="mt-2 grid grid-cols-4 gap-2">
                    {(Array.isArray(value) ? value : [value]).map((file, index) => (
                        <div key={index} className="relative aspect-square">
                            {file.type.startsWith('image/') ? (
                                <img src={URL.createObjectURL(file)} alt={file.name} className="h-full w-full rounded-lg object-cover" />
                            ) : (
                                <div className="bg-muted flex h-full w-full items-center justify-center rounded-lg">{file.name}</div>
                            )}
                            {!disabled && !readOnly && (
                                <button
                                    type="button"
                                    className="bg-background/80 hover:bg-background absolute top-1 right-1 rounded-full p-1"
                                    onClick={() => {
                                        const newFiles = Array.isArray(value) ? value.filter((_, i) => i !== index) : [];
                                        onChange?.(newFiles);
                                    }}
                                >
                                    ✕
                                </button>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
});

FileInput.displayName = 'FileInput';
