import MahasInput from '@/components/mahas/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { flashMessage } from '@/lib/utils';
import { Head, Link, useForm } from '@inertiajs/react';
import { IconArrowLeft } from '@tabler/icons-react';
import { toast } from 'sonner';

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    parent_id: number | null;
    parent?: { id: number; name: string };
    children?: { id: number; name: string }[];
}

interface PageSettings {
    title: string;
    subtitle: string;
    method: string;
    action: string;
    mode: 'create' | 'edit' | 'view';
}

interface FormProps {
    data: Category | null;
    pageSettings: PageSettings;
    categories: Category[]; // Daftar semua kategori yang tersedia
    errors?: Record<string, string>;
}

type FormData = Record<string, any> & {
    name: string;
    description: string;
    parent_id: string | number;
    _method: string;
};

export default function Form({ data, pageSettings, categories = [], errors = {} }: FormProps) {
    const isViewMode = pageSettings.mode === 'view';

    const {
        data: formData,
        setData,
        post,
        put,
        processing,
        reset,
    } = useForm<FormData>({
        name: data?.name || '',
        description: data?.description || '',
        parent_id: data?.parent_id || '',
        _method: pageSettings.method,
    });

    // Filter kategori untuk dropdown parent
    // Kita harus menghapus kategori saat ini agar tidak memilih dirinya sendiri sebagai parent
    // Dan juga anak-anaknya untuk menghindari struktur kategori yang melingkar
    const parentOptions = categories
        .filter(
            (category) =>
                // Jika mode edit/view, jangan tampilkan kategori saat ini
                (!data || category.id !== data.id) &&
                // Juga jangan tampilkan anak-anak dari kategori saat ini
                (!data?.children || !data.children.some((child) => child.id === category.id)),
        )
        .map((category) => ({
            value: category.id.toString(),
            label: category.name,
        }));

    // Tambahkan opsi "Tidak ada" di awal daftar
    parentOptions.unshift({ value: '', label: 'Tidak ada' });

    const onHandleChange = (field: keyof FormData, value: any) => {
        setData(field, value);
    };

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const submitMethod = pageSettings.method === 'POST' ? post : put;

        submitMethod(pageSettings.action, {
            preserveScroll: true,
            onSuccess: (page) => {
                const flash = flashMessage(page);
                if (flash) {
                    toast[flash.type as 'success' | 'error'](flash.message);
                }
            },
        });
    };

    const onReset = () => {
        reset();
    };

    return (
        <>
            <Head title={pageSettings.title} />
            <div className="flex w-full flex-col gap-4 p-4">
                <div className="mb-4 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{pageSettings.title}</h1>
                        <p className="text-muted-foreground">{pageSettings.subtitle}</p>
                    </div>

                    <Button variant="default" size="default" asChild>
                        <Link href={route('categories.index')}>
                            <IconArrowLeft className="mr-2 size-4" />
                            Kembali
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <form className="space-y-6" onSubmit={onSubmit}>
                            <MahasInput
                                type="text"
                                name="name"
                                label="Nama Kategori"
                                placeholder="Masukkan nama kategori"
                                value={formData.name}
                                onChange={(value) => onHandleChange('name', value)}
                                error={errors.name}
                                required
                                disabled={isViewMode}
                            />

                            {isViewMode && data?.slug && (
                                <MahasInput
                                    type="text"
                                    name="slug"
                                    label="Slug"
                                    value={data.slug}
                                    readOnly
                                    disabled
                                    description="Slug digunakan untuk URL dan otomatis di-generate dari nama kategori."
                                />
                            )}

                            <MahasInput
                                type="textarea"
                                name="description"
                                label="Deskripsi"
                                placeholder="Masukkan deskripsi kategori (opsional)"
                                value={formData.description || ''}
                                onChange={(value) => onHandleChange('description', value)}
                                error={errors.description}
                                rows={4}
                                disabled={isViewMode}
                            />

                            {/* <MahasInput
                                type="select"
                                name="parent_id"
                                label="Kategori Induk"
                                placeholder="Pilih kategori induk (opsional)"
                                value={formData.parent_id}
                                onChange={(value) => onHandleChange('parent_id', value)}
                                error={errors.parent_id}
                                options={parentOptions}
                                disabled={isViewMode}
                            /> */}

                            {!isViewMode && (
                                <div className="flex justify-end gap-x-2">
                                    <Button type="button" variant="outline" onClick={onReset} disabled={processing}>
                                        Reset
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        {pageSettings.mode === 'create' ? 'Simpan' : 'Perbarui'}
                                    </Button>
                                </div>
                            )}

                            {isViewMode && (
                                <div className="flex justify-end gap-x-2">
                                    <Button asChild>
                                        <Link href={pageSettings.action}>Edit</Link>
                                    </Button>
                                </div>
                            )}
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Form.layout = (page: any) => {
    const {
        breadcrumbs = [
            {
                title: page.props.pageSettings.title,
                href: route('categories.index'),
            },
        ],
    } = page.props || {};

    return <AppLayout breadcrumbs={breadcrumbs}>{page}</AppLayout>;
};
