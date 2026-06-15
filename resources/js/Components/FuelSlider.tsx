/**
 * Fuel-gauge slider, 0–100%. Controlled via value/onChange (number).
 */
export default function FuelSlider({
    value,
    onChange,
}: {
    value: number;
    onChange: (value: number) => void;
}) {
    return (
        <div>
            <div className="mb-2 flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Vide</span>
                <span className="font-semibold text-foreground">{value}%</span>
                <span className="text-muted-foreground">Plein</span>
            </div>
            <input
                type="range"
                min="0"
                max="100"
                step="5"
                value={value}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                    onChange(Number(e.target.value))
                }
                className="w-full accent-primary"
                aria-label="Niveau de carburant"
            />
        </div>
    );
}
