import { defineConfig } from 'astro/config';
import svelte from '@astrojs/svelte';

import tailwindcss from '@tailwindcss/vite';

// https://astro.build/config
export default defineConfig({
  output: 'static',

  build: {
    assets: 'assets'
  },

  integrations: [svelte()],

  vite: {
    plugins: [tailwindcss()]
  }
});