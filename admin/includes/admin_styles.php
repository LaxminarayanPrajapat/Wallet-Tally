<style>
    :root {
        --primary-color: #1A237E;
        --secondary-color: #1B5E20;
        --gradient-start: #1A237E;
        --gradient-end: #1B5E20;
        --gradient-angle: 135deg;
        --light-blue: #E8EAF6;
        --background-light: #F5F7FA;
    }
    
    body {
        background: var(--background-light);
        padding-top: 70px;
        font-family: 'Poppins', sans-serif;
    }
    
    /* Gradient Buttons */
    .btn-gradient {
        background: linear-gradient(var(--gradient-angle), var(--gradient-start), var(--gradient-end));
        border: none;
        color: white !important;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        color: white !important;
    }
    
    .btn-gradient i {
        color: white !important;
    }
    
    /* Gradient Icons */
    .icon-gradient {
        background: linear-gradient(var(--gradient-angle), var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    /* Stat Cards */
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: transform 0.3s;
        border-left: 4px solid transparent;
        border-image: linear-gradient(var(--gradient-angle), var(--gradient-start), var(--gradient-end)) 1;
        height: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(26, 35, 126, 0.1);
    }
    
    .stat-card i {
        font-size: 2.5rem;
        margin-bottom: 15px;
    }
    
    .stat-card h3 {
        font-size: 2.5rem;
        margin: 0;
    }
    
    /* Table Cards */
    .table-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-top: 30px;
    }
    
    .table-card h5 {
        margin-bottom: 20px;
    }
    
    .table-card .table thead th {
        background: var(--light-blue);
        color: var(--primary-color);
        font-weight: 600;
        border: none;
    }
    
    .table-card .table tbody tr:hover {
        background: rgba(26, 35, 126, 0.02);
    }
    
    /* Action Buttons */
    .action-btn {
        padding: 8px 12px;
        border-radius: 6px;
        transition: all 0.3s;
        margin: 0 2px;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
    }
    
    .btn-view {
        background: linear-gradient(var(--gradient-angle), var(--gradient-start), var(--gradient-end));
        color: white;
        border: none;
    }
    
    .btn-delete {
        background: linear-gradient(135deg, #c62828, #b71c1c);
        color: white;
        border: none;
    }
    
    /* Page Header */
    .page-header {
        background: white;
        padding: 20px 25px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }
    
    .page-header h2 {
        margin: 0;
    }
    
    /* Gradient Text */
    .gradient-text {
        background: linear-gradient(var(--gradient-angle), var(--gradient-start), var(--gradient-end));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    /* Star Rating */
    .star-rating i {
        color: #FFC107;
    }
    
    /* Chart Containers */
    .table-card canvas {
        max-height: 300px;
    }
    
    /* System Info Cards */
    .table-card .border.rounded {
        border-color: rgba(26, 35, 126, 0.1) !important;
        transition: all 0.3s ease;
    }
    
    .table-card .border.rounded:hover {
        border-color: rgba(26, 35, 126, 0.3) !important;
        box-shadow: 0 2px 8px rgba(26, 35, 126, 0.1);
        transform: translateY(-2px);
    }
    
    /* Badge Styling */
    .badge {
        padding: 6px 12px;
        font-weight: 500;
    }
    
    /* Loading State */
    .loading-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 300px;
        color: #999;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .stat-card {
            margin-bottom: 20px;
        }
        
        body {
            padding-top: 60px;
        }
        
        .table-card {
            margin-top: 20px;
            padding: 15px;
        }
        
        .table-card canvas {
            max-height: 250px;
        }
    }
</style>
