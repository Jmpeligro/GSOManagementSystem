<?php
require_once 'database.php';

checkLogin();
if (!isAdmin()) {
    $_SESSION['error'] = "You don't have permission to access this page.";
    header('Location: ../../index.php');
    exit;
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT m.*, e.name as equipment_name, e.equipment_code 
        FROM maintenance m 
        JOIN equipment e ON m.equipment_id = e.equipment_id 
        WHERE 1=1";

if (!empty($status_filter)) {
    $sql .= " AND m.status = ?";
}

if (!empty($search_query)) {
    $sql .= " AND (e.name LIKE ? OR e.equipment_code LIKE ? OR m.issue_description LIKE ?)";
}

$sql .= " ORDER BY m.created_at DESC";

$stmt = $conn->prepare($sql);

$param_index = 1;
if (!empty($status_filter)) {
    $stmt->bind_param('s', $status_filter);
    $param_index++;
}

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $stmt->bind_param('sss', $search_param, $search_param, $search_param);
}

$stmt->execute();
$result = $stmt->get_result();

include '../../components/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Management - University GSO</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <?php include '../../components/sidebar.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h2>Maintenance Management</h2>
            <a href="../../php/maintenance/create_maintenance.php" class="btn btn-primary">Create Maintenance Record</a>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
            ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
            ?>
        </div>
        <?php endif; ?>
        
        <div class="filter-section">
            <form method="get" action="../../php/maintenance/maintenance.php" class="filter-form">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search equipment...">
                </div>
                
                <button type="submit" class="btn btn-filter">Apply Filters</button>
                <a href="../../php/maintenance/maintenance.php" class="btn btn-reset">Reset</a>
            </form>
        </div>
        
        <div class="maintenance-list">
            <?php if ($result && $result->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Equipment Code</th>   
                            <th>Equipment Name</th>
                            <th>Issue</th>
                            <th>Created Date</th>
                            <th>Status</th>
                            <th>Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['equipment_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($row['issue_description'], 0, 50)) . (strlen($row['issue_description']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?></span></td>
                            <td><?php echo $row['cost'] ? '$' . number_format($row['cost'], 2) : '-'; ?></td>
                            <td class="actions">
                                <a href="../../php/maintenance/view_maintenance.php?id=<?php echo $row['maintenance_id']; ?>" class="btn btn-small">View</a>
                                
                                <?php if ($row['status'] != 'completed'): ?>
                                <a href="../../php/maintenance/update_maintenance.php?id=<?php echo $row['maintenance_id']; ?>" class="btn btn-small btn-secondary">Update</a>
                                <?php endif; ?>
                                
                                <?php if ($row['status'] == 'pending'): ?>
                                <form method="post" action="../../php/maintenance/maintenance.php" style="display:inline-block;">
                                    <input type="hidden" name="maintenance_id" value="<?php echo $row['maintenance_id']; ?>">
                                    <input type="hidden" name="new_status" value="in_progress">
                                    <button type="submit" class="btn btn-small btn-primary" name="update_status">
                                        Start Repair
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if ($row['status'] == 'in_progress'): ?>
                                <form method="post" action="../../php/maintenance/complete_maintenance.php" style="display:inline-block;">
                                    <input type="hidden" name="maintenance_id" value="<?php echo $row['maintenance_id']; ?>">
                                    <button type="submit" class="btn btn-small btn-success">
                                        Complete
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    <p>No maintenance records found with the current filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../../components/footer.php'; ?>
</body>
</html>