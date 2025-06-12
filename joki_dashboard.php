<?php
// This file assumes it's included by index.php, so session is already started.
require_once "config/config.php";
$jokiID = $_SESSION['id'];
$errors = [];
$success_msg = "";

// Handle Joki Actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accept_order'])) {
    $orderID_to_accept = $_POST['orderID'];

    // Atomically accept the order: check if it's still available and assign it
    mysqli_begin_transaction($conn);
    try {
        // Check if the order is still available (jokiID is NULL)
        $sql_check = "SELECT jokiID FROM orderjoki WHERE orderID = ? FOR UPDATE";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "i", $orderID_to_accept);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $order_check = mysqli_fetch_assoc($result_check);

        if ($order_check && is_null($order_check['jokiID'])) {
            // It's available, so accept it
            $sql_update = "UPDATE orderjoki SET jokiID = ? WHERE orderID = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "ii", $jokiID, $orderID_to_accept);
            mysqli_stmt_execute($stmt_update);
            
            mysqli_commit($conn);
            $success_msg = "Order #" . $orderID_to_accept . " has been assigned to you.";
        } else {
            mysqli_rollback($conn);
            $errors[] = "Order is no longer available.";
        }
    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($conn);
        $errors[] = "A transaction error occurred. Please try again.";
    }
}

// Handle Joki marking order as complete
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_order'])) {
    $orderID_to_complete = $_POST['orderID'];

    // Update order status to 'selesai'
    $sql_complete = "UPDATE orderjoki SET status_order = 'selesai' WHERE orderID = ? AND jokiID = ?";
    if ($stmt = mysqli_prepare($conn, $sql_complete)) {
        mysqli_stmt_bind_param($stmt, "ii", $orderID_to_complete, $jokiID);
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Order #" . $orderID_to_complete . " has been marked as complete.";
        } else {
            $errors[] = "Failed to update order status.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch available orders (status 'diproses' and no joki assigned)
$available_orders = [];
$sql_available = "SELECT o.orderID, c.username AS customer_name, o.start_rank, o.target_rank, o.total_biaya
                  FROM orderjoki o
                  JOIN users c ON o.customerID = c.userID
                  WHERE o.status_order = 'diproses' AND o.jokiID IS NULL
                  ORDER BY o.orderID ASC";
$result_available = mysqli_query($conn, $sql_available);
if ($result_available) {
    $available_orders = mysqli_fetch_all($result_available, MYSQLI_ASSOC);
}

// Fetch orders assigned to this joki, including game account details
$my_orders = [];
$sql_my = "SELECT 
            o.orderID, 
            c.username AS customer_name, 
            o.start_rank, 
            o.target_rank, 
            o.total_biaya, 
            o.status_order,
            ag.usernameGame,
            ag.passwordGame
           FROM orderjoki o
           JOIN users c ON o.customerID = c.userID
           JOIN akun_game ag ON o.akunID = ag.akunID
           WHERE o.jokiID = ?
           ORDER BY o.orderID DESC";
if ($stmt_my = mysqli_prepare($conn, $sql_my)) {
    mysqli_stmt_bind_param($stmt_my, "i", $jokiID);
    mysqli_stmt_execute($stmt_my);
    $result_my = mysqli_stmt_get_result($stmt_my);
    $my_orders = mysqli_fetch_all($result_my, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_my);
}

// Fetch reviews for this joki
$my_reviews = [];
$avg_rating = 0;
$sql_reviews = "SELECT u.rating, u.komentar, c.username as customer_name, u.tanggal
                FROM ulasan u
                JOIN users c ON u.customerID = c.userID
                WHERE u.jokiID = ?
                ORDER BY u.tanggal DESC";
if ($stmt_reviews = mysqli_prepare($conn, $sql_reviews)) {
    mysqli_stmt_bind_param($stmt_reviews, "i", $jokiID);
    mysqli_stmt_execute($stmt_reviews);
    $result_reviews = mysqli_stmt_get_result($stmt_reviews);
    $total_rating = 0;
    $review_count = 0;
    while($row = mysqli_fetch_assoc($result_reviews)){
        $my_reviews[] = $row;
        $total_rating += $row['rating'];
        $review_count++;
    }
    if($review_count > 0){
        $avg_rating = $total_rating / $review_count;
    }
    mysqli_stmt_close($stmt_reviews);
}
?>

<?php include 'includes/header.php'; ?>

<div class="page-header">
    <h2>Hi, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Welcome to your Joki Dashboard.</h2>
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

<!-- Available Orders -->
<div class="table-container">
    <h3>Available Orders</h3>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Task</th>
                <th>Est. Payout</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($available_orders)): ?>
                <?php foreach ($available_orders as $order): ?>
                    <tr>
                        <td><?php echo $order['orderID']; ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['start_rank']) . ' to ' . htmlspecialchars($order['target_rank']); ?></td>
                        <td>Rp <?php echo number_format($order['total_biaya'], 2); ?></td>
                        <td>
                             <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="margin:0;">
                                <input type="hidden" name="orderID" value="<?php echo $order['orderID']; ?>">
                                <input type="submit" name="accept_order" class="btn" value="Kerjakan">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No available orders at the moment.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- My Accepted Orders -->
<div class="table-container">
    <h3>My Ongoing Orders</h3>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Game Account Details</th>
                <th>Task</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
             <?php if (!empty($my_orders)): ?>
                <?php foreach ($my_orders as $order): ?>
                    <tr>
                        <td><?php echo $order['orderID']; ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td>
                            <b>Username:</b> <?php echo htmlspecialchars($order['usernameGame']); ?><br>
                            <b>Password:</b> <?php echo htmlspecialchars($order['passwordGame']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($order['start_rank']) . ' to ' . htmlspecialchars($order['target_rank']); ?></td>
                        <td><?php echo ucfirst($order['status_order']); ?></td>
                        <td>
                            <?php if ($order['status_order'] == 'diproses'): ?>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="margin:0;">
                                    <input type="hidden" name="orderID" value="<?php echo $order['orderID']; ?>">
                                    <input type="submit" name="complete_order" class="btn" value="Selesai">
                                </form>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">You have not accepted any orders.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- My Reviews -->
<div class="table-container">
    <h3>My Reviews (Average Rating: <?php echo number_format($avg_rating, 2); ?> / 5)</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Rating</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
             <?php if (!empty($my_reviews)): ?>
                <?php foreach ($my_reviews as $review): ?>
                    <tr>
                        <td><?php echo date("d M Y", strtotime($review['tanggal'])); ?></td>
                        <td><?php echo htmlspecialchars($review['customer_name']); ?></td>
                        <td><?php echo str_repeat("★", $review['rating']) . str_repeat("☆", 5 - $review['rating']); ?></td>
                        <td><?php echo htmlspecialchars($review['komentar']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">You have not received any reviews yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<?php include 'includes/footer.php'; ?>