<?php
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirectBasedOnRole();
}
?>

<?php include_once 'includes/header.php'; ?>

<!-- Hero Section -->
<div class="hero-section py-5">
    <div class="container-fluid px-0">
        <div class="row g-0 align-items-center">
            <div class="col-lg-6 px-5">
                <div class="px-4 px-lg-5 py-5">
                    <h1 class="display-4">About <span class="text-success">GreenSevak</span></h1>
                    <p class="lead">A platform for better waste management</p>
                    <p class="mb-4">Connecting communities with efficient waste collection services through technology.</p>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <img src="assets/img/about-banner.png" alt="About GreenSevak"
                     class="img-fluid img-thumbnail"
                     style="width: 700px; height: auto; object-fit: cover;">
            </div>
        </div>
    </div>
</div>

<!-- What We Offer Section -->
<div class="py-5" style="width: 100%;">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="display-5 fw-bold">What We Offer</h2>
                <p class="lead text-muted">Features that make waste management easier</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-success bg-opacity-10 rounded-circle p-3 mb-3 mx-auto" style="width: 70px; height: 70px;">
                            <i class="bi bi-calendar-check text-success" style="font-size: 1.5rem;"></i>
                        </div>
                        <h4 class="mb-3">Scheduled Pickups</h4>
                        <p>Request waste collection at your convenience with our easy scheduling system.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-success bg-opacity-10 rounded-circle p-3 mb-3 mx-auto" style="width: 70px; height: 70px;">
                            <i class="bi bi-map text-success" style="font-size: 1.5rem;"></i>
                        </div>
                        <h4 class="mb-3">Route Optimization</h4>
                        <p>Efficient collection routes that save time and reduce environmental impact.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="bg-success bg-opacity-10 rounded-circle p-3 mb-3 mx-auto" style="width: 70px; height: 70px;">
                            <i class="bi bi-graph-up text-success" style="font-size: 1.5rem;"></i>
                        </div>
                        <h4 class="mb-3">Tracking & Reporting</h4>
                        <p>Monitor your waste collection history and environmental contribution.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- How It Works -->
<div class="py-5 bg-light" style="width: 100%;">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="display-5 fw-bold">How It Works</h2>
                <p class="lead text-muted">Simple steps for effective waste management</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0">
                    <div class="card-body text-center p-4">
                        <div class="bg-success text-white rounded-circle p-3 mb-3 mx-auto" style="width: 50px; height: 50px; line-height: 24px;">1</div>
                        <h4 class="mb-3">Register</h4>
                        <p>Create an account as a resident or waste collector to get started.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0">
                    <div class="card-body text-center p-4">
                        <div class="bg-success text-white rounded-circle p-3 mb-3 mx-auto" style="width: 50px; height: 50px; line-height: 24px;">2</div>
                        <h4 class="mb-3">Schedule or Accept</h4>
                        <p>Residents schedule pickups, collectors accept requests in their area.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0">
                    <div class="card-body text-center p-4">
                        <div class="bg-success text-white rounded-circle p-3 mb-3 mx-auto" style="width: 50px; height: 50px; line-height: 24px;">3</div>
                        <h4 class="mb-3">Complete & Rate</h4>
                        <p>Collection happens, both parties confirm completion and provide feedback.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="py-5 bg-success text-white" style="width: 100%;">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-4">Ready to Get Started?</h2>
        <p class="lead mb-5">Join our platform to make waste management more efficient.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="auth/login.php" class="btn btn-light btn-lg px-4">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </a>
            <a href="auth/register.php" class="btn btn-outline-light btn-lg px-4">
                <i class="bi bi-person-plus me-2"></i>Register
            </a>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>