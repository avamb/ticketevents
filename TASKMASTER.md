# Taskmaster AI - Project Management System

Taskmaster AI is a lightweight, PHP-based project management system designed specifically for the Bil24 Connector WordPress plugin development. It provides both command-line and web-based interfaces for tracking tasks, sprints, and project progress.

## 🚀 Features

- **Task Management**: Create, track, and manage development tasks
- **Sprint Planning**: Organize tasks into sprints with goals and timelines
- **Progress Tracking**: Real-time progress visualization with charts and metrics
- **Dependency Management**: Track task dependencies and relationships
- **Multiple Interfaces**: CLI and web-based dashboard
- **Acceptance Criteria**: Define clear acceptance criteria for each task
- **Time Estimation**: Track estimated vs actual hours
- **Activity Logging**: Automatic logging of task start/completion times

## 📁 Files

- `taskmaster.json` - Main configuration file with tasks, sprints, and project settings
- `taskmaster.php` - Command-line interface for task management
- `taskmaster-dashboard.php` - Web-based dashboard interface

## 🛠️ Installation

1. **CLI Interface**: No installation required, just run the PHP script
2. **Web Dashboard**: Access via browser at `http://your-domain.com/taskmaster-dashboard.php`

## 📖 Usage

### Command Line Interface

```bash
# List all tasks
php taskmaster.php list

# Show project status
php taskmaster.php status

# Show sprint details
php taskmaster.php sprint sprint_1

# Show task details
php taskmaster.php task settings

# Start a task
php taskmaster.php start settings

# Complete a task
php taskmaster.php complete settings

# Show progress report
php taskmaster.php progress

# Show help
php taskmaster.php help
```

### Web Dashboard

1. Open `taskmaster-dashboard.php` in your browser
2. View project statistics and progress
3. Start/complete tasks with one click
4. Monitor sprint progress
5. View recent activity

## 📊 Project Structure

### Tasks
Each task includes:
- **Name**: Descriptive task name
- **Description**: Detailed task description
- **Status**: pending, in_progress, completed
- **Priority**: high, medium, low
- **Assignee**: Team member responsible
- **Estimated Hours**: Time estimation
- **Dependencies**: Tasks that must be completed first
- **Acceptance Criteria**: Clear success criteria
- **Files**: Related files to be created/modified

### Sprints
Organized into 4 sprints:
1. **Foundation** (2 weeks): Basic plugin structure and API connectivity
2. **Core Sync** (3 weeks): Core synchronization functionality
3. **Integration & Polish** (2 weeks): WooCommerce integration and frontend
4. **Quality Assurance** (1 week): Testing and documentation

## 🎯 Current Project: Bil24 Connector

The system is configured for the Bil24 Connector WordPress plugin with the following key tasks:

### High Priority Tasks
- **Settings Page Implementation**: Admin settings for Bil24 configuration
- **Custom Post Type Registration**: Events and sessions CPTs
- **API Client**: Bil24 API integration wrapper

### Medium Priority Tasks
- **Event Synchronization**: Bidirectional sync between WordPress and Bil24
- **Session Synchronization**: Session data and capacity management
- **Order Synchronization**: Order creation and status updates
- **WooCommerce Integration**: Ticket sales integration

### Low Priority Tasks
- **Frontend Display**: Event listing and booking interface
- **Documentation**: Comprehensive documentation

## 📈 Metrics & Reporting

### Progress Tracking
- Overall project completion percentage
- Sprint-wise progress tracking
- Task status distribution
- Time estimation accuracy

### Activity Monitoring
- Recent task activities
- Start/completion timestamps
- Team velocity tracking

## 🔧 Configuration

### Adding New Tasks
Edit `taskmaster.json` to add new tasks:

```json
{
  "task_id": {
    "name": "Task Name",
    "description": "Task description",
    "status": "pending",
    "priority": "medium",
    "assignee": "developer",
    "estimated_hours": 4,
    "dependencies": [],
    "acceptance_criteria": [
      "Criteria 1",
      "Criteria 2"
    ],
    "files": [
      "path/to/file.php"
    ]
  }
}
```

### Modifying Sprints
Update sprint configuration in `taskmaster.json`:

```json
{
  "sprint_id": {
    "name": "Sprint Name",
    "duration": "2 weeks",
    "tasks": ["task1", "task2"],
    "goal": "Sprint goal description"
  }
}
```

## 🎨 Customization

### Styling the Dashboard
The web dashboard uses inline CSS for easy customization. Modify the `<style>` section in `taskmaster-dashboard.php` to change colors, layout, and styling.

### Adding New Commands
Extend the CLI by adding new methods to the `TaskmasterAI` class in `taskmaster.php`.

## 🔒 Security Considerations

- The web dashboard includes basic input sanitization
- File operations are restricted to the project directory
- No external dependencies or database requirements
- JSON configuration file should be protected from public access

## 📝 Best Practices

1. **Regular Updates**: Update task status regularly
2. **Time Tracking**: Record actual time spent on tasks
3. **Documentation**: Keep acceptance criteria clear and up-to-date
4. **Dependencies**: Maintain accurate dependency relationships
5. **Sprint Planning**: Review and adjust sprint goals as needed

## 🚀 Getting Started

1. **Review Current Tasks**: Run `php taskmaster.php list` to see all tasks
2. **Check Project Status**: Run `php taskmaster.php status` for overview
3. **Start Working**: Use `php taskmaster.php start <task_id>` to begin a task
4. **Monitor Progress**: Access the web dashboard for visual progress tracking
5. **Complete Tasks**: Use `php taskmaster.php complete <task_id>` when done

## 🤝 Contributing

To contribute to the taskmaster system:
1. Update task status as you work
2. Add new tasks as needed
3. Refine time estimates based on actual experience
4. Update acceptance criteria for clarity
5. Document any new features or requirements

## 📞 Support

For issues or questions about Taskmaster AI:
1. Check the task details with `php taskmaster.php task <task_id>`
2. Review the project structure in `taskmaster.json`
3. Use the web dashboard for visual troubleshooting
4. Refer to this documentation for usage guidelines

---

**Taskmaster AI** - Making project management simple and effective for the Bil24 Connector development team. 