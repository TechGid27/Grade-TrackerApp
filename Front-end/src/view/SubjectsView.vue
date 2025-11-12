<script setup>
  // import NavigationComponent from './components/NavigationComponent.vue';
  import NavigationChild from './components/NavigationChild.vue';

  import SubNavigation from './components/SubNavigation.vue';
  import FilterComponent from './components/FilterComponent.vue';
  import AddUpdateComponent from './components/AddUpdateComponent.vue';

  import { onMounted, ref, computed } from 'vue';
  import { useSubjects } from "../composables/subjects.js";


  const token = localStorage.getItem("token");
  const subjectModal = ref(null);
  const searchQuery = ref("");
  const sortOption = ref("name");

  const { subjects, loading, getSubjects, deleteSubject} = useSubjects(token);

  const handleDelete = async (id) => {
    if (!confirm("Are you sure you want to delete this subject?")) {
      return;
    }
    await deleteSubject(id);
  };

  const filteredSubjects = computed(() => {
  if (!subjects.value) return [];

  let result = subjects.value.filter(sub =>
    sub.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  );

    switch (sortOption.value) {
      case "name":
        return result.sort((a, b) => a.name.localeCompare(b.name));
      case "1":
        return result.sort((a, b) => b.current_grade - a.current_grade);
      case "2":
        return result.sort((a, b) => b.target_grade - a.target_grade);
      case "3":
        return result.sort((a, b) => b.assessments_count - a.assessments_count);
      case "4":
         return result.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
      default:
        return result;
    }
  });


  onMounted(getSubjects);

</script>

<template>
  <NavigationChild />
  <SubNavigation />
  <FilterComponent
    v-model:searchQuery="searchQuery"
    v-model:sortOption="sortOption"
  />

  <!-- Loading -->
  <div v-if="loading" class="text-center loading-customized">
    <div class="spinner-border text-primary" role="status">
      <span class="visually-hidden">Loading...</span>
    </div>
    <p>Loading subjects...</p>
  </div>

  <!-- Subjects -->
  <div v-else class="container-fluid my-3">
    <transition-group name="fade-slide" tag="div" class="row g-3 justify-content-center">
      <div
        v-for="subject in filteredSubjects"
        :key="subject.id"
        class="col-12 col-md-6 col-lg-4"
      >
        <div class="subject-card border rounded shadow-sm p-3 bg-white">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center gap-2">
              <div :class="subject.color" class="border rounded-circle shadow" style="height:28px; width:28px"></div>
              <div>
                <h5 class="mb-0 text-capitalize fw-semibold">{{ subject.name }}</h5>
                <p class="text-muted small mb-0">{{ subject.assessments_count }} assessments</p>
              </div>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-sm text-primary ri-edit-circle-line fs-5" @click="subjectModal.openForEdit('subject', subject)"></button>
              <button class="btn btn-sm text-danger ri-delete-bin-line fs-5" @click="handleDelete(subject.id)"></button>
            </div>
          </div>

          <!-- Grades -->
          <div class="row text-center my-2">
            <div class="col">
              <h6 class="fw-bold">{{ subject.current_grade }}%</h6>
              <p class="small text-muted mb-0">Current</p>
            </div>
            <div class="col">
              <h6 class="fw-bold">{{ subject.target_grade }}%</h6>
              <p class="small text-muted mb-0">Target</p>
            </div>
            <div class="col">
              <h6 class="fw-bold">{{ subject.assessments_count }}</h6>
              <p class="small text-muted mb-0">Assessments</p>
            </div>
          </div>

          <!-- Progress -->
          <div class="mt-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="fw-semibold">Progress</span>
              <span class="small text-muted">{{ subject.current_grade }}%</span>
            </div>
            <div class="progress" style="height: 8px;">
              <div
                class="progress-bar"
                role="progressbar"
                :class="{
                  'bg-success': subject.current_grade >= subject.target_grade,
                  'bg-warning': subject.current_grade >= subject.target_grade - 10 && subject.current_grade < subject.target_grade,
                  'bg-danger': subject.current_grade < subject.target_grade - 10
                }"
                :style="{ width: Math.min(subject.current_grade, 100) + '%' }"
              ></div>
            </div>
          </div>

          <!-- Footer -->
          <div class="d-flex justify-content-between mt-3">
            <span class="badge bg-secondary text-white">
              Target: {{ subject.target_grade }}%
            </span>
            <span class="badge bg-light text-dark">
              Created: {{ new Date(subject.created_at).toLocaleDateString() }}
            </span>
          </div>
        </div>
      </div>
    </transition-group>

    <!-- Empty state -->
    <div v-if="!filteredSubjects.length && !loading" class="text-center text-muted py-5">
      <i class="ri-inbox-2-line fs-1"></i>
      <p class="mt-2">No subjects found. Try adjusting your search or add a new one.</p>
    </div>
  </div>

  <AddUpdateComponent ref="subjectModal" @add="handleAdd" />

</template>

<style scoped>
/* Animations */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.4s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

/* Loading overlay */
.loading-customized {
  height: 100%;
  position: fixed;
  top: 0;
  background: #ffffffcc;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

/* Card design */
.subject-card {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.subject-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Floating Add Button */
.fab {
  position: fixed;
  bottom: 40px;
  right: 40px;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
  transition: all 0.3s ease;
}
.fab:hover {
  transform: scale(1.05);
}
</style>
