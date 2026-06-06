<?php
include ('head.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$row = null;
if ($id) {
    $row_stmt = $conn->prepare("SELECT * FROM photography WHERE id = ?");
    $row_stmt->bind_param('i', $id);
    $row_stmt->execute();
    $row = $row_stmt->get_result()->fetch_assoc();
    $row_stmt->close();
}

$upload_dir = '/uploads/photography/';
$upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir;
if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);

function uploadPhotographyImage($file, $upload_path, $upload_dir) {
    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) return '';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) return '';
    if (!isValidImageFile($file['tmp_name'])) return '';
    $filename = date('YmdHis') . mt_rand(1000, 9999) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $upload_path . $filename)) {
        return $upload_dir . $filename;
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $comment_enabled = !empty($_POST['comment_enabled']) ? 1 : 0;
    $date_input = trim($_POST['created_at'] ?? '');
    if ($date_input) {
        // 编辑时保留原时分秒，新增时用当前时间
        if ($id && $row && $row['created_at']) {
            $time_part = substr($row['created_at'], 11, 8);
            $created_at = $date_input . ' ' . ($time_part ?: date('H:i:s'));
        } else {
            $created_at = $date_input . ' ' . date('H:i:s');
        }
    } else {
        $created_at = $id && $row && $row['created_at'] ? $row['created_at'] : date('Y-m-d H:i:s');
    }
    $cover = $row['cover'] ?? '';
    $images = isset($_POST['images']) && is_array($_POST['images']) ? array_values(array_filter($_POST['images'])) : [];
    $old_images = $id ? extractImageUrlsFromJson($row['images'] ?? '') : [];

    if (isset($_FILES['cover'])) {
        $new_cover = uploadPhotographyImage($_FILES['cover'], $upload_path, $upload_dir);
        if ($new_cover) $cover = $new_cover;
    }

    if (isset($_FILES['replace_images']) && is_array($_FILES['replace_images']['name'])) {
        foreach ($_FILES['replace_images']['name'] as $index => $name) {
            if (!isset($images[$index]) || $_FILES['replace_images']['error'][$index] !== UPLOAD_ERR_OK) continue;
            $file = [
                'name' => $name,
                'type' => $_FILES['replace_images']['type'][$index],
                'tmp_name' => $_FILES['replace_images']['tmp_name'][$index],
                'error' => $_FILES['replace_images']['error'][$index],
                'size' => $_FILES['replace_images']['size'][$index],
            ];
            $url = uploadPhotographyImage($file, $upload_path, $upload_dir);
            if ($url) $images[$index] = $url;
        }
    }

    if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
        foreach ($_FILES['photos']['name'] as $index => $name) {
            $file = [
                'name' => $name,
                'type' => $_FILES['photos']['type'][$index],
                'tmp_name' => $_FILES['photos']['tmp_name'][$index],
                'error' => $_FILES['photos']['error'][$index],
                'size' => $_FILES['photos']['size'][$index],
            ];
            $url = uploadPhotographyImage($file, $upload_path, $upload_dir);
            if ($url) $images[] = $url;
        }
    }

    $images = array_values(array_unique($images));
    if (!$cover && !empty($images)) $cover = $images[0];
    $images_json = json_encode($images, JSON_UNESCAPED_SLASHES);

    if (empty($title)) {
        echo "<script>Uigg.alert('Title is required !')</script>";
    } elseif ($id) {
        $removed = array_diff(array_merge([$row['cover'] ?? ''], $old_images), array_merge([$cover], $images));
        deleteFilesByUrls($removed);

        try {
            $stmt = $conn->prepare("UPDATE photography SET title=?, cover=?, images=?, comment_enabled=?, created_at=? WHERE id=?");
        } catch (Throwable $e) {
            die("Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }
        $stmt->bind_param("sssisi", $title, $cover, $images_json, $comment_enabled, $created_at, $id);
        if ($stmt->execute()) {
            header("Location: photography-add.php?id=$id&msg=saved");
            exit();
        }
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO photography (title, cover, images, comment_enabled, created_at) VALUES (?, ?, ?, ?, ?)");
        } catch (Throwable $e) {
            die("Database error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }
        $stmt->bind_param("sssis", $title, $cover, $images_json, $comment_enabled, $created_at);
        if ($stmt->execute()) {
            header("Location: photography-add.php?id=".$stmt->insert_id."&msg=added");
            exit();
        }
    }
    echo "<script>Uigg.alert('Failed: " . addslashes($conn->error) . " !')</script>";
}

$title_val = htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8');
$cover_val = $row['cover'] ?? '';
$images_val = extractImageUrlsFromJson($row['images'] ?? '');
$comment_active = ($row && !$row['comment_enabled']) ? '' : ' active';
$comment_val = ($row && !$row['comment_enabled']) ? 0 : 1;
$date_val = $row ? substr($row['created_at'], 0, 10) : date('Y-m-d');
$heading = $id ? 'photography edit' : 'photography add';
$msg = $_GET['msg'] ?? '';
if ($msg) $msg_text = $msg === 'added' ? 'Added successfully !' : 'Saved successfully !';
?>

<div class="title"><h4>photography</h4></div>
<div class="contant">
    <div class="title"><h5><?php echo $heading; ?></h5></div>
    <div class="item">
        <section class="form">
            <form method="POST" enctype="multipart/form-data">
                <?php if ($id): ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
                <li><span>title</span><input class="wide-80" type="text" name="title" value="<?php echo $title_val; ?>" required></li>
                <li><span>date</span><input class="wide-20" type="date" name="created_at" value="<?php echo $date_val; ?>"></li>
                <li><span>comment</span><label><o class="toggle<?php echo $comment_active; ?>"></o><input type="hidden" name="comment_enabled" value="<?php echo $comment_val; ?>"></label></li>
                <li><span>cover</span>
                    <div class="upload">
                        <div class="ico upload-group" <?php if ($cover_val): ?>style="background-image: url('<?php echo htmlspecialchars($cover_val); ?>');color: transparent"<?php endif; ?>>
                            <input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp,.gif">
                        </div>
                    </div>
                    <cite>500x500px</cite>
                </li>
                <li><span>album</span>
                    <div class="upload wide">
                        <!-- 上传以后的图片，每张图片都以这段代码显示，这段代码里，点击input是更换，点击n是删除，这些功能的前端效果已经有了，只要程序功能-->
                        <?php foreach ($images_val as $image): ?>
                        <div class="ico upload-group" style="background-image: url('<?php echo htmlspecialchars($image); ?>');color: transparent">
                            <input type="hidden" name="images[]" value="<?php echo htmlspecialchars($image); ?>">
                            <input type="file" name="replace_images[]" accept=".jpg,.jpeg,.png,.webp,.gif">
                            <n class="ico"></n>
                        </div>
                        <?php endforeach; ?>
                        <!-- 结束 -->
                        <!-- 点击这个会上传多张图片 -->
                        <div class="ico upload-group">
                            <input type="file" name="photos[]" accept=".jpg,.jpeg,.png,.webp,.gif" multiple>
                        </div>
                        <!-- 结束 -->
                    </div>
                </li>
                <li class="resolve"><button class="btn btn-submit">submit</button></li>
            </form>
        </section>
    </div>
</div>

<script>
    var message = <?php echo json_encode($msg ? $msg_text : '', JSON_UNESCAPED_SLASHES); ?>;
    if (message) {
        Uigg.alert(message)
        history.replaceState(null,'',location.pathname+location.search.replace(/&?msg=\w+/,''))
    }
    $(function(){
        $('o.toggle').click(function(){
            $('input[name=comment_enabled]').val($(this).hasClass('active') ? 1 : 0)
        })

        $('.upload').on('click', '.upload-group n', function(e){
            e.preventDefault()
            var group = $(this).closest('.upload-group'),
                newIndex = group.data('newIndex')
            if (newIndex !== undefined) {
                selectedPhotos.splice(newIndex, 1)
                refreshPhotosInput()
                group.remove()
                $('.upload-group[data-new-index]').each(function(index){
                    $(this).attr('data-new-index', index)
                })
            } else {
                group.remove()
            }
        })

        $('.upload').on('change', 'input[type=file]:not([multiple])', function(){
            var input = this,
                group = $(input).closest('.upload-group'),
                file = input.files[0]
            if (group.data('newIndex') !== undefined) return
            if (!file) return
            var reader = new FileReader()
            reader.onload = function(e){
                group.css({
                    backgroundImage: 'url("' + e.target.result + '")',
                    color: 'transparent'
                })
            }
            reader.readAsDataURL(file)
        })

        var photosInput = $('input[name="photos[]"]')[0],
            selectedPhotos = []

        function refreshPhotosInput(){
            var transfer = new DataTransfer()
            selectedPhotos.forEach(function(file){ transfer.items.add(file) })
            photosInput.files = transfer.files
        }

        function renderSelectedPhotos(){
            $('.upload-group[data-new-index]').remove()
            selectedPhotos.forEach(function(file, index){
                var reader = new FileReader()
                reader.onload = function(e){
                    $('<div class="ico upload-group" style="color: transparent"><input type="file" accept=".jpg,.jpeg,.png,.webp,.gif"><n class="ico"></n></div>')
                        .attr('data-new-index', index)
                        .css({backgroundImage: 'url("' + e.target.result + '")', color: 'transparent'})
                        .insertBefore($(photosInput).closest('.upload-group'))
                }
                reader.readAsDataURL(file)
            })
        }

        $('input[name="photos[]"]').on('change', function(){
            $(this).closest('.upload-group').css({backgroundImage: '', color: ''})
            selectedPhotos = selectedPhotos.concat(Array.from(this.files))
            refreshPhotosInput()
            renderSelectedPhotos()
        })

        $('.upload').on('change', '.upload-group[data-new-index] input[type=file]', function(){
            var input = this,
                group = $(input).closest('.upload-group'),
                index = group.data('newIndex')
            if (!this.files[0]) return
            selectedPhotos[index] = input.files[0]
            refreshPhotosInput()
            var reader = new FileReader()
            reader.onload = function(e){
                group.css({
                    backgroundImage: 'url("' + e.target.result + '")',
                    color: 'transparent'
                })
            }
            reader.readAsDataURL(input.files[0])
        })
    })
</script>

</section>
</section>
</section>
</body>
</html>
