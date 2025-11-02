# 🎨 AnimaID Style Guide

## Overview

This style guide establishes the visual identity and design system for the AnimaID platform. It ensures consistency across all interfaces: web admin console, staff web app, public portal, and mobile applications.

## Color Palette

### Primary Colors
- **Primary Blue**: `#2563eb` (Tailwind blue-600)
  - Used for primary actions, links, and key UI elements
- **Primary Green**: `#16a34a` (Tailwind green-600)
  - Used for success states, confirmations, and positive actions

### Secondary Colors
- **Accent Orange**: `#ea580c` (Tailwind orange-600)
  - Used for warnings, highlights, and secondary actions
- **Accent Purple**: `#9333ea` (Tailwind violet-600)
  - Used for special features and applets

### Neutral Colors
- **Gray-50**: `#f9fafb` - Very light backgrounds
- **Gray-100**: `#f3f4f6` - Light backgrounds, cards
- **Gray-200**: `#e5e7eb` - Borders, dividers
- **Gray-300**: `#d1d5db` - Muted text
- **Gray-600**: `#4b5563` - Body text
- **Gray-900**: `#111827` - Headings, strong text

### Semantic Colors
- **Success**: `#16a34a` (green-600)
- **Warning**: `#ea580c` (orange-600)
- **Error**: `#dc2626` (red-600)
- **Info**: `#2563eb` (blue-600)

## Typography

### Font Families
- **Primary**: `Inter` (Google Fonts) - Clean, modern sans-serif
- **Monospace**: `JetBrains Mono` - For code, technical content

### Font Sizes (Tailwind classes)
- **xs**: 12px - Small labels
- **sm**: 14px - Secondary text
- **base**: 16px - Body text
- **lg**: 18px - Large body text
- **xl**: 20px - Small headings
- **2xl**: 24px - Section headings
- **3xl**: 30px - Page headings
- **4xl**: 36px - Hero headings

### Font Weights
- **Light**: 300
- **Normal**: 400
- **Medium**: 500
- **Semibold**: 600
- **Bold**: 700

## Spacing

### Padding/Margin Scale (Tailwind)
- **1**: 4px (0.25rem)
- **2**: 8px (0.5rem)
- **3**: 12px (0.75rem)
- **4**: 16px (1rem)
- **5**: 20px (1.25rem)
- **6**: 24px (1.5rem)
- **8**: 32px (2rem)
- **10**: 40px (2.5rem)
- **12**: 48px (3rem)
- **16**: 64px (4rem)

## Components

### Buttons

#### Primary Button
```html
<button class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
  Primary Action
</button>
```

#### Secondary Button
```html
<button class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition-colors">
  Secondary Action
</button>
```

#### Success Button
```html
<button class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
  Success Action
</button>
```

### Cards

#### Basic Card
```html
<div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
  <h3 class="text-lg font-semibold text-gray-900 mb-2">Card Title</h3>
  <p class="text-gray-600">Card content goes here.</p>
</div>
```

#### Highlighted Card
```html
<div class="bg-blue-50 rounded-lg shadow-md p-6 border border-blue-200">
  <h3 class="text-lg font-semibold text-blue-900 mb-2">Highlighted Card</h3>
  <p class="text-blue-700">Important information.</p>
</div>
```

### Form Elements

#### Input Field
```html
<div class="mb-4">
  <label class="block text-sm font-medium text-gray-700 mb-1">Label</label>
  <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
</div>
```

#### Select Field
```html
<div class="mb-4">
  <label class="block text-sm font-medium text-gray-700 mb-1">Select Option</label>
  <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    <option>Option 1</option>
    <option>Option 2</option>
  </select>
</div>
```

## Icons

### FontAwesome Usage
- **Solid icons**: `<i class="fas fa-icon-name"></i>`
- **Regular icons**: `<i class="far fa-icon-name"></i>`
- **Brands**: `<i class="fab fa-brand-name"></i>`

### Common Icons
- **User**: `fas fa-user`
- **Calendar**: `fas fa-calendar`
- **Check**: `fas fa-check`
- **Times/X**: `fas fa-times`
- **Cog/Settings**: `fas fa-cog`
- **Bell**: `fas fa-bell`
- **Search**: `fas fa-search`
- **Plus**: `fas fa-plus`
- **Edit**: `fas fa-edit`
- **Trash**: `fas fa-trash`

## Layout Patterns

### Page Header
```html
<header class="bg-white shadow-sm border-b border-gray-200">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center py-4">
      <h1 class="text-2xl font-bold text-gray-900">Page Title</h1>
      <nav class="flex space-x-4">
        <!-- Navigation items -->
      </nav>
    </div>
  </div>
</header>
```

### Sidebar Navigation
```html
<aside class="bg-gray-50 w-64 min-h-screen border-r border-gray-200">
  <nav class="p-4">
    <ul class="space-y-2">
      <li><a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-200 rounded-md">Menu Item</a></li>
    </ul>
  </nav>
</aside>
```

### Content Grid
```html
<main class="flex-1 p-6">
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Cards or content items -->
  </div>
</main>
```

## Responsive Design

### Breakpoints (Tailwind)
- **sm**: 640px and up
- **md**: 768px and up
- **lg**: 1024px and up
- **xl**: 1280px and up
- **2xl**: 1536px and up

### Mobile-First Approach
- Design for mobile first, then enhance for larger screens
- Use responsive utilities: `sm:`, `md:`, `lg:`, `xl:`

## Accessibility

### Color Contrast
- Ensure text meets WCAG AA standards (4.5:1 ratio for normal text, 3:1 for large text)
- Use sufficient contrast between background and foreground colors

### Focus States
- All interactive elements must have visible focus indicators
- Use `focus:ring-2 focus:ring-blue-500` for consistent focus styling

### Semantic HTML
- Use appropriate HTML elements (`<button>`, `<a>`, `<nav>`, etc.)
- Provide alt text for images
- Use ARIA attributes when necessary

## Implementation Notes

### Tailwind Configuration
Add custom colors to `tailwind.config.js`:
```javascript
module.exports = {
  theme: {
    extend: {
      colors: {
        'anima-blue': '#2563eb',
        'anima-green': '#16a34a',
        'anima-orange': '#ea580c',
        'anima-purple': '#9333ea',
      }
    }
  }
}
```

### CSS Custom Properties (Optional)
```css
:root {
  --color-primary: #2563eb;
  --color-success: #16a34a;
  --color-warning: #ea580c;
  --color-error: #dc2626;
}
```

This style guide should be followed across all AnimaID interfaces to maintain visual consistency and user experience quality.
