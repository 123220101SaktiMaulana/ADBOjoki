<?php
require_once "config/config.php";
// session_start(); // This is now handled by header.php

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$customerID = $_SESSION['id'];
$order = null;
$errors = [];
$success_msg = "";

if ($order_id > 0) {
    // Fetch order details to ensure it belongs to the logged-in customer
    $sql = "SELECT o.orderID, o.total_biaya, p.status as payment_status, o.pembayaranID 
            FROM orderjoki o
            JOIN pembayaran p ON o.pembayaranID = p.pembayaranID
            WHERE o.orderID = ? AND o.customerID = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $order_id, $customerID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $order = mysqli_fetch_assoc($result);
        } else {
            $errors[] = "Order not found or you don't have permission to view it.";
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $errors[] = "Invalid order ID.";
}

// Handle payment confirmation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_payment'])) {
    if ($order && $order['payment_status'] == 'menunggu') {
        $pembayaranID = $order['pembayaranID'];
        // In a real scenario, this would be after a payment gateway callback.
        // Here, we simulate it with a button click.
        // Admin still needs to verify this payment. Let's change status to 'terverifikasi'
        // based on the image flow. Let's assume the button means "I have paid".
        // The admin will then "receive" it.
        // So we update status to 'terverifikasi' as "paid"
        $sql_update = "UPDATE pembayaran SET status = 'terverifikasi', metode_pembayaran = 'Bank Transfer' WHERE pembayaranID = ?";
         if ($stmt = mysqli_prepare($conn, $sql_update)) {
            mysqli_stmt_bind_param($stmt, "i", $pembayaranID);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Payment successful! An admin will verify your payment shortly.";
                // Refresh the page to show updated status
                header("refresh:3;url=customer_dashboard.php");
            } else {
                $errors[] = "Failed to update payment status. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $errors[] = "This order cannot be paid for.";
    }
}

?>

<?php include 'includes/header.php'; ?>

<div class="page-header">
    <h2>Payment Confirmation</h2>
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

<?php if ($order && empty($success_msg)): ?>
    <div class="form-container">
        <h3>Order #<?php echo $order['orderID']; ?></h3>
        <p><strong>Total Amount:</strong> Rp <?php echo number_format($order['total_biaya'], 2); ?></p>
        <p><strong>Status:</strong> <?php echo ucfirst($order['payment_status']); ?></p>
        
        <?php if ($order['payment_status'] == 'menunggu'): ?>
            <p>Please make a payment of the total amount to our bank account. After payment, click the button below.</p>
            <form action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>" method="post">
                <input type="hidden" name="order_id" value="<?php echo $order['orderID']; ?>">
                <div class="form-group">
                    <input type="submit" name="confirm_payment" class="btn" value="I Have Paid">
                </div>
            </form>
        <?php elseif ($order['payment_status'] == 'terverifikasi'): ?>
            <p>Your payment has been received and is awaiting admin confirmation.</p>
        <?php else: ?>
             <p>Your payment for this order has been processed.</p>
        <?php endif; ?>
         <a href="customer_dashboard.php">Back to Dashboard</a>
    </div>
<?php endif; ?>


<?php include 'includes/footer.php'; ?> 