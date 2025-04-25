<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once 'db_connection.php';
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <a href="index.php">PLP GSO Management</a>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
    </div>
    
    <div class="sidebar-content">
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="equipment.php" class="sidebar-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 7l-5 5 5 5"></path>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <circle cx="15.5" cy="8.5" r="1.5"></circle>
                    <circle cx="12" cy="16" r="1.5"></circle>
                    <path d="M20 4v7a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4V4"></path>
                </svg>
                <span>Equipment</span>
            </a>
            <a href="borrowings.php" class="sidebar-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>
                </svg>
                <span>Borrowings</span>
            </a>
            <?php if (isAdmin()): ?>
            <a href="categories.php" class="sidebar-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
                <span>Categories</span>
            </a>
            <a href="users.php" class="sidebar-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Users</span>
            </a>
            <a href="reports.php" class="sidebar-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span>Reports</span>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    
    <div class="sidebar-footer">
    <div class="user-profile" id="userProfile">
        <div class="user-avatar">
            <?php 
                $initials = '';
                if(isset($_SESSION['first_name'], $_SESSION['last_name'])) {
                    $initials = strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1));
                } else {
                    $initials = 'G';
                }
                echo $initials;
            ?>
        </div>
        <div class="user-info">
            <span class="user-name"><?php echo isset($_SESSION['first_name'], $_SESSION['last_name']) ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : 'Guest'; ?></span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
        <div class="user-menu-dropdown" id="userMenuDropdown">
            <?php if (!isAdmin()): ?>   
                <a href="my_borrowings.php">My Borrowings</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>
    </div>
</aside>

<div class="content-wrapper">
    <div class="top-bar">
        <button class="sidebar-toggle" id="mobileToggle">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <h1 class="page-title">
            <?php 
                // Get the current filename
                $current_file = basename($_SERVER['PHP_SELF']);
                
                // Remove the extension
                $page_name = pathinfo($current_file, PATHINFO_FILENAME);
                
                // Convert to title case and replace underscores with spaces
                echo ucwords(str_replace('_', ' ', $page_name)); 
            ?>
        </h1>
    </div>

<script src="sidebar.js"></script>
