<script setup>
// import NavigationComponent from './components/NavigationComponent.vue';
import NavigationChild from './components/NavigationChild.vue';
import SubNavigation from './components/SubNavigation.vue';
import FilterComponent from './components/FilterComponent.vue';
import AddUpdateComponent from './components/AddUpdateComponent.vue';
import { ref, reactive, onMounted, computed } from 'vue';
import { Modal } from 'bootstrap';
import { useAssessment } from "../composables/assessment.js";
import { useSubjects } from "../composables/subjects.js";

const token = localStorage.getItem("token");
const { loading, deleteAssessment } = useAssessment(token);
const { subjects, getSubjects } = useSubjects(token);

const selectedSubject = ref(null);

const subjectModalRef = ref(null);
let bsSubjectModal = null;

const assessmentModalRef = ref(null);

const searchQueryGrade = ref("");
const sortOptionGrade = ref("name");

const isEditing = ref(false);

const form = reactive({
  id: null,
  subject_id: null,
  name: '',
  type_assessment: '',
  grade: 0,
  max_grade: 0,
  weight: 0,
  date_taken: ''
});

const openSubjectModal = (subject) => {
  selectedSubject.value = subject;
  bsSubjectModal?.show();
};

const openAssessmentEdit = (assessment) => {
  if (!assessment) return;

  isEditing.value = true;
  Object.assign(form, {
    id: assessment.id,
    subject_id: assessment.subject_id,
    name: assessment.name,
    type_assessment: assessment.type_assessment ?? assessment.type,
    grade: assessment.grade,
    max_grade: assessment.max_grade,
    weight: assessment.weight,
    date_taken: assessment.date_taken,
  });

  bsSubjectModal?.hide();

  setTimeout(() => {
    assessmentModalRef.value?.openForEdit("assessment", form);
  }, 300);
};

const closeSubjectModal = async () => {
  bsSubjectModal?.hide();
  await getSubjects();
};

const averageGrade = (assessments) => {
  if (!assessments?.length) return 0;

  let total = 0, totalWeight = 0;
  assessments.forEach(a => {
    const percent = (a.grade / a.max_grade) * 100;
    const w = parseFloat(a.weight) || 1;
    total += percent * w;
    totalWeight += w;
     console.log("weights ",w);
  });

  return (total / totalWeight).toFixed(1);
};


const handleDelete = async (id) => {
  if (!confirm("Are you sure you want to delete this assessment?")) return;

  try {
    const success = await deleteAssessment(id);
    if (!success) throw new Error("Delete failed");

    if (!selectedSubject.value) return;

    selectedSubject.value.assessments = selectedSubject.value.assessments.filter(a => a.id !== id);

    const count = selectedSubject.value.assessments.length;
    const total = selectedSubject.value.assessments.reduce((acc, a) => acc + (a.grade / a.max_grade) * 100, 0);
    selectedSubject.value.current_grade = count ? total / count : 0;
    selectedSubject.value.assessments_count = count;

    const index = subjects.value.findIndex(s => s.id === selectedSubject.value.id);
    if (index !== -1) subjects.value[index] = { ...selectedSubject.value };
  } catch (error) {
    console.error(error);
    alert("Something went wrong while deleting the assessment.");
  }
};

const filteredSubjects = computed(() => {
  if (!subjects.value) return [];

  let result = subjects.value.filter(sub =>
    sub.name.toLowerCase().includes(searchQueryGrade.value.toLowerCase())
  );

  switch (sortOptionGrade.value) {
    case "1":
      return result.filter(sub =>
        sub.assessments.some(a => a.type.toLowerCase() === "quiz")
      );
    case "2":
      return result.filter(sub =>
        sub.assessments.some(a => a.type.toLowerCase() === "test")
      );
    case "3":
      return result.filter(sub =>
        sub.assessments.some(a => a.type.toLowerCase() === "exam")
      );
    case "4":
      return result.filter(sub =>
        sub.assessments.some(a => a.type.toLowerCase() === "assignment")
      );
    case "5":
      return result.filter(sub =>
        sub.assessments.some(a => a.type.toLowerCase() === "project")
      );
    default:
      return result;
  }
});

onMounted(async () => {
  await getSubjects();
  if (subjectModalRef.value) bsSubjectModal = new Modal(subjectModalRef.value);
});
</script>

<template>
  <!-- <NavigationComponent /> -->
  <NavigationChild />
  <SubNavigation />
  <FilterComponent
    v-model:searchQueryGrade="searchQueryGrade"
    v-model:sortOptionGrade="sortOptionGrade"
  />

  <!-- Subjects List -->
  <transition-group name="fade-slide" tag="div" class="container mt-4 py-5">
    <div v-if="filteredSubjects.length">
      <button
        v-for="subject in filteredSubjects"
        :key="subject.id"
        class="btn mb-3 w-100 shadow-sm border-0 rounded-4 py-3 px-4 text-start subject-card"
        @click="openSubjectModal(subject)"
      >
        <div class="row align-items-center">
          <div :class="subject.color" class="col-auto rounded-circle shadow-sm" style="height:50px; width:50px"></div>
          <div class="col ms-3">
            <h5 class="mb-0">{{ subject.name }}</h5>
            <p class="fst-italic text-secondary mb-0">{{ subject.assessments_count }} assessments recorded</p>
          </div>
          <div class="col-auto text-end">
            <div class="d-flex flex-column align-items-end">
              <h4 class="mb-0">{{ subject.current_grade }}%</h4>
              <p class="text-secondary fst-italic mb-0">Target: {{ subject.target_grade }}%</p>
              <div class="progress mt-2 rounded-pill" style="height: 15px; width: 100px; background: #e0e0e0;">
                <div
                  class="progress-bar progress-bar-striped progress-bar-animated rounded-pill"
                  :style="{ width: subject.current_grade > subject.target_grade ? '100%' : subject.current_grade + '%', background: 'linear-gradient(90deg, #3b82f6, #a855f7)' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </button>
    </div>
    <div v-else class="text-center text-secondary py-5">
      No subjects found for this filter.
    </div>
  </transition-group>

  <!-- Subject Modal -->
  <div class="modal fade" ref="subjectModalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content rounded-4 shadow-lg" v-if="selectedSubject">
        <div class="modal-header bg-gradient text-black py-3 rounded-top-4">
          <h1 class="modal-title fs-5 text-uppercase fst-italic">{{ selectedSubject.name }}</h1>
          <button type="button" class="btn-close btn-close-black" @click="closeSubjectModal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4 bg-light">
          <div class="d-flex justify-content-between mb-2">
            <p class="fst-italic text-secondary mb-0">Assessments History</p>
            <p class="text-secondary mb-0 fw-bold">Average: {{ averageGrade(selectedSubject.assessments) }}%</p>
          </div>

          <div v-if="selectedSubject.assessments.length" class="mt-2">
            <div
              v-for="assessment in selectedSubject.assessments"
              :key="assessment.id"
              class="border shadow-sm rounded-4 p-3 mb-3 assessment-card"
            >
              <div class="row row-cols-4 align-items-center">
                <div class="col">
                  <p class="fs-6 mb-0 text-capitalize">{{ assessment.name }}</p>
                  <p class="mb-0 text-secondary">{{ new Date(assessment.date_taken).toLocaleDateString() }}</p>
                </div>
                <div class="col">
                  <p class="mb-0 text-capitalize fw-semibold">{{ assessment.type }}</p>
                </div>
                <div class="col text-end">
                  <p class="mb-0">{{ assessment.grade }}/{{ assessment.max_grade }}</p>
                  <p class="mb-0 text-secondary">{{ ((assessment.grade / assessment.max_grade) * 100).toFixed(1) }}%</p>
                </div>
                <div class="col text-end">
                  <a class="btn ri-edit-circle-line fs-5 me-2" @click="openAssessmentEdit(assessment)"></a>
                  <a class="btn ri-delete-bin-line text-danger fs-5" @click="handleDelete(assessment.id)"></a>
                </div>
              </div>
            </div>

            <div v-if="loading" class="mt-3 text-center loading-customized">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p>Loading...</p>
            </div>
          </div>
          <div v-else class="text-center text-secondary py-3">
            No assessments recorded.
          </div>
        </div>
      </div>
    </div>
  </div>

  <AddUpdateComponent
    ref="assessmentModalRef"
    :form="form"
    :isEditing="isEditing"
    @update="getSubjects"
  />
</template>

<style scoped>
.subject-card {
  transition: all 0.3s ease;
}
.subject-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 18px rgba(0,0,0,0.15);
}

.assessment-card {
  transition: all 0.3s ease;
}
.assessment-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.bg-gradient {
  background: linear-gradient(135deg, #3b82f6, #a855f7);
}

.progress-bar {
  transition: width 0.4s ease;
}


  .fade-slide-enter-active,
  .fade-slide-leave-active {
    transition: all 0.8s ease-in-out;
  }
  .fade-slide-enter-from,
  .fade-slide-leave-to {
    opacity: 0;
    transform: translateY(20px);
  }
  .fade-slide-enter-to,
  .fade-slide-leave-from {
    opacity: 1;
    transform: translateY(0);
  }



 .loading-customized {
  position: absolute;
  left: 50%;
  top: 29.3%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 115vh;
  background: #80808054;
  display: flex;
  justify-content: center;
  align-items: center;
}


.customize-border {
  border: 1px solid #d5d5d5f5;
  border-radius: 6px;
  box-shadow: gray 0px 2.5px 3px 0px;
  text-align: left;
}
.customize-border:hover {
  box-shadow: #0a0c1a 0px 5.5px 15px 2px !important;
  transition: all 0.5s;
}
</style>


<!-- <style scoped>


  .fade-slide-enter-active,
  .fade-slide-leave-active {
    transition: all 0.8s ease-in-out;
  }
  .fade-slide-enter-from,
  .fade-slide-leave-to {
    opacity: 0;
    transform: translateY(20px);
  }
  .fade-slide-enter-to,
  .fade-slide-leave-from {
    opacity: 1;
    transform: translateY(0);
  }



 .loading-customized {
  position: absolute;
  left: 50%;
  top: 29.3%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 115vh;
  background: #80808054;
  display: flex;
  justify-content: center;
  align-items: center;
}


.customize-border {
  border: 1px solid #d5d5d5f5;
  border-radius: 6px;
  box-shadow: gray 0px 2.5px 3px 0px;
  text-align: left;
}
.customize-border:hover {
  box-shadow: #0a0c1a 0px 5.5px 15px 2px !important;
  transition: all 0.5s;
}
</style> -->
