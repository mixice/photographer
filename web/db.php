<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------------------------------------------------db
$servername = "localhost";
$username = "";
$password = "";
$dbname = "";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ---------------------------------------------------------------------------------------------settings
function getSettings($conn, $refresh = false) {
    static $settings = null;
    if ($refresh) {
        $settings = null;
    }
    if ($settings === null) {
        $result = $conn->query("SELECT * FROM settings LIMIT 1");
        $settings = $result ? ($result->fetch_assoc() ?: []) : [];
    }
    return $settings;
}

// ---------------------------------------------------------------------------------------------csrf
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrf($token) {
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

// ---------------------------------------------------------------------------------------------count
function countWhere($conn, $table, $where, $types = '', $params = []) {
    $sql = "SELECT COUNT(*) FROM `$table` WHERE $where";
    if ($types === '') {
        return (int) $conn->query($sql)->fetch_row()[0];
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_row()[0];
}

function isValidImageFile($path) {
    $info = @getimagesize($path);
    if ($info === false) {
        return false;
    }
    return in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true);
}

// ---------------------------------------------------------------------------------------------page
function renderPagination($current_page, $total_pages, $params = []) {
    $query_string = '';
    foreach ($params as $key => $value) {
        if (!empty($value)) {
            $query_string .= '&' . urlencode($key) . '=' . urlencode($value);
        }
    }
    ob_start();
    ?>
    <page>
        <?php if ($current_page > 1): ?>
            <a href="?page=1<?php echo $query_string; ?>" class="ico ico-alone-side-left"></a>
            <a href="?page=<?php echo $current_page - 1 . $query_string; ?>" class="ico ico-alone-left"></a>
        <?php endif; ?>
        <?php
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        if ($current_page <= 3) {
            $end_page = min(5, $total_pages);
        } elseif ($current_page > $total_pages - 3) {
            $start_page = max(1, $total_pages - 4);
        }
        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <a href="?page=<?php echo $i . $query_string; ?>" class="<?php echo $i == $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page + 1 . $query_string; ?>" class="ico ico-alone-right"></a>
            <a href="?page=<?php echo $total_pages . $query_string; ?>" class="ico ico-alone-side-right"></a>
        <?php endif; ?>
        <span><?php echo $current_page . '/' . $total_pages; ?></span>
        <input type="number" min="1" max="<?php echo $total_pages; ?>" id="pageInput">
        <a class="ico ico-arrow-enter" id="pageJump"></a>
    </page>
    <script>
        function jumpToPage(){
            var pageInput = document.getElementById('pageInput').value;
            var totalPages = <?php echo $total_pages; ?>;
            if(pageInput >= 1 && pageInput <= totalPages){
                var url = '?page=' + pageInput + '<?php echo $query_string; ?>';
                window.location.href = url;
            } else {
                Uigg.alert("Please enter a valid page number");
            }
        }
        document.getElementById('pageInput').onkeypress = function(event){
            event = event || window.event;
            if(event.key === 'Enter' || event.keyCode === 13) jumpToPage();
        };
        document.getElementById('pageJump').onclick = jumpToPage;
    </script>
    <?php
    return ob_get_clean();
}

// ---------------------------------------------------------------------------------------------- del
function deleteRecordWithFiles($table, $id, $image_fields = [], $content_fields = [], $json_image_fields = []) {
    global $conn;
    if (empty($table) || $id <= 0) {
        return [false, 'Parameter error'];
    }
    try {
        $fields_to_select = array_merge($image_fields, $content_fields, $json_image_fields);
        if (empty($fields_to_select)) {
            $delete_sql = "DELETE FROM `$table` WHERE id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            return [true, 'Deleted successfully'];
        }
        $field_list = implode(', ', array_map(function($field) {
            return "`$field`";
        }, $fields_to_select));
        $select_sql = "SELECT $field_list FROM `$table` WHERE id = ?";
        $stmt = $conn->prepare($select_sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            return [false, 'Record does not exist'];
        }
        $data = $result->fetch_assoc();
        $stmt->close();
        foreach ($image_fields as $field) {
            if (!empty($data[$field])) {
                $file = $_SERVER['DOCUMENT_ROOT'] . $data[$field];
                if (file_exists($file)) unlink($file);
            }
        }
        foreach ($content_fields as $field) {
            if (!empty($data[$field])) {
                $image_urls = extractImageUrlsFromHtml($data[$field]);
                foreach ($image_urls as $url) {
                    $file = $_SERVER['DOCUMENT_ROOT'] . $url;
                    if (file_exists($file)) unlink($file);
                }
            }
        }
        foreach ($json_image_fields as $field) {
            if (!empty($data[$field])) {
                deleteFilesByUrls(extractImageUrlsFromJson($data[$field]));
            }
        }
        $delete_sql = "DELETE FROM `$table` WHERE id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            return [true, 'Deleted successfully'];
        } else {
            return [false, 'Database deletion failed: ' . $conn->error];
        }
    } catch (Exception $e) {
        return [false, 'Deletion failed: ' . $e->getMessage()];
    }
}

function extractImageUrlsFromHtml($html) {
    $urls = [];
    if (empty($html)) return $urls;
    preg_match_all('/src=["\']([^"\']+)["\']/i', $html, $matches);
    if (!empty($matches[1])) {
        foreach (array_unique($matches[1]) as $url) {
            // convert relative path ../uploads/ to /uploads/
            $url = preg_replace('#^(\.\./)+#', '/', $url);
            $urls[] = $url;
        }
    }
    return $urls;
}

function extractImageUrlsFromJson($json) {
    $urls = [];
    if (empty($json)) return $urls;
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) return $urls;
    foreach ($decoded as $url) {
        if (is_string($url) && $url !== '') {
            $urls[] = $url;
        }
    }
    return array_values(array_unique($urls));
}

function deleteFilesByUrls($urls) {
    foreach (array_unique(array_filter($urls)) as $url) {
        $file = $_SERVER['DOCUMENT_ROOT'] . $url;
        if (is_file($file)) unlink($file);
    }
}

// ------------------------------------------------------------------------------------------ tmp move
function moveTmpFiles($content, $target_type) {
    if (empty($content)) return $content;
    $urls = extractImageUrlsFromHtml($content);
    $doc_root = $_SERVER['DOCUMENT_ROOT'];
    $moved = false;
    foreach ($urls as $url) {
        if (strpos($url, '/uploads/tmp/') === 0) {
            $filename = basename($url);
            $src = $doc_root . $url;
            $new_url = '/uploads/' . $target_type . '/' . $filename;
            $dst = $doc_root . $new_url;
            if (file_exists($src)) {
                $dst_dir = dirname($dst);
                if (!is_dir($dst_dir)) mkdir($dst_dir, 0755, true);
                rename($src, $dst);
            }
            $content = str_replace($url, $new_url, $content);
            $moved = true;
        }
    }
    return $content;
}

// ------------------------------------------------------------------------------------------ edit clean
function cleanUnusedFiles($old_cover, $new_cover, $old_content, $new_content) {
    // delete old cover if replaced
    if (!empty($old_cover) && $old_cover !== $new_cover) {
        $file = $_SERVER['DOCUMENT_ROOT'] . $old_cover;
        if (file_exists($file)) unlink($file);
    }
    // delete images removed from content
    $old_urls = extractImageUrlsFromHtml($old_content);
    $new_urls = extractImageUrlsFromHtml($new_content);
    $removed = array_diff($old_urls, $new_urls);
    foreach ($removed as $url) {
        $file = $_SERVER['DOCUMENT_ROOT'] . $url;
        if (file_exists($file)) unlink($file);
    }
}

// ---------------------------------------------------------------------------------------------sanitize html
function sanitizeHtml($html) {
    if (empty($html)) return '';

    // Remove full-block dangerous elements (with content)
    $html = preg_replace('#<(script|iframe|object|embed|applet|base|link|meta|style)[^>]*>.*?</\1>#is', '', $html);
    // Remove self-closing / unclosed dangerous elements
    $html = preg_replace('#<(script|iframe|object|embed|applet|base|link|meta|style)\b[^>]*/?>#is', '', $html);

    // Remove HTML comments (conditional execution in IE)
    $html = preg_replace('/<!--.*?-->/s', '', $html);

    // Remove on* event handler attributes (onclick, onerror, onload, etc.)
    $html = preg_replace('#\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]*)#i', '', $html);

    // Remove javascript:/vbscript:/data: protocols in href/src/action
    $html = preg_replace('#\b(href|src|action)\s*=\s*(?:"[^"]*(?:javascript|vbscript|data)\s*:[^"]*"|\'[^\']*(?:javascript|vbscript|data)\s*:[^\']*\')#i', '', $html);

    // Remove CSS expression()
    $html = preg_replace('#expression\s*\(#i', '', $html);

    return $html;
}
?>
