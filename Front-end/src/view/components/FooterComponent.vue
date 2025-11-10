<template>
  <footer class="footer-modern">
    <!-- Social Section -->
    <section class="social-section d-flex flex-column flex-md-row justify-content-between align-items-center p-4">
      <div class="mb-3 mb-md-0 highlight-text fw-bold">Connect with us on social networks:</div>
      <div class="social-icons">
        <a href="#" class="icon"><i class="fab fa-facebook-f"></i></a>
        <a href="#" class="icon"><i class="fab fa-twitter"></i></a>
        <a href="#" class="icon"><i class="fab fa-google"></i></a>
        <a href="#" class="icon"><i class="fab fa-instagram"></i></a>
        <a href="#" class="icon"><i class="fab fa-linkedin"></i></a>
        <a href="#" class="icon"><i class="fab fa-github"></i></a>
      </div>
    </section>

    <!-- Info Cards -->
    <section class="links-section py-5">
      <div class="container">
        <div class="row g-4">
          <!-- About Card -->
          <div class="col-md-4">
            <div class="info-card p-4 rounded-4 shadow">
              <h6 class="fw-bold highlight-text">Grade Tracker App</h6>
              <hr />
              <p class="highlight-text">
                Intuitive and user-friendly app helping students track grades and assignments efficiently.
              </p>
            </div>
          </div>

          <!-- Quick Links Card -->
          <div class="col-md-4">
            <div class="info-card p-4 rounded-4 shadow">
              <h6 class="fw-bold highlight-text">About</h6>
              <hr />
              <p><a href="#!" class="footer-link">About the App</a></p>
              <p><a href="#!" class="footer-link">Who Developed?</a></p>
              <p><a href="#!" class="footer-link">Team</a></p>
              <p><a href="#!" class="footer-link">Vision</a></p>
            </div>
          </div>

          <!-- Contact Card -->
          <div class="col-md-4">
            <div class="info-card p-4 rounded-4 shadow">
              <h6 class="fw-bold highlight-text">Contact</h6>
              <hr />
              <p class="highlight-text"><i class="fas fa-home me-2"></i>Basak, Lapu-Lapu City</p>
              <p class="highlight-text"><i class="fas fa-envelope me-2"></i>gideonayao@gmail.com</p>
              <p class="highlight-text"><i class="fas fa-phone me-2"></i>+63 9922 784 144</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Copyright -->
    <div class="copyright py-3 text-center">
      <p class="mb-0 highlight-text">
        © 2025 Created by
        <a href="https://gideon-26e30.web.app/" target="_blank" class="footer-link">Gideon Ayao</a>
      </p>
    </div>
  </footer>
</template>

<style scoped>
.footer-modern {
  background: linear-gradient(135deg, #f5c949, #f2b705, #ffc107);
  color: #2b2b2b;
  font-family: "Segoe UI", sans-serif;
}

/* Highlighted Text */
.highlight-text {
  color: #2b2b2b;
  font-weight: 500;
  text-shadow: 0 1px 2px rgba(255, 255, 255, 0.5);
}

/* Social Section */
.social-section {
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.social-icons a {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.2);
  color: #2b2b2b;
  width: 40px;
  height: 40px;
  margin: 0 0.25rem;
  border-radius: 50%;
  font-size: 1.2rem;
  transition: all 0.3s;
}

.social-icons a:hover {
  background: #fff;
  color: #532444;
  transform: scale(1.2);
}

/* Info Cards */
.info-card {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  transition: transform 0.3s, box-shadow 0.3s;
}

.info-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.info-card hr {
  border: 0;
  height: 2px;
  background: #532444;
  margin: 0.5rem 0 1rem 0;
}

/* Footer Links */
.footer-link {
  color: #2b2b2b;
  text-decoration: none;
  transition: color 0.3s, transform 0.3s;
}

.footer-link:hover {
  color: #532444;
  transform: translateX(3px);
}

/* Copyright */
.copyright {
  background-color: rgba(0, 0, 0, 0.1);
  color: #2b2b2b;
}

/* Responsive */
@media (max-width: 768px) {
  .social-section {
    flex-direction: column;
    gap: 1rem;
  }
}
</style>
