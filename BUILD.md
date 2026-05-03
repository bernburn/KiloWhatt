# Production Deployment Guide: Tailwind CSS

The current implementation uses `cdn.tailwindcss.com`, which is intended for development and prototyping. It is not suitable for production because it is heavy, slows down page loads, and runs the entire CSS engine in the browser.

## Transitioning to Production

To move to a production-ready setup, follow these steps to integrate Tailwind CSS into your build pipeline:

1. **Initialize NPM** (if not already done):
   ```bash
   npm init -y
   ```

2. **Install Tailwind CSS, PostCSS, and Autoprefixer**:
   ```bash
   npm install -D tailwindcss postcss autoprefixer
   npx tailwindcss init
   ```

3. **Configure Tailwind**:
   Update `tailwind.config.js` to include your file paths:
   ```javascript
   /** @type {import('tailwindcss').Config} */
   module.exports = {
     content: ["./*.{html,php}", "./api/*.{html,php}"],
     theme: {
       extend: {
         colors: {
           ink: '#0b1220',
           primary: '#0b1220',
           accent: '#f6c21f',
           'accent-strong': '#f59e0b',
         }
       },
     },
     plugins: [],
   }
   ```

4. **Add Tailwind Directives to `styles.css`**:
   Replace the top of your `styles.css` with:
   ```css
   @tailwind base;
   @tailwind components;
   @tailwind utilities;
   ```

5. **Build for Production**:
   Use the Tailwind CLI to compile your CSS into a single, minified file:
   ```bash
   npx tailwindcss -i ./styles.css -o ./dist/output.css --minify
   ```

6. **Update HTML/PHP**:
   Remove the `<script src="https://cdn.tailwindcss.com"></script>` tag and link to your compiled `dist/output.css` instead.
