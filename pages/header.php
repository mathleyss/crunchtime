<header>
    <nav class="menu">
        <div class="menuLeft">
            <a href="index.php" class="logoAccueil"> <img src="assets/images/logo.png" alt=""></a>
            <a href="index.php" id="active">Accueil</a>
            <a href="/pages/swipe.php">CrunchSwipe</a>
        </div>
        <!-- BARRE DE RECHERCHE À REFAIRE ET EN CSS AUSSI -->
        <div class="searchBar">
            <form action="search.php" method="GET">

                <img src="assets/images/icon/search.svg" alt="Search">

                <input type="text" name="query" placeholder="Rechercher..." class="searchInput">
            </form>
        </div>
        <div class="menuRight">

            <!-- Si un utilisateur est connecté, alors ... -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="profile">
                    <img src="https://doodleipsum.com/700/avatar-2?i=6197810111afde5fbb243bac8463665e" alt="Profile"
                        class="profile-img">
                    <div class="dropdown-menu">
                        <img src="https://doodleipsum.com/700/avatar-2?i=6197810111afde5fbb243bac8463665e" alt="">
                        <p><?= htmlspecialchars($user['username']) ?></p>
                        <a href="/pages/profile.php">Profil</a>
                        <a href="/pages/watchlist.php">Ma watchlist</a>
                        <a href="/pages/logout.php" id="logout">Déconnexion</a>
                    </div>
                </div>
                <!-- ... Sinon ... -->
            <?php else: ?>
                <a href="pages/login.php" class="btnLogin">
                    Connexion
                </a>
            <?php endif; ?>
        </div>
    </nav>
    <div class="headerContent">
        <h1>Crunchtime</h1>
        <p>Swipez, découvrez, partagez</p>
    </div>
</header>