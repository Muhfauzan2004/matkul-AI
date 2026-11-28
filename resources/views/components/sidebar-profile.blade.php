<div class="sidebar-footer">
    <div class="dropdown sidebar-profile-dropdown">
        <button type="button" class="sidebar-profile-btn">
            <div class="sidebar-profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="sidebar-profile-info">
                <span class="sidebar-profile-name">{{ auth()->user()->name }}</span>
                <span class="sidebar-profile-role">{{ $roleLabel }}</span>
            </div>
            <i class="fas fa-chevron-up sidebar-profile-chevron"></i>
        </button>
        <div class="dropdown-menu sidebar-profile-menu">
            <a href="{{ route($profileRoute) }}" class="dropdown-item">
                <i class="fas fa-user-edit" style="color:var(--primary);"></i> Edit Profil
            </a>
            <div class="dropdown-divider"></div>
            <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="dropdown-item w-full" style="border:none;background:none;cursor:pointer;font-family:inherit;font-size:inherit;text-align:left;width:100%;">
                    <i class="fas fa-sign-out-alt" style="color:var(--danger);"></i> Logout
                </button>
            </form>
        </div>
    </div>
</div>
