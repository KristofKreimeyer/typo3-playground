export type JsonPrimitive = string | number | boolean | null;

export type JsonValue = JsonPrimitive | JsonObject | JsonValue[];

export type JsonObject = {
  [key: string]: JsonValue;
};

export type ComponentVariant = {
  key: string;
  label: string;
  props: JsonObject;
};

export type ComponentSchemaProperty =
  | { type: 'string' }
  | { type: 'array'; items: ComponentSchemaProperty }
  | { type: 'object'; required?: string[]; properties: Record<string, ComponentSchemaProperty> };

export type ComponentSchema = {
  type: 'object';
  required?: string[];
  properties: Record<string, ComponentSchemaProperty>;
};

export type PlaygroundComponent = {
  key: string;
  label: string;
  description: string;
  template: string;
  schema: ComponentSchema;
  defaultProps: JsonObject;
  variants: ComponentVariant[];
};

export type ViewportMode = 'desktop' | 'tablet' | 'mobile';

export type RenderPreviewRequest = {
  component: string;
  props: JsonObject;
};

export type RenderPreviewResponse = {
  html: string;
  css: string;
};

export type RenderPreviewError = {
  error: {
    code: 'invalid_json' | 'invalid_request' | 'unknown_component' | 'invalid_props' | 'render_failed' | string;
    message: string;
    details?: PropsValidationError[];
  };
};

export type PropsValidationError = {
  path: string;
  code: 'required' | 'invalid_type' | string;
  message: string;
};

export type InvalidPropsRenderError = RenderPreviewError & {
  error: {
    code: 'invalid_props';
    message: string;
    details: PropsValidationError[];
  };
};

export type RenderState =
  | { status: 'idle' }
  | { status: 'loading' }
  | { status: 'success'; preview: RenderPreviewResponse }
  | { status: 'empty' }
  | { status: 'error'; message: string; code?: string; details?: PropsValidationError[] };
