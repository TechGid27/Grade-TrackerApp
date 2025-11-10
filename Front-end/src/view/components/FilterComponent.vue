<script setup>

import { useRoute } from 'vue-router';

const route = useRoute();

defineProps({
  searchQuery: String,
  sortOption: String,
  searchQueryGrade: String,
  sortOptionGrade: String,
});

defineEmits(["update:searchQuery", "update:sortOption",
"update:searchQueryGrade", "update:sortOptionGrade"]);

</script>

<template>
  <div v-if="route.name === 'Subjects'" class="container mt-4">
    <div class="d-flex flex-wrap gap-3 mb-3 align-items-center">
      <!-- Search input -->
      <div class="flex-grow-1 flex-md-grow-0 col-12 col-md-4 col-lg-3">
        <div class="position-relative">
          <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
          <input
            type="text"
            class="form-control ps-5"
            placeholder="Search Subjects"
            :value="searchQuery"
            @input="$emit('update:searchQuery', $event.target.value)"
          />
        </div>
      </div>

      <!-- Sort dropdown -->
      <div class="col-12 col-md-4 col-lg-3">
        <select
          class="form-select"
          :value="sortOption"
          @change="$emit('update:sortOption', $event.target.value)"
        >
          <option value="name">Name (A-Z)</option>
          <option value="1">Current Grade (High - Low)</option>
          <option value="2">Target Grade (High - Low)</option>
          <option value="3">Most Assessments</option>
          <option value="4">Recently Added</option>
        </select>
      </div>
    </div>
  </div>

  <div v-else-if="route.name === 'Grades'" class="container mt-4">
    <div class="d-flex flex-wrap gap-3 mb-3 align-items-center">
      <!-- Search input -->
      <div class="flex-grow-1 flex-md-grow-0 col-12 col-md-4 col-lg-3">
        <div class="position-relative">
          <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
          <input
            type="text"
            class="form-control ps-5"
            placeholder="Search Subjects"
            :value="searchQueryGrade"
            @input="$emit('update:searchQueryGrade', $event.target.value)"
          />
        </div>
      </div>

      <!-- Filter dropdown -->
      <div class="col-12 col-md-4 col-lg-3">
        <select
          class="form-select"
          :value="sortOptionGrade"
          @change="$emit('update:sortOptionGrade', $event.target.value)"
        >
          <option value="name">All types</option>
          <option value="1">Quizzes</option>
          <option value="2">Test</option>
          <option value="3">Exam</option>
          <option value="4">Assignments</option>
          <option value="5">Projects</option>
        </select>
      </div>
    </div>
  </div>
</template>


<style scoped>
.input-wrapper {
  position: relative;
}

.input-wrapper i {
  position: absolute;
  top: 50%;
  left: 12px;
  transform: translateY(-50%);
  font-size: 18px;
  color: #555;
}

.input-wrapper input {
  padding-left: 40px !important;
}


</style>
