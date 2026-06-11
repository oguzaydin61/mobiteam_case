# Mobivents - Custom Event Manager WordPress Plugin

Mobivents is a lightweight, secure, and translation-ready custom WordPress plugin engineered from scratch using native core functions and OOP design patterns. This repository serves as a code-quality portfolio demonstration to highlight clean architecture, secure data management, and decoupled capabilities without reliance on heavy third-party plugins.

---

## 🛠️ Core Engineering & Standards Applied

* **Custom Post Types & Hierarchical Taxonomies:** Registers a native `Events` registry paired with custom `Event Types` (e.g., Webinar, Conference) using clean core functions, keeping the database footprint lightweight and fast.
* **Defensive Security Framework:** Implements strict data sanitization (`sanitize_text_field`) before saving, context-aware output escaping (`esc_html`, `esc_attr`), and strict Anti-CSRF verification via WordPress Nonces.
* **Responsive Architecture:** Uses an intuitive vanilla CSS Grid implementation that provides seamless, cross-browser, and mobile-first layouts without relying on modern framework overhead.
* **Internationalization (i18n):** Fully configured for multilingual environments. Every UI string is abstracted through standard translation wrappers (`__` and `_e`), making it fully compatible with WPML/Polylang setups.
* **Decoupled/Headless Readiness:** Features a custom WordPress REST API controller mapping out a public endpoint at `/wp-json/mobivents/v1/upcoming` to serve structured JSON for modern frontend pipelines (React/Vue/iOS apps).

---

## 📁 Repository Blueprint

```text
mobivents/
├── mobivents.php         # Core Bootstrap, Hook Registry, Metadata Processors, Custom Controllers
├── css/
│   └── style.css         # Modern, Cross-Browser Responsive Grid Styles
└── languages/
    └── mobivents.pot     # Localization Blueprint for Multi-Language Deployments
