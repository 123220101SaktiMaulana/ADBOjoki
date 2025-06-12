<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// This file assumes it's included by index.php, so session is already started.
require_once "config/config.php";
$customerID = $_SESSION['id'];
$errors = [];
$success_msg = "";

// Handle adding a new game account
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_account'])) {
    $usernameGame = trim($_POST['usernameGame']);
    $passwordGame = trim($_POST['passwordGame']); // In a real app, this should be handled more securely

    if (empty($usernameGame) || empty($passwordGame)) {
        $errors[] = "Game username and password cannot be empty.";
    } else {
        $sql = "INSERT INTO akun_game (customerID, usernameGame, passwordGame) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iss", $customerID, $usernameGame, $passwordGame);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Game account added successfully! The page will refresh.";
                 echo "<meta http-equiv='refresh' content='2'>";
            } else {
                $errors[] = "Error adding account. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle creating a new order
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_order'])) {
    $akunID = $_POST['akunID'];
    $start_rank = $_POST['start_rank'];
    $target_rank = $_POST['target_rank'];
    $total_biaya = $_POST['total_biaya'];

    if (empty($akunID) || empty($start_rank) || empty($target_rank) || empty($total_biaya)) {
        $errors[] = "Please fill all fields for the order.";
    } else {
        // Transaction: create payment record, then create order record.
        mysqli_begin_transaction($conn);
        try {
            // 1. Create Pembayaran record
            $sql_payment = "INSERT INTO pembayaran (jumlah, metode_pembayaran, status) VALUES (?, 'pending', 'menunggu')";
            $stmt_payment = mysqli_prepare($conn, $sql_payment);
            mysqli_stmt_bind_param($stmt_payment, "d", $total_biaya);
            mysqli_stmt_execute($stmt_payment);
            $pembayaranID = mysqli_insert_id($conn);

            // 2. Create OrderJoki record
            // JokiID is set to NULL because it's unassigned initially.
            $sql_order = "INSERT INTO orderjoki (customerID, akunID, jokiID, pembayaranID, start_rank, target_rank, total_biaya, status_order) VALUES (?, ?, NULL, ?, ?, ?, ?, 'pending')";
            $stmt_order = mysqli_prepare($conn, $sql_order);
            mysqli_stmt_bind_param($stmt_order, "iiissd", $customerID, $akunID, $pembayaranID, $start_rank, $target_rank, $total_biaya);
            mysqli_stmt_execute($stmt_order);

            mysqli_commit($conn);
            $success_msg = "Order created successfully! Please proceed to payment.";
            echo "<meta http-equiv='refresh' content='2'>";

        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($conn);
            $errors[] = "Error creating order. Please try again. " . $exception->getMessage();
        }
    }
}

// Handle payment confirmation directly from dashboard
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pay_now'])) {
    $pembayaranID = $_POST['pembayaranID'];
    // Simulate payment by updating status to 'terverifikasi'
    $sql_update = "UPDATE pembayaran SET status = 'terverifikasi' WHERE pembayaranID = ?";
    if ($stmt = mysqli_prepare($conn, $sql_update)) {
        mysqli_stmt_bind_param($stmt, "i", $pembayaranID);
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Payment confirmed. An admin will verify your payment shortly.";
        } else {
            $errors[] = "Failed to update payment status. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch customer's game accounts
$accounts = [];
$sql = "SELECT akunID, usernameGame FROM akun_game WHERE customerID = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $customerID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $accounts[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Fetch order history
$orders = [];
$sql_orders = "SELECT o.orderID, o.pembayaranID, a.usernameGame, o.start_rank, o.target_rank, o.total_biaya, o.status_order, p.status as status_pembayaran, 
    (SELECT u.ulasanID FROM ulasan u WHERE u.jokiID = o.jokiID AND u.customerID = o.customerID LIMIT 1) as review_exists 
    FROM orderjoki o 
    JOIN akun_game a ON o.akunID = a.akunID 
    JOIN pembayaran p ON o.pembayaranID = p.pembayaranID 
    WHERE o.customerID = ? 
    ORDER BY o.orderID DESC";
if($stmt = mysqli_prepare($conn, $sql_orders)){
    mysqli_stmt_bind_param($stmt, "i", $customerID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $orders[] = $row;
    }
    mysqli_stmt_close($stmt);
}


// Hardcoded ranks and prices
$ranks = [
    "Warrior" => 10, "Elite" => 20, "Master" => 30, "Grandmaster" => 40, "Epic" => 50, "Legend" => 60, "Mythic" => 80, "Mythical Glory" => 100
];

?>

<?php include 'includes/header.php'; ?>

<div class="page-header">
    <h2>Hi, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Welcome to your dashboard.</h2>
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

<div style="display: flex; gap: 20px; flex-wrap: wrap;">
    <!-- Section for Managing Game Accounts -->
    <div class="form-container" style="flex: 1; min-width: 300px;">
        <h3>Manage Your Game Accounts</h3>
        
        <?php if (!empty($accounts)): ?>
            <h4>Your Accounts:</h4>
            <ul>
                <?php foreach ($accounts as $account): ?>
                    <li><?php echo htmlspecialchars($account['usernameGame']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You have not added any game accounts yet.</p>
        <?php endif; ?>
        <hr>
        <h4>Add New Account:</h4>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Game Username</label>
                <input type="text" name="usernameGame" required>
            </div>
            <div class="form-group">
                <label>Game Password</label>
                <input type="password" name="passwordGame" required>
            </div>
            <div class="form-group">
                <input type="submit" name="add_account" class="btn" value="Add Account">
            </div>
        </form>
    </div>


    <!-- Section for Creating a New Order -->
    <div class="form-container" style="flex: 1; min-width: 300px;">
        <h3>Create New Joki Order</h3>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="orderForm">
            <div class="form-group">
                <label>Select Game Account</label>
                <select name="akunID" required>
                    <option value="">-- Select Account --</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?php echo $account['akunID']; ?>"><?php echo htmlspecialchars($account['usernameGame']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Start Rank</label>
                <select name="start_rank" id="start_rank" required>
                    <option value="">-- Select Rank --</option>
                    <?php foreach ($ranks as $rank => $price): ?>
                        <option value="<?php echo $rank; ?>" data-price="<?php echo $price; ?>"><?php echo $rank; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Target Rank</label>
                <select name="target_rank" id="target_rank" required>
                    <option value="">-- Select Rank --</option>
                     <?php foreach ($ranks as $rank => $price): ?>
                        <option value="<?php echo $rank; ?>" data-price="<?php echo $price; ?>"><?php echo $rank; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Total Price</label>
                <input type="text" name="total_biaya" id="total_biaya" readonly>
            </div>
            <div class="form-group">
                <input type="submit" name="create_order" class="btn" value="Place Order">
            </div>
        </form>
    </div>
</div>

<!-- Section for Order History -->
<div class="table-container">
    <h3>Your Order History</h3>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Game Account</th>
                <th>Start Rank</th>
                <th>Target Rank</th>
                <th>Price</th>
                <th>Order Status</th>
                <th>Payment Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo $order['orderID']; ?></td>
                        <td><?php echo htmlspecialchars($order['usernameGame']); ?></td>
                        <td><?php echo htmlspecialchars($order['start_rank']); ?></td>
                        <td><?php echo htmlspecialchars($order['target_rank']); ?></td>
                        <td>Rp <?php echo number_format($order['total_biaya'], 2); ?></td>
                        <td><?php echo ucfirst($order['status_order']); ?></td>
                        <td>
                            <?php
                                if ($order['status_pembayaran'] == 'menunggu') {
                                    echo 'Menunggu Pembayaran';
                                } elseif ($order['status_pembayaran'] == 'terverifikasi' && $order['status_order'] == 'pending') {
                                    echo 'Menunggu Konfirmasi Admin';
                                } elseif ($order['status_pembayaran'] == 'terverifikasi' && ($order['status_order'] == 'diproses' || $order['status_order'] == 'selesai')) {
                                    echo 'Pembayaran Dikonfirmasi';
                                } else {
                                    echo ucfirst($order['status_pembayaran']);
                                }
                            ?>
                        </td>
                        <td>
                            <?php if ($order['status_pembayaran'] == 'menunggu'): ?>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="margin:0;">
                                    <input type="hidden" name="pembayaranID" value="<?php echo $order['pembayaranID']; ?>">
                                    <input type="submit" name="pay_now" class="btn" value="Pay Now">
                                </form>
                            <?php elseif ($order['status_order'] == 'selesai' && is_null($order['review_exists'])): ?>
                                <a href="review.php?order_id=<?php echo $order['orderID']; ?>" class="btn">Beri Rating</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">You have no orders yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const startRankSelect = document.getElementById('start_rank');
    const targetRankSelect = document.getElementById('target_rank');
    const totalBiayaInput = document.getElementById('total_biaya');
    const ranks = <?php echo json_encode($ranks); ?>;

    function calculatePrice() {
        const startRank = startRankSelect.value;
        const targetRank = targetRankSelect.value;

        if (startRank && targetRank) {
            const startPrice = ranks[startRank];
            const endPrice = ranks[targetRank];
            
            const rankKeys = Object.keys(ranks);
            const startIndex = rankKeys.indexOf(startRank);
            const targetIndex = rankKeys.indexOf(targetRank);

            if (targetIndex > startIndex) {
                let totalPrice = 0;
                for(let i = startIndex; i < targetIndex; i++) {
                    totalPrice += ranks[rankKeys[i+1]] - ranks[rankKeys[i]]; // Simplified logic, better would be per-star cost
                }
                // This logic is a bit flawed. Let's do a simpler one: sum of prices from start to target.
                let cost = 0;
                 for(let i = startIndex; i < targetIndex; i++) {
                    cost += ranks[rankKeys[i]]; 
                }
                totalBiayaInput.value = 'Rp ' + cost; // You might want to send the raw number
                 document.getElementById('total_biaya_hidden').value = cost;


                 let finalPrice = endPrice - startPrice;
                 if(finalPrice > 0){
                    totalBiayaInput.value = finalPrice;
                 }else{
                    totalBiayaInput.value = 0;
                 }
                 

            } else {
                totalBiayaInput.value = 'Invalid rank selection';
            }
        }
    }
    
    // A better calculation logic
    function calculatePriceV2() {
        const startRank = startRankSelect.value;
        const targetRank = targetRankSelect.value;

        if(!startRank || !targetRank) return;

        const rankKeys = Object.keys(ranks);
        const startIndex = rankKeys.indexOf(startRank);
        const targetIndex = rankKeys.indexOf(targetRank);

        if (targetIndex <= startIndex) {
            totalBiayaInput.value = "0.00";
            return;
        }

        let price = 0;
        // This calculates price based on jumping ranks. e.g. E to M = (M-L) + (L-E)
        for (let i = startIndex; i < targetIndex; i++) {
            price += ranks[rankKeys[i+1]] - ranks[rankKeys[i]];
        }
        // A simpler logic might be just price per rank jump
        let simplePrice = (targetIndex - startIndex) * 15; // e.g. 15 per rank
        
        // Let's use the difference in base price as the cost.
        let startPrice = ranks[startRank];
        let targetPrice = ranks[targetRank];
        let totalCost = targetPrice - startPrice;

        totalBiayaInput.value = totalCost > 0 ? totalCost.toFixed(2) : "0.00";
    }


    startRankSelect.addEventListener('change', calculatePriceV2);
    targetRankSelect.addEventListener('change', calculatePriceV2);
});
</script>


<?php include 'includes/footer.php'; ?>