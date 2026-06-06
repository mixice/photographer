<?php if ($comment_enabled): ?>
<section class="comment">
    <div class="title"><h3>comment</h3></div>
    <div class="form comment-form">
        <form method="POST" action="<?php echo htmlspecialchars($comment_form_action, ENT_QUOTES, 'UTF-8'); ?>">
            <li><input type="text" name="name" placeholder="name" required></li>
            <li><input type="email" name="email" placeholder="email"></li>
            <li hide><input type="email" name="email-repeat" hide></li>
            <li><textarea name="content" placeholder="comment" required></textarea></li>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
            <li><button class="btn" type="submit">send</button></li>
        </form>
    </div>
    <?php if ($comment_ticket): ?><div class="ticket"><?php echo sanitizeHtml($comment_ticket); ?></div><?php endif; ?>
    <ul>
        <?php if ($comment_list->num_rows > 0): ?>
            <?php while ($c = $comment_list->fetch_assoc()): ?>
            <li><div class="comment-txt"><aside><a class="line" href="mailto:<?php echo htmlspecialchars($c['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><h6><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></h6></a><u></u><span><?php echo substr($c['created_at'], 0, 10); ?></span></aside><p><?php echo htmlspecialchars($c['content'], ENT_QUOTES, 'UTF-8'); ?></p></div></li>
            <?php endwhile; ?>
        <?php endif; ?>
    </ul>
    <?php if ($comment_pages > 1): ?>
    <page>
        <?php if ($comment_page > 1): ?>
            <a class="line ico ico-alone-left" href="<?php echo htmlspecialchars($comment_pagination_base, ENT_QUOTES, 'UTF-8'); ?>&cpage=<?php echo $comment_page - 1; ?>"></a>
        <?php endif; ?>
        <?php if ($comment_page < $comment_pages): ?>
            <a class="line ico ico-alone-right" href="<?php echo htmlspecialchars($comment_pagination_base, ENT_QUOTES, 'UTF-8'); ?>&cpage=<?php echo $comment_page + 1; ?>"></a>
        <?php endif; ?>
    </page>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if (!empty($comment_msg)): ?>
<script>
    var msgs = {
        'commented': 'Comment submitted !',
        'csrf_fail': 'Invalid request, please refresh and retry',
        'rate_limited': 'Please wait a moment before commenting again'
    };
    var msgKey = <?php echo json_encode($comment_msg, JSON_UNESCAPED_SLASHES); ?>;
    if (msgs[msgKey]) {
        Uigg.alert(msgs[msgKey])
        history.replaceState(null,'',location.pathname+location.search.replace(/&?msg=\w+/,''))
    }
</script>
<?php endif; ?>
