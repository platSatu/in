{{-- Tab menu Students > File Student: daftar file & pengaturan notifikasi. --}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $active === 'files' ? 'active' : '' }}" href="{{ route('student.files.index') }}">
            <i class="bi bi-folder2-open"></i> File Student
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $active === 'notifications' ? 'active' : '' }}" href="{{ route('student.files.notifications') }}">
            <i class="bi bi-bell"></i> Notifikasi
        </a>
    </li>
</ul>
