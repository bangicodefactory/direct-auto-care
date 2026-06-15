import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { useEffect, useRef, useState } from 'react';
import SignatureCanvas from 'react-signature-canvas';

/**
 * Client signature capture. Exports the drawing as a PNG File via onChange so it
 * uploads through the same multipart form as the photos. Pass null to onChange
 * when cleared/empty.
 *
 * The canvas is sized to its container width (react-signature-canvas does not
 * rescale a CSS-stretched canvas, which would offset the pointer coordinates).
 */
interface SignaturePadProps {
    onChange: (file: File | null) => void;
    error?: string;
}

export default function SignaturePad({ onChange, error }: SignaturePadProps) {
    const wrapRef = useRef<HTMLDivElement>(null);
    const padRef = useRef<SignatureCanvas>(null);
    const [width, setWidth] = useState(0);

    useEffect(() => {
        const measure = () => setWidth(wrapRef.current?.clientWidth ?? 0);
        measure();
        window.addEventListener('resize', measure);
        return () => window.removeEventListener('resize', measure);
    }, []);

    function handleEnd() {
        const pad = padRef.current;
        if (!pad || pad.isEmpty()) {
            onChange(null);
            return;
        }
        pad.getCanvas().toBlob((blob: Blob | null) => {
            onChange(blob ? new window.File([blob], 'signature.png', { type: 'image/png' }) : null);
        }, 'image/png');
    }

    function clear() {
        padRef.current?.clear();
        onChange(null);
    }

    return (
        <div className="space-y-2">
            <div ref={wrapRef} className="overflow-hidden rounded-lg border border-border bg-white">
                {width > 0 && (
                    <SignatureCanvas
                        ref={padRef}
                        onEnd={handleEnd}
                        penColor="#0d3d93"
                        canvasProps={{
                            width,
                            height: 180,
                            className: 'touch-none',
                            'aria-label': 'Signature du client',
                        }}
                    />
                )}
            </div>
            <div className="flex justify-end">
                <Button type="button" variant="outline" size="sm" onClick={clear}>
                    Effacer
                </Button>
            </div>
            <InputError message={error} />
        </div>
    );
}
