import { useEffect, useRef, useState, type CSSProperties } from 'react';
import { PreviewApiError, renderPreview } from '../api/components';
import type { JsonObject, RenderState, ViewportMode } from '../types';
import { PreviewIframe } from './PreviewIframe';
import { ViewportToggle } from './ViewportToggle';

const viewportWidths: Record<ViewportMode, number> = {
  desktop: 1200,
  tablet: 768,
  mobile: 390,
};

type PreviewPanelProps = {
  componentKey: string | null;
  componentLabel: string | null;
  variantKey: string;
  viewport: ViewportMode;
  props: JsonObject;
  isJsonValid: boolean;
  onViewportChange: (mode: ViewportMode) => void;
};

export function PreviewPanel({
  componentKey,
  componentLabel,
  variantKey,
  viewport,
  props,
  isJsonValid,
  onViewportChange,
}: PreviewPanelProps) {
  const [renderState, setRenderState] = useState<RenderState>({ status: 'idle' });
  const requestSequence = useRef(0);
  const frameStyle = { '--preview-width': `${viewportWidths[viewport]}px` } as CSSProperties;

  useEffect(() => {
    const sequence = ++requestSequence.current;
    if (!componentKey || !isJsonValid) {
      setRenderState({ status: 'idle' });
      return;
    }

    const controller = new AbortController();
    setRenderState({ status: 'loading' });
    const timeout = window.setTimeout(() => {
      void renderPreview({ component: componentKey, props }, controller.signal)
        .then((preview) => {
          if (sequence !== requestSequence.current || controller.signal.aborted) return;
          setRenderState(preview.html.trim() === '' ? { status: 'empty' } : { status: 'success', preview });
        })
        .catch((requestError: unknown) => {
          if (controller.signal.aborted || sequence !== requestSequence.current) return;
          setRenderState({
            status: 'error',
            message: requestError instanceof Error ? requestError.message : 'The preview could not be rendered.',
            code: requestError instanceof PreviewApiError ? requestError.code : undefined,
            details: requestError instanceof PreviewApiError ? requestError.details : undefined,
          });
        });
    }, 350);

    return () => {
      window.clearTimeout(timeout);
      controller.abort();
    };
  }, [componentKey, props, isJsonValid]);

  const statusLabel = !isJsonValid ? 'invalid JSON' : renderState.status;

  return (
    <section className="preview-panel">
      <div className="panel-toolbar">
        <div><span className="panel-kicker">Preview</span><h2>{componentLabel ?? 'No component'}</h2></div>
        <ViewportToggle value={viewport} onChange={onViewportChange} />
      </div>
      <div className="developer-meta">
        <span>component <code>{componentKey ?? 'none'}</code></span>
        <span>variant <code>{variantKey}</code></span>
        <span>viewport <code>{viewportWidths[viewport]}px</code></span>
        <span>JSON <code className={isJsonValid ? 'is-valid' : 'is-invalid'}>{isJsonValid ? 'valid' : 'invalid'}</code></span>
        <span>render <code>{statusLabel}</code></span>
      </div>
      <div className="preview-canvas">
        <div className="preview-frame" style={frameStyle}>
          <div className="preview-frame-header"><span>{viewport} preview</span><span>{viewportWidths[viewport]} px</span></div>
          {!componentKey && <div className="preview-message"><strong>No component selected</strong><p>Choose a component to start a preview.</p></div>}
          {componentKey && !isJsonValid && <div className="preview-message is-warning"><strong>Preview paused</strong><p>Fix the JSON error to render the component.</p></div>}
          {componentKey && isJsonValid && renderState.status === 'idle' && <div className="preview-message"><strong>Preview ready</strong></div>}
          {componentKey && isJsonValid && renderState.status === 'loading' && <div className="preview-message is-loading"><span className="loading-indicator" /><strong>Rendering preview…</strong><p>The previous result has been cleared.</p></div>}
          {componentKey && isJsonValid && renderState.status === 'empty' && <div className="preview-message is-warning"><strong>Empty render result</strong><p>The Fluid template returned no HTML for these props.</p></div>}
          {componentKey && isJsonValid && renderState.status === 'error' && (
            <div className="preview-message is-error">
              <strong>{renderState.code === 'invalid_props' ? 'Props validation failed' : 'Rendering failed'}</strong>
              <p>{renderState.message}</p>
              {renderState.details && renderState.details.length > 0 && (
                <ul className="validation-errors">
                  {renderState.details.map((detail, index) => <li key={`${detail.path}:${detail.code}:${index}`}><code>{detail.path}</code> {detail.message}</li>)}
                </ul>
              )}
              {renderState.code && <code>{renderState.code}</code>}
            </div>
          )}
          {componentKey && isJsonValid && renderState.status === 'success' && <PreviewIframe label={componentLabel ?? componentKey} preview={renderState.preview} />}
        </div>
      </div>
    </section>
  );
}
