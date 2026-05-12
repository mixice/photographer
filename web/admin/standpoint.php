<?php
include ('head.php');

$page_size = 20;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// delete
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id']);
    deleteRecordWithFiles('standpoint', $id, ['cover'], ['content']);
    $conn->query("DELETE FROM comment WHERE target_type='standpoint' AND target_id=$id");
    header("Location: standpoint.php?page=1");
    exit();
}

// query
$where = "1=1";
$params = [];
if (!empty($keyword)) {
    $where .= " AND title LIKE ?";
    $params[] = "%$keyword%";
}

$total = $conn->query("SELECT COUNT(*) FROM standpoint WHERE $where")->fetch_row()[0];
$total_pages = max(1, ceil($total / $page_size));
$offset = ($current_page - 1) * $page_size;

if (!empty($params)) {
    $stmt = $conn->prepare("SELECT * FROM standpoint WHERE $where ORDER BY created_at DESC LIMIT $offset, $page_size");
    $stmt->bind_param("s", ...$params);
    $stmt->execute();
    $list = $stmt->get_result();
} else {
    $list = $conn->query("SELECT * FROM standpoint WHERE $where ORDER BY created_at DESC LIMIT $offset, $page_size");
}
?>

<div class="title"><h4>standpoint</h4></div>
<div class="contant">
    <div class="title"><h5>standpoint list</h5><u></u><a class="btn" href="standpoint-add.php">add</a></div>
    <div class="item">
        <div class="form filter">
            <form method="GET">
                <input type="hidden" name="page" value="1">
                <li><input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>"></li>
                <li><button class="btn">search</button></li>
            </form>
        </div>
        <div class="table">
            <table>
                <thead>
                    <tr><th>ID</th><td>title</td><td>cover</td><td>time</td><td>control</td></tr>
                </thead>
                <tbody>
                    <?php if ($list->num_rows > 0): ?>
                        <?php while ($row = $list->fetch_assoc()): ?>
                        <tr>
                            <th><?php echo $row['id']; ?></th>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php if ($row['cover']): ?><em><img cover src="<?php echo htmlspecialchars($row['cover']); ?>"></em><?php endif; ?></td>
                            <td><?php echo substr($row['created_at'], 0, 10); ?></td>
                            <td><a class="ico ico-link" href="/article.php?type=standpoint&id=<?=$row['id']?>" target="_blank"></a>
                                <a class="ico ico-edit co-green" href="standpoint-add.php?id=<?php echo $row['id']; ?>"></a>
                                <button class="ico ico-delete co-red" onclick="del(<?=$row['id']?>)"></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="100">Empty</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php echo renderPagination($current_page, $total_pages, ['keyword' => $keyword]); ?>
    </div>
</div>

<script>function del(id){confirm('Delete will remove related files and comments, confirm ?').then(function(r){if(r)location.href='standpoint.php?action=delete&id='+id})}</script>

</section>
</section>
</section>
</body>
</html>
