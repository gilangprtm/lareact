import MahasInput from '@/components/mahas/input';
import MahasTableDatas from '@/components/mahas/table/table-datas';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { IconPlus, IconRefresh } from '@tabler/icons-react';
import { useState } from 'react';

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    parent_id: number | null;
    created_at: string;
    updated_at: string;
    children: {
        id: number;
        name: string;
        slug: string;
        parent_id: number;
    }[];
    books: {
        id: number;
        title: string;
        category_id: number;
    }[];
}

interface PageProps {
    datas: {
        data: Category[];
        meta: {
            current_page: number;
            per_page: number;
            total: number;
            to: number;
            has_pages: boolean;
            links: {
                url?: string;
                label: string;
                active: boolean;
            }[];
        };
    };
    pageSettings: {
        title: string;
        subtitle: string;
    };
    state: {
        search: string;
        page: number;
        load: number;
        field?: string;
        direction?: string;
    };
}

export default function Index({ datas, pageSettings, state }: PageProps) {
    const [params, setParams] = useState({
        ...state,
        search: state.search || '',
        page: Number(state.page) || 1,
        load: Number(state.load) || 10,
        field: state.field || 'id',
        direction: state.direction || 'desc',
    });

    const columns = [
        { field: 'name', label: 'Nama Kategori' },
        { field: 'slug', label: 'Slug' },
        {
            field: 'children',
            label: 'Sub Kategori',
            // Ini untuk kustom rendering jumlah sub kategori
            render: (item: Category) => (item.children?.length > 0 ? `${item.children.length} sub kategori` : 'Tidak ada'),
        },
        {
            field: 'books',
            label: 'Jumlah Buku',
            render: (item: Category) => (item.books?.length > 0 ? `${item.books.length} buku` : 'Tidak ada'),
        },
    ];

    const actions = [
        {
            field: 'view',
            label: 'Lihat',
            action: (item: Category) => route('categories.view', [item.id]),
        },
        {
            field: 'edit',
            label: 'Edit',
            action: (item: Category) => route('categories.edit', [item.id]),
        },
    ];

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
                        <Link href={route('categories.create')}>
                            <IconPlus className="mr-2 size-4" />
                            Tambah
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex w-full flex-col gap-4 lg:flex-row lg:items-center">
                            <MahasInput
                                type="text"
                                name="name"
                                label="Cari"
                                value={params.search}
                                onChange={(value) => setParams({ ...params, search: value, page: 1 })}
                                placeholder="Cari..."
                                className="w-full sm:max-w-[300px]"
                            />

                            <Button
                                variant="outline"
                                size="icon"
                                onClick={() =>
                                    setParams({
                                        search: '',
                                        page: 1,
                                        load: 10,
                                        field: 'id',
                                        direction: 'desc',
                                    })
                                }
                                className="ml-auto"
                            >
                                <IconRefresh className="size-4" />
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="px-0">
                        <MahasTableDatas
                            state={params}
                            datas={datas.data}
                            meta={datas.meta}
                            columns={columns}
                            actions={actions}
                            datasFrom="categories.index"
                            datasDelete="categories.destroy"
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

// Menggunakan layout AppLayout
Index.layout = (page: any) => {
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
