# Questions Local Plugin (local_questions)

A local plugin for Moodle 5.1 (compatible with 4.1+) that provides enhanced management, monitoring, and analytics for Moodle questions.

## Features

- **Event Observers**: Monitors question creation, updates, and deletions with logging.
- **Statistics Dashboard**: View total question counts with caching for performance.
- **Navigation Integration**: Automatic links in site navigation for authorized users.
- **Scheduled Tasks**: Periodic recalculation of question statistics (every 6 hours).
- **Database Tracking**: Custom tables for statistics and audit logs.
- **Full Multi-language Support**: English and Spanish included.
- **Privacy API Compliance**: GDPR-ready with null provider.
- **Modern Mustache Rendering**: Templates for all UI components.

## Capabilities

| Capability | Description | Default Roles |
|------------|-------------|---------------|
| `local/questions:view` | Access the dashboard | Manager, Editing Teacher |
| `local/questions:manage` | Manage plugin settings | Manager |
| `local/questions:export` | Export statistics | Manager, Editing Teacher |

## Installation

1. Copy the `local_questions` folder to your Moodle's `local/` directory.
2. Visit **Site Administration → Notifications** to trigger the installation.
3. Configure at **Site Administration → Plugins → Local plugins → Questions Settings**.

## Configuration

| Setting | Description |
|---------|-------------|
| `enable_features` | Toggle extended features on/off |

## Scheduled Tasks

The plugin includes a scheduled task that runs every 6 hours:
- **Recalculate Statistics**: Updates question counts per category and invalidates cache.

View/modify at **Site Administration → Server → Scheduled tasks**.

## Database Tables

| Table | Purpose |
|-------|---------|
| `local_questions_stats` | Per-category question statistics |
| `local_questions_log` | Audit log of question events |

## Requirements

- Moodle 4.1 or higher
- PHP 7.4+

## License

GNU GPL v3 or later.
