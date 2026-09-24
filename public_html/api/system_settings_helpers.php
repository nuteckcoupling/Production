<?php
function system_settings_values(mysqli $conn): array
{
    $result = $conn->query("SELECT company_name, report_heading, company_address,
                                  idle_alert_minutes, handover_overdue_minutes,
                                  shift_end_grace_minutes, updated_at
                           FROM system_settings WHERE id = 1 LIMIT 1");
    $row = $result->fetch_assoc();
    if (!$row) {
        return [
            'company_name' => 'NU-TECK COUPLINGS',
            'report_heading' => 'Production Report',
            'company_address' => '',
            'idle_alert_minutes' => 60,
            'handover_overdue_minutes' => 30,
            'shift_end_grace_minutes' => 0,
            'updated_at' => null
        ];
    }
    foreach (['idle_alert_minutes', 'handover_overdue_minutes', 'shift_end_grace_minutes'] as $field) {
        $row[$field] = (int)$row[$field];
    }
    $row['company_address'] = $row['company_address'] ?? '';
    return $row;
}
?>
