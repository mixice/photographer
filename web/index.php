<?php
include ('head.php');

$settings = getSettings($conn);
$home_ticket = $settings['home_ticket'] ?? '';
$site_description = $settings['description'] ?? '';
$photography_ticket = $settings['photography_ticket'] ?? '';
$standpoint_ticket = $settings['standpoint_ticket'] ?? '';
$comment_ticket = $settings['comment_ticket'] ?? '';

$photos = $conn->query("SELECT id, title, cover, created_at FROM photography ORDER BY created_at DESC LIMIT 30");
$articles = $conn->query("SELECT id, title, cover, content, created_at FROM standpoint ORDER BY created_at DESC LIMIT 2");
$comments = $conn->query("SELECT id, name, content, created_at FROM comment WHERE status=1 ORDER BY created_at DESC LIMIT 5");
?>

<section class="light">
    <section class="swiper swiper-banner">
        <div class="swiper-wrapper">
        <?php $selected = array_rand(range(1, 20), 3);foreach($selected as $n) {echo '<div class="swiper-slide"><img src="images/light/' . $n . '.jpg" cover loading="lazy"></div>';}?>
        </div>
    </section>
    <?php if ($home_ticket): ?><div class="ticket absolute-5 center anime-fade-in-up"><?php echo sanitizeHtml($home_ticket); ?></div><?php endif; ?>
    <div class="light-logo absolute-2 anime-fade-in-down"><i class="ico ico-m"></i></div>
    <div class="light-txt absolute-7 anime-fade-in-up"><h3><?php echo $site_title; ?><br>photographer</h3><span><?php echo htmlspecialchars($site_description) ?: 'Freeze the important moments in life'; ?></span></div>
    <div class="light-sns absolute-9 center anime-fade-in">
        <a pop="pop" class="ico ico-douyin"></a>
        <a pop="pop" class="ico ico-wechat"></a>
        <a pop="pop" class="ico ico-qq"></a>
    </div>
    <a class="light-next absolute-8"><i class="ico ico-alone-bottom anime-fade-in-down infinite"></i></a>
</section>
<section class="photography restriction">
    <?php if ($photography_ticket): ?><div class="ticket"><?php echo sanitizeHtml($photography_ticket); ?></div><?php endif; ?>
    <div class="title"><a class="line" href="photography.php"><h3>new photography</h3></a></div>
    <ul>
        <?php if ($photos->num_rows > 0): ?>
            <?php while ($row = $photos->fetch_assoc()): ?>
            <li><a href="album.php?id=<?php echo $row['id']; ?>"><img src="<?php echo htmlspecialchars($row['cover']); ?>" cover loading="lazy"><aside><h5 class="anime-fade-in"><?php echo htmlspecialchars($row['title']); ?></h5></aside></a></li>
            <?php endwhile; ?>
        <?php endif; ?>
    </ul>
</section>
<section class="standpoint">
    <div class="title"><a class="line" href="standpoint.php"><h3>new standpoint</h3></a></div>
    <ul>
        <?php if ($articles->num_rows > 0): ?>
            <?php while ($row = $articles->fetch_assoc()): ?>
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
</section>
<section class="comment">
    <?php if ($comment_ticket): ?><div class="ticket"><?php echo sanitizeHtml($comment_ticket); ?></div><?php endif; ?>
    <div class="title"><h3>new comment</h3></div>
    <ul>
        <?php if ($comments->num_rows > 0): ?>
            <?php $i=-1; while ($row = $comments->fetch_assoc()): $i++; ?>
            <li><i class="ico ico-square-<?php echo $i % 5 + 1; ?>"></i><a class="line comment-txt"><aside><h6><?php echo htmlspecialchars($row['name']); ?></h6><u></u><span><?php echo substr($row['created_at'], 0, 10); ?></span></aside><p><?php echo htmlspecialchars($row['content']); ?></p></a></li>
            <?php endwhile; ?>
        <?php endif; ?>
    </ul>
</section>
<pop pop="pop">
    <pop-main>
        <pop-title><h3>scan QR code</h3><a class="close"></a></pop-title>
        <pop-cont><img></pop-cont>
    </pop-main>
</pop>

<script>
    //light
    $('body').prepend($('.light'))
    var swiper = new Swiper('.swiper-banner',{
        loop: true,
        effect: 'fade',
        simulateTouch: false,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
    })

    //next
    $('.light-next').click(function(){
        var viewportHeight = window.innerHeight,
            currentScroll = $(window).scrollTop(),
            targetScroll = currentScroll + viewportHeight
        $('html, body').animate({scrollTop: targetScroll},{duration: 1000,easing: 'swing',})
    })

    //pop
    $('a[pop="pop"]').click(function(){$('pop[pop="pop"] img').attr('src', `//ui.gg/lib/qr/${$(this).attr('class').match(/ico-(\w+)/)[1]}.svg`)})

</script>

<?php include ('foot.php') ?>
