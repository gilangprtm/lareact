import { MahasInput } from '@/components/mahas/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/react';
import * as React from 'react';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    avatar: File | null;
    birth_date: Date | null;
    working_hours: string;
    bio: string;
    skills: string[];
    notifications: {
        email: boolean;
        push: boolean;
        sms: boolean;
    };
    preferred_contact: string;
    documents: File[];
}

export default function ExampleForm() {
    const [formData, setFormData] = React.useState<User>({
        id: 0,
        name: '',
        email: '',
        role: '',
        avatar: null,
        birth_date: null,
        working_hours: '',
        bio: '',
        skills: [],
        notifications: {
            email: false,
            push: true,
            sms: false,
        },
        preferred_contact: '',
        documents: [],
    });

    const [errors, setErrors] = React.useState<Record<string, string>>({});
    const [isLoading, setIsLoading] = React.useState(false);

    // Simulasi data untuk select options
    const roleOptions = [
        { value: 'admin', label: 'Administrator' },
        { value: 'manager', label: 'Manager' },
        { value: 'user', label: 'Regular User' },
    ];

    const contactOptions = [
        { value: 'email', label: 'Email' },
        { value: 'phone', label: 'Phone' },
        { value: 'slack', label: 'Slack' },
    ];

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsLoading(true);
        setErrors({});

        try {
            // Simulasi API call dengan delay
            await new Promise((resolve) => setTimeout(resolve, 1000));

            // Simulasi validasi
            const newErrors: Record<string, string> = {};
            if (!formData.name) newErrors.name = 'Name is required';
            if (!formData.email) newErrors.email = 'Email is required';
            if (!formData.role) newErrors.role = 'Role is required';
            if (Object.keys(newErrors).length > 0) {
                setErrors(newErrors);
                throw new Error('Validation failed');
            }

            // Simulasi sukses
            console.log('Form submitted:', formData);
            router.visit('/success', {
                method: 'post',
                data: {
                    ...formData,
                    birth_date: formData.birth_date?.toISOString(),
                    avatar: formData.avatar || undefined,
                    documents: formData.documents || undefined,
                    notifications: JSON.stringify(formData.notifications),
                },
            });
        } catch (error) {
            console.error('Error submitting form:', error);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="container mx-auto py-8">
            <Card>
                <CardHeader>
                    <CardTitle>Example Form</CardTitle>
                    <CardDescription>This form demonstrates various input types and their usage with MahasInput component.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    {/* Basic Text Input */}
                    <MahasInput
                        type="text"
                        name="name"
                        label="Full Name"
                        value={formData.name}
                        onChange={(value) => setFormData({ ...formData, name: value })}
                        error={errors.name}
                        required
                        placeholder="Enter your full name"
                    />

                    {/* Email Input with Validation */}
                    <MahasInput
                        type="email"
                        name="email"
                        label="Email Address"
                        value={formData.email}
                        onChange={(value) => setFormData({ ...formData, email: value })}
                        error={errors.email}
                        required
                        placeholder="your.email@example.com"
                        pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                    />

                    {/* Select Input with Options */}
                    <MahasInput
                        type="select"
                        name="role"
                        label="Role"
                        value={formData.role}
                        onChange={(value) => setFormData({ ...formData, role: value })}
                        options={roleOptions}
                        error={errors.role}
                        required
                        placeholder="Select a role"
                    />

                    {/* File Input for Avatar */}
                    <MahasInput
                        type="file"
                        name="avatar"
                        label="Profile Picture"
                        onChange={(files) => setFormData({ ...formData, avatar: files[0] || null })}
                        accept="image/*"
                        maxSize={5 * 1024 * 1024} // 5MB
                        showPreview
                        dragAndDrop
                        error={errors.avatar}
                    />

                    {/* Date Input */}
                    <MahasInput
                        type="date"
                        name="birth_date"
                        label="Birth Date"
                        value={formData.birth_date || undefined}
                        onChange={(date) => setFormData({ ...formData, birth_date: date })}
                        error={errors.birth_date}
                        min={new Date('1900-01-01')}
                        max={new Date()}
                        showClearButton
                    />

                    {/* Time Input */}
                    <MahasInput
                        type="time"
                        name="working_hours"
                        label="Preferred Working Hours"
                        value={formData.working_hours}
                        onChange={(time) => setFormData({ ...formData, working_hours: time ? time.toString() : '' })}
                        error={errors.working_hours}
                    />

                    {/* Textarea Input */}
                    <MahasInput
                        type="textarea"
                        name="bio"
                        label="Bio"
                        value={formData.bio}
                        onChange={(value) => setFormData({ ...formData, bio: value })}
                        error={errors.bio}
                        placeholder="Tell us about yourself"
                        rows={4}
                    />

                    {/* Checkbox Group */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Notification Preferences</label>
                        <div className="space-y-2">
                            <MahasInput
                                type="checkbox"
                                name="notifications.email"
                                label="Email Notifications"
                                checked={formData.notifications.email}
                                onChange={(checked) =>
                                    setFormData({
                                        ...formData,
                                        notifications: { ...formData.notifications, email: checked },
                                    })
                                }
                            />
                            <MahasInput
                                type="checkbox"
                                name="notifications.push"
                                label="Push Notifications"
                                checked={formData.notifications.push}
                                onChange={(checked) =>
                                    setFormData({
                                        ...formData,
                                        notifications: { ...formData.notifications, push: checked },
                                    })
                                }
                            />
                            <MahasInput
                                type="checkbox"
                                name="notifications.sms"
                                label="SMS Notifications"
                                checked={formData.notifications.sms}
                                onChange={(checked) =>
                                    setFormData({
                                        ...formData,
                                        notifications: { ...formData.notifications, sms: checked },
                                    })
                                }
                            />
                        </div>
                    </div>

                    {/* Radio Input */}
                    <MahasInput
                        type="radio"
                        name="preferred_contact"
                        label="Preferred Contact Method"
                        value={formData.preferred_contact}
                        onChange={(value) => setFormData({ ...formData, preferred_contact: value })}
                        options={contactOptions}
                        error={errors.preferred_contact}
                        required
                    />

                    {/* Multiple File Input */}
                    <MahasInput
                        type="file"
                        name="documents"
                        label="Additional Documents"
                        onChange={(files) => setFormData({ ...formData, documents: files })}
                        accept=".pdf,.doc,.docx"
                        maxSize={10 * 1024 * 1024} // 10MB
                        maxFiles={3}
                        multiple
                        showPreview
                        dragAndDrop
                        error={errors.documents}
                    />

                    <div className="flex justify-end space-x-4 pt-4">
                        <Button type="button" variant="outline" onClick={() => router.visit('/cancel')}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={isLoading}>
                            {isLoading ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    );
}
