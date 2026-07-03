<?php
include ('head.php');

$version_file = dirname(__DIR__) . '/version.json';
$local = ['version' => '0.0.0', 'build' => ''];
if (file_exists($version_file)) {
    $local = json_decode(file_get_contents($version_file), true) ?: $local;
}

$github_user = 'mixice';
$github_repo = 'photographer';
$github_branch = 'main';
$repo_sub = 'web'; // subdirectory inside the repo where project files live
$raw_url = "https://raw.githubusercontent.com/{$github_user}/{$github_repo}/{$github_branch}/{$repo_sub}/version.json";
$zip_url = "https://github.com/{$github_user}/{$github_repo}/archive/refs/heads/{$github_branch}.zip";

$message = '';
$message_type = '';
$remote = null;

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// ------------------------------------------------------------------ check
if ($action === 'check') {
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'Photographer-Updater/1.0']]);
    $json = @file_get_contents($raw_url, false, $ctx);
    if ($json === false) {
        $message = 'Cannot connect to GitHub. Please check your network or DNS.';
        $message_type = 'error';
    } else {
        $remote = json_decode($json, true);
        if (!$remote || empty($remote['version'])) {
            $message = 'Remote version info is malformed.';
            $message_type = 'error';
        } elseif (version_compare($remote['version'], $local['version'], '>')) {
            $message = 'New version found: v' . htmlspecialchars($remote['version']);
            $message_type = 'success';
        } else {
            $message = 'You are already on the latest version v' . htmlspecialchars($local['version']);
            $message_type = 'info';
        }
    }
}

// ------------------------------------------------------------------ do update
if ($action === 'update') {
    if (!class_exists('ZipArchive')) {
        $message = 'ZipArchive extension is not installed. Cannot auto-update.';
        $message_type = 'error';
    } else {
        // download
        $tmp_zip = sys_get_temp_dir() . '/photographer_update_' . time() . '.zip';
        $ctx = stream_context_create(['http' => ['timeout' => 60, 'user_agent' => 'Photographer-Updater/1.0']]);
        $zip_data = @file_get_contents($zip_url, false, $ctx);

        if ($zip_data === false) {
            $message = 'Failed to download update package. Check your network.';
            $message_type = 'error';
        } elseif (file_put_contents($tmp_zip, $zip_data) === false) {
            $message = 'Failed to write temp file. Check disk permissions.';
            $message_type = 'error';
        } else {
            // extract
            $tmp_dir = sys_get_temp_dir() . '/photographer_update_extract_' . time();
            mkdir($tmp_dir, 0755, true);

            $zip = new ZipArchive();
            if ($zip->open($tmp_zip) !== true) {
                $message = 'Failed to extract update package. File may be corrupted.';
                $message_type = 'error';
            } else {
                $zip->extractTo($tmp_dir);
                $zip->close();

                // github zip structure: photographer-main/web/...
                $inner_dirs = glob($tmp_dir . '/*', GLOB_ONLYDIR);
                $repo_root = !empty($inner_dirs) ? $inner_dirs[0] : $tmp_dir;
                $src_root = is_dir($repo_root . '/' . $repo_sub) ? $repo_root . '/' . $repo_sub : $repo_root;

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
                        if ($rel_path === $p || strpos($rel_path, $p . '/') === 0) {
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
                            $errors[] = 'copy failed: ' . $rel_path;
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
                    $message = "Update complete. {$copied} file(s) overwritten, {$skipped} protected file(s) preserved.";
                    $message_type = 'success';
                } else {
                    $message = "Update partially complete. {$copied} file(s) succeeded, errors: " . implode(', ', $errors);
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

// escape message for JS
$js_msg = json_encode($message);
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
                <span>Current version</span>
                <span class="co-sapphire"><strong>v<?php echo htmlspecialchars($local['version']); ?></strong></span>
            </li>
            <li>
                <span>Build date</span>
                <span><?php echo htmlspecialchars($local['build'] ?? '-'); ?></span>
            </li>
            <?php if ($remote): ?>
            <li>
                <span>remote version</span>
                <span class="<?php echo version_compare($remote['version'], $local['version'], '>') ? 'co-green' : ''; ?>"><strong>v<?php echo htmlspecialchars($remote['version']); ?></strong></span>
            </li>
            <?php endif; ?>
        </div>

        <div class="form" style="margin-top:16px">
            <?php if ($remote && version_compare($remote['version'], $local['version'], '>')): ?>
            <form method="POST" id="update-form">
                <input type="hidden" name="action" value="update">
                <button type="submit" class="btn co-green">update to v<?php echo htmlspecialchars($remote['version']); ?></button>
            </form>
            <?php else: ?>
            <a href="?action=check" class="btn btn-submit">check for updates</a>
            <?php endif; ?>
        </div>

        <reminder style="margin-top:16px">
            <p>Protected files: db.php, uploads/, install.lock</p>
            <p>Update source: GitHub raw (raw.githubusercontent.com)</p>
        </reminder>
    </div>
</div>

<?php if ($message): ?>
<script>
    ready(function () {
        Uigg.alert('<?php echo $js_msg; ?>')
    })
</script>
<?php endif; ?>


</section>
</section>
</section>
</body>
</html>
