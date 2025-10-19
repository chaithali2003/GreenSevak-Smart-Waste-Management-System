<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// Initialize CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }

    // Store form data in session for repopulation
    $_SESSION['contact_form'] = [
        'name' => $_POST['name'],
        'email' => $_POST['email'],
        'subject' => $_POST['subject'],
        'message' => $_POST['message']
    ];

    // Validate inputs
    $errors = [];
    if (empty($_POST['name'])) $errors[] = "Name is required";
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($_POST['subject'])) $errors[] = "Subject is required";
    if (empty($_POST['message'])) $errors[] = "Message is required";

    if (empty($errors)) {
        // Sanitize and insert into database
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $subject = $conn->real_escape_string($_POST['subject']);
        $message = $conn->real_escape_string($_POST['message']);

        $query = "INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
            
            if ($stmt->execute()) {
                $_SESSION['contact_message'] = "Thank you for your message! We'll get back to you soon.";
                $_SESSION['contact_message_type'] = "success";
                unset($_SESSION['contact_form']); // Clear saved form data
            } else {
                $_SESSION['contact_message'] = "Error submitting your message. Please try again later.";
                $_SESSION['contact_message_type'] = "danger";
            }
            $stmt->close();
        } else {
            $_SESSION['contact_message'] = "Database error. Please try again later.";
            $_SESSION['contact_message_type'] = "danger";
        }
    } else {
        $_SESSION['contact_message'] = implode("<br>", $errors);
        $_SESSION['contact_message_type'] = "danger";
    }
    
    // Redirect to prevent form resubmission
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Redirect logged-in users
if (isLoggedIn()) {
    redirectBasedOnRole();
}

$title = "Contact GreenSevak";
include_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section py-5 mb-0">
    <div class="container-fluid px-0">
        <div class="row g-0 align-items-center">
            <div class="col-lg-6 px-5">
                <div class="px-4 px-lg-5 py-5">
                    <h1 class="display-4">Contact <span class="text-success">GreenSevak</span></h1>
                    <p class="lead">We'd love to hear from you</p>
                    <p class="mb-4">Have questions, suggestions, or feedback? Reach out to our team and we'll get back to you promptly.</p>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <img src="assets/img/contact-banner.png" alt="About GreenSevak"
                     class="img-fluid img-thumbnail"
                     style="width: 500px; height: auto; object-fit: cover;">
            </div>
        </div>
    </div>
</div>

<!-- Contact Form Section -->
<div class="py-5" style="width: 100%;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="text-center mb-4">Send Us a Message</h2>
                        
                        <?php if (isset($_SESSION['contact_message'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['contact_message_type']; ?> alert-dismissible fade show">
                                <?php echo $_SESSION['contact_message']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['contact_message'], $_SESSION['contact_message_type']); ?>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="name" name="name" 
                                               placeholder="Your Name" required
                                               value="<?php echo isset($_SESSION['contact_form']['name']) ? htmlspecialchars($_SESSION['contact_form']['name']) : ''; ?>">
                                        <label for="name">Your Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" 
                                               placeholder="Your Email" required
                                               value="<?php echo isset($_SESSION['contact_form']['email']) ? htmlspecialchars($_SESSION['contact_form']['email']) : ''; ?>">
                                        <label for="email">Your Email</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="subject" name="subject" 
                                               placeholder="Subject" required
                                               value="<?php echo isset($_SESSION['contact_form']['subject']) ? htmlspecialchars($_SESSION['contact_form']['subject']) : ''; ?>">
                                        <label for="subject">Subject</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="message" name="message" 
                                                  placeholder="Your Message" style="height: 150px" required><?php echo isset($_SESSION['contact_form']['message']) ? htmlspecialchars($_SESSION['contact_form']['message']) : ''; ?></textarea>
                                        <label for="message">Your Message</label>
                                    </div>
                                </div>
                                <div class="col-12 text-center">
                                    <button type="submit" name="submit_contact" class="btn btn-success btn-lg px-4">
                                        <i class="bi bi-send me-2"></i>Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Info Section -->
<div class="py-5 bg-light" style="width: 100%;">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="display-5 fw-bold">Other Ways to Reach Us</h2>
                <p class="lead text-muted">We're here to help</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon text-success mb-3">
                            <i class="bi bi-geo-alt fs-1"></i>
                        </div>
                        <h4 class="mb-3">Our Office</h4>
                        <p class="text-muted">Puttur, Kranataka</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon text-success mb-3">
                            <i class="bi bi-telephone fs-1"></i>
                        </div>
                        <h4 class="mb-3">Call Us</h4>
                        <p class="text-muted">
                            <a href="tel:+919164044335" class="text-decoration-none text-dark">+91 1234567890</a><br>
                            Mon-Fri, 9AM-6PM
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon text-success mb-3">
                            <i class="bi bi-envelope fs-1"></i>
                        </div>
                        <h4 class="mb-3">Email Us</h4>
                        <p class="text-muted">
                            <a href="mailto:chaithalis471@gmail.com" class="text-decoration-none text-dark">chaithalis471@gmail.com</a><br>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="py-5" style="width: 100%;">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="display-5 fw-bold">Frequently Asked Questions</h2>
                <p class="lead text-muted">Quick answers to common questions</p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                How do I schedule a waste pickup?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Simply register for an account, log in, and use the "Schedule Pickup" feature in your dashboard. You can select your preferred date and time slot.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                What types of waste do you collect?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We collect all types of household waste including dry waste, wet waste, e-waste, and hazardous waste (with special handling). Please check our waste segregation guidelines for details.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 shadow-sm mb-3">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                How can I become a waste collector with GreenSevak?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We're always looking for responsible collectors! Visit our Careers page or contact our operations team with your details and experience.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>