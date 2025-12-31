<?php
require_once __DIR__ . '/../../database.php';

function sendCommand($androidIds, $command) {
    try {
        if (empty($androidIds) || !is_array($androidIds) || empty($command)) {
            return ['success' => false, 'error' => 'Missing androidIds or command'];
        }
        
        if (count($androidIds) > 100) {
            return ['success' => false, 'error' => 'Too many devices (max 100)'];
        }
        
        $pdo = getDatabaseConnection();
        if ($pdo === null) {
            return ['success' => false, 'error' => 'Database connection failed'];
        }
        
        $validIds = [];
        foreach ($androidIds as $id) {
            $id = trim($id);
            if (!empty($id) && strlen($id) <= 255) {
                $validIds[] = $id;
            }
        }
        
        if (empty($validIds)) {
            return ['success' => false, 'error' => 'No valid device IDs'];
        }
        
        $placeholders = implode(',', array_fill(0, count($validIds), '?'));
        $stmt = $pdo->prepare("UPDATE devices SET cmd = ? WHERE android_id IN ($placeholders)");
        $params = array_merge([$command], $validIds);
        $stmt->execute($params);
        
        return ['success' => true, 'updated' => $stmt->rowCount()];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Database error'];
    }
}

