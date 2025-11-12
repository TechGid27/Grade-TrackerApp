<template>
  <div class="container pb-5">
    <section class="mb-5">
      <h2 class="fw-bold mb-5 text-primary">About the App</h2>
      <p class="text-muted">
      <span class="fs-4 fw-lighter">✅ About the Grade Tracker App</span> <br> <br>
      The Grade Tracker App is a user-friendly academic monitoring system designed to help students easily record,
      calculate, and track their grades across different subjects.
      It provides a centralized platform where users can view their performance,
      monitor progress, and stay updated with their academic standing throughout the semester.
      This application aims to remove the manual hassle of computing grades by
      automatically generating results based on predefined criteria. With its intuitive interface and organized structure, students can focus more on improving their performance instead of dealing with confusing grade calculations.
      <br><br>
      <span class="fs-4 fw-lighter">✅ Key Features</span> <br> <br>
        <span class="fs-5 text-black">
          <ul v-for="key_features in features" :key="key_features.description">
            <li>{{ key_features.description }}</li>
          </ul>
        </span>
      </p>
    </section>

    <!-- Who Developed -->
    <section class="mb-5">
      <h2 class="fw-bold mb-3 text-primary">Who Developed?</h2>
      <p class="text-muted">
        Provide a brief explanation of who developed the system or what team/organization
        created the application.
      </p>
    </section>

    <!-- Team -->
    <section class="mb-5">
      <h2 class="fw-bold mb-3 text-primary">Team</h2>
      <div class="row g-3">
        <div class="col-md-4" v-for="member in team" :key="member.name">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h5 class="card-title fw-bold">{{ member.name }}</h5>
              <p class="card-text text-muted">{{ member.role }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Vision -->
    <section>
      <h2 class="fw-bold mb-3 text-primary">Vision</h2>
      <p class="text-muted">
        State your application's long-term goals and the impact you want it to achieve.
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
    description: "Subject & Assessment Management – Add, view, edit, and manage subjects easily."
  },
  {
    description: "Automated Grade Computation – Calculates grades instantly based on user inputs."
  },
  {
    description: "Progress Overview Dashboard – Shows GPA, performance trends, and upcoming assessments."
  },
  {
    description: "Secure User Accounts – Ensures that grade data is protected."
  },
  {
    description: "Responsive Design – Works smoothly across devices using Vue and Bootstrap."
  },
];

</script>

<style scoped>
h2 {
border-left: 4px solid #0d6efd;
padding-left: 10px;
}
.card { transition: all 0.35s ease; }


.card:hover {
transform: translateY(-8px);
transition: 0.3s ease;
box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
</style>
