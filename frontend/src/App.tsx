import { useCallback, useEffect, useState } from 'react';
import { fetchComponents } from './api/components';
import { ComponentSidebar } from './components/ComponentSidebar';
import { JsonEditor } from './components/JsonEditor';
import { PreviewPanel } from './components/PreviewPanel';
import { VariantSelector } from './components/VariantSelector';
import { playgroundComponents as fallbackComponents } from './data/components';
import type { ComponentVariant, JsonObject, PlaygroundComponent, ViewportMode } from './types';

const defaultVariantKey = 'default';
const useDevelopmentMocks = import.meta.env.DEV && import.meta.env.VITE_USE_MOCK_COMPONENTS === 'true';

function formatProps(props: JsonObject): string {
  return JSON.stringify(props, null, 2);
}

export function App() {
  const [components, setComponents] = useState<PlaygroundComponent[]>([]);
  const [selectedComponent, setSelectedComponent] = useState<PlaygroundComponent | null>(null);
  const [selectedVariantKey, setSelectedVariantKey] = useState(defaultVariantKey);
  const [editorContent, setEditorContent] = useState('');
  const [validProps, setValidProps] = useState<JsonObject>({});
  const [viewport, setViewport] = useState<ViewportMode>('desktop');
  const [editorResetKey, setEditorResetKey] = useState(0);
  const [isJsonValid, setIsJsonValid] = useState(true);
  const [isLoading, setIsLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);

  const loadProps = (props: JsonObject) => {
    setEditorContent(formatProps(props));
    setValidProps(props);
    setIsJsonValid(true);
    setEditorResetKey((currentKey) => currentKey + 1);
  };

  const initializeComponents = useCallback((loadedComponents: PlaygroundComponent[]) => {
    if (loadedComponents.length === 0) {
      setComponents([]);
      setSelectedComponent(null);
      return;
    }
    const firstComponent = loadedComponents[0];
    setComponents(loadedComponents);
    setSelectedComponent(firstComponent);
    setSelectedVariantKey(defaultVariantKey);
    loadProps(firstComponent.defaultProps);
  }, []);

  const loadComponents = useCallback(async () => {
    setIsLoading(true);
    setLoadError(null);
    try {
      initializeComponents(useDevelopmentMocks ? fallbackComponents : await fetchComponents());
    } catch (error: unknown) {
      setLoadError(error instanceof Error ? error.message : 'The component registry could not be loaded.');
    } finally {
      setIsLoading(false);
    }
  }, [initializeComponents]);

  useEffect(() => {
    void loadComponents();
  }, [loadComponents]);

  const selectComponent = (component: PlaygroundComponent) => {
    setSelectedComponent(component);
    setSelectedVariantKey(defaultVariantKey);
    loadProps(component.defaultProps);
  };

  const selectVariant = (variant: ComponentVariant) => {
    if (!selectedComponent) return;
    setSelectedVariantKey(variant.key);
    loadProps(variant.props);
  };

  if (isLoading) {
    return <main className="app-state"><div className="state-card"><strong>Loading component registry…</strong><p>Retrieving component metadata from TYPO3.</p></div></main>;
  }

  if (loadError) {
    return (
      <main className="app-state">
        <div className="state-card is-error">
          <strong>Component registry unavailable</strong>
          <p>{loadError}</p>
          <button type="button" onClick={() => void loadComponents()}>Try again</button>
        </div>
      </main>
    );
  }

  if (!selectedComponent) {
    return (
      <main className="app-state">
        <div className="state-card">
          <strong>No components registered</strong>
          <p>Add explicit component definitions in <code>Classes/Service/ComponentRegistry.php</code>, then flush TYPO3 caches and reload this module.</p>
          <button type="button" onClick={() => void loadComponents()}>Reload registry</button>
        </div>
      </main>
    );
  }

  return (
    <main className="playground-app">
      <header className="app-header">
        <div>
          <h1>TYPO3 Component Playground</h1>
          <p>Shape mock component data and inspect the preview context.</p>
        </div>
        <span className="local-data-badge">{useDevelopmentMocks ? 'Development mock data' : 'TYPO3 registry'}</span>
      </header>

      <div className="playground-workspace">
        <ComponentSidebar components={components} selectedKey={selectedComponent.key} onSelect={selectComponent} />
        <section className="editor-panel">
          <div className="panel-toolbar">
            <div><span className="panel-kicker">Props</span><h2>{selectedComponent.label}</h2></div>
            <VariantSelector variants={selectedComponent.variants} selectedKey={selectedVariantKey} onSelect={selectVariant} />
          </div>
          <div className="developer-meta editor-meta">
            <span>component <code>{selectedComponent.key}</code></span>
            <span>variant <code>{selectedVariantKey}</code></span>
            <span>JSON <code className={isJsonValid ? 'is-valid' : 'is-invalid'}>{isJsonValid ? 'valid' : 'invalid'}</code></span>
          </div>
          <JsonEditor
            value={editorContent}
            resetKey={editorResetKey}
            onTextChange={setEditorContent}
            onValidChange={setValidProps}
            onValidityChange={setIsJsonValid}
            onReset={() => {
              setSelectedVariantKey(defaultVariantKey);
              loadProps(selectedComponent.defaultProps);
            }}
          />
        </section>
        <PreviewPanel
          componentKey={selectedComponent.key}
          componentLabel={selectedComponent.label}
          variantKey={selectedVariantKey}
          viewport={viewport}
          props={validProps}
          isJsonValid={isJsonValid}
          onViewportChange={setViewport}
        />
      </div>
    </main>
  );
}
