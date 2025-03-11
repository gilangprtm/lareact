import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Pagination, PaginationContent, PaginationItem, PaginationLink } from '@/components/ui/pagination';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { UseFilter } from '@/hooks/use-filter';
import { flashMessage } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { IconArrowsDownUp, IconMenu2 } from '@tabler/icons-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface Column {
    field: string;
    label: string;
    render?: (item: any) => React.ReactNode;
}

interface Action {
    field: string;
    label: string;
    action: (item: any) => string;
}

interface MahasTableDatasProps {
    state: Record<string, any>;
    datas: any[];
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
    columns: Column[];
    actions: Action[];
    datasFrom: string;
    datasDelete: string;
}

export default function MahasTableDatas({ state, datas, meta, columns, actions, datasFrom, datasDelete }: MahasTableDatasProps) {
    const [params, setParams] = useState({
        ...state,
    });

    const [deleteItem, setDeleteItem] = useState<any>(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    // Menggunakan hook UseFilter untuk reload data saat parameter berubah
    UseFilter({
        route: route(datasFrom),
        values: params,
        only: ['datas'],
    });

    // Memperbarui params ketika state berubah
    useEffect(() => {
        setParams(state);
    }, [state]);

    // Handler untuk pengurutan kolom
    const onSortable = (field: string) => {
        const newDirection = params.direction === 'asc' ? 'desc' : 'asc';
        setParams((prev) => ({
            ...prev,
            field: field,
            direction: newDirection,
        }));
    };

    // Handler untuk menampilkan dialog hapus
    const onHandleDelete = (item: any) => {
        setDeleteItem(item);
        setIsDialogOpen(true);
    };

    // Konfirmasi penghapusan
    const confirmDelete = () => {
        if (deleteItem) {
            router.delete(route(datasDelete, [deleteItem]), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: (success) => {
                    const flash = flashMessage(success);
                    if (flash) {
                        toast[flash.type as 'success' | 'error'](flash.message);
                    }
                },
                onError: (error) => {
                    console.error('Error:', error);
                },
            });
            setDeleteItem(null);
            setIsDialogOpen(false);
        }
    };

    // Menutup dialog
    const closeDialog = () => {
        setIsDialogOpen(false);
    };

    return (
        <>
            <Table className="w-full">
                <TableHeader>
                    <TableRow>
                        <TableHead className="w-16 pl-4">
                            <Button variant="ghost" className="group inline-flex px-0" onClick={() => onSortable('id')}>
                                No{' '}
                                <span className="text-muted-foreground ml-2 flex-none rounded">
                                    <IconArrowsDownUp className="text-muted-foreground size-4" />
                                </span>
                            </Button>
                        </TableHead>
                        {columns.map((column) => (
                            <TableHead key={column.field}>
                                <Button variant="ghost" className="group inline-flex px-0" onClick={() => onSortable(column.field)}>
                                    {column.label}{' '}
                                    <span className="text-muted-foreground ml-2 flex-none rounded">
                                        <IconArrowsDownUp className="text-muted-foreground size-4" />
                                    </span>
                                </Button>
                            </TableHead>
                        ))}
                        <TableHead className="w-25 pr-4">Aksi</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    {datas.map((item, index) => (
                        <TableRow key={item.id}>
                            <TableCell className="text-center">{index + 1 + (meta.current_page - 1) * meta.per_page}</TableCell>
                            {columns.map((column) => (
                                <TableCell key={column.field}>{column.render ? column.render(item) : item[column.field]}</TableCell>
                            ))}
                            <TableCell className="items-center">
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="ghost" size="sm">
                                            <IconMenu2 className="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent side="left">
                                        {actions.map((action) => (
                                            <DropdownMenuItem key={action.field} asChild>
                                                <Link href={action.action(item)}>{action.label}</Link>
                                            </DropdownMenuItem>
                                        ))}
                                        <DropdownMenuItem onSelect={() => onHandleDelete(item)}>Hapus</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>

            {/* Dialog Konfirmasi Hapus */}
            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Konfirmasi Hapus</DialogTitle>
                        <DialogDescription>Apakah Anda yakin ingin menghapus item ini? Tindakan ini tidak dapat dibatalkan.</DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="ghost" onClick={closeDialog}>
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete}>
                            Hapus
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Pagination */}
            <div className="flex w-full flex-col items-center justify-between border-t px-6 py-2 lg:flex-row">
                <div className="flex flex-col items-center gap-4 lg:flex-row">
                    <p className="text-muted-foreground mb-2 text-sm">
                        Menampilkan <span className="font-bold">{meta.to ?? 0}</span> dari {meta.total} data
                    </p>
                    <Select value={String(params.load)} onValueChange={(e) => setParams({ ...params, load: Number(e), page: 1 })}>
                        <SelectTrigger className="w-full sm:w-24">
                            <SelectValue placeholder="Load" />
                        </SelectTrigger>
                        <SelectContent>
                            {[10, 25, 50, 75, 100].map((number, index) => (
                                <SelectItem key={index} value={String(number)}>
                                    {number}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="overflow-x-auto">
                    {meta.has_pages && (
                        <Pagination>
                            <PaginationContent className="flex flex-wrap justify-center lg:justify-end">
                                {/* First page */}
                                <PaginationItem>
                                    {meta.current_page > 1 ? (
                                        <PaginationLink href={`?page=1&load=${params.load}`}>
                                            <span className="sr-only">First page</span>
                                            <span aria-hidden="true">&laquo;&laquo;</span>
                                        </PaginationLink>
                                    ) : (
                                        <span className="text-muted-foreground flex h-9 cursor-not-allowed items-center justify-center px-3">
                                            <span className="sr-only">First page</span>
                                            <span aria-hidden="true">&laquo;&laquo;</span>
                                        </span>
                                    )}
                                </PaginationItem>

                                {/* Previous page */}
                                <PaginationItem>
                                    {meta.current_page > 1 ? (
                                        <PaginationLink href={`?page=${meta.current_page - 1}&load=${params.load}`}>
                                            <span className="sr-only">Previous page</span>
                                            <span aria-hidden="true">&laquo;</span>
                                        </PaginationLink>
                                    ) : (
                                        <span className="text-muted-foreground flex h-9 cursor-not-allowed items-center justify-center px-3">
                                            <span className="sr-only">Previous page</span>
                                            <span aria-hidden="true">&laquo;</span>
                                        </span>
                                    )}
                                </PaginationItem>

                                {/* Page numbers */}
                                {Array.from({ length: Math.ceil(meta.total / meta.per_page) }).map((_, i) => {
                                    const page = i + 1;
                                    // Show only current page, 2 pages before and 2 pages after
                                    if (
                                        page === 1 ||
                                        page === Math.ceil(meta.total / meta.per_page) ||
                                        (page >= meta.current_page - 2 && page <= meta.current_page + 2)
                                    ) {
                                        return (
                                            <PaginationItem key={page}>
                                                <PaginationLink href={`?page=${page}&load=${params.load}`} isActive={page === meta.current_page}>
                                                    {page}
                                                </PaginationLink>
                                            </PaginationItem>
                                        );
                                    }

                                    // Add ellipsis if needed
                                    if (
                                        (page === meta.current_page - 3 && meta.current_page > 3) ||
                                        (page === meta.current_page + 3 && meta.current_page < Math.ceil(meta.total / meta.per_page) - 2)
                                    ) {
                                        return (
                                            <PaginationItem key={`ellipsis-${page}`}>
                                                <span className="flex h-9 items-center justify-center px-3">...</span>
                                            </PaginationItem>
                                        );
                                    }

                                    return null;
                                })}

                                {/* Next page */}
                                <PaginationItem>
                                    {meta.current_page < Math.ceil(meta.total / meta.per_page) ? (
                                        <PaginationLink href={`?page=${meta.current_page + 1}&load=${params.load}`}>
                                            <span className="sr-only">Next page</span>
                                            <span aria-hidden="true">&raquo;</span>
                                        </PaginationLink>
                                    ) : (
                                        <span className="text-muted-foreground flex h-9 cursor-not-allowed items-center justify-center px-3">
                                            <span className="sr-only">Next page</span>
                                            <span aria-hidden="true">&raquo;</span>
                                        </span>
                                    )}
                                </PaginationItem>

                                {/* Last page */}
                                <PaginationItem>
                                    {meta.current_page < Math.ceil(meta.total / meta.per_page) ? (
                                        <PaginationLink href={`?page=${Math.ceil(meta.total / meta.per_page)}&load=${params.load}`}>
                                            <span className="sr-only">Last page</span>
                                            <span aria-hidden="true">&raquo;&raquo;</span>
                                        </PaginationLink>
                                    ) : (
                                        <span className="text-muted-foreground flex h-9 cursor-not-allowed items-center justify-center px-3">
                                            <span className="sr-only">Last page</span>
                                            <span aria-hidden="true">&raquo;&raquo;</span>
                                        </span>
                                    )}
                                </PaginationItem>
                            </PaginationContent>
                        </Pagination>
                    )}
                </div>
            </div>
        </>
    );
}
