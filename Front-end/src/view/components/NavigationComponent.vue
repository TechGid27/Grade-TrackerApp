<script setup>

// import { useRoute} from "vue-router";
import { useSubjects } from "../../composables/subjects.js";
import { useAssessment } from "../../composables/assessment.js";
import AddUpdateComponent from "./AddUpdateComponent.vue";
import { ref } from "vue";

const token = localStorage.getItem("token");

const { addSubject } = useSubjects(token);
const { addAssessment  } = useAssessment(token);

const subjectModal = ref(null);

const handleAdd = async (form) => {
  await addSubject(form);
  await addAssessment(form);

};



</script>
<template>
  <div class="container py-2">
    <ul class="list-unstyled d-flex flex-column gap-2">
      <li class="nav-item">
        <router-link
          @click="$emit('link-click')"
          class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded hover-bg transition"
          to="subjects"
        >
          <i class="ri-settings-5-line fs-5"></i>
          <span class="fw-medium">Manage Subjects</span>
        </router-link>
      </li>

      <li class="nav-item">
        <router-link
          @click="$emit('link-click')"
          class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded hover-bg transition"
          to="analytics"
        >
          <i class="ri-line-chart-line fs-5"></i>
          <span class="fw-medium">View Analytics</span>
        </router-link>
      </li>

      <li class="nav-item">
        <router-link
          @click="$emit('link-click')"
          class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded hover-bg transition"
          to="grade"
        >
          <i class="ri-bar-chart-box-line fs-5"></i>
          <span class="fw-medium">View Grades</span>
        </router-link>
      </li>

      <li class="nav-item">
        <router-link
          @click="$emit('link-click')"
          class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded hover-bg transition"
          to="task"
        >
          <i class="ri-calendar-todo-line fs-5"></i>
          <span class="fw-medium">Manage Task</span>
        </router-link>
      </li>
    </ul>
  </div>

  <AddUpdateComponent ref="subjectModal" @add="handleAdd" />
</template>

<style scoped>
/* Modern hover effect */
.nav-link.hover-bg {
  background-color: rgba(100, 116, 139, 0.05);
  color: #1f2937;
  transition: all 0.3s ease;
}

.nav-link.hover-bg:hover {
  background-color: rgba(100, 116, 139, 0.15);
  transform: translateX(5px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

/* Smooth rounded links */
.nav-link {
  border-radius: 0.5rem;
  font-weight: 500;
}

/* Optional icon sizing */
.nav-link i {
  min-width: 24px;
  text-align: center;
}
</style>
