<?php
require __DIR__ . "/bootstrap.php";
$user = require_operator_supervisor();

$data = json_input();
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
$coupling_type = $data['coupling_type'] ?? '';
$part_name = strtoupper(trim($data['part_name'] ?? ''));
$allowed_types = ['GC Gear Coupling', 'NA Gear Coupling', 'Roller Chain Coupling', 'Gear', 'Sprocket'];

if (!$id || $part_name === '' || !in_array($coupling_type, $allowed_types, true)) {
    json_error('Valid Coupling Range and Coupling Code are required', 400);
}

try {
    $conn->begin_transaction();
    $current_stmt = $conn->prepare("SELECT part_name, coupling_type FROM parts WHERE id = ? AND status = 'Active' FOR UPDATE");
    $current_stmt->bind_param('i', $id);
    $current_stmt->execute();
    $current = $current_stmt->get_result()->fetch_assoc();
    $current_stmt->close();
    if (!$current) throw new RuntimeException('Coupling not found', 404);

    $duplicate = $conn->prepare('SELECT id FROM parts WHERE part_name = ? AND id <> ? LIMIT 1');
    $duplicate->bind_param('si', $part_name, $id);
    $duplicate->execute();
    if ($duplicate->get_result()->num_rows > 0) {
        $duplicate->close();
        throw new RuntimeException('Coupling Code already exists', 409);
    }
    $duplicate->close();

    if ($current['coupling_type'] !== $coupling_type) {
        $active = $conn->prepare("SELECT id FROM production_jobs WHERE part_id = ? AND status IN ('Running','Handover Pending','Breakdown','Stopped') LIMIT 1");
        $active->bind_param('i', $id);
        $active->execute();
        $has_active_job = $active->get_result()->num_rows > 0;
        $active->close();
        if ($has_active_job) throw new RuntimeException('Coupling Range cannot change while this part has an active or stopped job', 409);
    }

    $update = $conn->prepare('UPDATE parts SET part_name = ?, coupling_type = ? WHERE id = ?');
    $update->bind_param('ssi', $part_name, $coupling_type, $id);
    $update->execute();
    $update->close();

    $delete_components = $conn->prepare('DELETE FROM part_components WHERE part_id = ?');
    $delete_components->bind_param('i', $id);
    $delete_components->execute();
    $delete_components->close();

    if (in_array($coupling_type, ['GC Gear Coupling', 'NA Gear Coupling'], true)) {
        $components = [['Hub', 1], ['Sleeve', 2]];
    } elseif (in_array($coupling_type, ['Roller Chain Coupling', 'Sprocket'], true)) {
        $components = [['Sprocket', 1]];
    } else {
        $components = [['Gear', 1]];
    }
    $component_stmt = $conn->prepare('INSERT INTO part_components (part_id, component_name, sort_order) VALUES (?, ?, ?)');
    foreach ($components as [$component_name, $sort_order]) {
        $component_stmt->bind_param('isi', $id, $component_name, $sort_order);
        $component_stmt->execute();
    }
    $component_stmt->close();

    $conn->commit();
    audit_log($conn, $user, 'Coupling Management', 'Updated',
        'Updated coupling ' . $current['part_name'] . ' to ' . $part_name . ' in ' . $coupling_type, 'part', (int)$id);
    json_response(['success' => true, 'id' => (int)$id]);
} catch (RuntimeException $error) {
    $conn->rollback();
    json_error($error->getMessage(), $error->getCode() >= 400 ? $error->getCode() : 400);
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    json_error($error->getCode() === 1062 ? 'Coupling Code already exists' : 'Unable to update coupling', $error->getCode() === 1062 ? 409 : 500);
} catch (Throwable $error) {
    $conn->rollback();
    json_error('Unable to update coupling', 500);
}
$conn->close();
?>
