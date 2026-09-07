<?php
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('resources/views'));
$files = array();
foreach ($iterator as $info) {
    if ($info->isFile() && $info->getExtension() == 'php') {
        $files[] = $info->getPathname();
    }
}

$oldMenu = <<<HTML
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

$newMenu = <<<HTML
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/mesin-absensi*') ? 'active' : '' }}"
                                href="{{ route('admin.mesin.index') }}">
                                <i class="bi bi-diagram-3 me-2"></i> Kontrol Pusat All Vendor
                            </a>
                        </li>
HTML;

foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'route(\'admin.mesin.hikvision\')') !== false) {
        $content = str_replace($oldMenu, $newMenu, $content);
        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
