import { fireEvent, render, screen } from '@testing-library/react';
import { useState } from 'react';
import { describe, expect, it, vi } from 'vitest';
import { JsonEditor } from './JsonEditor';
import type { JsonObject } from '../types';

const formattedJson = '{\n  "headline": "Example"\n}';

function renderEditor(overrides: Partial<React.ComponentProps<typeof JsonEditor>> = {}) {
  const props: React.ComponentProps<typeof JsonEditor> = {
    value: formattedJson,
    resetKey: 0,
    onTextChange: vi.fn(),
    onValidChange: vi.fn(),
    onValidityChange: vi.fn(),
    onReset: vi.fn(),
    ...overrides,
  };
  return { ...render(<JsonEditor {...props} />), props };
}

describe('JsonEditor', () => {
  it('renders formatted JSON and reports valid edits', () => {
    const { props } = renderEditor();
    expect(screen.getByRole('textbox')).toHaveValue(formattedJson);

    fireEvent.change(screen.getByRole('textbox'), { target: { value: '{"headline":"Changed"}' } });
    expect(props.onValidChange).toHaveBeenCalledWith({ headline: 'Changed' });
    expect(props.onValidityChange).toHaveBeenLastCalledWith(true);
  });

  it('shows invalid JSON without updating the last valid props', () => {
    const { props } = renderEditor();
    fireEvent.change(screen.getByRole('textbox'), { target: { value: '{invalid' } });

    expect(document.getElementById('props-editor-error')).toHaveTextContent(/position|line|JSON/i);
    expect(props.onValidChange).not.toHaveBeenCalled();
    expect(props.onValidityChange).toHaveBeenLastCalledWith(false);
    expect(screen.getByRole('button', { name: 'Format JSON' })).toBeDisabled();
  });

  it('formats valid JSON', () => {
    const onTextChange = vi.fn();
    renderEditor({ value: '{"headline":"Example"}', onTextChange });
    fireEvent.click(screen.getByRole('button', { name: 'Format JSON' }));
    expect(onTextChange).toHaveBeenCalledWith(formattedJson);
  });

  it('resets to default props through the parent callback', () => {
    const defaults: JsonObject = { headline: 'Default' };
    function Harness() {
      const [value, setValue] = useState('{"headline":"Changed"}');
      return <JsonEditor value={value} resetKey={0} onTextChange={setValue} onValidChange={vi.fn()} onValidityChange={vi.fn()} onReset={() => setValue(JSON.stringify(defaults, null, 2))} />;
    }
    render(<Harness />);
    fireEvent.click(screen.getByRole('button', { name: 'Reset to default' }));
    expect(screen.getByRole('textbox')).toHaveValue('{\n  "headline": "Default"\n}');
  });

  it('copies JSON without breaking the editor', async () => {
    const writeText = vi.fn().mockResolvedValue(undefined);
    Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText } });
    renderEditor();

    fireEvent.click(screen.getByRole('button', { name: 'Copy JSON' }));
    expect(await screen.findByText('JSON copied to clipboard')).toBeInTheDocument();
    expect(writeText).toHaveBeenCalledWith(formattedJson);
  });
});
