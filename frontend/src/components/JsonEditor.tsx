import { useEffect, useState, type ChangeEvent } from 'react';
import type { JsonObject } from '../types';

type JsonEditorProps = {
  value: string;
  resetKey: number;
  onTextChange: (value: string) => void;
  onValidChange: (props: JsonObject) => void;
  onValidityChange: (isValid: boolean) => void;
  onReset: () => void;
};

function isJsonObject(value: unknown): value is JsonObject {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function parseJsonObject(value: string): JsonObject {
  const parsed: unknown = JSON.parse(value);
  if (!isJsonObject(parsed)) throw new Error('Props must be a JSON object.');
  return parsed;
}

function describeParseError(error: unknown, source: string): string {
  if (!(error instanceof Error)) return 'Invalid JSON.';
  const positionMatch = error.message.match(/position\s+(\d+)/i);
  if (!positionMatch) return error.message;
  const position = Number(positionMatch[1]);
  const precedingText = source.slice(0, position);
  const lines = precedingText.split('\n');
  return `${error.message} (line ${lines.length}, column ${(lines.at(-1)?.length ?? 0) + 1})`;
}

export function JsonEditor({
  value,
  resetKey,
  onTextChange,
  onValidChange,
  onValidityChange,
  onReset,
}: JsonEditorProps) {
  const [error, setError] = useState<string | null>(null);
  const [copyStatus, setCopyStatus] = useState<'idle' | 'copied' | 'failed'>('idle');

  useEffect(() => {
    setError(null);
    setCopyStatus('idle');
    onValidityChange(true);
  }, [resetKey, onValidityChange]);

  const updateValue = (nextValue: string) => {
    onTextChange(nextValue);
    setCopyStatus('idle');
    try {
      const parsed = parseJsonObject(nextValue);
      setError(null);
      onValidityChange(true);
      onValidChange(parsed);
    } catch (parseError: unknown) {
      setError(describeParseError(parseError, nextValue));
      onValidityChange(false);
    }
  };

  const handleChange = (event: ChangeEvent<HTMLTextAreaElement>) => updateValue(event.target.value);

  const formatJson = () => {
    try {
      updateValue(JSON.stringify(parseJsonObject(value), null, 2));
    } catch {
      // The action is disabled while the current value is invalid.
    }
  };

  const copyJson = async () => {
    try {
      await navigator.clipboard.writeText(value);
      setCopyStatus('copied');
    } catch {
      setCopyStatus('failed');
    }
  };

  return (
    <div className="json-editor">
      <div className="editor-actions">
        <label htmlFor="props-editor">Props JSON</label>
        <div>
          <button type="button" onClick={formatJson} disabled={error !== null}>Format JSON</button>
          <button type="button" onClick={onReset}>Reset to default</button>
          <button type="button" onClick={() => void copyJson()} disabled={value.length === 0}>Copy JSON</button>
        </div>
      </div>
      <textarea
        id="props-editor"
        value={value}
        onChange={handleChange}
        spellCheck={false}
        aria-invalid={error !== null}
        aria-describedby={error ? 'props-editor-error' : undefined}
      />
      <div className={`editor-status${error ? ' is-error' : ''}`} id={error ? 'props-editor-error' : undefined}>
        {error ?? (copyStatus === 'copied' ? 'JSON copied to clipboard' : copyStatus === 'failed' ? 'Could not copy JSON' : 'Valid JSON')}
      </div>
    </div>
  );
}
