<?php
require_once __DIR__ . '/../../database.php';

function getLogs($page, $perPage, $search, $typeFilter) {
    try {
        $pdo = getDatabaseConnection();
        if ($pdo === null) {
            return ['error' => 'Database connection failed', 'logs' => [], 'total' => 0, 'page' => $page, 'perPage' => $perPage, 'totalPages' => 0];
        }
        
        $offset = max(0, ($page - 1) * $perPage);
        $where = [];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(android_id LIKE ? OR log LIKE ?)";
            $searchParam = "%" . str_replace(['%', '_'], ['\%', '\_'], $search) . "%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($typeFilter)) {
            $where[] = "typelog = ?";
            $params[] = $typeFilter;
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs $whereClause");
        $countStmt->execute($params);
        $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
        $total = intval($totalResult['total'] ?? 0);
        
        $limit = max(1, min(200, (int)$perPage));
        $offset = max(0, (int)$offset);
        
        $orderBy = "ORDER BY id DESC";
        try {
            $testStmt = $pdo->query("SELECT created_at FROM logs LIMIT 1");
            $orderBy = "ORDER BY created_at DESC, id DESC";
        } catch (PDOException $e) {
            $orderBy = "ORDER BY id DESC";
        }
        
        $stmt = $pdo->prepare("SELECT * FROM logs $whereClause $orderBy LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($logs === false) {
            $logs = [];
        }
        
        return [
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $total > 0 ? max(1, ceil($total / $perPage)) : 0
        ];
    } catch (Exception $e) {
        return ['error' => 'Database error', 'logs' => [], 'total' => 0, 'page' => $page, 'perPage' => $perPage, 'totalPages' => 0];
    }
}

