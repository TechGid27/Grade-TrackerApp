<template>
  <div class="container pb-5">

    <section class="mb-5">
      <h2 class="fw-bold mb-5 text-primary">About the Application 🎓</h2>
      <p class="text-muted">
        <span class="fs-4 fw-medium text-dark">Grade Tracker: Your Academic Compass 🧭</span> <br> <br>
        The Grade Tracker App is a centralized academic monitoring system developed as a Capstone Project for students of ACLC College of Mandaue. Its core mission is to replace complex manual computations with an accurate, automated platform for recording and tracking semester grades.
        <br><br>
        It strictly adheres to the official ACLC grading criteria (60% F2F / 40% Online) and uses the standard grade transmutation scale (1.00 - 5.00). By providing instant, error-free results, the application empowers students to focus their efforts on improving performance rather than wrestling with calculation uncertainties.
        <br><br>
        <span class="fs-4 fw-medium text-dark">Key Features ✨</span> <br> <br>
        <div class="row g-3">
          <div class="col-12 col-md-6" v-for="key_features in features" :key="key_features.description">
            <div class="d-flex align-items-start">
              <i class="ri-check-line text-success fs-5 me-2"></i>
              <p class="fs-6 text-black mb-0">{{ key_features.description }}</p>
            </div>
          </div>
        </div>
      </p>
    </section>
    <section class="mb-5">
      <h2 class="fw-bold mb-3 text-primary">ACLC Grading System Breakdown ⚖️</h2>
      <p class="text-muted">
        The system uses a weighted formula to calculate the Overall Quarter Percentage for each subject before applying the 1.00 - 5.00 transmutation:
      </p>
      <div class="p-4 rounded-4 border border-primary bg-light shadow-lg">
        <h5 class="fw-bold text-primary mb-4">Quarter Score Calculation (60% F2F + 40% Online)</h5>

        <div class="row g-4 justify-content-center align-items-stretch">

          <div class="col-12 col-md-5">
            <div class="p-4 bg-white rounded-3 h-100 shadow-sm border border-primary-subtle">
              <p class="small text-muted mb-1 fw-medium text-uppercase">Face-to-Face Average</p>
              <p class="fw-bold text-dark fs-3 mb-0">
                  $$\overline{\text{F2F}} \times 60\%$$
              </p>
            </div>
          </div>

          <div class="col-12 col-md-2 d-flex justify-content-center align-items-center">
            <i class="ri-add-line fs-2 fw-bold text-primary"></i>
          </div>

          <div class="col-12 col-md-5">
            <div class="p-4 bg-white rounded-3 h-100 shadow-sm border border-success-subtle">
              <p class="small text-muted mb-1 fw-medium text-uppercase">Online Average</p>
              <p class="fw-bold text-dark fs-3 mb-0">
                  $$\overline{\text{Online}} \times 40\%$$
              </p>
            </div>
          </div>
        </div>

        <div class="text-center my-4">
            <i class="ri-arrow-down-line fs-4 text-primary"></i>
        </div>

        <div class="p-3 bg-primary text-white rounded-3 shadow-lg text-center">
            <p class="text-uppercase fw-light mb-1 small opacity-75">Resulting Overall Quarter Raw Percentage</p>
            <p class="fw-bold fs-4 mb-0">
                $$\mathbf{\text{Overall Score}} = \mathbf{60\%} \mathbf{\text{F2F}} + \mathbf{40\%} \mathbf{\text{Online}}$$
            </p>
        </div>
      </div>
      <p class="mt-3 small text-secondary">
        Note: The average scores ($\overline{\text{F2F}}$ and $\overline{\text{Online}}$) are derived from the overall performance across all four activity types (Quiz, Exam, Assignment, Project) within the quarter.
      </p>
    </section>


    <section class="mb-5">
      <h2 class="fw-bold mb-3 text-primary">Who Developed? 🧑‍💻</h2>
      <p class="text-muted">
        This Grade Tracker App was conceptualized, designed, and implemented by the Capstone Research Group from ACLC College of Mandaue, BS Information Technology. The development phase spanned from [Start Date] to [End Date], focusing on creating a robust and accurate academic utility for the student body as part of our thesis requirement.
      </p>
    </section>

    <section class="mb-5">
      <h2 class="fw-bold mb-3 text-primary">Development Team 👨‍🎓</h2>
      <div class="row g-3">
        <div class="col-md-4" v-for="member in team" :key="member.name">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
              <h5 class="card-title fw-bold text-dark">{{ member.name }}</h5>
              <p class="card-text text-primary fw-medium small">{{ member.role }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section>
      <h2 class="fw-bold mb-3 text-primary">Vision & Goal 🚀</h2>
      <p class="text-muted">
        To be the leading automated academic monitoring tool at ACLC College of Mandaue, ensuring every student has immediate, accurate, and transparent access to their performance metrics, thereby fostering a proactive and self-directed approach to academic success throughout their academic journey.
      </p>
    </section>
  </div>
</template>

<script setup>
const team = [
  { name: "Gideon Ayao", role: "Product Owner / Backend Developer" },
  { name: "Adriean Gepiga", role: "Documentation" },
  { name: "Darious Cyril Liwagon", role: "Front-end Developer" },
  { name: "Gerry Silorio", role: "Front-end Developer" },
  { name: "Kaiken Gemida", role: "Designer" },
  { name: "Mark Anthony Bayubay", role: "Animations" },
  { name: "Rhea Banzon", role: "Documentation" },
  { name: "Vince Correos", role: "Design & Animation" }
];

const features = [
  {
    description: "Accurate Grade Computation based on ACLC's 60% F2F and 40% Online weightage criteria."
  },
  {
    description: "Automated Transmutation to the official 1.00 - 5.00 grade scale."
  },
  {
    description: "Centralized Subject & Assessment Management (Add, view, edit, and manage records)."
  },
  {
    description: "Comprehensive Performance Dashboard showing GWA, quarter averages, and trend analyses."
  },
  {
    description: "Responsive Design and Secure User Authentication for data protection."
  },
];
</script>

<style scoped>
/* Minor adjustments to h2 for a cleaner line */
h2 {
border-left: 5px solid #0d6efd;
padding-left: 12px;
}
.card {
    transition: all 0.35s ease;
    border: none; /* Make cards borderless for a cleaner look */
}

/* Enhanced hover effect */
.card:hover {
transform: translateY(-8px);
transition: 0.3s ease;
box-shadow: 0 10px 25px rgba(0,0,0,0.1); /* Slightly heavier shadow */
}
</style>
