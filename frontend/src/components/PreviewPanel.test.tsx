import { act, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderPreview } from '../api/components';
import { deferred } from '../test/deferred';
import { PreviewPanel } from './PreviewPanel';

vi.mock('../api/components', () => ({
  PreviewApiError: class PreviewApiError extends Error {
    public constructor(message: string, public readonly code?: string, public readonly details?: unknown[]) { super(message); }
  },
  renderPreview: vi.fn(),
}));

const mockedRenderPreview = vi.mocked(renderPreview);

const baseProps = {
  componentKey: 'hero',
  componentLabel: 'Hero',
  variantKey: 'default',
  viewport: 'desktop' as const,
  props: { headline: 'Old' },
  isJsonValid: true,
  onViewportChange: vi.fn(),
};

describe('PreviewPanel', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    mockedRenderPreview.mockReset();
  });

  afterEach(() => vi.useRealTimers());

  it('does not render while JSON is invalid', () => {
    render(<PreviewPanel {...baseProps} isJsonValid={false} />);
    act(() => vi.advanceTimersByTime(500));
    expect(mockedRenderPreview).not.toHaveBeenCalled();
    expect(screen.getByText('Preview paused')).toBeInTheDocument();
  });

  it('shows loading and then renders successful HTML in a sandboxed iframe', async () => {
    mockedRenderPreview.mockResolvedValue({ html: '<section>Rendered hero</section>', css: '/preview.css' });
    render(<PreviewPanel {...baseProps} />);
    expect(screen.getByText('Rendering preview…')).toBeInTheDocument();

    await act(async () => { vi.advanceTimersByTime(350); await Promise.resolve(); });
    const iframe = screen.getByTitle('Hero preview');
    expect(iframe).toHaveAttribute('sandbox', '');
    expect(iframe.getAttribute('srcdoc')).toContain('Rendered hero');
    expect(iframe.getAttribute('srcdoc')).toContain('<html lang="en">');
  });

  it('shows a render error', async () => {
    mockedRenderPreview.mockRejectedValue(new Error('Renderer unavailable'));
    render(<PreviewPanel {...baseProps} />);
    await act(async () => { vi.advanceTimersByTime(350); await Promise.resolve(); });
    expect(screen.getByText('Rendering failed')).toBeInTheDocument();
    expect(screen.getByText('Renderer unavailable')).toBeInTheDocument();
  });

  it('shows structured backend props validation errors', async () => {
    const { PreviewApiError } = await import('../api/components');
    mockedRenderPreview.mockRejectedValue(new PreviewApiError(
      'The provided props do not match the component schema.',
      'invalid_props',
      [{ path: 'headline', code: 'required', message: 'headline is required.' }],
    ));
    render(<PreviewPanel {...baseProps} />);
    await act(async () => { vi.advanceTimersByTime(350); await Promise.resolve(); });

    expect(screen.getByText('Props validation failed')).toBeInTheDocument();
    expect(screen.getByText('headline is required.')).toBeInTheDocument();
    expect(screen.getByText('invalid_props')).toBeInTheDocument();
  });

  it('prevents an older slow response from replacing a newer response', async () => {
    const oldRequest = deferred<{ html: string; css: string }>();
    const newRequest = deferred<{ html: string; css: string }>();
    mockedRenderPreview.mockReturnValueOnce(oldRequest.promise).mockReturnValueOnce(newRequest.promise);

    const view = render(<PreviewPanel {...baseProps} />);
    act(() => vi.advanceTimersByTime(350));
    view.rerender(<PreviewPanel {...baseProps} props={{ headline: 'New' }} />);
    act(() => vi.advanceTimersByTime(350));

    await act(async () => newRequest.resolve({ html: '<section>New result</section>', css: '/preview.css' }));
    expect(screen.getByTitle('Hero preview').getAttribute('srcdoc')).toContain('New result');

    await act(async () => oldRequest.resolve({ html: '<section>Old result</section>', css: '/preview.css' }));
    expect(screen.getByTitle('Hero preview').getAttribute('srcdoc')).toContain('New result');
    expect(screen.getByTitle('Hero preview').getAttribute('srcdoc')).not.toContain('Old result');
  });
});
