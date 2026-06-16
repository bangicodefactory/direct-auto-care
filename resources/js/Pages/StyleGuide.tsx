import { Head } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';

export default function StyleGuide() {
    return (
        <>
            <Head title="Guide de style" />

            <div className="min-h-screen bg-background p-8">
                <h1 className="mb-8 text-3xl font-bold text-foreground">
                    Direct Auto Care — Guide de style
                </h1>

                <section className="mb-10">
                    <h2 className="mb-4 text-xl font-semibold text-foreground">
                        Couleurs de marque
                    </h2>
                    <div className="flex flex-wrap gap-4">
                        <div className="flex flex-col items-center gap-2">
                            <div className="h-16 w-32 rounded-md bg-primary" />
                            <span className="text-sm text-muted-foreground">primary #002058</span>
                        </div>
                        <div className="flex flex-col items-center gap-2">
                            <div className="h-16 w-32 rounded-md bg-accent" />
                            <span className="text-sm text-muted-foreground">accent #500000</span>
                        </div>
                        <div className="flex flex-col items-center gap-2">
                            <div className="h-16 w-32 rounded-md bg-secondary" />
                            <span className="text-sm text-muted-foreground">secondary</span>
                        </div>
                        <div className="flex flex-col items-center gap-2">
                            <div className="h-16 w-32 rounded-md bg-muted" />
                            <span className="text-sm text-muted-foreground">muted</span>
                        </div>
                    </div>
                </section>

                <section className="mb-10">
                    <h2 className="mb-4 text-xl font-semibold text-foreground">
                        Boutons shadcn/ui
                    </h2>
                    <div className="flex flex-wrap gap-3">
                        <Button>Primaire</Button>
                        <Button variant="accent">Accent</Button>
                        <Button variant="secondary">Secondaire</Button>
                        <Button variant="outline">Contour</Button>
                        <Button variant="ghost">Fantôme</Button>
                        <Button variant="destructive">Supprimer</Button>
                        <Button variant="link">Lien</Button>
                        <Button disabled>Désactivé</Button>
                    </div>

                    <div className="mt-4 flex flex-wrap gap-3">
                        <Button size="sm">Petit</Button>
                        <Button size="default">Normal</Button>
                        <Button size="lg">Grand</Button>
                    </div>
                </section>
            </div>
        </>
    );
}
