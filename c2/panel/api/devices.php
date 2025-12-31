<?php
require_once __DIR__ . '/../../database.php';

function getDevices($page, $perPage, $search) {
    try {
        $pdo = getDatabaseConnection();
        if ($pdo === null) {
            return ['error' => 'Database connection failed', 'devices' => [], 'total' => 0, 'page' => $page, 'perPage' => $perPage, 'totalPages' => 0];
        }
        
        $offset = max(0, ($page - 1) * $perPage);
        $where = '';
        $params = [];
        
        if (!empty($search)) {
            $where = "WHERE android_id LIKE ? OR model LIKE ? OR phone_numbers LIKE ?";
            $searchParam = "%" . str_replace(['%', '_'], ['\%', '\_'], $search) . "%";
            $params = [$searchParam, $searchParam, $searchParam];
        }
        
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM devices $where");
        if (!empty($params)) {
            $countStmt->execute($params);
        } else {
            $countStmt->execute();
        }
        $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
        $total = intval($totalResult['total'] ?? 0);
        
        $limit = max(1, min(100, (int)$perPage));
        $offset = max(0, (int)$offset);
        $stmt = $pdo->prepare("SELECT * FROM devices $where ORDER BY online DESC LIMIT $limit OFFSET $offset");
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($devices === false) {
            $devices = [];
        }
        
        return [
            'devices' => $devices,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $total > 0 ? max(1, ceil($total / $perPage)) : 0
        ];
    } catch (Exception $e) {
        return ['error' => 'Database error', 'devices' => [], 'total' => 0, 'page' => $page, 'perPage' => $perPage, 'totalPages' => 0];
    }
}

function getDeviceCount() {
    try {
        $pdo = getDatabaseConnection();
        if ($pdo === null) {
            return ['count' => 0, 'online' => 0, 'offline' => 0];
        }
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM devices");
        if ($stmt === false) {
            return ['count' => 0, 'online' => 0, 'offline' => 0];
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = intval($result['count'] ?? 0);
        
        $oneMinuteAgo = date('Y-m-d H:i:s', time() - 60);
        $onlineStmt = $pdo->prepare("SELECT COUNT(*) as count FROM devices WHERE online > ?");
        $onlineStmt->execute([$oneMinuteAgo]);
        $onlineResult = $onlineStmt->fetch(PDO::FETCH_ASSOC);
        $online = intval($onlineResult['count'] ?? 0);
        $offline = max(0, $total - $online);
        
        return [
            'count' => $total,
            'online' => $online,
            'offline' => $offline
        ];
    } catch (Exception $e) {
        return ['count' => 0, 'online' => 0, 'offline' => 0];
    }
}

