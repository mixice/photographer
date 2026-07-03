<?php
if (!file_exists(__DIR__ . '/install.lock')) { header('Location: install.php'); exit(); }
require_once('db.php');
require_once __DIR__ . '/includes/comment.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: photography.php");
    exit();
}

$row_stmt = $conn->prepare("SELECT id, title, cover, images, comment_enabled, created_at FROM photography WHERE id = ?");
$row_stmt->bind_param('i', $id);
$row_stmt->execute();
$row = $row_stmt->get_result()->fetch_assoc();
$row_stmt->close();
if (!$row) {
    header("Location: photography.php");
    exit();
}

$prev_stmt = $conn->prepare("SELECT id, title FROM photography WHERE id < ? ORDER BY id DESC LIMIT 1");
$prev_stmt->bind_param('i', $id);
$prev_stmt->execute();
$prev = $prev_stmt->get_result()->fetch_assoc();
$prev_stmt->close();
$next_stmt = $conn->prepare("SELECT id, title FROM photography WHERE id > ? ORDER BY id ASC LIMIT 1");
$next_stmt->bind_param('i', $id);
$next_stmt->execute();
$next = $next_stmt->get_result()->fetch_assoc();
$next_stmt->close();

$settings = getSettings($conn);
$comment_ticket = $settings['comment_ticket'] ?? '';
$comment_enabled = $row['comment_enabled'];
$images = extractImageUrlsFromJson($row['images'] ?? '');
if (empty($images) && !empty($row['cover'])) $images[] = $row['cover'];

handleCommentSubmission($conn, 'photography', $id, $comment_enabled, "album.php?id=$id&msg=commented", "album.php?id=$id");

$comment_msg = $_GET['msg'] ?? '';
extract(getCommentPagination($conn, 'photography', $id));
$comment_form_action = "album.php?id=$id";
$comment_pagination_base = "album.php?id=$id";

include ('head.php');
?>

<section class="article">
    <div class="article-title"><h3><?php echo htmlspecialchars($row['title']); ?></h3><span><?php echo substr($row['created_at'], 0, 10); ?></span></div>
    <div class="article-cont">
        <ul class="album">
            <?php foreach ($images as $image): ?>
            <li><a href="<?php echo htmlspecialchars($image); ?>" target="_blank"><img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" loading="lazy"></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="article-page center">
        <?php if ($prev): ?>
            <a class="center" href="album.php?id=<?php echo $prev['id']; ?>"><i class="ico ico-alone-left"></i><h6><?php echo htmlspecialchars($prev['title']); ?></h6></a>
        <?php endif; ?>
        <u></u>
        <?php if ($next): ?>
            <a class="center" href="album.php?id=<?php echo $next['id']; ?>"><h6><?php echo htmlspecialchars($next['title']); ?></h6><i class="ico ico-alone-right"></i></a>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/comment-section.php'; ?>

<swiper loop class="album-swiper anime-fade-in" style="display:none">
    <swiper-wrapper>
        <?php foreach ($images as $image): ?>
            <swiper-slide><img contain src="<?php echo htmlspecialchars($image); ?>" loading="lazy"></swiper-slide>
        <?php endforeach; ?>
    </swiper-wrapper>
    <swiper-arrow></swiper-arrow>
    <a class="album-swiper-close ico ico-close"></a>
</swiper>


<?php include ('foot.php') ?>

<script type="module">
    const { $, $$ } = Uigg
    const albumSwiper = $('.album-swiper')
    $$('.album a').forEach(function(a, i) {
        a.addEventListener('click', function(e) {
            e.preventDefault()
            albumSwiper.style.display = 'block'
            albumSwiper.offsetHeight
            albumSwiper.slideTo(i, false)
        })
    })

    $('.album-swiper-close').addEventListener('click', function() {
        albumSwiper.style.display = 'none'
    })
</script>
