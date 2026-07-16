import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import { useForm, usePage } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useState, type FormEvent } from 'react';

interface BulkImportModalProps {
    open: boolean;
    onClose: () => void;
    /** Heading shown in the modal, e.g. "Importer des véhicules". */
    title: string;
    /** GET route that streams the .xlsx template. */
    templateUrl: string;
    /** POST route that receives the uploaded file. */
    endpoint: string;
}

/**
 * Reusable bulk-import dialog: download the .xlsx template, upload a filled file,
 * and see a per-row report. Parsing/validation happen server-side; this only
 * ships the raw file and renders the flashed {@link ImportReport}.
 */
export default function BulkImportModal({
    open,
    onClose,
    title,
    templateUrl,
    endpoint,
}: BulkImportModalProps) {
    const { data, setData, post, processing, errors, reset, progress } = useForm<{
        file: File | null;
    }>({ file: null });

    const report = usePage().props.flash.importReport;

    // The report is flashed into page props and lingers there until the next
    // Inertia visit; gate it on a local flag so reopening the modal doesn't show
    // a previous run's results. It's revealed only after a successful submit and
    // hidden again whenever the modal transitions open (adjust-during-render).
    const [showReport, setShowReport] = useState(false);
    const [wasOpen, setWasOpen] = useState(open);

    if (open !== wasOpen) {
        setWasOpen(open);
        if (open) {
            setShowReport(false);
        }
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        post(endpoint, {
            forceFormData: true,
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                reset('file');
                setShowReport(true);
            },
        });
    }

    function close() {
        reset('file');
        setShowReport(false);
        onClose();
    }

    return (
        <Modal show={open} onClose={close} maxWidth="lg" title={title}>
            <form onSubmit={submit} className="space-y-5 p-6">
                <div>
                    <h2 className="text-lg font-semibold text-foreground">{title}</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Téléchargez le modèle, remplissez-le dans Excel, puis importez-le. Les
                        lignes valides sont importées ; les autres sont listées ci-dessous.
                    </p>
                </div>

                <Button type="button" variant="outline" asChild>
                    <a href={templateUrl}>
                        <Download className="h-4 w-4" />
                        <span className="ms-2">Télécharger le modèle</span>
                    </a>
                </Button>

                <div>
                    <input
                        type="file"
                        accept=".xlsx"
                        onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                        className="block w-full text-sm text-foreground file:me-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-foreground hover:file:opacity-90"
                    />
                    <InputError message={errors.file} className="mt-1" />
                    {progress && (
                        <progress value={progress.percentage} max="100" className="mt-2 w-full">
                            {progress.percentage}%
                        </progress>
                    )}
                </div>

                {showReport && report && (
                    <div className="rounded-md border border-border bg-muted/40 p-3 text-sm">
                        <p className="font-medium text-foreground">
                            {report.imported} ligne(s) importée(s), {report.failed.length} en
                            erreur.
                        </p>
                        {report.truncated && (
                            <p className="mt-1 text-warning">
                                Le fichier a été tronqué à 2000 lignes.
                            </p>
                        )}
                        {report.failed.length > 0 && (
                            <div className="mt-2 max-h-56 overflow-y-auto">
                                <table className="w-full text-xs">
                                    <thead>
                                        <tr className="text-start text-muted-foreground">
                                            <th className="pe-3 text-start font-medium">Ligne</th>
                                            <th className="text-start font-medium">Erreurs</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {report.failed.map((f) => (
                                            <tr key={f.row} className="border-t border-border">
                                                <td className="pe-3 py-1 align-top tabular-nums text-foreground">
                                                    {f.row}
                                                </td>
                                                <td className="py-1 text-destructive">
                                                    {f.errors.join(' ')}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}

                <div className="flex justify-end gap-3">
                    <Button type="button" variant="secondary" onClick={close}>
                        Fermer
                    </Button>
                    <Button type="submit" disabled={!data.file || processing}>
                        Importer
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
