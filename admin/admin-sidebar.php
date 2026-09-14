<?php
$activePage = $activePage ?? "";

$adminNavItems = [
    "dashboard" => [
        "label" => "Dashboard",
        "href"  => "admin-dashboard.php",
    ],
    "users" => [
        "label" => "Users",
        "href"  => "users.php",
    ],
    "artists" => [
        "label" => "Artists",
        "href"  => "artists.php",
    ],
    "artworks" => [
        "label" => "Artworks",
        "href"  => "artworks.php",
    ],
    "exhibitions" => [
        "label" => "Exhibitions",
        "href"  => "exhibitions.php",
    ],
    "inquiries" => [
        "label" => "Inquiries",
        "href"  => "inquiries.php",
    ],
];

?>
<aside class="admin-sidebar">

    <div>

        <div class="admin-sidebar-brand">
            <span class="admin-sidebar-brand-mark">EDL</span>
            <span class="admin-sidebar-brand-sub">GALLERY ADMIN</span>
        </div>

        <nav class="admin-nav">

            <?php foreach ($adminNavItems as $key => $item): ?>

                <a
                    href="<?php echo htmlspecialchars($item['href']); ?>"
                    class="admin-nav-link<?php echo ($activePage === $key) ? ' active' : ''; ?>"
                >
                    <?php echo htmlspecialchars($item['label']); ?>
                </a>

            <?php endforeach; ?>

        </nav>

    </div>

    <div class="admin-sidebar-footer">

        <a href="../logout.php" class="admin-nav-link admin-nav-logout">
            Logout
        </a>

    </div>

</aside>