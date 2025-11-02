# 🛠️ AnimaID Tech Stack

## Overview

AnimaID is built using a lightweight, portable tech stack optimized for multi-deployment scenarios across animation centers. The stack prioritizes simplicity, maintainability, and extensibility while supporting the modular architecture described in the functional specification.

## Core Technologies

### Database
- **SQLite**: File-based relational database
  - Zero-configuration setup
  - ACID compliance
  - Suitable for single-file deployments
  - Cross-platform compatibility

### Backend
- **PHP 8.1+**: Server-side scripting language
  - Native support for web development
  - Extensive ecosystem of libraries
  - Good performance for CRUD operations
  - Easy integration with SQLite

### Frontend
- **HTML5**: Semantic markup and structure
- **TailwindCSS**: Utility-first CSS framework
  - Rapid UI development
  - Responsive design utilities
  - Small bundle size when purged
- **FontAwesome**: Icon library
  - Consistent iconography
  - CDN or local hosting options

### Client-side Scripting
- **Vanilla JavaScript (ES6+)**: Core interactivity
  - No framework overhead for simple interactions
  - Native browser APIs
  - Progressive enhancement approach

## Optional Extensions

### JavaScript Frameworks
- **Vue.js** or **React**: For complex UI components in applets
  - Component-based architecture
  - Reactive data binding
  - Ecosystem of UI libraries

### PHP Libraries & Frameworks
- **Composer**: Dependency management
- **Slim Framework** or **Laravel**: API routing and structure
  - RESTful API development
  - Middleware support
  - Dependency injection
- **PDO**: Database abstraction layer
  - Secure prepared statements
  - Multiple database support
- **JWT (JSON Web Tokens)**: Authentication tokens
  - Stateless authentication
  - Secure API access
- **PHPMailer**: Email notifications
  - SMTP support
  - HTML/plain text emails

## Infrastructure & Deployment

### Containerization
- **Docker**: Application containerization
  - Consistent environments
  - Easy scaling
  - Multi-deployment support

### Web Server
- **Nginx** or **Apache**: HTTP server
  - Static asset serving
  - PHP-FPM proxy
  - SSL/TLS termination

### Version Control
- **Git**: Source code management
  - Branching for features/modules
  - Collaborative development
  - Deployment automation

### Build Tools
- **npm/yarn**: Frontend asset compilation
  - CSS/JS minification
  - Development server
- **Composer**: PHP dependency management

## Mobile Development

### Hybrid Mobile App
- **Capacitor** or **Cordova**: Web-to-mobile bridge
  - Single codebase for web and mobile
  - Native device APIs access
  - App store distribution

### Progressive Web App (PWA)
- **Service Workers**: Offline functionality
- **Web App Manifest**: Installable web app
- **Push Notifications**: Real-time updates

## Development Environment

### Local Development
- **PHP built-in server** or **XAMPP/MAMP/WAMP**
- **SQLite browser tools** for database inspection
- **Browser DevTools** for frontend debugging

### Code Quality
- **PHPStan** or **Psalm**: Static analysis
- **ESLint**: JavaScript linting
- **Prettier**: Code formatting

## Deployment Considerations

### Single Instance Deployment
- SQLite database file
- PHP files served directly
- Static assets (CSS/JS/images)

### Multi-Instance Scaling
- Docker containers per instance
- Shared volume for applets
- Load balancer for multiple instances

### Security
- Input validation and sanitization
- Prepared statements for SQL
- HTTPS enforcement
- CSRF protection
- Role-based access control

## File Structure Example

```
animaid/
├── public/           # Web root
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── fonts/
│   └── applets/      # Applet assets
├── src/              # PHP source
│   ├── modules/
│   ├── applets/
│   ├── core/
│   └── api/
├── config/           # Configuration files
├── database/         # SQLite files
├── vendor/           # Composer dependencies
├── node_modules/     # npm dependencies
├── docker/           # Container configs
└── docs/             # Documentation
```

## Benefits of This Stack

- **Lightweight**: Minimal dependencies, fast deployment
- **Portable**: SQLite enables file-based distribution
- **Maintainable**: Familiar technologies, large community
- **Extensible**: Framework-optional approach for applets
- **Cost-effective**: Open-source tools, no licensing fees
- **Scalable**: Container-ready for cloud deployments

## Version Requirements

- PHP: 8.1 or higher
- SQLite: 3.7 or higher
- Node.js: 16+ (for build tools)
- Composer: 2.0+

---

*This tech stack document is part of the AnimaID project architecture.*
