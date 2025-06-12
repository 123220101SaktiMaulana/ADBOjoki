<?php
require_once "config/config.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$customerID = $_SESSION['id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order_details = null;
$errors = [];
$success_msg = "";

// 1. Verify the order exists, belongs to the customer, and is completed.
if ($order_id > 0) {
    $sql = "SELECT orderID, jokiID FROM orderjoki WHERE orderID = ? AND customerID = ? AND status_order = 'selesai'";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $order_id, $customerID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $order_details = mysqli_fetch_assoc($result);
        } else {
            $errors[] = "Error: Order ini tidak valid atau bukan milik Anda.";
        }
        mysqli_stmt_close($stmt);
    }

    // 2. If order is valid, check if THIS SPECIFIC ORDER has already been reviewed.
    if ($order_details) {
        // PERBAIKAN: Cek berdasarkan orderID, bukan kombinasi jokiID+customerID
        $sql_check_review = "SELECT ulasanID FROM ulasan WHERE orderID = ?";
        if ($stmt_check = mysqli_prepare($conn, $sql_check_review)) {
            mysqli_stmt_bind_param($stmt_check, "i", $order_id);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $errors[] = "Error: Anda sudah memberikan ulasan untuk pesanan ini.";
                $order_details = null; // Block the form from showing
            }
            mysqli_stmt_close($stmt_check);
        }
    }
} else {
    $errors[] = "Error: Order ID tidak valid.";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $rating = $_POST['rating'];
    $komentar = trim($_POST['komentar']);
    $jokiID = $_POST['jokiID'];
    $order_id_from_form = $_POST['order_id'];

    if (empty($rating) || $rating < 1 || $rating > 5) {
        $errors[] = "Silakan berikan rating antara 1 dan 5.";
    }
    if (empty($komentar)) {
        $errors[] = "Silakan tulis komentar Anda.";
    }
    if ($order_id_from_form != $order_id) {
        $errors[] = "Terjadi kesalahan referensi pesanan.";
    }

    if (empty($errors)) {
        // PERBAIKAN: Tambahkan orderID ke dalam INSERT statement
        $sql_insert = "INSERT INTO ulasan (customerID, jokiID, orderID, rating, komentar) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql_insert)) {
            mysqli_stmt_bind_param($stmt, "iiiis", $customerID, $jokiID, $order_id_from_form, $rating, $komentar);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Terima kasih atas ulasan Anda!";
                header("refresh:3;url=customer_dashboard.php");
            } else {
                $errors[] = "Gagal mengirim ulasan. Silakan coba lagi.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="page-header">
    <h2>Beri Ulasan</h2>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="customer_dashboard.php">Kembali ke Dashboard</a>
    </div>
<?php endif; ?>

<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success">
        <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<?php if ($order_details && empty($success_msg) && empty($errors)): ?>
<div class="form-container">
    <h3>Ulasan untuk Pesanan #<?php echo htmlspecialchars($order_details['orderID']); ?></h3>
    <form action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>" method="post">
        <input type="hidden" name="jokiID" value="<?php echo $order_details['jokiID']; ?>">
        <input type="hidden" name="order_id" value="<?php echo $order_details['orderID']; ?>">
        <div class="form-group">
            <label>Rating</label>
            <div class="rating">
                <input type="radio" name="rating" value="5" id="5" required><label for="5">☆</label>
                <input type="radio" name="rating" value="4" id="4"><label for="4">☆</label>
                <input type="radio" name="rating" value="3" id="3"><label for="3">☆</label>
                <input type="radio" name="rating" value="2" id="2"><label for="2">☆</label>
                <input type="radio" name="rating" value="1" id="1"><label for="1">☆</label>
            </div>
        </div>
        <div class="form-group">
            <label>Komentar</label>
            <textarea name="komentar" rows="5" class="form-control" required></textarea>
        </div>
        <div class="form-group">
            <input type="submit" name="submit_review" class="btn" value="Kirim Ulasan">
        </div>
    </form>
</div>

<style>
.rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: center;
}
.rating > input{ display:none; }
.rating > label {
    position: relative;
    width: 1em;
    font-size: 3rem;
    color: #FFD600;
    cursor: pointer;
}
.rating > label::before{
    content: "\2605";
    position: absolute;
    opacity: 0;
}
.rating > label:hover:before,
.rating > label:hover ~ label:before {
    opacity: 1 !important;
}
.rating > input:checked ~ label:before {
    opacity:1;
}
.rating:hover > input:checked ~ label:before{ opacity: 0.4; }
</style>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>