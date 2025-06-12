<?php include 'includes/header.php'; ?>

<style>
    .hero {
        text-align: center;
        padding: 80px 20px;
        background: #f4f4f9;
        border-radius: 8px;
    }
    .hero h1 {
        font-size: 3rem;
        margin-bottom: 20px;
    }
    .hero p {
        font-size: 1.2rem;
        margin-bottom: 30px;
    }
    .hero .btn {
        padding: 15px 30px;
        font-size: 1.1rem;
        text-decoration: none;
    }
    .features {
        display: flex;
        justify-content: space-around;
        padding: 50px 0;
        text-align: center;
    }
    .feature-item {
        max-width: 30%;
    }
    .feature-item h3 {
        font-size: 1.5rem;
        margin-bottom: 10px;
    }
</style>

<div class="hero">
    <h1>Welcome to JokiPro</h1>
    <p>Your number one solution for professional game boosting services. Safe, fast, and reliable.</p>
    <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
        <a href="dashboard.php" class="btn">Go to Dashboard</a>
    <?php else: ?>
        <a href="register.php" class="btn">Get Started</a>
    <?php endif; ?>
</div>

<div class="features">
    <div class="feature-item">
        <h3>Safe & Secure</h3>
        <p>Your account security is our top priority. We use secure methods to ensure your data is safe.</p>
    </div>
    <div class="feature-item">
        <h3>Fast Service</h3>
        <p>Our professional jokis are ready to help you reach your desired rank quickly and efficiently.</p>
    </div>
    <div class="feature-item">
        <h3>24/7 Support</h3>
        <p>Our admin and support team are available around the clock to assist you with any inquiries.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 