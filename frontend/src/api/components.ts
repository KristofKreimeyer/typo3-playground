import type {
  ComponentVariant,
  ComponentSchema,
  JsonObject,
  JsonValue,
  PlaygroundComponent,
  RenderPreviewError,
  RenderPreviewRequest,
  RenderPreviewResponse,
  PropsValidationError,
} from '../types';

declare global {
  interface Window {
    TYPO3?: {
      settings?: {
        ajaxUrls?: Record<string, string>;
      };
    };
  }
}

const routeIdentifier = 'component_playground_components';

export class PreviewApiError extends Error {
  public constructor(
    message: string,
    public readonly code?: string,
    public readonly details?: PropsValidationError[],
  ) {
    super(message);
    this.name = 'PreviewApiError';
  }
}

function isSchemaProperty(value: unknown): boolean {
  if (typeof value !== 'object' || value === null) return false;
  const candidate = value as Record<string, unknown>;
  if (candidate.type === 'string') return true;
  if (candidate.type === 'array') return isSchemaProperty(candidate.items);
  return candidate.type === 'object'
    && (candidate.required === undefined || (Array.isArray(candidate.required) && candidate.required.every((item) => typeof item === 'string')))
    && typeof candidate.properties === 'object'
    && candidate.properties !== null
    && Object.values(candidate.properties).every(isSchemaProperty);
}

function isComponentSchema(value: unknown): value is ComponentSchema {
  return isSchemaProperty(value) && (value as { type?: unknown }).type === 'object';
}

function isJsonValue(value: unknown): value is JsonValue {
  if (value === null || ['string', 'number', 'boolean'].includes(typeof value)) return true;
  if (Array.isArray(value)) return value.every(isJsonValue);
  if (typeof value === 'object') return Object.values(value).every(isJsonValue);
  return false;
}

function isJsonObject(value: unknown): value is JsonObject {
  return typeof value === 'object' && value !== null && !Array.isArray(value) && isJsonValue(value);
}

function isVariant(value: unknown): value is ComponentVariant {
  if (typeof value !== 'object' || value === null) return false;
  const candidate = value as Record<string, unknown>;
  return typeof candidate.key === 'string'
    && typeof candidate.label === 'string'
    && isJsonObject(candidate.props);
}

function isComponent(value: unknown): value is PlaygroundComponent {
  if (typeof value !== 'object' || value === null) return false;
  const candidate = value as Record<string, unknown>;
  return typeof candidate.key === 'string'
    && typeof candidate.label === 'string'
    && typeof candidate.description === 'string'
    && typeof candidate.template === 'string'
    && isComponentSchema(candidate.schema)
    && isJsonObject(candidate.defaultProps)
    && Array.isArray(candidate.variants)
    && candidate.variants.length > 0
    && candidate.variants.every(isVariant);
}

export async function fetchComponents(): Promise<PlaygroundComponent[]> {
  const endpoint = window.TYPO3?.settings?.ajaxUrls?.[routeIdentifier];
  if (!endpoint) {
    throw new Error('The TYPO3 component registry route is unavailable. Flush TYPO3 caches and reload the module.');
  }

  const response = await fetch(endpoint, {
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  });
  if (!response.ok) {
    throw new Error(`The component registry request failed with status ${response.status}.`);
  }

  const payload: unknown = await response.json();
  if (typeof payload !== 'object' || payload === null) {
    throw new Error('The component registry returned an invalid response.');
  }
  const components = (payload as Record<string, unknown>).components;
  if (!Array.isArray(components) || !components.every(isComponent)) {
    throw new Error('The component registry response does not match the expected schema.');
  }

  return components;
}

function isRenderPreviewResponse(value: unknown): value is RenderPreviewResponse {
  if (typeof value !== 'object' || value === null) return false;
  const candidate = value as Record<string, unknown>;
  return typeof candidate.html === 'string' && typeof candidate.css === 'string';
}

function isRenderPreviewError(value: unknown): value is RenderPreviewError {
  if (typeof value !== 'object' || value === null) return false;
  const error = (value as Record<string, unknown>).error;
  if (typeof error !== 'object' || error === null) return false;
  const candidate = error as Record<string, unknown>;
  return typeof candidate.code === 'string'
    && typeof candidate.message === 'string'
    && (candidate.details === undefined || (Array.isArray(candidate.details) && candidate.details.every((detail) => {
      if (typeof detail !== 'object' || detail === null) return false;
      const item = detail as Record<string, unknown>;
      return typeof item.path === 'string' && typeof item.code === 'string' && typeof item.message === 'string';
    })));
}

export async function renderPreview(
  request: RenderPreviewRequest,
  signal?: AbortSignal,
): Promise<RenderPreviewResponse> {
  const endpoint = window.TYPO3?.settings?.ajaxUrls?.component_playground_preview_render;
  if (!endpoint) {
    throw new PreviewApiError('The TYPO3 preview route is unavailable. Flush TYPO3 caches and reload the module.', 'route_unavailable');
  }

  const response = await fetch(endpoint, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(request),
    signal,
  });
  const payload: unknown = await response.json().catch(() => null);

  if (!response.ok) {
    const apiError = isRenderPreviewError(payload) ? payload.error : null;
    throw new PreviewApiError(
      apiError?.message ?? `Preview rendering failed with status ${response.status}.`,
      apiError?.code ?? 'request_failed',
      apiError?.details,
    );
  }
  if (!isRenderPreviewResponse(payload)) {
    throw new PreviewApiError('The preview endpoint returned an invalid response.', 'invalid_response');
  }

  return payload;
}
