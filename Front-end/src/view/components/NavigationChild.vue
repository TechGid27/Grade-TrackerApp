<script setup>


import { useRoute} from "vue-router";
import { useSubjects } from "../../composables/subjects.js";
import { useAssessment } from "../../composables/assessment.js";
import AddUpdateComponent from "./AddUpdateComponent.vue";
import { ref } from "vue";

const route = useRoute();
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
  <div class="container d-flex justify-content-end">
    <ul class="list-unstyled d-flex gap-2">
      <li v-if="route.name === 'Grades'" class="nav-item">
        <button class="btn btn-gradient w-100 mt-3 px-5" @click="subjectModal.openForAdd('assessment')">
          + Assessments
        </button>
      </li>
      <li v-else-if="route.name === 'ManageTask'" class="nav-item">
        <button class="btn btn-gradient w-100 mt-3 px-5" @click="subjectModal.openForAdd('todos')">
          + Add Task
        </button>
      </li>
      <li v-else-if="route.name === 'Subjects'" class="nav-item">
        <button class="btn btn-gradient w-100 mt-3 px-5" @click="subjectModal.openForAdd('subject')">
          + Add Subject
        </button>
      </li>
      <li v-else class="nav-item">
        <router-link @click="$emit('link-click')" class="btn btn-gradient w-100 mt-3 px-5" to="subjects">
          + Add Subject
        </router-link>
      </li>
    </ul>
  </div>

  <AddUpdateComponent ref="subjectModal" @add="handleAdd" />
</template>

<style scoped>

/* Button */
.btn-gradient {
  background: linear-gradient(90deg, #f7d060, #f0b400);
  border: none;
  color: #fff;
  font-weight: 700;
  padding: 12px;
  border-radius: 12px;
  transition: all 0.3s;
}
.btn-gradient:hover {
  background: linear-gradient(90deg, #f0b400, #f7d060);
  transform: translateY(-2px);
}

</style>


