<?php include 'includes/header.php'; ?>
<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="hero-title">Welcome to Almas Clothing Brand</h1>
                <p class="hero-subtitle">Discover premium fashion with our exclusive collection of quality clothing for everyone.</p>
                <div class="hero-buttons">
                    <a href="shop.php" class="btn btn-primary btn-lg me-3">Shop Now</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Categories -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold">Shop by Category</h2>
            <p class="lead text-muted">Explore our diverse collection</p>
        </div>
        
        <div class="row">
            <?php
            $sql = "SELECT * FROM categories ORDER BY name";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while($category = $result->fetch_assoc()) {
                    echo '<div class="col-lg-3 col-md-6 mb-4">';
                    echo '<a href="shop.php?category=' . $category['id'] . '" class="category-card-link">';
                    echo '<div class="category-card">';
                    echo '<div class="category-icon">';
                    echo '<i class="fas fa-' . ($category['name'] == 'Men\'s Wear' ? 'male' : 
                                         ($category['name'] == 'Women\'s Wear' ? 'female' : 
                                         ($category['name'] == 'Kids Wear' ? 'child' : 'shopping-bag'))) . '"></i>';
                    echo '</div>';
                    echo '<h5 class="category-name">' . htmlspecialchars($category['name']) . '</h5>';
                    echo '</div>';
                    echo '</a>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold text-dark">What Our Customers Say</h2>
            <p class="lead text-muted">Real reviews from satisfied customers</p>
            <div class="mx-auto" style="width: 60px; height: 3px; background: var(--accent-color);"></div>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="testimonial-card card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="testimonial-stars mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <blockquote class="testimonial-text mb-4 fst-italic text-muted">
                            "Amazing quality clothing! The fabric is so comfortable and the designs are elegant. I've been a loyal customer for over a year now."
                        </blockquote>
                        <div class="testimonial-author d-flex align-items-center">
                            <div class="author-avatar me-3">
                                <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: var(--accent-color);">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div class="author-info">
                                <h6 class="author-name mb-0 fw-semibold">Sarah Johnson</h6>
                                <span class="author-role text-muted small">Fashion Designer</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="testimonial-card card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="testimonial-stars mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <blockquote class="testimonial-text mb-4 fst-italic text-muted">
                            "Excellent customer service and fast delivery. The clothes fit perfectly and the attention to detail is outstanding. Highly recommend!"
                        </blockquote>
                        <div class="testimonial-author d-flex align-items-center">
                            <div class="author-avatar me-3">
                                <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: var(--accent-color);">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div class="author-info">
                                <h6 class="author-name mb-0 fw-semibold">Michael Chen</h6>
                                <span class="author-role text-muted small">Business Executive</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="testimonial-card card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="testimonial-stars mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star-half-alt text-warning"></i>
                        </div>
                        <blockquote class="testimonial-text mb-4 fst-italic text-muted">
                            "Great value for money! The collection is trendy yet timeless. I love how they blend traditional styles with modern fashion."
                        </blockquote>
                        <div class="testimonial-author d-flex align-items-center">
                            <div class="author-avatar me-3">
                                <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: var(--accent-color);">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div class="author-info">
                                <h6 class="author-name mb-0 fw-semibold">Emily Rodriguez</h6>
                                <span class="author-role text-muted small">Marketing Manager</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="why-choose-section py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold text-dark">Why Choose Almas Clothing</h2>
            <p class="lead text-muted">Experience the difference with our premium services</p>
            <div class="mx-auto" style="width: 60px; height: 3px; background: var(--accent-color);"></div>
        </div>
        
        <div class="row g-4 justify-content-center">
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card h-100 border-0 shadow-sm text-center p-4">
                    <div class="feature-icon mb-4">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--accent-color) 0%, #b8941f 100%);">
                            <i class="fas fa-gem fa-2x"></i> 
                        </div>
                    </div>
                    <div class="card-body">
                        <h4 class="feature-title card-title fw-bold mb-3">Premium Quality</h4>
                        <p class="feature-description card-text text-muted">We use only the finest fabrics and materials to ensure exceptional quality and durability in every piece.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card h-100 border-0 shadow-sm text-center p-4">
                    <div class="feature-icon mb-4">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary-color) 0%, #495057 100%);">
                            <i class="fas fa-truck fa-2x"></i> 
                        </div>
                    </div>
                    <div class="card-body">
                        <h4 class="feature-title card-title fw-bold mb-3">Fast Delivery</h4>
                        <p class="feature-description card-text text-muted">Quick and reliable shipping worldwide. Get your orders delivered within 3-5 business days.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card h-100 border-0 shadow-sm text-center p-4">
                    <div class="feature-icon mb-4">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px; background: linear-gradient(135deg, #8b7355 0%, #6d5a44 100%);">
                            <i class="fas fa-shield-alt fa-2x"></i> 
                        </div>
                    </div>
                    <div class="card-body">
                        <h4 class="feature-title card-title fw-bold mb-3">Secure Shopping</h4>
                        <p class="feature-description card-text text-muted">Your security is our priority. Shop with confidence using our encrypted payment system.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
// Add click animations to testimonial cards
document.addEventListener('DOMContentLoaded', function() {
    const testimonialCards = document.querySelectorAll('.testimonial-card');
    const featureCards = document.querySelectorAll('.feature-card');
    
    // Add click animation to testimonial cards
    testimonialCards.forEach(card => {
        card.addEventListener('click', function() {
            this.classList.add('clicked');
            setTimeout(() => {
                this.classList.remove('clicked');
            }, 600);
        });
    });
    
    // Add click animation to feature cards
    featureCards.forEach(card => {
        card.addEventListener('click', function() {
            this.classList.add('clicked');
            setTimeout(() => {
                this.classList.remove('clicked');
            }, 600);
        });
    });
});
</script>
<?php include('chatbot_embed.php'); ?>
