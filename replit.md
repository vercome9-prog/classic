# Replit.md

## Overview

This is a Command and Control (C2) panel web application designed to manage and monitor remote devices. The panel provides a dashboard interface for viewing connected devices, sending commands to them, and reviewing activity logs. It appears to be built for managing Android devices, with features for device tracking, command execution (including SMS functionality), and real-time status monitoring.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Frontend Architecture
- **Pure vanilla JavaScript** with no frameworks - all functionality is built using native browser APIs
- **Modular JavaScript pattern** - code is organized into manager objects (DevicesManager, LogsManager, TabsManager, etc.) that encapsulate related functionality
- **CSS organization** - styles are split across multiple files by concern:
  - `base.css` - resets and global styles
  - `layout.css` - header, tabs, and page structure
  - `components.css` - buttons, inputs, and reusable UI elements
  - `tables.css` - data table styling

### Data Management
- **Client-side state** - each manager maintains its own state (currentPage, allDevices, filteredLogs, etc.)
- **Pagination** - handled client-side with a dedicated Pagination utility that renders page controls
- **Polling** - devices tab auto-refreshes every 30 seconds to keep data current

### API Communication
- **Single API endpoint** - all backend communication goes through `api.php` with an `action` query parameter
- **RESTful-ish pattern** - GET requests for data retrieval, POST for commands
- **Timeout handling** - 30-second timeout on all API requests with proper error handling
- **Actions supported**:
  - `getDevices` - paginated device list with search
  - `getLogs` - paginated logs with search and type filtering
  - `getDeviceCount` - summary stats (total, online, offline)
  - `sendCommand` - execute commands on selected devices

### UI Components
- **Tab-based navigation** - switches between Devices and Logs views
- **Data tables** - selectable rows with checkbox support for bulk operations
- **Modal dialogs** - for command input (SMS messages, phone numbers, SIM slot selection)
- **Real-time stats** - header displays online/offline device counts

## External Dependencies

### Backend Requirements
- **PHP** - the `api.php` endpoint indicates a PHP backend is required
- **Database** - implied by device/log storage, specific database type not visible in frontend code

### No External JavaScript Libraries
- No jQuery, React, Vue, or other frontend frameworks
- No CSS frameworks like Bootstrap or Tailwind
- All functionality is implemented with vanilla JavaScript and custom CSS

### Browser APIs Used
- Fetch API for HTTP requests
- AbortSignal for request timeouts
- DOM manipulation APIs