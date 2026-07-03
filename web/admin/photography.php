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
    deleteRecordWithFiles('photography', $id, ['cover'], [], ['images']);
    $del_stmt = $conn->prepare("DELETE FROM comment WHERE target_type='photography' AND target_id=?");
    $del_stmt->bind_param('i', $id);
    $del_stmt->execute();
    $del_stmt->close();
    header("Location: photography.php?page=1");
    exit();
}

// query
$where = "1=1";
$params = [];
$count_types = '';
if (!empty($keyword)) {
    $where .= " AND title LIKE ?";
    $params[] = "%$keyword%";
    $count_types = 's';
}

$total = countWhere($conn, 'photography', $where, $count_types, $params);
$offset = ($current_page - 1) * $page_size;
$list_sql = "SELECT id, title, cover, created_at FROM photography WHERE $where ORDER BY created_at DESC LIMIT $offset, $page_size";

if (!empty($params)) {
    $stmt = $conn->prepare($list_sql);
    $stmt->bind_param("s", ...$params);
    $stmt->execute();
    $list = $stmt->get_result();
} else {
    $list = $conn->query($list_sql);
}
$csrf = csrfToken();
?>

<div class="title"><h4>photography</h4></div>
<div class="contant">
    <div class="title"><h5>photography list</h5><u></u><a class="btn" href="photography-add.php">add</a></div>
    <div class="item">
        <form class="form filter" method="GET">
            <input type="hidden" name="page" value="1">
            <item><cont><input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>"></cont></item>
            <item><cont><button class="btn">search</button></cont></item>
        </form>
        <table class="table">
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
                        <td><a class="ico ico-link" href="/album.php?id=<?=$row['id']?>" target="_blank"></a>
                            <a class="ico ico-edit co-green" href="photography-add.php?id=<?php echo $row['id']; ?>"></a>
                            <button type="button" class="ico ico-delete co-red" onclick="del(<?=$row['id']?>)"></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="100">Empty</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <page total="<?php echo $total; ?>" page="<?php echo $current_page; ?>" limit="<?php echo $page_size; ?>" param="page"></page>
    </div>
</div>

<form id="del-form" method="POST" style="display:none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="del-id">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
</form>

<script type="module">
    const { $, confirm } = Uigg
    window.del = id => {
        confirm('Delete will remove related files and comments, confirm ?').then(r => {
            if(r){
                $('#del-id').value = id
                $('#del-form').submit()
            }
        })
    }
</script>

</section>
</section>
</section>
</body>
</html>
