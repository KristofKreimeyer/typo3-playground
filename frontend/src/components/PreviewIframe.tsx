import { useMemo } from 'react';
import type { RenderPreviewResponse } from '../types';
import { buildPreviewDocument } from '../utils/previewDocument';

type PreviewIframeProps = {
  label: string;
  preview: RenderPreviewResponse;
};

export function PreviewIframe({ label, preview }: PreviewIframeProps) {
  const document = useMemo(() => buildPreviewDocument(preview), [preview]);

  return <iframe title={`${label} preview`} sandbox="" srcDoc={document} />;
}
