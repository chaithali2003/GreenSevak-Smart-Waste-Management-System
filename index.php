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
                    <h1 class="display-4">Welcome to <span class="text-success">GreenSevak</span></h1>
                    <p class="lead">Join the movement for a cleaner, greener tomorrow.</p>
                    <p class="mb-4">We're revolutionizing waste management through technology, community engagement, and sustainable practices.</p>
                    <div class="d-flex gap-3 mt-4">
                        <a href="auth/login.php" class="btn btn-success btn-lg px-4">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </a>
                        <a href="auth/register.php" class="btn btn-success btn-lg px-4">
                            <i class="bi bi-person-plus me-2"></i>Register
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <img src="assets/img/banner.png" alt="GreenSevak Banner"  class="img-fluid" style="object-fit: cover;">
            </div>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="py-5 bg-light" style="width: 100%;">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="stats-item">
                    <div class="stats-number">5,000+</div>
                    <div class="stats-label">Happy Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-item">
                    <div class="stats-number">12,500+</div>
                    <div class="stats-label">Pickups Completed</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-item">
                    <div class="stats-number">85%</div>
                    <div class="stats-label">Recycling Rate</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-item">
                    <div class="stats-number">24/7</div>
                    <div class="stats-label">Support Available</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="py-5" style="width: 100%;">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="display-5 fw-bold">How It Works</h2>
                <p class="lead text-muted">Simple steps for a cleaner environment</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon">
    <i class="bi bi-calendar-check fs-1"></i>
</div>
                        <h4 class="mb-3">Schedule Pickup</h4>
                        <p class="text-muted">Citizens can easily schedule waste pickups at their convenience through our user-friendly platform.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon">
    <i class="bi bi-truck fs-1"></i>
</div>
                        <h4 class="mb-3">Smart Collection</h4>
                        <p class="text-muted">Our intelligent system assigns the nearest available collector for efficient waste management.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon">
    <i class="bi bi-check-circle fs-1"></i>
</div>
                        <h4 class="mb-3">Track & Confirm</h4>
                        <p class="text-muted">Real-time tracking and confirmation when your waste is responsibly processed.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Testimonials -->
<div class="py-5 bg-light" style="width: 100%;">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="display-5 fw-bold">What Our Users Say</h2>
                <p class="lead text-muted">Join thousands of satisfied community members</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body p-4 testimonial-card">
                        <p class="mb-4">"GreenSevak has made waste disposal so convenient. I love how I can schedule pickups right from the website!"</p>
                        <p class="mb-0 testimonial-author">- Priya Sharma, Resident</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body p-4 testimonial-card">
                        <p class="mb-4">"As a collector, the app helps me optimize my routes and serve more households efficiently."</p>
                        <p class="mb-0 testimonial-author">- Raj Patel, Waste Collector</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body p-4 testimonial-card">
                        <p class="mb-4">"Our community is cleaner than ever thanks to GreenSevak. The difference is remarkable!"</p>
                        <p class="mb-0 testimonial-author">- Municipal Officer</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="py-5 bg-success text-white" style="width: 100%;">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-4">Ready to Make a Difference?</h2>
        <p class="lead mb-5">Join our growing community of environmentally conscious citizens today.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="auth/register.php" class="btn btn-light btn-lg px-4">
                <i class="bi bi-person-plus me-2"></i>Sign Up Now
            </a>
            <a href="about.php" class="btn btn-outline-light btn-lg px-4">
                <i class="bi bi-info-circle me-2"></i>Learn More
            </a>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>