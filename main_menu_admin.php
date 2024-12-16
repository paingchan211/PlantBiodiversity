<?php
session_name('paing_chan');
session_start();
?>

<?php include_once 'head.php'; ?>

<body>
    <?php include_once 'header.php'; ?>
    <main id="main-mt">
        <div class="container mt-5">
            <img src="images/logo.svg" alt="logo" class="mx-auto d-block">
        </div>

        <div class="container mt-5">
            <h1 class="text-center">Admin Main Menu</h1>
            <div class="d-flex justify-content-center mt-4">
                <a href="manage_accounts.php" class="btn btn-dark btn-lg mx-2">Manage Users' Accounts</a>
                <a href="manage_plants.php" class="btn btn-success btn-lg mx-2">Manage Plants</a>
            </div>
        </div>
    </main>
    <?php include_once 'footer.php'; ?>
</body>

</html>