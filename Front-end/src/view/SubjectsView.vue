<script setup>
    import NavigationChild from './components/NavigationChild.vue';
    import SubNavigation from './components/SubNavigation.vue';
    import FilterComponent from './components/FilterComponent.vue';
    import AddUpdateComponent from './components/AddUpdateComponent.vue';

    import { onMounted, ref, computed } from 'vue';
    import { useSubjects } from "../composables/subjects.js";

    const token = localStorage.getItem("token");

    // --- REQUIRED STATE ---
    const subjectModal = ref(null);
    const searchQuery = ref("");
    const sortOption = ref("name"); // Default sort option

    // --- COMPOSABLE DESTRUCTURING ---
    const {
      subjects,
      loading,
      getSubjects,
      deleteSubject,
    } = useSubjects(token);

    // --- ASYNCHRONOUS DATA FETCHING (OPTIMIZED) ---
    const fetchData = async () => {
      // Fetches subjects and overall performance data in parallel
      await Promise.all([
        getSubjects(),
      ]);
    };

  const getSubjectInitial = (name) => {
    if (!name || typeof name !== 'string' || name.length === 0) {
      return '?'; // Handle null, undefined, or empty strings
    }
    // Get the first character and convert it to uppercase
    return name.charAt(0).toUpperCase();
  };

    // --- HANDLERS ---
    const handleAdd = async () => {
      // Refresh data after adding a subject
      await fetchData();
    };

    const handleDelete = async (id) => {
      if (!confirm("Are you sure you want to delete this subject?")) {
        return;
      }
      await deleteSubject(id);
      await fetchData(); // Refresh list
    };

    // --- COMPUTED PROPERTIES ---

  const filteredSubjects = computed(() => {
    // 1. Initial Data Check and Standardization
    if (!Array.isArray(subjects.value)) return [];

    // Create a working array, standardizing the name for easier use
    let result = subjects.value.map(sub => ({
      ...sub,
      name: sub.subject_name || sub.name, // Ensure 'name' property exists
    }));

    // 2. Filtering by Search Query
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase();
      result = result.filter(sub =>
        sub.name.toLowerCase().includes(query)
      );
    }

    // 3. Sorting Logic
    switch (sortOption.value) {
      case "name":
        // Sort alphabetically by name (default)
        return result.sort((a, b) => a.name.localeCompare(b.name));
      case "3":
        // Sort by creation date (newest first)
        return result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
      default:
        // If sortOption is invalid or not set, return current result
        return result;
    }
  });

  onMounted(fetchData);
</script>


<template>
  <div class="subjects-page">
    <NavigationChild />
    <SubNavigation />
    <FilterComponent
      v-model:searchQuery="searchQuery"
      v-model:sortOption="sortOption"
      class="mb-4"
    />

    <div v-if="loading" class="loading-state">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading subjects...</p>
    </div>

    <div v-else class="subject-list-container my-4 px-5">
      <transition-group name="card-transition" tag="div" class="row g-4 justify-content-center">
        <div
          v-for="subject in filteredSubjects"
          :key="subject.id"
          class="col-12 col-sm-6 col-md-6 col-lg-4 col-xl-3"
        >
          <article class="subject-card card h-100 shadow-sm border-0">
            <div class="card-body p-4">
              <header class="d-flex align-items-center mb-3">
                <div
                  :class="subject.color"
                  class="subject-color-indicator rounded-circle shadow-sm me-3 text-center align-items-center py-1"
                  role="presentation"
                >
                <span :class="subject.color === '#FF5733' ? 'text-black' : 'text-white'" class="avatar-initials fw-bold">{{ getSubjectInitial(subject.subject_name) }}</span>
              </div>

                <h3 class="card-title h5 mb-0 text-capitalize fw-bold flex-grow-1">
                  {{ subject.subject_name }}
                </h3>

                <div class="subject-actions ms-auto">
                  <button
                    class="btn btn-sm btn-icon text-primary me-1"
                    :aria-label="`Edit ${subject.subject_name}`"
                    @click="subjectModal.openForEdit('subject', subject)"
                  >
                    <i class="ri-edit-circle-line fs-5"></i>
                  </button>
                  <button
                    class="btn btn-sm btn-icon text-danger"
                    :aria-label="`Delete ${subject.subject_name}`"
                    @click="handleDelete(subject.id)"
                  >
                    <i class="ri-delete-bin-line fs-5"></i>
                  </button>
                </div>
              </header>

              <footer class="mt-4 pt-3 border-top">
                <span class="badge bg-light text-muted fw-normal">
                  <i class="ri-calendar-line me-1"></i>
                  Created: {{ new Date(subject.created_at).toLocaleDateString() }}
                </span>
              </footer>
            </div>
          </article>
        </div>
      </transition-group>

      <div v-if="!filteredSubjects.length && !loading" class="empty-state text-center text-muted py-5">
        <i class="ri-inbox-2-line fs-1 text-secondary"></i>
        <p class="mt-3 fs-5">No subjects found.</p>
        <p>Try adjusting your search or add a new one.</p>
      </div>
    </div>

    <AddUpdateComponent ref="subjectModal" @add="handleAdd" />
  </div>
</template>

<style scoped>
/* --- Component-Specific Styles --- */

/* 1. Transitions */
.card-transition-enter-active,
.card-transition-leave-active {
  transition: all 0.4s cubic-bezier(0.5, 0, 0.5, 1); /* Smoother, bouncier curve */
}
.card-transition-enter-from,
.card-transition-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(20px);
}
.card-transition-leave-active {
  position: absolute; /* Allows remaining elements to transition smoothly */
}

/* 2. Layout & States */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 150px; /* Ensure loading state is prominent */
  padding: 2rem;
}

.empty-state i {
  /* Larger, more noticeable icon for empty state */
  font-size: 4rem !important;
  opacity: 0.7;
}

/* 3. Subject Card */
.subject-card {
  border: 1px solid var(--bs-border-color-translucent, #f0f0f0);
  transition: all 0.3s ease-out;
  cursor: pointer;
}

.subject-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.08) !important; /* Stronger, modern shadow */
  border-color: var(--bs-primary-border-subtle, #e0e0e0);
}

.subject-color-indicator {
  height: 32px; /* Slightly larger */
  width: 32px; /* Slightly larger */
  min-width: 32px; /* Prevent shrinking */
}

.subject-actions .btn-icon {
  /* Optimized button styles */
  padding: 0.25rem 0.4rem;
  line-height: 1;
}

.subject-actions .ri-delete-bin-line {
  /* Use lighter red for delete icon */
  color: var(--bs-red-600, #dc3545);
}

.subject-actions .ri-edit-circle-line {
  /* Use lighter blue for edit icon */
  color: var(--bs-blue-600, #0d6efd);
}

/* 4. Utility Overrides (If necessary, but kept to a minimum) */
.h-100 {
  /* Ensure the card takes full height in the grid row */
  height: 100% !important;
}

</style>