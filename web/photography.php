<?php
include ('head.php');

$settings = getSettings($conn);
$photography_ticket = $settings['photography_ticket'] ?? '';

$page_size = 30;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total = $conn->query("SELECT COUNT(*) FROM photography")->fetch_row()[0];
$total_pages = max(1, ceil($total / $page_size));
$offset = ($current_page - 1) * $page_size;
$list = $conn->query("SELECT id, title, cover, created_at FROM photography ORDER BY created_at DESC LIMIT $offset, $page_size");
?>

<section class="photography">
    <?php if ($photography_ticket): ?><div class="ticket"><?php echo $photography_ticket; ?></div><?php endif; ?>
    <div class="title"><h3>photography</h3></div>
    <ul>
        <?php if ($list->num_rows > 0): ?>
            <?php while ($row = $list->fetch_assoc()): ?>
            <li><a href="album.php?id=<?php echo $row['id']; ?>"><img src="<?php echo htmlspecialchars($row['cover']); ?>" cover loading="lazy"><aside><h5 class="anime-fade-in"><?php echo htmlspecialchars($row['title']); ?></h5></aside></a></li>
            <?php endwhile; ?>
        <?php endif; ?>
    </ul>
    <page>
        <?php if ($current_page > 1): ?><a class="line ico ico-alone-left" href="?page=<?php echo $current_page - 1; ?>"></a><?php endif; ?>
        <?php if ($current_page < $total_pages): ?><a class="line ico ico-alone-right" href="?page=<?php echo $current_page + 1; ?>"></a><?php endif; ?>
    </page>
</section>

<?php include ('foot.php') ?>
