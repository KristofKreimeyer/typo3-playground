import { act, fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fetchComponents, renderPreview } from './api/components';
import { App } from './App';
import { deferred } from './test/deferred';
import type { PlaygroundComponent } from './types';

vi.mock('./api/components', () => ({
  PreviewApiError: class PreviewApiError extends Error {},
  fetchComponents: vi.fn(),
  renderPreview: vi.fn(),
}));

const mockedFetchComponents = vi.mocked(fetchComponents);
const mockedRenderPreview = vi.mocked(renderPreview);
const component: PlaygroundComponent = {
  key: 'hero',
  label: 'Hero',
  description: 'Hero description',
  template: 'Hero',
  schema: { type: 'object', required: ['headline'], properties: { headline: { type: 'string' } } },
  defaultProps: { headline: 'Default hero' },
  variants: [{ key: 'default', label: 'Default', props: { headline: 'Default hero' } }],
};

describe('App component loading', () => {
  beforeEach(() => {
    mockedFetchComponents.mockReset();
    mockedRenderPreview.mockReset();
    mockedRenderPreview.mockResolvedValue({ html: '<section>Preview</section>', css: '/preview.css' });
  });

  it('shows loading while components are being fetched', () => {
    mockedFetchComponents.mockReturnValue(new Promise(() => undefined));
    render(<App />);
    expect(screen.getByText('Loading component registry…')).toBeInTheDocument();
  });

  it('shows a friendly empty registry state', async () => {
    mockedFetchComponents.mockResolvedValue([]);
    render(<App />);
    expect(await screen.findByText('No components registered')).toBeInTheDocument();
    expect(screen.getByText(/ComponentRegistry\.php/)).toBeInTheDocument();
  });

  it('shows an error and retries component loading', async () => {
    mockedFetchComponents.mockRejectedValueOnce(new Error('Registry failed')).mockResolvedValueOnce([component]);
    render(<App />);
    expect(await screen.findByText('Registry failed')).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: 'Try again' }));
    expect((await screen.findAllByText('Hero')).length).toBeGreaterThan(0);
    expect(mockedFetchComponents).toHaveBeenCalledTimes(2);
  });

  it('selects the first loaded component and its default props', async () => {
    const request = deferred<PlaygroundComponent[]>();
    mockedFetchComponents.mockReturnValue(request.promise);
    render(<App />);
    await act(async () => request.resolve([component]));

    expect(screen.getByRole('textbox')).toHaveValue('{\n  "headline": "Default hero"\n}');
    expect(screen.getAllByText('hero').length).toBeGreaterThan(0);
  });
});
