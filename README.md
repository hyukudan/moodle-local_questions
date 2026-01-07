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
- **Export Questions**: Export questions from categories to CSV format with filtering by question type.
- **Import Questions**: Import questions from CSV files with preview before import.

## Capabilities

| Capability | Description | Default Roles |
|------------|-------------|---------------|
| `local/questions:view` | Access the dashboard | Manager, Editing Teacher |
| `local/questions:manage` | Manage plugin settings | Manager |
| `local/questions:export` | Export questions to CSV | Manager, Editing Teacher |
| `local/questions:import` | Import questions from CSV | Manager |

## Installation

1. Copy the `local_questions` folder to your Moodle's `local/` directory.
2. Visit **Site Administration → Notifications** to trigger the installation.
3. Configure at **Site Administration → Plugins → Local plugins → Questions Settings**.

## Configuration

| Setting | Description |
|---------|-------------|
| `enable_features` | Toggle extended features on/off |
| `enable_export` | Enable/disable export functionality |
| `enable_import` | Enable/disable import functionality |

## Export/Import

### Export
- Navigate to the **Export** tab
- Select a category and optionally include subcategories
- Filter by question type if needed
- Download questions as CSV

### Import
- Navigate to the **Import** tab
- Select a target category
- Upload a CSV file
- Preview questions before confirming import
- View import results with any errors

### CSV Format
The CSV file should have the following columns:
- `name` - Question name
- `questiontext` - The question text
- `qtype` - Question type (multichoice, truefalse, shortanswer)
- `answers` - Pipe-separated answers (e.g., "Answer1|Answer2|Answer3")
- `feedback` - Pipe-separated feedback for each answer
- `fractions` - Pipe-separated fractions (1 for correct, 0 for incorrect)

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
