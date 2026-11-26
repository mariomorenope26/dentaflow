import { Head, router, useForm, usePage } from '@inertiajs/react';
import { ClipboardList, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Auth, OdontogramCondition, TreatmentCatalogItem } from '@/types';

type Props = {
    catalog: TreatmentCatalogItem[];
    categories: string[];
    conditions: OdontogramCondition[];
    filters: { search: string; category: string };
};

export default function TreatmentsIndex({
    catalog,
    categories,
    conditions,
    filters,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canEdit = Boolean(
        auth.user?.can.edit_clinical || auth.user?.can.manage_clinic,
    );
    const currency = auth.clinic?.currency_symbol ?? 'S/';
    const [openNew, setOpenNew] = useState(false);
    const [editingItem, setEditingItem] = useState<TreatmentCatalogItem | null>(
        null,
    );
    const [deletingItem, setDeletingItem] =
        useState<TreatmentCatalogItem | null>(null);
    const [search, setSearch] = useState(filters.search);

    const form = useForm({
        code: '',
        name: '',
        category: categories[0] ?? 'Operatoria',
        default_price: '120.00',
        duration_minutes: 30,
        odontogram_condition: '',
    });

    const editForm = useForm({
        name: '',
        category: '',
        default_price: '0.00',
        duration_minutes: 30,
        odontogram_condition: '',
        is_active: true,
    });

    const handleFilter = (category: string) => {
        router.get(
            '/treatments',
            { search, category },
            { preserveState: true },
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/treatments', {
            preserveScroll: true,
            onSuccess: () => {
                setOpenNew(false);
                form.reset();
            },
        });
    };

    const openEditModal = (item: TreatmentCatalogItem) => {
        setEditingItem(item);
        editForm.setData({
            name: item.name,
            category: item.category,
            default_price: String(item.default_price),
            duration_minutes: item.duration_minutes,
            odontogram_condition: item.odontogram_condition ?? '',
            is_active: item.is_active,
        });
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingItem) return;
        editForm.patch(`/treatments/${editingItem.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditingItem(null),
        });
    };

    const confirmDelete = () => {
        if (!deletingItem) return;
        router.delete(`/treatments/${deletingItem.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeletingItem(null),
        });
    };

    return (
        <>
            <Head title="Catálogo de tratamientos" />
            <div className="mx-auto max-w-7xl space-y-5 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">
                            Catálogo de tratamientos
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Tarifario del consultorio vinculado con los
                            hallazgos del odontograma
                        </p>
                    </div>
                    {canEdit && (
                        <Button
                            onClick={() => setOpenNew(true)}
                            className="h-11 gap-2 rounded-xl bg-[#0E8FA3] px-5 text-white hover:bg-[#0A6577]"
                        >
                            <Plus className="size-4" />
                            Nuevo tratamiento
                        </Button>
                    )}
                </div>

                <div className="space-y-3 rounded-2xl border bg-card p-4 shadow-xs">
                    <div className="flex gap-2">
                        <div className="relative flex-1">
                            <Search className="pointer-events-none absolute top-3 left-3 size-4 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Buscar procedimiento o código..."
                                className="h-10 rounded-xl pl-9"
                            />
                        </div>
                        <Button
                            variant="outline"
                            className="h-10 rounded-xl"
                            onClick={() => handleFilter(filters.category)}
                        >
                            Buscar
                        </Button>
                    </div>

                    <div className="flex flex-wrap gap-1.5">
                        <button
                            type="button"
                            onClick={() => handleFilter('')}
                            className={`rounded-full border px-3 py-1 text-xs font-medium ${
                                filters.category === ''
                                    ? 'border-[#0E8FA3] bg-[#0E8FA3] text-white'
                                    : 'bg-background text-muted-foreground'
                            }`}
                        >
                            Todas
                        </button>
                        {categories.map((cat) => (
                            <button
                                key={cat}
                                type="button"
                                onClick={() => handleFilter(cat)}
                                className={`rounded-full border px-3 py-1 text-xs font-medium ${
                                    filters.category === cat
                                        ? 'border-[#0E8FA3] bg-[#0E8FA3] text-white'
                                        : 'bg-background text-muted-foreground'
                                }`}
                            >
                                {cat}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border bg-card shadow-xs">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-muted/40 text-xs text-muted-foreground">
                            <tr>
                                <th className="px-4 py-3">Código</th>
                                <th className="px-4 py-3">Procedimiento</th>
                                <th className="px-4 py-3">Categoría</th>
                                <th className="px-4 py-3">Duración</th>
                                <th className="px-4 py-3">
                                    Vínculo odontograma
                                </th>
                                <th className="px-4 py-3 text-right">Precio</th>
                                {canEdit && (
                                    <th className="px-4 py-3 text-right">
                                        Acciones
                                    </th>
                                )}
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {catalog.map((item) => (
                                <tr key={item.id} className="hover:bg-muted/20">
                                    <td className="px-4 py-3 font-mono text-xs font-bold text-[#0A6577]">
                                        {item.code}
                                    </td>
                                    <td className="px-4 py-3 font-medium">
                                        <div className="flex items-center gap-2">
                                            <span>{item.name}</span>
                                            {!item.is_active && (
                                                <Badge variant="outline">
                                                    Inactivo
                                                </Badge>
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge variant="secondary">
                                            {item.category}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-xs text-muted-foreground">
                                        {item.duration_minutes} min
                                    </td>
                                    <td className="px-4 py-3 text-xs text-muted-foreground">
                                        {item.odontogram_condition ?? '-'}
                                    </td>
                                    <td className="px-4 py-3 text-right font-bold">
                                        {currency}{' '}
                                        {Number(item.default_price).toFixed(2)}
                                    </td>
                                    {canEdit && (
                                        <td className="px-4 py-3 text-right">
                                            <div className="inline-flex items-center gap-1">
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="ghost"
                                                    className="h-8 px-2 text-xs"
                                                    onClick={() =>
                                                        openEditModal(item)
                                                    }
                                                >
                                                    <Pencil className="size-3.5" />
                                                </Button>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="ghost"
                                                    className="h-8 px-2 text-xs text-[#D64545]"
                                                    onClick={() =>
                                                        setDeletingItem(item)
                                                    }
                                                >
                                                    <Trash2 className="size-3.5" />
                                                </Button>
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <Dialog open={openNew} onOpenChange={setOpenNew}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <ClipboardList className="size-5 text-[#0E8FA3]" />
                            Agregar procedimiento al catálogo
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="space-y-3">
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label>Código *</Label>
                                <Input
                                    required
                                    placeholder="OPER-05"
                                    value={form.data.code}
                                    onChange={(e) =>
                                        form.setData('code', e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label>Categoría *</Label>
                                <Input
                                    required
                                    value={form.data.category}
                                    onChange={(e) =>
                                        form.setData('category', e.target.value)
                                    }
                                />
                            </div>
                        </div>
                        <div>
                            <Label>Nombre del procedimiento *</Label>
                            <Input
                                required
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label>Precio base (S/)</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    required
                                    value={form.data.default_price}
                                    onChange={(e) =>
                                        form.setData(
                                            'default_price',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Duración (min)</Label>
                                <Input
                                    type="number"
                                    required
                                    value={form.data.duration_minutes}
                                    onChange={(e) =>
                                        form.setData(
                                            'duration_minutes',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <div>
                            <Label>Hallazgo asociado en odontograma</Label>
                            <select
                                value={form.data.odontogram_condition}
                                onChange={(e) =>
                                    form.setData(
                                        'odontogram_condition',
                                        e.target.value,
                                    )
                                }
                                className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                            >
                                <option value="">
                                    Sin asociación automática
                                </option>
                                {conditions.map((c) => (
                                    <option key={c.code} value={c.code}>
                                        {c.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpenNew(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                className="bg-[#0E8FA3] text-white"
                            >
                                Guardar
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Editar Tratamiento */}
            <Dialog
                open={editingItem !== null}
                onOpenChange={(open) => !open && setEditingItem(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Editar procedimiento ({editingItem?.code})
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitEdit} className="space-y-3">
                        <div>
                            <Label>Nombre del procedimiento *</Label>
                            <Input
                                required
                                value={editForm.data.name}
                                onChange={(e) =>
                                    editForm.setData('name', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label>Categoría *</Label>
                                <Input
                                    required
                                    value={editForm.data.category}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'category',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Precio base ({currency}) *</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    required
                                    value={editForm.data.default_price}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'default_price',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label>Duración (min)</Label>
                                <Input
                                    type="number"
                                    required
                                    value={editForm.data.duration_minutes}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'duration_minutes',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label>Vínculo odontograma</Label>
                                <select
                                    value={editForm.data.odontogram_condition}
                                    onChange={(e) =>
                                        editForm.setData(
                                            'odontogram_condition',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    <option value="">
                                        Sin asociación automática
                                    </option>
                                    {conditions.map((c) => (
                                        <option key={c.code} value={c.code}>
                                            {c.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <label className="flex items-center gap-2 text-xs font-medium">
                            <input
                                type="checkbox"
                                checked={editForm.data.is_active}
                                onChange={(e) =>
                                    editForm.setData(
                                        'is_active',
                                        e.target.checked,
                                    )
                                }
                            />
                            Procedimiento activo en presupuestos
                        </label>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditingItem(null)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={editForm.processing}
                                className="bg-[#0E8FA3] text-white"
                            >
                                Actualizar tratamiento
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Eliminar Tratamiento */}
            <Dialog
                open={deletingItem !== null}
                onOpenChange={(open) => !open && setDeletingItem(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar procedimiento</DialogTitle>
                        <DialogDescription>
                            ¿Seguro que deseas eliminar{' '}
                            <strong>{deletingItem?.name}</strong> del catálogo
                            del consultorio?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeletingItem(null)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={confirmDelete}
                        >
                            Eliminar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
