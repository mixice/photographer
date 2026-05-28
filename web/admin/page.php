<?php
include ('head.php');

$page_size = 20;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        die('Invalid request');
    }
    $id = intval($_POST['id']);
    deleteRecordWithFiles('page', $id, [], ['content']);
    $del_stmt = $conn->prepare("DELETE FROM comment WHERE target_type='page' AND target_id=?");
    $del_stmt->bind_param('i', $id);
    $del_stmt->execute();
    $del_stmt->close();
    header("Location: page.php?page=1");
    exit();
}

// query
$where = "1=1";
$params = [];
$count_types = '';
if (!empty($keyword)) {
    $where .= " AND (title LIKE ? OR slug LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $count_types = 'ss';
}

$total = countWhere($conn, 'page', $where, $count_types, $params);
$total_pages = max(1, ceil($total / $page_size));
$offset = ($current_page - 1) * $page_size;
$list_sql = "SELECT id, title, slug, created_at FROM page WHERE $where ORDER BY created_at DESC LIMIT $offset, $page_size";

if (!empty($params)) {
    $stmt = $conn->prepare($list_sql);
    $stmt->bind_param("ss", ...$params);
    $stmt->execute();
    $list = $stmt->get_result();
} else {
    $list = $conn->query($list_sql);
}
$csrf = csrfToken();
?>

<div class="title"><h4>page</h4></div>
<div class="contant">
    <div class="title"><h5>page list</h5><u></u><a class="btn" href="page-add.php">add</a></div>
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
                    <tr><th>ID</th><td>title</td><td>url</td><td>time</td><td>control</td></tr>
                </thead>
                <tbody>
                    <?php if ($list->num_rows > 0): ?>
                        <?php while ($row = $list->fetch_assoc()): ?>
                        <tr>
                            <th><?php echo $row['id']; ?></th>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['slug']); ?></td>
                            <td><?php echo substr($row['created_at'], 0, 10); ?></td>
                            <td><a class="ico ico-link" href="/page.php?slug=<?=htmlspecialchars($row['slug'])?>" target="_blank"></a>
                                <a class="ico ico-edit co-green" href="page-add.php?id=<?php echo $row['id']; ?>"></a>
                                <button type="button" class="ico ico-delete co-red" onclick="del(<?=$row['id']?>)"></button>
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

<form id="del-form" method="POST" style="display:none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="del-id">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
</form>
<script>function del(id){confirm('Delete will remove related files and comments, confirm ?').then(function(r){if(r){document.getElementById('del-id').value=id;document.getElementById('del-form').submit()}})}</script>

</section>
</section>
</section>
</body>
</html>
