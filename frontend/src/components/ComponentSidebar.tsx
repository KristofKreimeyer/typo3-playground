import type { PlaygroundComponent } from '../types';

type ComponentSidebarProps = {
  components: PlaygroundComponent[];
  selectedKey: string;
  onSelect: (component: PlaygroundComponent) => void;
};

export function ComponentSidebar({ components, selectedKey, onSelect }: ComponentSidebarProps) {
  return (
    <aside className="component-sidebar">
      <div className="panel-heading">
        <span className="panel-kicker">Library</span>
        <h2>Components</h2>
      </div>
      <nav aria-label="Playground components">
        <ul className="component-list">
          {components.map((component) => (
            <li key={component.key}>
              <button
                className={`component-item${component.key === selectedKey ? ' is-active' : ''}`}
                type="button"
                onClick={() => onSelect(component)}
              >
                <strong>{component.label}</strong>
                <span>{component.description}</span>
              </button>
            </li>
          ))}
        </ul>
      </nav>
    </aside>
  );
}
