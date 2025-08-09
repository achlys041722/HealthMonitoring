<?php
if (!isset($activePage)) $activePage = '';
?>
<nav class="col-lg-2 col-md-3 d-md-block sidebar collapse show" id="sidebarMenu" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100vh; width: 280px; min-width: 280px; flex-shrink: 0; position: fixed; top: 0; left: 0; overflow-y: auto; z-index: 1000;">
    <div class="position-sticky pt-3">
        <!-- User Profile Section -->
        <div class="text-center mb-4 px-3">
            <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                <i class="fas fa-user-tie fa-2x text-primary"></i>
            </div>
            <h6 class="text-white mb-1"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Principal'); ?></h6>
            <small class="text-white-50">School Principal</small>
        </div>
        
        <!-- Navigation Menu -->
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-3 rounded <?php if($activePage==='dashboard') echo 'active'; ?>" 
                   href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/principal_dashboard.php"
                   style="color: rgba(255,255,255,0.8); transition: all 0.3s ease; <?php if($activePage==='dashboard') echo 'background: rgba(255,255,255,0.2); color: white;'; ?>">
                    <i class="fas fa-tachometer-alt me-3" style="width: 20px;"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-3 rounded <?php if($activePage==='notifications') echo 'active'; ?>" 
                   href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/notifications.php"
                   style="color: rgba(255,255,255,0.8); transition: all 0.3s ease; <?php if($activePage==='notifications') echo 'background: rgba(255,255,255,0.2); color: white;'; ?>">
                    <i class="fas fa-bell me-3" style="width: 20px;"></i>
                    <span>Notifications</span>
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-3 rounded <?php if($activePage==='teachers') echo 'active'; ?>" 
                   href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/teachers.php"
                   style="color: rgba(255,255,255,0.8); transition: all 0.3s ease; <?php if($activePage==='teachers') echo 'background: rgba(255,255,255,0.2); color: white;'; ?>">
                    <i class="fas fa-chalkboard-teacher me-3" style="width: 20px;"></i>
                    <span>Teachers</span>
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-3 rounded <?php if($activePage==='students') echo 'active'; ?>" 
                   href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/students.php"
                   style="color: rgba(255,255,255,0.8); transition: all 0.3s ease; <?php if($activePage==='students') echo 'background: rgba(255,255,255,0.2); color: white;'; ?>">
                    <i class="fas fa-user-graduate me-3" style="width: 20px;"></i>
                    <span>Students</span>
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link d-flex align-items-center px-3 py-3 rounded <?php if($activePage==='health_records') echo 'active'; ?>" 
                   href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/principal/health_records.php"
                   style="color: rgba(255,255,255,0.8); transition: all 0.3s ease; <?php if($activePage==='health_records') echo 'background: rgba(255,255,255,0.2); color: white;'; ?>">
                    <i class="fas fa-heartbeat me-3" style="width: 20px;"></i>
                    <span>Health Records</span>
                </a>
            </li>
        </ul>
        
        <!-- Divider -->
        <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">
        
        <!-- Logout Section -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center px-3 py-3 rounded" 
                   href="/Web-Based%20Health-Integrated%20Student%20Information%20System/templates/register/logout.php"
                   style="color: rgba(255,255,255,0.8); transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.2);">
                    <i class="fas fa-sign-out-alt me-3" style="width: 20px;"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
.sidebar {
    width: 280px !important;
    min-width: 280px !important;
    flex-shrink: 0 !important;
    max-width: 280px !important;
    height: 100vh !important;
    position: fixed !important;
    top: 0;
    left: 0;
    overflow-y: auto;
    z-index: 1000;
}

/* Adjust main content for fixed sidebar */
.container-fluid .row {
    display: flex;
    flex-wrap: nowrap;
}

.container-fluid .row > main {
    flex: 1;
    margin-left: 280px !important;
    min-height: 100vh;
}

@media (max-width: 768px) {
    .container-fluid .row {
        flex-wrap: wrap;
    }
    
    .container-fluid .row > main {
        margin-left: 0 !important;
    }
    
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1000;
        width: 100%;
        height: 100vh;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
}

.sidebar .nav-link:hover {
    background: rgba(255,255,255,0.1) !important;
    color: white !important;
    transform: translateX(5px);
}

.sidebar .nav-link.active {
    background: rgba(255,255,255,0.2) !important;
    color: white !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.sidebar .nav-link i {
    transition: transform 0.3s ease;
}

.sidebar .nav-link:hover i {
    transform: scale(1.1);
}

.fas, .fa {
    display: inline-block !important;
    font-style: normal;
    font-variant: normal;
    text-rendering: auto;
    line-height: 1;
}
.me-3 {
    margin-right: 1rem !important;
}
</style> 