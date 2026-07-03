<?php
include ('head.php');

$photo_count = $conn->query("SELECT COUNT(*) FROM photography")->fetch_row()[0];
$stand_count = $conn->query("SELECT COUNT(*) FROM standpoint")->fetch_row()[0];
$page_count = $conn->query("SELECT COUNT(*) FROM page")->fetch_row()[0];
$comment_count = $conn->query("SELECT COUNT(*) FROM comment WHERE status = 0")->fetch_row()[0];
?>

<div class="title"><h4>statistics</h4></div>
<div class="contant">
    <div class="item">
        <div class="statistics">
            <li><span>photography</span><h3><?php echo $photo_count; ?></h3>
                <a href="photography-add.php" class="btn btn-med btn-empty">add</a>
            </li>
            <li><span>standpoint</span><h3 class="co-tomato"><?php echo $stand_count; ?></h3>
                <a href="standpoint-add.php" class="btn btn-med btn-empty co-tomato">add</a>
            </li>
            <li><span>page</span><h3 class="co-sapphire"><?php echo $page_count; ?></h3>
                <a href="page-add.php" class="btn btn-med btn-empty co-sapphire">add</a>
            </li>
            <li><span>new comment</span><h3 class="co-green"><?php echo $comment_count; ?></h3>
                <a href="comment.php" class="btn btn-med btn-empty co-green">read</a>
            </li>
        </div>
    </div>
</div>
<div class="title"><h4>explanation</h4></div>
<div class="contant">
    <div class="title"><h5>program description</h5></div>
    <div class="item">
        <div class="article">
            <ul>
                <li>This program is prohibited for commercial users</li>
                <li>If you have any questions, please contact mixice@mixice.com</li>
            </ul>
        </div>
        <?php
        $version_text = '无法连接';
        $vf = dirname(__DIR__) . '/version.json';
        if (file_exists($vf)) {
            $ver = json_decode(file_get_contents($vf), true);
            if ($ver && !empty($ver['version'])) {
                $version_text = $ver['version'] . ' (' . ($ver['build'] ?? '?') . ')';
            }
        }
        ?>
        <reminder>
            Version <?php echo htmlspecialchars($version_text); ?>
        </reminder>
    </div>
</div>


</section>
</section>
</section>
</body>
</html>
