<?php
if (!file_exists(dirname(__DIR__) . '/install.lock')) {
    header('Location: ../install.php');
    exit();
}
require_once('../db.php');

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$version_file = dirname(__DIR__) . '/version.json';
$local = ['version' => '0.0.0', 'build' => ''];
if (file_exists($version_file)) {
    $local = json_decode(file_get_contents($version_file), true) ?: $local;
}

$github_user = 'mixice';
$github_repo = 'photographer';
$github_branch = 'main';
$repo_sub = 'web';
$raw_url = "https://raw.githubusercontent.com/{$github_user}/{$github_repo}/{$github_branch}/{$repo_sub}/version.json";
$zip_url = "https://github.com/{$github_user}/{$github_repo}/archive/refs/heads/{$github_branch}.zip";

$message = '';
$message_type = '';
$remote = null;
$csrf = csrfToken();
$can_update = empty(updateRequirementErrors(true));

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === '' && !$can_update) {
    $message = updateRequirementMessage(updateRequirementErrors(true));
    $message_type = 'error';
}

// ------------------------------------------------------------------ check
if ($action === 'check') {
    $missing = updateRequirementErrors(false);
    if (!empty($missing)) {
        $message = updateRequirementMessage($missing);
        $message_type = 'error';
    } else {
        $remote_error = '';
        $remote = fetchRemoteVersion($raw_url, $remote_error);
        if (!$remote) {
            $message = $remote_error;
            $message_type = 'error';
        } elseif (version_compare($remote['version'], $local['version'], '>')) {
            if ($can_update) {
                $message = 'New version found: ' . htmlspecialchars($remote['version']);
                $message_type = 'success';
            } else {
                $message = 'New version found: ' . htmlspecialchars($remote['version']) . ', but ' . updateRequirementMessage(updateRequirementErrors(true));
                $message_type = 'error';
            }
        } else {
            $message = 'You are already on the latest version ' . htmlspecialchars($local['version']);
            $message_type = 'info';
        }
    }
}

// ------------------------------------------------------------------ do update
if ($action === 'update') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid update request. Please refresh and try again.';
        $message_type = 'error';
    } elseif ($missing = updateRequirementErrors(true)) {
        $message = updateRequirementMessage($missing);
        $message_type = 'error';
    } else {
        // download
        $tmp_zip = sys_get_temp_dir() . '/photographer_update_' . time() . '.zip';
        $zip_data = @file_get_contents(cacheBustedUrl($zip_url), false, updateHttpContext(60));

        if ($zip_data === false) {
            $message = 'Failed to download update package. Check your network.';
            $message_type = 'error';
        } elseif (substr($zip_data, 0, 2) !== 'PK') {
            $message = 'Downloaded update package is not a valid ZIP file. Please try again later.';
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
                @unlink($tmp_zip);
                deleteDir($tmp_dir);
                $message = 'Failed to extract update package. File may be corrupted.';
                $message_type = 'error';
            } else {
                $extracted = $zip->extractTo($tmp_dir);
                $zip->close();

                if (!$extracted) {
                    @unlink($tmp_zip);
                    deleteDir($tmp_dir);
                    $message = 'Failed to extract update package. File may be corrupted.';
                    $message_type = 'error';
                } else {
                    // github zip structure: photographer-main/web/...
                    $inner_dirs = glob($tmp_dir . '/*', GLOB_ONLYDIR);
                    $repo_root = !empty($inner_dirs) ? $inner_dirs[0] : $tmp_dir;
                    $src_root = $repo_root . '/' . $repo_sub;

                    if (!is_dir($src_root)) {
                        @unlink($tmp_zip);
                        deleteDir($tmp_dir);
                        $message = 'Update package structure is invalid. Expected web/ directory was not found.';
                        $message_type = 'error';
                    } else {
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
    }
}

// reload version after potential update
if (file_exists($version_file)) {
    $local = json_decode(file_get_contents($version_file), true) ?: $local;
}

if ($action === 'update' && empty(updateRequirementErrors(false))) {
    $remote_error = '';
    $fresh_remote = fetchRemoteVersion($raw_url, $remote_error);
    if ($fresh_remote) {
        $remote = $fresh_remote;
    }
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

function updateHttpContext($timeout) {
    return stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'user_agent' => 'Photographer-Updater/1.0',
            'header' => "Cache-Control: no-cache\r\nPragma: no-cache\r\n",
        ],
    ]);
}

function cacheBustedUrl($url) {
    return $url . (strpos($url, '?') === false ? '?' : '&') . '_=' . time();
}

function fetchRemoteVersion($url, &$error = '') {
    $json = @file_get_contents(cacheBustedUrl($url), false, updateHttpContext(15));
    if ($json === false) {
        $error = 'Cannot connect to GitHub. Please check your network or DNS.';
        return null;
    }

    $remote = json_decode($json, true);
    if (!is_array($remote) || empty($remote['version'])) {
        $error = 'Remote version info is malformed.';
        return null;
    }

    return $remote;
}

function updateRequirementErrors($need_zip = false) {
    $errors = [];
    if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $errors[] = 'allow_url_fopen is disabled';
    }
    if (!extension_loaded('openssl')) {
        $errors[] = 'OpenSSL extension is not enabled';
    }
    if (!in_array('https', stream_get_wrappers(), true)) {
        $errors[] = 'HTTPS stream wrapper is not available';
    }
    if ($need_zip && !class_exists('ZipArchive')) {
        $errors[] = 'ZipArchive extension is not installed';
    }
    return $errors;
}

function updateRequirementMessage($errors) {
    return 'Auto-update is unavailable: ' . implode('; ', $errors) . '. Please enable the required PHP extension(s) and try again.';
}

$js_msg = json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES);
include ('head.php');
?>

<div class="title"><h4>system update</h4></div>
<div class="contant">
    <div class="item">
        <div class="article">
            <li>
                <span>Current version: </span>
                <span><?php echo htmlspecialchars($local['version']); ?></span>
                <span>(<?php echo htmlspecialchars($local['build'] ?? '-'); ?>)</span>
            </li>
            <?php if ($remote): ?>
            <li>
                <span>Remote version: </span>
                <span><?php echo htmlspecialchars($remote['version']); ?></span>
                <span>(<?php echo htmlspecialchars($remote['build'] ?? '-'); ?>)</span>
            </li>
            <?php endif; ?>
        </div>
        <div class="form">
            <?php if ($remote && $can_update && version_compare($remote['version'], $local['version'], '>')): ?>
            <form method="POST" id="update-form">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn co-green">update to v<?php echo htmlspecialchars($remote['version']); ?></button>
            </form>
            <?php else: ?>
            <a href="?action=check" class="btn btn-submit">check for updates</a>
            <?php endif; ?>
        </div>
        <reminder>
            <p>Protected files: db.php, uploads/, install.lock</p>
            <p>Update source: <a href="https://github.com/mixice/photographer" target="_blank"><u>https://github.com/mixice/photographer</u></a></p>
        </reminder>
    </div>
</div>

<?php if ($message): ?>
<script type="module">
    ready(function (){
        Uigg.alert(<?php echo $js_msg; ?>)
    })
</script>
<?php endif; ?>


</section>
</section>
</section>
</body>
</html>
