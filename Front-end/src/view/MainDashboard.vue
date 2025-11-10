<script setup>
import { computed, onMounted } from "vue";
import { useSubjects } from "../composables/subjects.js";
import { useTodos } from "@/composables/task.js";
import NavigationChild from "./components/NavigationChild.vue";
import SubNavigation from "./components/SubNavigation.vue";

const token = localStorage.getItem("token");

// composables
const { subjects, getSubjects, loading: subjectsLoading } = useSubjects(token);
const { todos, getTodos, loading: todosLoading } = useTodos(token);

// lifecycle
onMounted(async () => {
  await Promise.all([getSubjects(), getTodos()]);
});

// ---------- COMPUTED ----------

// Combine loading states
const isLoading = computed(() => subjectsLoading.value || todosLoading.value);

// Show top 3 upcoming tasks that are not completed and sorted by nearest due date
const upcomingTasks = computed(() => {
  return todos.value
    .filter(t => !t.completed && t.due_date)
    .sort((a, b) => new Date(a.due_date) - new Date(b.due_date))
    .slice(0, 3)
    .map(t => ({
      ...t,
      formattedDate: new Date(t.due_date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      })
    }));
});

// Progress width per subject
const progressWidth = grade => `${Math.min(grade, 100)}%`;
</script>

<template>
  <NavigationChild />
  <SubNavigation />

  <!-- Loading Overlay -->
  <div v-if="isLoading" class="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
    <p class="mt-2 text-muted">Loading dashboard...</p>
  </div>

  <div v-else class="container py-4 px-3 mt-3">
    <div class="row g-4">
      <!-- 🧮 Subject Performance -->
      <div class="col-lg-8 col-md-7">
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body px-4 py-4">
            <h4 class="fw-semibold">Subject Performance</h4>
            <p class="text-muted mb-4">
              Your current grades and progress toward targets
            </p>

            <div
              v-for="subject in subjects"
              :key="subject.id"
              class="d-flex align-items-center justify-content-between py-3 border-bottom"
            >
              <div class="d-flex align-items-center gap-3">
                <div
                  :class="subject.color"
                  class="rounded-circle border shadow-sm"
                  style="height: 30px; width: 30px"
                ></div>
                <div>
                  <h6 class="mb-0 fw-semibold">{{ subject.name }}</h6>
                  <small class="text-muted"
                    >{{ subject.assessments_count }} assessments</small
                  >
                </div>
              </div>

              <div class="flex-grow-1 mx-4">
                <div class="progress" style="height: 10px">
                  <div
                    class="progress-bar bg-primary"
                    role="progressbar"
                    :style="{ width: progressWidth(subject.current_grade) }"
                  ></div>
                </div>
              </div>

              <div class="text-end">
                <h6 class="mb-0">{{ subject.current_grade }}%</h6>
                <small class="text-muted">Target: {{ subject.target_grade }}%</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 📝 Upcoming Tasks -->
      <div class="col-lg-4 col-md-5">
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body px-4 py-4">
            <h4 class="fw-semibold">Upcoming Tasks</h4>
            <p class="text-muted mb-4">
              Your pending assignments and deadlines
            </p>

            <div v-if="upcomingTasks.length">
              <div
                v-for="task in upcomingTasks"
                :key="task.id"
                class="p-3 mb-3 border rounded-3 shadow-sm bg-light"
              >
                <h6 class="fw-semibold mb-1">{{ task.title }}</h6>
                <p class="text-muted small mb-1">
                  {{ task.subject?.name || "No Subject" }}
                </p>
                <div class="d-flex justify-content-between align-items-center">
                  <span
                    class="badge text-white px-2 py-1"
                    :class="{
                      'bg-danger': task.priority === 'High',
                      'bg-warning text-dark': task.priority === 'Medium',
                      'bg-success': task.priority === 'Low'
                    }"
                    >{{ task.priority }}</span
                  >
                  <small class="text-muted">{{ task.formattedDate }}</small>
                </div>
              </div>
            </div>

            <div v-else class="text-center text-muted py-5">
              <i class="ri-checkbox-circle-line fs-1"></i>
              <p class="mt-2">No upcoming tasks 🎉</p>
            </div>

            <router-link
              to="/task"
              class="btn btn-outline-primary w-100 mt-3 rounded-pill"
            >
              + Manage All Tasks
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.loading-overlay {
  position: fixed;
  inset: 0;
  background: #ffffffb8;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.card {
  transition: all 0.3s ease;
}

.card:hover {
  transform: translateY(-3px);
}

.progress-bar {
  transition: width 0.4s ease;
}
</style>
