<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header("Location: ../index.php");
    exit();
}

$collectors = getAllCollectors();
?>

<?php include_once '../includes/header.php'; ?>

<div class="container pb-3">
    <h2 class="my-4">Manage Waste Collectors</h2>
    
    <div class="mb-3">
        <a href="add_collector.php" class="btn btn-success">Add New Collector</a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Vehicle Number</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($collectors as $collector): ?>
                            <tr>
                                <td><?php echo $collector['id']; ?></td>
                                <td><?php echo $collector['name']; ?></td>
                                <td><?php echo $collector['email']; ?></td>
                                <td><?php echo $collector['phone']; ?></td>
                                <td><?php echo $collector['vehicle_number'] ?? 'N/A'; ?></td>
                                <td>
                                    <a href="edit_collector.php?id=<?php echo $collector['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="delete_collector.php?id=<?php echo $collector['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this collector?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>