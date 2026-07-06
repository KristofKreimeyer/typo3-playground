import type { ComponentVariant } from '../types';

type VariantSelectorProps = {
  variants: ComponentVariant[];
  selectedKey: string;
  onSelect: (variant: ComponentVariant) => void;
};

export function VariantSelector({ variants, selectedKey, onSelect }: VariantSelectorProps) {
  return (
    <label className="field-label">
      <span>Variant</span>
      <select value={selectedKey} onChange={(event) => {
        const variant = variants.find((item) => item.key === event.target.value);
        if (variant) onSelect(variant);
      }}>
        {variants.map((variant) => (
          <option key={variant.key} value={variant.key}>{variant.label}</option>
        ))}
      </select>
    </label>
  );
}
