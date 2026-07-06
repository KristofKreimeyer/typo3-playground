import type { ViewportMode } from '../types';

const viewportOptions: { mode: ViewportMode; label: string }[] = [
  { mode: 'desktop', label: 'Desktop' },
  { mode: 'tablet', label: 'Tablet' },
  { mode: 'mobile', label: 'Mobile' },
];

type ViewportToggleProps = {
  value: ViewportMode;
  onChange: (mode: ViewportMode) => void;
};

export function ViewportToggle({ value, onChange }: ViewportToggleProps) {
  return (
    <div className="viewport-toggle" aria-label="Preview viewport">
      {viewportOptions.map(({ mode, label }) => (
        <button
          key={mode}
          className={value === mode ? 'is-active' : ''}
          type="button"
          aria-pressed={value === mode}
          onClick={() => onChange(mode)}
        >
          {label}
        </button>
      ))}
    </div>
  );
}
