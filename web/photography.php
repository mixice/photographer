<?php
include ('head.php');

$settings = getSettings($conn);
$photography_ticket = $settings['photography_ticket'] ?? '';

$page_size = 30;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total = $conn->query("SELECT COUNT(*) FROM photography")->fetch_row()[0];
$offset = ($current_page - 1) * $page_size;
$list_stmt = $conn->prepare("SELECT id, title, cover, created_at FROM photography ORDER BY created_at DESC, id DESC LIMIT ?, ?");
$list_stmt->bind_param('ii', $offset, $page_size);
$list_stmt->execute();
$list = $list_stmt->get_result();
?>

<section class="photography">
    <?php if ($photography_ticket): ?><div class="ticket"><?php echo sanitizeHtml($photography_ticket); ?></div><?php endif; ?>
    <div class="title"><h3>photography</h3></div>
    <ul>
        <?php if ($list->num_rows > 0): ?>
            <?php while ($row = $list->fetch_assoc()): ?>
            <li><a href="album.php?id=<?php echo $row['id']; ?>"><img src="<?php echo htmlspecialchars($row['cover']); ?>" cover loading="lazy"><aside><h5 class="anime-fade-in"><?php echo htmlspecialchars($row['title']); ?></h5></aside></a></li>
            <?php endwhile; ?>
        <?php endif; ?>
    </ul>
    <page total="<?php echo $total; ?>" page="<?php echo $current_page; ?>" limit="<?php echo $page_size; ?>" param="page"></page>
</section>

<?php include ('foot.php') ?>
