# Document Center

A WordPress plugin that provides a frontend document management system with role-based access, viewer groups, document sharing, and a custom dashboard.

**Version:** 2.6

**Full documentation:** [docs/DOCUMENTATION.md](docs/DOCUMENTATION.md) — architecture, data model, roles, handlers, functions, and security in one file.

## Features

- **Frontend dashboard** — Upload, browse, search, and manage documents without using the WordPress admin.
- **Role-based access** — Three custom roles: Document Super Admin, Document Admin, and Document Viewer.
- **Viewer groups** — Organize users into hierarchical groups and share documents by group.
- **Document visibility** — Control who can see each file (admins only, everyone, or custom users/groups).
- **Document requests** — Viewers can request documents; admins can reply and track status.
- **User management** — Create users, assign groups, promote/demote admins (super admin only).
- **Unique document tags** — Each document gets a searchable alphanumeric tag.
- **Auth pages** — Built-in login and registration shortcodes with redirect logic.

## Requirements

- WordPress 5.8+
- PHP 7.4+

## Installation

1. Copy the `document-center` folder into `wp-content/plugins/`.
2. Activate **Document Center** from the WordPress **Plugins** screen.
3. Create the required pages (see [Setup](#setup) below).
4. Assign roles to users as needed.

## Setup

Create three WordPress pages with the following slugs and shortcodes:

| Page slug  | Shortcode              | Purpose              |
|------------|------------------------|----------------------|
| `sign-in`  | `[doccenter_login]`    | User login           |
| `sign-up`  | `[doccenter_register]` | User registration    |
| `dashboard`| `[doccenter_dashboard]`| Main document center |

### Redirect behavior

- **Guests** visiting any frontend page are redirected to `/sign-in`.
- **Logged-in users** with a document role visiting the home page are redirected to `/dashboard`.
- The frontend admin bar is hidden for all users.

## User Roles

| Role                   | Capabilities                                              |
|------------------------|-----------------------------------------------------------|
| `document_super_admin` | Full control; can promote/demote admins and manage all content |
| `document_admin`       | Upload, edit, delete documents; manage users and groups   |
| `document_viewer`      | View shared documents; submit document requests           |

WordPress `administrator` users are treated as super admins.

## Shortcodes

```
[doccenter_login]
[doccenter_register]
[doccenter_dashboard]
```

## Supported File Types

- **Documents:** PDF, DOCX, XLSX, PPTX, TXT
- **Images:** JPG, JPEG, PNG, GIF, WEBP, SVG
- **Video:** MP4, MOV, AVI, WEBM, MKV
- **Audio:** MP3, WAV, OGG

Maximum upload size per file: **250 MB**.

## Plugin Structure

```
document-center/
├── document-center.php              # Plugin bootstrap
├── README.md
├── docs/DOCUMENTATION.md            # Full plugin documentation
├── assets/
│   ├── css/style.css                # Dashboard styles
│   └── js/dashboard.js              # Dashboard interactions
└── includes/
    ├── class-doccenter.php          # Main loader (loads all modules)
    ├── class-doccenter-assets.php   # Script/style enqueue
    ├── core/                        # Notices, roles, admin bar
    ├── auth/                        # Redirects, login/register handlers
    ├── groups/                      # Group CRUD and member assignment
    ├── documents/                   # Upload, edit, delete, search, visibility
    ├── requests/                    # Document request CPT and handlers
    ├── users/                       # User management
    ├── ajax/                        # AJAX endpoints
    └── shortcodes/                  # Login, register, dashboard views
```

Modules are loaded in dependency order by the `DocCenter` class. All public functions use the `doccenter_*` prefix for backward compatibility.

## Development

### Adding a new module

1. Create the PHP file under the appropriate `includes/` subdirectory.
2. Add an `ABSPATH` guard at the top of the file.
3. Register the file in the `$modules` array in `includes/class-doccenter.php`.

### Enqueuing assets

Frontend CSS and JS are loaded on the `dashboard` page only, via `DocCenter_Assets` in `includes/class-doccenter-assets.php`. Asset URLs use the `DOCCENTER_PLUGIN_URL` constant and are versioned with `DOCCENTER_PLUGIN_VERSION`.

### Key constants

| Constant                  | Description                    |
|---------------------------|--------------------------------|
| `DOCCENTER_PLUGIN_VERSION`| Plugin version string          |
| `DOCCENTER_PLUGIN_DIR`    | Absolute path to plugin folder |
| `DOCCENTER_PLUGIN_URL`    | URL to plugin folder           |

## Changelog

### 2.6

- Refactored monolithic core file into modular structure
- Added `DocCenter` bootstrap class and `DocCenter_Assets` handler
- Moved assets to `assets/css/` and `assets/js/`
- Fixed `doccenter_handle_group_management` not being hooked to `init`
- Fixed guest redirect running before `ABSPATH` check
- Synced version constant with plugin header

## License

GPL v2 or later.
