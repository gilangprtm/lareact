import MahasTableDatas from '@/components/mahas/table/table-datas';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { IconPlus, IconRefresh } from '@tabler/icons-react';
import { useState } from 'react';

interface PageProps {
    state: {
        search: string;
        name: string;
        email: string;
        created_at: string;
        load: number;
        page: number;
        field: string;
        direction: 'asc' | 'desc';
    };
    initial_state: {
        search: string;
        name: string;
        email: string;
        created_at: string;
        load: number;
        page: number;
        field: string;
        direction: 'asc' | 'desc';
    };
    datas: {
        data: any[];
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
    page_settings: {
        title: string;
        subtitle: string;
    };
}

export default function Index({ state, initial_state, datas, page_settings }: PageProps) {
    const [params, setParams] = useState({
        ...state,
        load: Number(state.load),
        page: Number(state.page),
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: page_settings.title,
            href: route('example.index'),
        },
    ];

    const columns = [
        { field: 'name', label: 'Nama' },
        { field: 'email', label: 'Email' },
        { field: 'created_at', label: 'Dibuat Pada' },
    ];

    const actions = [
        {
            field: 'view',
            label: 'Lihat',
            action: (item: any) => route('example.show', [item.id]),
        },
        {
            field: 'edit',
            label: 'Edit',
            action: (item: any) => route('example.edit', [item.id]),
        },
    ];

    return (
        <>
            <Head title={page_settings.title} />
            <div className="flex w-full flex-col space-y-6 pb-8">
                <div className="mb-4 flex flex-col items-start justify-between gap-y-4 lg:flex-row lg:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{page_settings.title}</h1>
                        <p className="text-muted-foreground">{page_settings.subtitle}</p>
                    </div>

                    <Button variant="default" size="default">
                        <IconPlus className="mr-2 size-4" />
                        Tambah Baru
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex w-full flex-col gap-4 lg:flex-row lg:items-center">
                            <Input
                                className="w-full sm:max-w-[200px]"
                                placeholder="Cari..."
                                value={params.search}
                                onChange={(e) =>
                                    setParams((prev) => ({
                                        ...prev,
                                        search: e.target.value,
                                        page: 1,
                                    }))
                                }
                            />
                            <Input
                                className="w-full sm:max-w-[200px]"
                                placeholder="Nama..."
                                value={params.name}
                                onChange={(e) =>
                                    setParams((prev) => ({
                                        ...prev,
                                        name: e.target.value,
                                        page: 1,
                                    }))
                                }
                            />
                            <Input
                                className="w-full sm:max-w-[200px]"
                                placeholder="Email..."
                                value={params.email}
                                onChange={(e) =>
                                    setParams((prev) => ({
                                        ...prev,
                                        email: e.target.value,
                                        page: 1,
                                    }))
                                }
                            />
                            <Input
                                type="date"
                                className="w-full sm:max-w-[200px]"
                                value={params.created_at}
                                onChange={(e) =>
                                    setParams((prev) => ({
                                        ...prev,
                                        created_at: e.target.value,
                                        page: 1,
                                    }))
                                }
                            />

                            <Button variant="outline" size="icon" onClick={() => setParams(initial_state)} className="ml-auto">
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
                            datasFrom="example.index"
                            datasDelete="example.destroy"
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

// Menggunakan layout AppLayout
Index.layout = (page: any) => {
    const { breadcrumbs = [] } = page.props || {};

    return <AppLayout breadcrumbs={breadcrumbs}>{page}</AppLayout>;
};
