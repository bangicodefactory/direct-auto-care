/**
 * Minimal ambient types for `react-signature-canvas`, which ships no types.
 * Covers only the surface we use (the `SignaturePad` component). Avoids adding
 * the `@types/react-signature-canvas` dependency.
 */
declare module 'react-signature-canvas' {
    import { Component, CanvasHTMLAttributes } from 'react';

    export interface SignatureCanvasProps {
        penColor?: string;
        minWidth?: number;
        maxWidth?: number;
        velocityFilterWeight?: number;
        dotSize?: number | (() => number);
        throttle?: number;
        backgroundColor?: string;
        clearOnResize?: boolean;
        onBegin?: () => void;
        onEnd?: () => void;
        canvasProps?: CanvasHTMLAttributes<HTMLCanvasElement>;
    }

    export default class SignatureCanvas extends Component<SignatureCanvasProps> {
        clear(): void;
        isEmpty(): boolean;
        getCanvas(): HTMLCanvasElement;
        getTrimmedCanvas(): HTMLCanvasElement;
        toDataURL(type?: string, encoderOptions?: number): string;
        fromDataURL(dataURL: string, options?: object): void;
        off(): void;
        on(): void;
    }
}
