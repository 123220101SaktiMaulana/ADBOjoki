<?php
// This file assumes it's included by index.php, so session is already started.
require_once "config/config.php";
$errors = [];
$success_msg = "";

// Handle Admin Actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_payment_process'])) {
    $orderID_to_process = $_POST['orderID'];
    
    // Update order status to 'diproses', making it available for jokis
    $sql_update = "UPDATE orderjoki SET status_order = 'diproses' WHERE orderID = ?";
    if ($stmt = mysqli_prepare($conn, $sql_update)) {
        mysqli_stmt_bind_param($stmt, "i", $orderID_to_process);
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Order #" . $orderID_to_process . " has been processed and is now available for Jokis.";
        } else {
            $errors[] = "Failed to update order status.";
        }
        mysqli_stmt_close($stmt);
    }
}


// Fetch all orders for the admin view
$all_orders = [];
$sql = "SELECT 
            o.orderID, 
            c.username AS customer_name, 
            ag.usernameGame,
            o.start_rank, 
            o.target_rank, 
            o.total_biaya, 
            o.status_order, 
            p.status AS payment_status,
            j.username AS joki_name
        FROM orderjoki o
        JOIN users c ON o.customerID = c.userID
        JOIN akun_game ag ON o.akunID = ag.akunID
        JOIN pembayaran p ON o.pembayaranID = p.pembayaranID
        LEFT JOIN users j ON o.jokiID = j.userID
        ORDER BY o.orderID DESC";

$result = mysqli_query($conn, $sql);
if ($result) {
    $all_orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $errors[] = "Could not fetch orders.";
}

// Fetch all reviews for the admin view
$all_reviews = [];
$sql_reviews = "SELECT 
                    r.ulasanID,
                    c.username as customer_name,
                    j.username as joki_name,
                    r.rating,
                    r.komentar,
                    r.tanggal
                FROM ulasan r
                JOIN users c ON r.customerID = c.userID
                JOIN users j ON r.jokiID = j.userID
                ORDER BY r.tanggal DESC";
$result_reviews = mysqli_query($conn, $sql_reviews);
if($result_reviews){
    $all_reviews = mysqli_fetch_all($result_reviews, MYSQLI_ASSOC);
}

?>

<?php include 'includes/header.php'; ?>

<div class="page-header">
    <h2>Hi, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Welcome to the Admin Dashboard.</h2>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success">
        <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<div class="table-container">
    <h3>All Customer Orders</h3>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Game Account</th>
                <th>Task</th>
                <th>Joki</th>
                <th>Price</th>
                <th>Order Status</th>
                <th>Payment Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($all_orders)): ?>
                <?php foreach ($all_orders as $order): ?>
                    <tr>
                        <td><?php echo $order['orderID']; ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['usernameGame']); ?></td>
                        <td><?php echo htmlspecialchars($order['start_rank']) . ' to ' . htmlspecialchars($order['target_rank']); ?></td>
                        <td><?php echo $order['joki_name'] ? htmlspecialchars($order['joki_name']) : '<i>Unassigned</i>'; ?></td>
                        <td>Rp <?php echo number_format($order['total_biaya'], 2); ?></td>
                        <td><?php echo ucfirst($order['status_order']); ?></td>
                        <td><?php echo ucfirst($order['payment_status']); ?></td>
                        <td>
                            <?php if ($order['payment_status'] == 'terverifikasi' && $order['status_order'] == 'pending'): ?>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="margin:0;">
                                    <input type="hidden" name="orderID" value="<?php echo $order['orderID']; ?>">
                                    <input type="submit" name="confirm_payment_process" class="btn" value="Konfirmasi Pembayaran">
                                </form>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">No orders found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- All Reviews -->
<div class="table-container">
    <h3>All Customer Reviews</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Joki</th>
                <th>Rating</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
             <?php if (!empty($all_reviews)): ?>
                <?php foreach ($all_reviews as $review): ?>
                    <tr>
                        <td><?php echo date("d M Y", strtotime($review['tanggal'])); ?></td>
                        <td><?php echo htmlspecialchars($review['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($review['joki_name']); ?></td>
                        <td><?php echo str_repeat("★", $review['rating']) . str_repeat("☆", 5 - $review['rating']); ?></td>
                        <td><?php echo htmlspecialchars($review['komentar']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No reviews found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<?php include 'includes/footer.php'; ?> 