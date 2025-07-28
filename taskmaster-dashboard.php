<?php
/**
 * Taskmaster AI Dashboard - Web Interface
 * 
 * Access via: http://your-domain.com/taskmaster-dashboard.php
 */

// Prevent direct access if not in WordPress context
if (!defined('ABSPATH') && !isset($_GET['standalone'])) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

class TaskmasterDashboard {
    private $config;
    private $configFile = 'taskmaster.json';
    
    public function __construct() {
        $this->loadConfig();
        $this->handleActions();
    }
    
    private function loadConfig() {
        if (!file_exists($this->configFile)) {
            die("Error: {$this->configFile} not found.");
        }
        
        $this->config = json_decode(file_get_contents($this->configFile), true);
        if (!$this->config) {
            die("Error: Invalid JSON in {$this->configFile}.");
        }
    }
    
    private function handleActions() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'start_task':
                    $this->startTask($_POST['task_id'] ?? '');
                    break;
                case 'complete_task':
                    $this->completeTask($_POST['task_id'] ?? '');
                    break;
                case 'update_task':
                    $this->updateTask($_POST['task_id'] ?? '', $_POST);
                    break;
            }
            
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
    
    private function startTask($taskId) {
        if (isset($this->config['tasks'][$taskId])) {
            $this->config['tasks'][$taskId]['status'] = 'in_progress';
            $this->config['tasks'][$taskId]['started_at'] = date('Y-m-d H:i:s');
            $this->saveConfig();
        }
    }
    
    private function completeTask($taskId) {
        if (isset($this->config['tasks'][$taskId])) {
            $this->config['tasks'][$taskId]['status'] = 'completed';
            $this->config['tasks'][$taskId]['completed_at'] = date('Y-m-d H:i:s');
            $this->saveConfig();
        }
    }
    
    private function updateTask($taskId, $data) {
        if (isset($this->config['tasks'][$taskId])) {
            $this->config['tasks'][$taskId]['status'] = $data['status'] ?? 'pending';
            $this->config['tasks'][$taskId]['priority'] = $data['priority'] ?? 'medium';
            $this->saveConfig();
        }
    }
    
    private function saveConfig() {
        file_put_contents($this->configFile, json_encode($this->config, JSON_PRETTY_PRINT));
    }
    
    private function getProjectStats() {
        $stats = [
            'total_tasks' => count($this->config['tasks']),
            'completed_tasks' => 0,
            'in_progress_tasks' => 0,
            'pending_tasks' => 0,
            'total_hours' => 0,
            'completed_hours' => 0
        ];
        
        foreach ($this->config['tasks'] as $task) {
            $stats['total_hours'] += $task['estimated_hours'];
            
            switch ($task['status']) {
                case 'completed':
                    $stats['completed_tasks']++;
                    $stats['completed_hours'] += $task['estimated_hours'];
                    break;
                case 'in_progress':
                    $stats['in_progress_tasks']++;
                    break;
                case 'pending':
                    $stats['pending_tasks']++;
                    break;
            }
        }
        
        $stats['progress_percentage'] = $stats['total_hours'] > 0 
            ? round(($stats['completed_hours'] / $stats['total_hours']) * 100, 1) 
            : 0;
            
        return $stats;
    }
    
    public function render() {
        $stats = $this->getProjectStats();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taskmaster AI - <?php echo htmlspecialchars($this->config['project']['name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 10px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .progress-bar {
            background: #e0e0e0;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .sections {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .section-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #333;
        }
        
        .task-list {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .task-item {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }
        
        .task-item:hover {
            background: #f8f9fa;
        }
        
        .task-item:last-child {
            border-bottom: none;
        }
        
        .task-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .task-name {
            font-weight: 600;
            color: #333;
            flex: 1;
        }
        
        .task-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-in_progress {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .task-priority {
            margin-left: 10px;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7em;
            font-weight: 600;
        }
        
        .priority-high {
            background: #f8d7da;
            color: #721c24;
        }
        
        .priority-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .priority-low {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .task-description {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .task-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8em;
            color: #888;
        }
        
        .task-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8em;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .sprint-list {
            padding: 20px;
        }
        
        .sprint-item {
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .sprint-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .sprint-duration {
            font-size: 0.8em;
            color: #666;
            margin-bottom: 10px;
        }
        
        .sprint-progress {
            margin-bottom: 10px;
        }
        
        .sprint-goal {
            font-size: 0.9em;
            color: #666;
            font-style: italic;
        }
        
        .recent-activity {
            padding: 20px;
        }
        
        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.9em;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-time {
            color: #888;
            font-size: 0.8em;
        }
        
        @media (max-width: 768px) {
            .sections {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Taskmaster AI</h1>
            <p><?php echo htmlspecialchars($this->config['project']['name']); ?> - Project Dashboard</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_tasks']; ?></div>
                <div class="stat-label">Total Tasks</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['completed_tasks']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['in_progress_tasks']; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['progress_percentage']; ?>%</div>
                <div class="stat-label">Progress</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $stats['progress_percentage']; ?>%"></div>
                </div>
            </div>
        </div>
        
        <div class="sections">
            <div class="section">
                <div class="section-header">
                    <div class="section-title">Tasks</div>
                </div>
                <div class="task-list">
                    <?php foreach ($this->config['tasks'] as $taskId => $task): ?>
                    <div class="task-item">
                        <div class="task-header">
                            <div class="task-name"><?php echo htmlspecialchars($task['name']); ?></div>
                            <span class="task-status status-<?php echo $task['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                            </span>
                            <span class="task-priority priority-<?php echo $task['priority']; ?>">
                                <?php echo ucfirst($task['priority']); ?>
                            </span>
                        </div>
                        <div class="task-description"><?php echo htmlspecialchars($task['description']); ?></div>
                        <div class="task-meta">
                            <div>
                                <strong><?php echo $task['estimated_hours']; ?>h</strong> estimated
                                <?php if (!empty($task['dependencies'])): ?>
                                • Depends on: <?php echo implode(', ', $task['dependencies']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="task-actions">
                                <?php if ($task['status'] === 'pending'): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="start_task">
                                    <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
                                    <button type="submit" class="btn btn-primary">Start</button>
                                </form>
                                <?php elseif ($task['status'] === 'in_progress'): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="complete_task">
                                    <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
                                    <button type="submit" class="btn btn-success">Complete</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="section">
                <div class="section-header">
                    <div class="section-title">Sprints</div>
                </div>
                <div class="sprint-list">
                    <?php foreach ($this->config['sprints'] as $sprintId => $sprint): ?>
                    <?php 
                    $sprintProgress = 0;
                    $sprintTotalHours = 0;
                    $sprintCompletedHours = 0;
                    
                    foreach ($sprint['tasks'] as $taskId) {
                        if (isset($this->config['tasks'][$taskId])) {
                            $task = $this->config['tasks'][$taskId];
                            $sprintTotalHours += $task['estimated_hours'];
                            if ($task['status'] === 'completed') {
                                $sprintCompletedHours += $task['estimated_hours'];
                            }
                        }
                    }
                    
                    $sprintProgress = $sprintTotalHours > 0 ? round(($sprintCompletedHours / $sprintTotalHours) * 100, 1) : 0;
                    ?>
                    <div class="sprint-item">
                        <div class="sprint-name"><?php echo htmlspecialchars($sprint['name']); ?></div>
                        <div class="sprint-duration"><?php echo $sprint['duration']; ?></div>
                        <div class="sprint-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $sprintProgress; ?>%"></div>
                            </div>
                            <small><?php echo $sprintProgress; ?>% complete</small>
                        </div>
                        <div class="sprint-goal"><?php echo htmlspecialchars($sprint['goal']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="section" style="margin-top: 30px;">
            <div class="section-header">
                <div class="section-title">Recent Activity</div>
            </div>
            <div class="recent-activity">
                <?php
                $recentTasks = [];
                foreach ($this->config['tasks'] as $task) {
                    if (isset($task['started_at']) || isset($task['completed_at'])) {
                        $recentTasks[] = $task;
                    }
                }
                
                usort($recentTasks, function($a, $b) {
                    $aTime = $a['completed_at'] ?? $a['started_at'] ?? '';
                    $bTime = $b['completed_at'] ?? $b['started_at'] ?? '';
                    return strcmp($bTime, $aTime);
                });
                
                foreach (array_slice($recentTasks, 0, 10) as $task):
                    $time = $task['completed_at'] ?? $task['started_at'];
                    $action = isset($task['completed_at']) ? 'completed' : 'started';
                ?>
                <div class="activity-item">
                    <div class="activity-time"><?php echo $time; ?></div>
                    <div><?php echo ucfirst($action); ?>: <?php echo htmlspecialchars($task['name']); ?></div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($recentTasks)): ?>
                <div class="activity-item">
                    <div>No recent activity</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
        <?php
    }
}

// Initialize and render the dashboard
$dashboard = new TaskmasterDashboard();
$dashboard->render(); 