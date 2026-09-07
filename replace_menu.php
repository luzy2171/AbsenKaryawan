<?php
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('resources/views'));
$files = array();
foreach ($iterator as $info) {
    if ($info->isFile() && $info->getExtension() == 'php') {
        $files[] = $info->getPathname();
    }
}

$oldMenu = '/<li class="nav-item">\s*<a class="nav-link[^>]+href="\{\{\s*route\(\'admin\.mesin\.index\'\)\s*\}\}"[^>]*>\s*<i class="bi bi-hdd-network me-2"><\/i> Mesin Absensi\s*<\/a>\s*<\/li>/is';

$newMenu = <<<HTML
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/mesin-absensi/hikvision') ? 'active' : '' }}"
                                href="{{ route('admin.mesin.hikvision') }}">
                                <i class="bi bi-person-bounding-box me-2"></i> Mesin Hikvision
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/mesin-absensi/solution') ? 'active' : '' }}"
                                href="{{ route('admin.mesin.solution') }}">
                                <i class="bi bi-fingerprint me-2"></i> Mesin Solution
                            </a>
                        </li>
HTML;

foreach ($files as $file) {
    if (strpos($file, 'detail.blade.php') !== false) continue;
    $content = file_get_contents($file);
    if (preg_match($oldMenu, $content)) {
        $content = preg_replace($oldMenu, $newMenu, $content);
        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
