# Tailwind CSS Production Setup

This guide explains how to set up Tailwind CSS for production use instead of the CDN.

## 🚀 Quick Setup

### 1. Install Dependencies
```bash
npm install
```

### 2. Build CSS for Production
```bash
npm run build-css-prod
```

### 3. For Development (with watch mode)
```bash
npm run build-css
```

## 📁 File Structure

```
├── package.json          # npm dependencies and scripts
├── tailwind.config.js    # Tailwind configuration
├── postcss.config.js     # PostCSS configuration
├── src/
│   └── css/
│       ├── input.css     # Tailwind input file (with directives)
│       └── output.css    # Generated CSS file (auto-created)
└── *.html                # HTML files using src/css/output.css
```

## 🔧 Configuration Files

### `tailwind.config.js`
- Configures content paths for purging unused CSS
- Includes custom AnimaID color palette
- Extends with Inter font family

### `postcss.config.js`
- Configures Tailwind and Autoprefixer plugins
- Ensures cross-browser compatibility

### `src/css/input.css`
- Contains Tailwind directives (`@tailwind base`, `@tailwind components`, `@tailwind utilities`)
- Includes custom component styles for AnimaID
- Defines priority indicators and status colors

## 📋 Available Scripts

### Development
```bash
npm run build-css
```
- Watches for changes to input files
- Automatically rebuilds CSS on save
- Includes source maps for debugging

### Production
```bash
npm run build-css-prod
```
- Minifies CSS for production
- Removes unused styles (purging)
- Optimizes for performance

## 🎨 Custom AnimaID Styles

The setup includes custom styles for:
- Priority indicators (low, normal, high, urgent)
- Status badges (draft, published, archived)
- Communication cards with hover effects
- Line clamping utilities

## 🔄 Migration from CDN

### Before (CDN)
```html
<script src="https://cdn.tailwindcss.com"></script>
<style>
  .priority-low { border-left: 4px solid #10b981; }
  /* ... other custom styles ... */
</style>
```

### After (Production Build)
```html
<link rel="stylesheet" href="src/css/output.css">
```

## 🚦 Build Process

1. **Input**: `src/css/input.css` with Tailwind directives
2. **Processing**: Tailwind scans HTML files for used classes
3. **Purging**: Removes unused CSS classes
4. **Output**: `src/css/output.css` with optimized, production-ready CSS

## 📊 File Size Comparison

- **CDN**: ~250KB (uncompressed, all classes included)
- **Production Build**: ~15-30KB (minified, only used classes)

## 🔍 Content Paths

Tailwind scans these paths for class usage:
- `./*.html` - Root HTML files
- `./admin/*.html` - Admin interface files
- `./src/**/*.{js,php}` - PHP and JS source files
- `./api/**/*.{js,php}` - API files

## 🎯 Best Practices

1. **Always run production build** before deploying
2. **Use watch mode** during development
3. **Include all HTML files** in content paths
4. **Use semantic class names** for custom components
5. **Leverage Tailwind utilities** before writing custom CSS

## 🐛 Troubleshooting

### CSS not updating?
- Ensure the build process is running
- Check that HTML files are in the content paths
- Verify class names are correctly spelled

### Large CSS file?
- Run production build with purging enabled
- Check that all HTML files are accessible during build
- Review content paths in `tailwind.config.js`

### Missing styles?
- Add HTML files to content paths
- Use full class names (no abbreviations)
- Check for typos in class names

## 📚 Resources

- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [PostCSS Configuration](https://postcss.org/)
- [Autoprefixer](https://autoprefixer.github.io/)

---

## ✅ Production Ready

This setup provides:
- ✅ **Optimized CSS** with unused styles removed
- ✅ **Cross-browser compatibility** with Autoprefixer
- ✅ **Custom AnimaID branding** and components
- ✅ **Development workflow** with watch mode
- ✅ **Production builds** with minification
