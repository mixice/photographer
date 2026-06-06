<?php
include ('head.php');

$settings = getSettings($conn);
$standpoint_ticket = $settings['standpoint_ticket'] ?? '';

$page_size = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total = $conn->query("SELECT COUNT(*) FROM standpoint")->fetch_row()[0];
$offset = ($current_page - 1) * $page_size;
$list_stmt = $conn->prepare("SELECT id, title, cover, content, created_at FROM standpoint ORDER BY created_at DESC LIMIT ?, ?");
$list_stmt->bind_param('ii', $offset, $page_size);
$list_stmt->execute();
$list = $list_stmt->get_result();
?>

<section class="standpoint">
    <?php if ($standpoint_ticket): ?><div class="ticket"><?php echo sanitizeHtml($standpoint_ticket); ?></div><?php endif; ?>
    <div class="title"><h3>standpoint</h3></div>
    <ul>
        <?php if ($list->num_rows > 0): ?>
            <?php while ($row = $list->fetch_assoc()): ?>
            <li><a class="line" href="article.php?type=standpoint&id=<?php echo $row['id']; ?>">
                <?php if ($row['cover']): ?><em><img src="<?php echo htmlspecialchars($row['cover']); ?>" cover loading="lazy"></em><?php endif; ?>
                <aside>
                    <h5><?php echo htmlspecialchars($row['title']); ?></h5>
                    <p><?php echo htmlspecialchars(mb_substr(strip_tags($row['content']), 0, 120)); ?></p>
                    <span><?php echo substr($row['created_at'], 0, 10); ?></span>
                </aside>
            </a></li>
            <?php endwhile; ?>
        <?php endif; ?>
    </ul>
    <page total="<?php echo $total; ?>" page="<?php echo $current_page; ?>" limit="<?php echo $page_size; ?>" param="page"></page>
</section>

<?php include ('foot.php') ?>
