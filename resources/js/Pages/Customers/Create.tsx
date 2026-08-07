import CustomerForm from '@/Components/CustomerForm';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { CustomerType } from '@/types/models';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

export default function Create() {
    // Not a form field, so it is not part of useForm's error shape: the server
    // raises it when the submitted phone belongs to a deleted client.
    const restoreError = usePage().props.errors?.confirm_restore as string | undefined;

    // The panel sits above the form, so on a phone it lands off-screen: the
    // user taps Enregistrer at the bottom of a long form and nothing appears to
    // happen. Bring it into view and move focus to it — CustomerForm only
    // focuses fields that have errors, and this one has no field.
    const restorePanelRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (restoreError) {
            restorePanelRef.current?.focus({ preventScroll: true });
            restorePanelRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }, [restoreError]);

    const { data, setData, post, processing, errors, transform } = useForm({
        type: 'individual' as CustomerType,
        company_name: '',
        ice: '',
        if: '',
        patente: '',
        ville: '',
        first_name: '',
        last_name: '',
        phone: '',
        email: '',
        address: '',
        notes: '',
        notify_whatsapp: true,
        notify_email: true,
    });

    // The server answers with a `confirm_restore` error when this phone number
    // belongs to a deleted client who still has vehicles, invoices or
    // appointments. Resubmitting with the flag set accepts the restore. The
    // flag is sent through transform() rather than kept in form state so a
    // confirmation can never be carried over into a later, unrelated submit.
    function submitWith(confirmRestore: boolean) {
        transform((current) => ({ ...current, confirm_restore: confirmRestore }));
        post(route('customers.store'));
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        submitWith(false);
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Nouveau client
                </h2>
            }
        >
            <Head title="Nouveau client" />

            <div className="py-8">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        {restoreError && (
                            <div
                                role="alert"
                                ref={restorePanelRef}
                                tabIndex={-1}
                                className="border-b border-amber-300 bg-amber-50 p-4 outline-none"
                            >
                                <h3 className="text-sm font-semibold text-amber-900">
                                    Client supprimé trouvé
                                </h3>
                                <p className="mt-1 text-sm text-amber-800">{restoreError}</p>
                                <Button
                                    type="button"
                                    className="mt-3"
                                    disabled={processing}
                                    onClick={() => submitWith(true)}
                                >
                                    Restaurer ce client
                                </Button>
                            </div>
                        )}

                        <CustomerForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            cancelHref={route('customers.index')}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
