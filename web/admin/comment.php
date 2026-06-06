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
    $del_stmt = $conn->prepare("DELETE FROM comment WHERE id = ?");
    $del_stmt->bind_param('i', $id);
    $del_stmt->execute();
    $del_stmt->close();
    header("Location: comment.php?page=1");
    exit();
}

// toggle status
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    $id = intval($_GET['id']);
    $toggle_stmt = $conn->prepare("UPDATE comment SET status = IF(status=1, 0, 1) WHERE id = ?");
    $toggle_stmt->bind_param('i', $id);
    $toggle_stmt->execute();
    $toggle_stmt->close();
    header("Location: comment.php?page=" . $current_page);
    exit();
}

// query
$where = "1=1";
$params = [];
$count_types = '';
if (!empty($keyword)) {
    $where .= " AND (name LIKE ? OR content LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $count_types = 'ss';
}

$total = countWhere($conn, 'comment', $where, $count_types, $params);
$total_pages = max(1, ceil($total / $page_size));
$offset = ($current_page - 1) * $page_size;
$list_sql = "SELECT id, name, email, content, target_type, target_id, status, created_at FROM comment WHERE $where ORDER BY created_at DESC LIMIT $offset, $page_size";

if (!empty($params)) {
    $stmt = $conn->prepare($list_sql);
    $stmt->bind_param("ss", ...$params);
    $stmt->execute();
    $list = $stmt->get_result();
} else {
    $list = $conn->query($list_sql);
}

// preload page slugs for link generation
$page_slugs = [];
$rows = [];
if ($list->num_rows > 0) {
    $ids = [];
    while ($r = $list->fetch_assoc()) {
        $rows[] = $r;
        if ($r['target_type'] === 'page') $ids[] = intval($r['target_id']);
    }
    if ($ids) {
        $id_list = implode(',', $ids);
        $res = $conn->query("SELECT id, slug FROM page WHERE id IN ($id_list)");
        while ($p = $res->fetch_assoc()) $page_slugs[$p['id']] = $p['slug'];
    }
}
$list = $rows;
$csrf = csrfToken();
?>

<div class="title"><h4>comment</h4></div>
<div class="contant">
    <div class="title"><h5>comment list</h5></div>
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
                    <tr><th>ID</th><td>name</td><td>email</td><td>comment</td><td>type</td><td>time</td><td>display</td><td>control</td></tr>
                </thead>
                <tbody>
                    <?php if (!empty($list)): ?>
                        <?php foreach ($list as $row): ?>
                        <?php
                        if ($row['target_type'] === 'page') {
                            $link = '/page.php?slug=' . urlencode($page_slugs[$row['target_id']] ?? '');
                        } else {
                            $link = '/article.php?type=' . $row['target_type'] . '&id=' . $row['target_id'];
                        }
                        ?>
                        <tr>
                            <th><?php echo $row['id']; ?></th>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(mb_substr($row['content'], 0, 50)); ?></td>
                            <td><?php echo htmlspecialchars($row['target_type']); ?></td>
                            <td><?php echo substr($row['created_at'], 0, 10); ?></td>
                            <td><o class="toggle<?php echo $row['status'] ? ' active' : ''; ?>" onclick="location.href='comment.php?action=toggle&id=<?php echo $row['id']; ?>&page=<?php echo $current_page; ?>'"></o></td>
                            <td><a class="ico ico-link" href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank"></a>
                                <button type="button" class="ico ico-delete co-red" onclick="del(<?=$row['id']?>)"></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
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
<script>function del(id){Uigg.confirm('Confirm delete ?').then(function(r){if(r){document.getElementById('del-id').value=id;document.getElementById('del-form').submit()}})}</script>

</section>
</section>
</section>
</body>
</html>
