<?php
include ('head.php');

$version_file = dirname(__DIR__) . '/version.json';
$local = json_decode(file_get_contents($version_file), true) ?: ['version' => '0.0.0', 'build' => '', 'description' => ''];

$github_user = 'mixice';
$github_repo = 'photographer';
$github_branch = 'main';
$raw_url = "https://raw.githubusercontent.com/{$github_user}/{$github_repo}/{$github_branch}/version.json";
$zip_url = "https://github.com/{$github_user}/{$github_repo}/archive/refs/heads/{$github_branch}.zip";

$message = '';
$message_type = '';
$remote = null;
$checked = false;

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// ------------------------------------------------------------------ check
if ($action === 'check') {
    $checked = true;
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'Photographer-Updater/1.0']]);
    $json = @file_get_contents($raw_url, false, $ctx);
    if ($json === false) {
        $message = '无法连接到 GitHub，请检查网络或 GitHub 是否可访问';
        $message_type = 'error';
    } else {
        $remote = json_decode($json, true);
        if (!$remote || empty($remote['version'])) {
            $message = '远程版本信息格式错误';
            $message_type = 'error';
        } elseif (version_compare($remote['version'], $local['version'], '>')) {
            $message = '发现新版本 v' . htmlspecialchars($remote['version']) . ' —— ' . htmlspecialchars($remote['description'] ?? '');
            $message_type = 'success';
        } else {
            $message = '已是最新版本 v' . htmlspecialchars($local['version']);
            $message_type = 'info';
        }
    }
}

// ------------------------------------------------------------------ do update
if ($action === 'update') {
    $checked = true;
    if (!class_exists('ZipArchive')) {
        $message = '服务器未安装 ZipArchive 扩展，无法自动更新';
        $message_type = 'error';
    } else {
        // download
        $tmp_zip = sys_get_temp_dir() . '/photographer_update_' . time() . '.zip';
        $ctx = stream_context_create(['http' => ['timeout' => 60, 'user_agent' => 'Photographer-Updater/1.0']]);
        $zip_data = @file_get_contents($zip_url, false, $ctx);

        if ($zip_data === false) {
            $message = '下载更新包失败，请检查网络';
            $message_type = 'error';
        } elseif (file_put_contents($tmp_zip, $zip_data) === false) {
            $message = '写入临时文件失败，请检查磁盘权限';
            $message_type = 'error';
        } else {
            // extract
            $tmp_dir = sys_get_temp_dir() . '/photographer_update_extract_' . time();
            mkdir($tmp_dir, 0755, true);

            $zip = new ZipArchive();
            if ($zip->open($tmp_zip) !== true) {
                $message = '解压更新包失败，文件可能已损坏';
                $message_type = 'error';
            } else {
                $zip->extractTo($tmp_dir);
                $zip->close();

                // github zip has a top-level folder like "photographer-main"
                $inner_dirs = glob($tmp_dir . '/*', GLOB_ONLYDIR);
                $src_root = !empty($inner_dirs) ? $inner_dirs[0] : $tmp_dir;

                $project_root = dirname(__DIR__);
                $protected = ['db.php', 'uploads', 'install.lock'];

                $copied = 0;
                $skipped = 0;
                $errors = [];

                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($src_root, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($files as $file) {
                    $rel_path = str_replace('\\', '/', substr($file->getPathname(), strlen($src_root) + 1));
                    $dest = $project_root . '/' . $rel_path;

                    // check protected
                    $skip = false;
                    foreach ($protected as $p) {
                        if ($rel_path === $p || strpos($rel_path, $p . '/') === 0 || strpos($rel_path, $p . '\\') === 0) {
                            $skip = true;
                            break;
                        }
                    }
                    if ($skip) {
                        $skipped++;
                        continue;
                    }

                    if ($file->isDir()) {
                        if (!is_dir($dest)) {
                            mkdir($dest, 0755, true);
                        }
                    } else {
                        $dest_dir = dirname($dest);
                        if (!is_dir($dest_dir)) {
                            mkdir($dest_dir, 0755, true);
                        }
                        if (copy($file->getPathname(), $dest)) {
                            $copied++;
                        } else {
                            $errors[] = '复制失败: ' . $rel_path;
                        }
                    }
                }

                // cleanup temp files
                @unlink($tmp_zip);
                deleteDir($tmp_dir);

                // reload local version after update
                if (file_exists($version_file)) {
                    $local = json_decode(file_get_contents($version_file), true) ?: $local;
                }

                if (empty($errors)) {
                    $message = "更新完成！已覆盖 {$copied} 个文件，保留 {$skipped} 个受保护文件（db.php、uploads/ 等）";
                    $message_type = 'success';
                } else {
                    $message = "更新部分完成，{$copied} 个文件成功，但以下文件失败：" . implode('、', $errors);
                    $message_type = 'error';
                }
            }
        }
    }
}

// reload version after potential update
if (file_exists($version_file)) {
    $local = json_decode(file_get_contents($version_file), true) ?: $local;
}

function deleteDir($dir) {
    if (!is_dir($dir)) return;
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item) : unlink($item);
    }
    rmdir($dir);
}
?>

<div class="title"><h4>system update</h4></div>
<div class="contant">
    <div class="item">
        <?php if ($message): ?>
        <div class="bloomer <?php echo $message_type === 'error' ? 'co-red' : ($message_type === 'success' ? 'co-green' : ''); ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="article">
            <li>
                <span>current version</span>
                <span class="co-sapphire"><strong>v<?php echo htmlspecialchars($local['version']); ?></strong></span>
            </li>
            <li>
                <span>build date</span>
                <span><?php echo htmlspecialchars($local['build'] ?? '-'); ?></span>
            </li>
            <?php if ($remote && version_compare($remote['version'], $local['version'], '>')): ?>
            <li>
                <span>latest version</span>
                <span class="co-green"><strong>v<?php echo htmlspecialchars($remote['version']); ?></strong></span>
            </li>
            <li>
                <span>update log</span>
                <span><?php echo nl2br(htmlspecialchars($remote['description'] ?? '-')); ?></span>
            </li>
            <?php endif; ?>
        </div>

        <div class="form" style="margin-top:16px">
            <?php if ($remote && version_compare($remote['version'], $local['version'], '>')): ?>
            <form method="POST" onsubmit="return confirm('确定要更新到 v<?php echo htmlspecialchars($remote['version']); ?> 吗？\n\n更新将覆盖除 db.php 和 uploads/ 外的所有文件。建议先备份。')">
                <input type="hidden" name="action" value="update">
                <button type="submit" class="btn co-green">更新到 v<?php echo htmlspecialchars($remote['version']); ?></button>
            </form>
            <?php else: ?>
            <a href="?action=check" class="btn btn-med">check for updates</a>
            <?php endif; ?>
        </div>

        <reminder style="margin-top:16px">
            受保护文件：db.php、uploads/ 目录、install.lock —— 更新时不会被覆盖。<br>
            更新来源：GitHub raw (raw.githubusercontent.com)，请确保服务器可访问 GitHub。
        </reminder>
    </div>
</div>


</section>
</section>
</section>
</body>
</html>
