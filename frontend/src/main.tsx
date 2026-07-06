import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { App } from './App';
import './styles.css';

const mountElement = document.getElementById('component-playground-app');

if (!mountElement) {
  throw new Error('Component Playground mount element was not found.');
}

createRoot(mountElement).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
