<?php
/**
 * Taskmaster AI - Task Management System for Bil24 Connector
 * 
 * Usage:
 * php taskmaster.php list                    # List all tasks
 * php taskmaster.php status                  # Show project status
 * php taskmaster.php sprint <sprint_name>    # Show sprint details
 * php taskmaster.php task <task_id>          # Show task details
 * php taskmaster.php start <task_id>         # Start a task
 * php taskmaster.php complete <task_id>      # Complete a task
 * php taskmaster.php progress                # Show progress report
 */

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

class TaskmasterAI {
    private $config;
    private $configFile = 'taskmaster.json';
    
    public function __construct() {
        $this->loadConfig();
    }
    
    private function loadConfig() {
        if (!file_exists($this->configFile)) {
            die("Error: {$this->configFile} not found.\n");
        }
        
        $this->config = json_decode(file_get_contents($this->configFile), true);
        if (!$this->config) {
            die("Error: Invalid JSON in {$this->configFile}.\n");
        }
    }
    
    public function run($args) {
        if (empty($args)) {
            $this->showHelp();
            return;
        }
        
        $command = $args[0];
        
        switch ($command) {
            case 'list':
                $this->listTasks();
                break;
            case 'status':
                $this->showStatus();
                break;
            case 'sprint':
                $sprintName = $args[1] ?? null;
                $this->showSprint($sprintName);
                break;
            case 'task':
                $taskId = $args[1] ?? null;
                $this->showTask($taskId);
                break;
            case 'start':
                $taskId = $args[1] ?? null;
                $this->startTask($taskId);
                break;
            case 'complete':
                $taskId = $args[1] ?? null;
                $this->completeTask($taskId);
                break;
            case 'progress':
                $this->showProgress();
                break;
            case 'help':
            default:
                $this->showHelp();
                break;
        }
    }
    
    private function showHelp() {
        echo "Taskmaster AI - Bil24 Connector Task Management\n";
        echo "================================================\n\n";
        echo "Usage: php taskmaster.php <command> [options]\n\n";
        echo "Commands:\n";
        echo "  list                    List all tasks\n";
        echo "  status                  Show project status\n";
        echo "  sprint <sprint_name>    Show sprint details\n";
        echo "  task <task_id>          Show task details\n";
        echo "  start <task_id>         Start a task\n";
        echo "  complete <task_id>      Complete a task\n";
        echo "  progress                Show progress report\n";
        echo "  help                    Show this help\n\n";
        echo "Examples:\n";
        echo "  php taskmaster.php list\n";
        echo "  php taskmaster.php sprint sprint_1\n";
        echo "  php taskmaster.php task settings\n";
        echo "  php taskmaster.php start settings\n";
    }
    
    private function listTasks() {
        echo "Tasks for {$this->config['project']['name']}\n";
        echo "==========================================\n\n";
        
        foreach ($this->config['tasks'] as $id => $task) {
            $status = strtoupper($task['status']);
            $priority = strtoupper($task['priority']);
            $hours = $task['estimated_hours'];
            
            echo sprintf("%-20s %-8s %-8s %2dh\n", 
                $task['name'], 
                "[$status]", 
                "[$priority]", 
                $hours
            );
        }
        
        echo "\n";
    }
    
    private function showStatus() {
        $totalTasks = count($this->config['tasks']);
        $completedTasks = 0;
        $inProgressTasks = 0;
        $pendingTasks = 0;
        $totalHours = 0;
        $completedHours = 0;
        
        foreach ($this->config['tasks'] as $task) {
            $totalHours += $task['estimated_hours'];
            
            switch ($task['status']) {
                case 'completed':
                    $completedTasks++;
                    $completedHours += $task['estimated_hours'];
                    break;
                case 'in_progress':
                    $inProgressTasks++;
                    break;
                case 'pending':
                    $pendingTasks++;
                    break;
            }
        }
        
        $progress = $totalHours > 0 ? round(($completedHours / $totalHours) * 100, 1) : 0;
        
        echo "Project Status: {$this->config['project']['name']}\n";
        echo "==============================================\n\n";
        echo "Tasks: {$completedTasks} completed, {$inProgressTasks} in progress, {$pendingTasks} pending\n";
        echo "Hours: {$completedHours}/{$totalHours} ({$progress}%)\n";
        echo "Progress: " . str_repeat('█', round($progress / 5)) . str_repeat('░', 20 - round($progress / 5)) . " {$progress}%\n\n";
        
        // Show current sprint
        foreach ($this->config['sprints'] as $sprintId => $sprint) {
            $sprintProgress = $this->calculateSprintProgress($sprint['tasks']);
            if ($sprintProgress > 0 && $sprintProgress < 100) {
                echo "Current Sprint: {$sprint['name']} ({$sprintProgress}%)\n";
                break;
            }
        }
    }
    
    private function showSprint($sprintName) {
        if (!$sprintName) {
            echo "Available Sprints:\n";
            echo "==================\n\n";
            foreach ($this->config['sprints'] as $id => $sprint) {
                echo "{$id}: {$sprint['name']} ({$sprint['duration']})\n";
            }
            return;
        }
        
        if (!isset($this->config['sprints'][$sprintName])) {
            echo "Error: Sprint '$sprintName' not found.\n";
            return;
        }
        
        $sprint = $this->config['sprints'][$sprintName];
        $progress = $this->calculateSprintProgress($sprint['tasks']);
        
        echo "Sprint: {$sprint['name']}\n";
        echo "========================\n\n";
        echo "Duration: {$sprint['duration']}\n";
        echo "Goal: {$sprint['goal']}\n";
        echo "Progress: {$progress}%\n\n";
        
        echo "Tasks:\n";
        echo "------\n";
        foreach ($sprint['tasks'] as $taskId) {
            if (isset($this->config['tasks'][$taskId])) {
                $task = $this->config['tasks'][$taskId];
                $status = strtoupper($task['status']);
                echo sprintf("%-30s %-8s %2dh\n", 
                    $task['name'], 
                    "[$status]", 
                    $task['estimated_hours']
                );
            }
        }
    }
    
    private function showTask($taskId) {
        if (!$taskId) {
            echo "Available Tasks:\n";
            echo "================\n\n";
            foreach ($this->config['tasks'] as $id => $task) {
                echo "{$id}: {$task['name']}\n";
            }
            return;
        }
        
        if (!isset($this->config['tasks'][$taskId])) {
            echo "Error: Task '$taskId' not found.\n";
            return;
        }
        
        $task = $this->config['tasks'][$taskId];
        
        echo "Task: {$task['name']}\n";
        echo "====================\n\n";
        echo "Description: {$task['description']}\n";
        echo "Status: {$task['status']}\n";
        echo "Priority: {$task['priority']}\n";
        echo "Assignee: {$task['assignee']}\n";
        echo "Estimated Hours: {$task['estimated_hours']}\n\n";
        
        if (!empty($task['dependencies'])) {
            echo "Dependencies: " . implode(', ', $task['dependencies']) . "\n\n";
        }
        
        echo "Acceptance Criteria:\n";
        echo "--------------------\n";
        foreach ($task['acceptance_criteria'] as $i => $criteria) {
            echo ($i + 1) . ". {$criteria}\n";
        }
        
        if (!empty($task['files'])) {
            echo "\nFiles:\n";
            echo "------\n";
            foreach ($task['files'] as $file) {
                echo "- {$file}\n";
            }
        }
    }
    
    private function startTask($taskId) {
        if (!$taskId) {
            echo "Error: Task ID required.\n";
            return;
        }
        
        if (!isset($this->config['tasks'][$taskId])) {
            echo "Error: Task '$taskId' not found.\n";
            return;
        }
        
        $task = &$this->config['tasks'][$taskId];
        
        if ($task['status'] === 'completed') {
            echo "Task '{$task['name']}' is already completed.\n";
            return;
        }
        
        $task['status'] = 'in_progress';
        $task['started_at'] = date('Y-m-d H:i:s');
        
        $this->saveConfig();
        echo "Started task: {$task['name']}\n";
    }
    
    private function completeTask($taskId) {
        if (!$taskId) {
            echo "Error: Task ID required.\n";
            return;
        }
        
        if (!isset($this->config['tasks'][$taskId])) {
            echo "Error: Task '$taskId' not found.\n";
            return;
        }
        
        $task = &$this->config['tasks'][$taskId];
        
        if ($task['status'] === 'completed') {
            echo "Task '{$task['name']}' is already completed.\n";
            return;
        }
        
        $task['status'] = 'completed';
        $task['completed_at'] = date('Y-m-d H:i:s');
        
        $this->saveConfig();
        echo "Completed task: {$task['name']}\n";
    }
    
    private function showProgress() {
        echo "Progress Report: {$this->config['project']['name']}\n";
        echo "===============================================\n\n";
        
        // Overall progress
        $this->showStatus();
        
        // Sprint progress
        echo "Sprint Progress:\n";
        echo "================\n";
        foreach ($this->config['sprints'] as $sprintId => $sprint) {
            $progress = $this->calculateSprintProgress($sprint['tasks']);
            echo sprintf("%-15s %3d%%\n", $sprint['name'], $progress);
        }
        
        echo "\nRecent Activity:\n";
        echo "================\n";
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
        
        foreach (array_slice($recentTasks, 0, 5) as $task) {
            $time = $task['completed_at'] ?? $task['started_at'];
            $action = isset($task['completed_at']) ? 'completed' : 'started';
            echo sprintf("%s - %s %s\n", $time, $action, $task['name']);
        }
    }
    
    private function calculateSprintProgress($taskIds) {
        if (empty($taskIds)) return 0;
        
        $totalHours = 0;
        $completedHours = 0;
        
        foreach ($taskIds as $taskId) {
            if (isset($this->config['tasks'][$taskId])) {
                $task = $this->config['tasks'][$taskId];
                $totalHours += $task['estimated_hours'];
                
                if ($task['status'] === 'completed') {
                    $completedHours += $task['estimated_hours'];
                }
            }
        }
        
        return $totalHours > 0 ? round(($completedHours / $totalHours) * 100, 1) : 0;
    }
    
    private function saveConfig() {
        file_put_contents($this->configFile, json_encode($this->config, JSON_PRETTY_PRINT));
    }
}

// Run the application
$taskmaster = new TaskmasterAI();
$taskmaster->run(array_slice($argv, 1)); 