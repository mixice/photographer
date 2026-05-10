<?php
include ('head.php');

// settings
$settings = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc();
$standpoint_ticket = $settings['standpoint_ticket'] ?? '';

// list
$page_size = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total = $conn->query("SELECT COUNT(*) FROM standpoint")->fetch_row()[0];
$total_pages = max(1, ceil($total / $page_size));
$offset = ($current_page - 1) * $page_size;
$list = $conn->query("SELECT * FROM standpoint ORDER BY created_at DESC LIMIT $offset, $page_size");
?>

<section class="standpoint">
    <?php if ($standpoint_ticket): ?><div class="ticket"><?php echo $standpoint_ticket; ?></div><?php endif; ?>
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
    <page>
        <?php if ($current_page > 1): ?><a class="line ico ico-alone-left" href="?page=<?php echo $current_page - 1; ?>"></a><?php endif; ?>
        <?php if ($current_page < $total_pages): ?><a class="line ico ico-alone-right" href="?page=<?php echo $current_page + 1; ?>"></a><?php endif; ?>
    </page>
</section>

<?php include ('foot.php') ?>