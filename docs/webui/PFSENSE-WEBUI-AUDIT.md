# PFsense WebUI Source Audit & Provenance

## 1. Provenance & Artifact Discovery
- **Source Artifact:** `pfsense-offline-installer.iso`
- **Location:** `C:\mitranet\pfsense-offline-installer.iso`
- **SHA-256 Checksum:** `16EBD1682C7F18D0B40300EC486611979F0D0C090300EFE2BFFD7C209A624291`
- **Package Container:** `packages/All/pfSense-system-2.9.0.pkg` (Size: 26,044,522 bytes)
- **Extracted WebUI Root:** `usr/local/www/` containing 233 PHP scripts, templates, CSS stylesheets, vendor libraries, and JavaScript helpers.

## 2. Key WebUI Components Audited

### A. Templates & Layout Includes
- `usr/local/www/head.inc`: Primary HTML header, CSS/JS inclusions, navigation bar, system dropdowns, and breadcrumbs.
- `usr/local/www/foot.inc`: Page footer, copyright notices, and modal container scripts.
- `usr/local/www/guiconfig.inc`: Global presentation helper routines and form builders.

### B. Navigation & Core Menu Pages
- **Dashboard / Status:**
  - `index.php`: Primary pfSense widget-based dashboard.
  - `status.php` / `status_interfaces.php` / `status_gateways.php`.
- **Interfaces:**
  - `interfaces.php`: Interface configuration (IPv4, IPv6, MTU, MSS, physical link).
  - `interfaces_assign.php`: Network interface assignment and port mapping.
  - `interfaces_vlan.php` & `interfaces_vlan_edit.php`: 802.1Q VLAN interface management.
  - `interfaces_bridge.php` & `interfaces_bridge_edit.php`: Bridge group configuration.
  - `interfaces_lagg.php` & `interfaces_lagg_edit.php`: Link Aggregation (LAGG / LACP).
- **Firewall:**
  - `firewall_rules.php` & `firewall_rules_edit.php`: Packet filter rule table and editor.
  - `firewall_aliases.php`: Host and port alias management.
  - `firewall_nat.php`: NAT rules (Port Forward, 1:1, Outbound).
- **Routing & System:**
  - `system_routes.php` & `system_routes_edit.php`: Static route definitions.
  - `system_gateways.php` & `system_gateways_edit.php`: Gateway definitions and monitoring.
  - `system.php` / `system_advanced_*.php`: System preferences, hostnames, DNS.
  - `status_logs.php`: System log viewers.

### C. Assets, Styles, and Libraries
- **CSS Stylesheets:**
  - `css/pfSense.css`, `css/pfSense-dark.css`, `css/login.css`.
- **Vendor Libraries (Local, Zero-CDN):**
  - `vendor/bootstrap/`: Bootstrap 3.3 responsive grid and navigation components.
  - `vendor/font-awesome/`: Local font glyphs and icons.
  - `vendor/jquery/`: jQuery 3.x core library.
  - `vendor/d3/`, `vendor/nvd3/`: SVG charting and metric rendering.
- **Client Scripting:**
  - `js/pfSense.js`, `js/pfSenseHelpers.js`.

## 3. License, Legal & Copyright Compliance
- **Copyright:** (c) Electric Sheep Fencing LLC / Rubicon Communications LLC (Netgate) and FreeBSD Project contributors.
- **Permissible Usage:** Adaptation and porting of presentation layout, CSS, icons, and workflow structures while preserving appropriate copyright notices and attribution.
- **Strict Isolation from FreeBSD Runtime:**
  - Zero FreeBSD binaries or kernel modules.
  - Zero pfctl, ipfw, or FreeBSD-specific daemons.
  - All data operations are strictly mapped to the **MitraNet REST JSON API** (`/api/v1/...`).
