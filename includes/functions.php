<?php
/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = 'USD') {
    switch($currency) {
        case 'BRL':
            return 'R$ ' . number_format($amount, 2, ',', '.');
        case 'USD':
        case 'USDT':
            return '$' . number_format($amount, 2, '.', ',');
        default:
            return number_format($amount, 8, '.', ',');
    }
}

/**
 * Calculate ROI percentage
 */
function calculateROI($initial_value, $final_value) {
    if ($initial_value == 0) return 0;
    return (($final_value - $initial_value) / $initial_value) * 100;
}

/**
 * Calculate days between dates
 */
function daysBetween($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    return $interval->days;
}

/**
 * Get user's operations summary
 */
function getUserOperationsSummary($user_id, $pdo) {
    try {
        // Active operations count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM operacoes WHERE usuario_id = ? AND status_operacao = 'ativa'");
        $stmt->execute([$user_id]);
        $active_operations = $stmt->fetch()['count'];
        
        // Total invested in active operations
        $stmt = $pdo->prepare("SELECT SUM(valor_inicial_usdt) as total FROM operacoes WHERE usuario_id = ? AND status_operacao = 'ativa'");
        $stmt->execute([$user_id]);
        $total_invested = $stmt->fetch()['total'] ?? 0;
        
        // Total profit from closed operations
        $stmt = $pdo->prepare("SELECT SUM(lucro_total) as total FROM operacoes WHERE usuario_id = ? AND status_operacao = 'finalizada'");
        $stmt->execute([$user_id]);
        $total_profit = $stmt->fetch()['total'] ?? 0;
        
        // Total profit from active operations (intermediate profits)
        $stmt = $pdo->prepare("SELECT SUM(rl.valor_lucro) as total FROM registros_lucro rl 
                              INNER JOIN operacoes o ON rl.operacao_id = o.id 
                              WHERE o.usuario_id = ? AND o.status_operacao = 'ativa'");
        $stmt->execute([$user_id]);
        $active_profit = $stmt->fetch()['total'] ?? 0;
        
        // Calculate days operated (unique days with profit records)
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT DATE(rl.data_registro)) as days_operated 
                              FROM registros_lucro rl 
                              INNER JOIN operacoes o ON rl.operacao_id = o.id 
                              WHERE o.usuario_id = ?");
        $stmt->execute([$user_id]);
        $days_operated = $stmt->fetch()['days_operated'] ?? 0;
        
        // Calculate average ROI per day
        $total_roi = ($total_invested > 0) ? (($active_profit / $total_invested) * 100) : 0;
        $avg_roi_per_day = ($days_operated > 0) ? ($total_roi / $days_operated) : 0;
        
        return [
            'active_operations' => $active_operations,
            'total_invested' => $total_invested,
            'total_profit' => $total_profit,
            'active_profit' => $active_profit,
            'total_profit_all' => $total_profit + $active_profit,
            'days_operated' => $days_operated,
            'avg_roi_per_day' => $avg_roi_per_day
        ];
    } catch(PDOException $e) {
        error_log("Error getting operations summary: " . $e->getMessage());
        return [
            'active_operations' => 0,
            'total_invested' => 0,
            'total_profit' => 0,
            'active_profit' => 0,
            'total_profit_all' => 0,
            'days_operated' => 0,
            'avg_roi_per_day' => 0
        ];
    }
}

// Function getDailyProfitData is declared below with full implementation

/**
 * Send email (placeholder for PHPMailer implementation)
 */
function sendEmail($to, $subject, $message) {
    // For now, just log the email - in production, implement PHPMailer
    error_log("Email to $to: $subject - $message");
    return true; // Assume success for demo
}

/**
 * Generate password reset token
 */
function generatePasswordResetToken($email, $pdo) {
    try {
        $token = generateToken(32);
        $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires_at]);
        
        return $token;
    } catch(PDOException $e) {
        error_log("Error generating reset token: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate password reset token
 */
function validatePasswordResetToken($token, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = FALSE");
        $stmt->execute([$token]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Error validating reset token: " . $e->getMessage());
        return false;
    }
}

/**
 * Use password reset token
 */
function usePasswordResetToken($token, $new_password, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // Get email from token
        $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = FALSE");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if (!$reset) {
            $pdo->rollBack();
            return false;
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $reset['email']]);
        
        // Mark token as used
        $stmt = $pdo->prepare("UPDATE password_resets SET used = TRUE WHERE token = ?");
        $stmt->execute([$token]);
        
        $pdo->commit();
        return true;
    } catch(PDOException $e) {
        $pdo->rollBack();
        error_log("Error using reset token: " . $e->getMessage());
        return false;
    }
}

/**
 * Get daily profit data for chart with real database data
 */
function getDailyProfitData($user_id, $pdo, $start_date = null, $end_date = null) {
    try {
        // Default to last 30 days if no dates provided
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        // Get profit records with operation data
        $sql = "SELECT 
                    DATE(rl.data_registro) as profit_date,
                    SUM(rl.valor_lucro) as daily_profit
                FROM registros_lucro rl 
                INNER JOIN operacoes o ON rl.operacao_id = o.id 
                WHERE o.usuario_id = ? 
                AND DATE(rl.data_registro) BETWEEN ? AND ?
                GROUP BY DATE(rl.data_registro)
                ORDER BY profit_date ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $start_date, $end_date]);
        $profit_data = $stmt->fetchAll();
        
        // Get total invested capital for ROI calculation
        $stmt = $pdo->prepare("SELECT SUM(valor_inicial_usdt) as total_invested FROM operacoes WHERE usuario_id = ?");
        $stmt->execute([$user_id]);
        $total_invested = $stmt->fetchColumn() ?: 1; // Avoid division by zero
        
        // Process only real profit data - no empty days
        $chart_data = [];
        $cumulative_profit = 0;
        
        // Group data by date and calculate cumulative
        $daily_totals = [];
        foreach ($profit_data as $record) {
            $date = $record['profit_date'];
            if (!isset($daily_totals[$date])) {
                $daily_totals[$date] = 0;
            }
            $daily_totals[$date] += $record['daily_profit'];
        }
        
        // Sort by date and build chart data
        ksort($daily_totals);
        foreach ($daily_totals as $date => $daily_profit) {
            $cumulative_profit += $daily_profit;
            $roi_percentage = ($cumulative_profit / $total_invested) * 100;
            
            $date_obj = new DateTime($date);
            $chart_data[] = [
                'date' => $date,
                'formatted_date' => $date_obj->format('d/m'),
                'daily_profit' => $daily_profit,
                'cumulative_profit' => $cumulative_profit,
                'roi_percentage' => $roi_percentage
            ];
        }
        
        return $chart_data;
    } catch(PDOException $e) {
        error_log("Error fetching chart data: " . $e->getMessage());
        return [];
    }
}
?>
