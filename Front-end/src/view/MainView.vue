<script setup>
// import SliderComponent from '@/view/components/OfferSliderComponent.vue';

import { onMounted } from "vue";

const about_description =
 [
  "No more spreadsheets.",
  "No more confusing websites.",
  "We'll handle the math."
 ]

onMounted(() => {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("in-view");
        } else {
          entry.target.classList.remove("in-view");
        }
      });
    },
    { threshold: 0.1 }
  );

  document.querySelectorAll(".animate-on-scroll").forEach((el) => {
    observer.observe(el);
  });
});
</script>
<template>
  <!-- Hero Section -->
  <section id="main-content" class="hero-section d-flex flex-column justify-content-center text-center">
    <div class="container h-90 d-flex flex-column justify-content-center align-items-center">
      <h1 class="fw-bold hero-title animate-title">
        <span>G</span><span>r</span><span>a</span><span>d</span><span>e</span>
        <span>&nbsp;</span><span>&nbsp;</span>
        <span>T</span><span>r</span><span>a</span><span>c</span><span>k</span><span>e</span><span>r</span>
      </h1>
      <p class="hero-subtitle mt-3">Track your grades, manage assignments, and monitor progress</p>
      <router-link to="/signin" class="btn hero-btn mt-4">Get Started</router-link>
    </div>
  </section>

  <!-- About Section -->
  <section id="about" class="py-5">
    <div class="container animate-on-scroll">
      <h2 class="text-center highlight-text mb-5">
        Grade Tracker Designed to work for <span class="text-decoration-underline">you</span>
      </h2>
      <ul class="text-center list-unstyled py-3">
        <li v-for="(item, index) in about_description" :key="index" class="mb-3">
          <h5 class="highlight-text">{{ item }}</h5>
        </li>
      </ul>
      <div class="text-center my-5">
        <img src="/image/image.png" alt="dashboard" class="img-fluid shadow rounded">
      </div>
    </div>

    <!-- Feature Sections -->
    <div class="container mt-5 animate-on-scroll">
      <div class="row g-5 align-items-center">
        <div class="col-md-6">
          <h4>📊 Visual Dashboard</h4>
          <p>Track your progress across all subjects with clear progress bars and averages so you always know where you stand.</p>
        </div>
        <div class="col-md-6 text-center">
          <img src="/image/visual-dashboard.png" alt="dashboard" class="img-fluid shadow rounded">
        </div>
      </div>
    </div>

    <div class="container mt-5 animate-on-scroll">
      <div class="row g-5 align-items-center flex-md-row-reverse">
        <div class="col-md-6">
          <h4>✅ Assessment History</h4>
          <p>View quizzes, exams, and assignments in one place, with detailed percentages and scores.</p>
        </div>
        <div class="col-md-6 text-center">
          <img src="/image/assessments.png" alt="assessments" class="img-fluid shadow rounded">
        </div>
      </div>
    </div>

    <div class="container mt-5 animate-on-scroll">
      <div class="row g-5 align-items-center">
        <div class="col-md-6">
          <h4>🎯 Goal Tracking</h4>
          <p>Compare your current grades with your target grade to stay motivated and on track.</p>
        </div>
        <div class="col-md-6 text-center">
          <img src="/image/visual-dashboard.png" alt="goal tracking" class="img-fluid shadow rounded">
        </div>
      </div>
    </div>
  </section>

</template>

<style scoped>
/* Hero Section */
.hero-section {
  min-height: 90vh;
  background: linear-gradient(135deg, #f5c949, #ffc107, #f2b705);
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  color: #2b2b2b;
}

.hero-title span {
  display: inline-block;
  opacity: 0;
  transform: translateY(20px);
  animation: fadeUp 0.5s forwards, bounce 1.5s infinite ease-in-out;
}

.hero-subtitle {
  font-size: 1.25rem;
  font-weight: 500;
}

.hero-btn {
  background-color: #2b2b2b;
  color: #ffc107;
  font-weight: 600;
  border-radius: 50px;
  padding: 0.75rem 2rem;
  transition: 0.3s;
}

.hero-btn:hover {
  background-color: #fff;
  color: #532444;
  transform: scale(1.1);
}

@keyframes fadeUp {
  to { opacity: 1; transform: translateY(0); }
}
@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}

/* Animate On Scroll */
.animate-on-scroll {
  opacity: 0;
  transform: translateY(50px);
  transition: all 0.8s ease-in-out;
}
.animate-on-scroll.in-view {
  opacity: 1;
  transform: translateY(0);
}

/* About Section */
#about {
  background: linear-gradient(to bottom, #fdf3c6, #fbe588);
  color: #2b2b2b;
}

.highlight-text {
  font-weight: 500;
  text-shadow: 0 1px 2px rgba(255,255,255,0.5);
}

/* Images */
img.shadow {
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
  border-radius: 1rem;
}
</style>
