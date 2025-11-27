<script setup>
import { reactive, ref, computed } from "vue";
// NOTE: Assuming Bootstrap 5 is available globally or imported elsewhere for Modal to work.
import { Modal } from "bootstrap";
// NOTE: Assuming these composables are correctly implemented and provide the expected functions
import { useSubjects } from "../../composables/subjects.js";
import { useAssessment } from "../../composables/assessment.js";
import { useTodos } from "@/composables/task.js";


const token = localStorage.getItem("token");
// Using a mock token check for safety, though the composables handle the actual auth logic
if (!token) console.error("Authentication token not found in localStorage.");

const { getSubjects, subjects, addSubject, updateSubject } = useSubjects(token);
const { addTodo , updateTodo , getTodos} = useTodos(token);
const { addAssessment, updateAssessment } = useAssessment(token);

const emit = defineEmits(["add", "update"]);
const isEditing = ref(false);
const modalRef = ref(null);
let bsModal = null;

const type = ref("subject");

// --- CONSTANTS ---

const colors = [
  "bg-warning bg-opacity-75", "bg-danger", "bg-secondary", "bg-primary bg-opacity-25",
  "bg-black", "bg-primary", "bg-danger bg-opacity-25", "bg-warning bg-opacity-25",
  "bg-black bg-opacity-25", "bg-success",
];

const quarters = [
  { value: 'preliminary', text: 'Preliminary' },
  { value: 'midterm', text: 'Midterm' },
  { value: 'pre_final', text: 'Semi_final' },
  { value: 'final', text: 'Final' },
];

const defaultAssessmentScoreItem = () => ({
  grade: 0,
  max_grade: 10,
});

const defaultForm = {
  // Common/Subject fields
  id: null,
  subject_name: "",
  color: "bg-secondary",

  // Assessment fields
  subject_id: null,
  name_assessment: "",
  type_quarter: "",
  type_activity: "",
  mode: "",
  score_items: [defaultAssessmentScoreItem()], // Array for multiple score entries
  date_taken: new Date().toISOString().split("T")[0],

  // Todo fields
  title: '',
  description: '',
  priority: 'low',
  due_date: new Date().toISOString().split("T")[0],
  completed: false
};

const form = reactive({ ...defaultForm });

// --- COMPUTED PROPERTIES FOR ASSESSMENT ---

const totalGrade = computed(() =>
  form.score_items.reduce((sum, item) => sum + (Number(item.grade) || 0), 0)
);

const totalMaxGrade = computed(() =>
  form.score_items.reduce((sum, item) => sum + (Number(item.max_grade) || 0), 0)
);

// --- ASSESSMENT METHODS ---

const addScorePair = () => {
  form.score_items.push(defaultAssessmentScoreItem());
};

const removeScorePair = (index) => {
  if (form.score_items.length > 1) {
    form.score_items.splice(index, 1);
  }
};

// --- GENERAL METHODS ---

const setColor = (color) => (form.color = color);

const resetForm = () => Object.assign(form, { ...defaultForm });

/**
 * Open modal for add/edit with proper data mapping
 */
const openModal = (modalType, data = null) => {
  type.value = modalType;
  isEditing.value = !!data;

  resetForm();

  if (data) {
    // Subject Mapping
    if (modalType === "subject") {
      Object.assign(form, {
        id: data.id,
        subject_name: data.subject_name,
        color: data.color || "bg-secondary",
      });
    }
    // Assessment Mapping (Handling old single-score data or new multi-score data)
    else if (modalType === "assessment") {
      // Fetch subjects just in case, for the dropdown
      getSubjects();

      const scoreItems = data.score_items && data.score_items.length > 0
        ? data.score_items
        : (data.score && data.total_items ? [{ grade: data.score, max_grade: data.total_items }] : [defaultAssessmentScoreItem()]);

      Object.assign(form, {
        id: data.id,
        subject_id: data.subject_id,
        name_assessment: data.name_assessment || data.name, // Use name_assessment, fallback to name
        type_quarter: data.type_quarter || data.quarter || "",
        type_activity: data.type_activity || data.type_assessment || "quiz", // Use type_activity, fallback to others
        mode: data.mode,
        score_items: scoreItems,
        date_taken: data.date_taken,
      });
    }
    // Task (Todo) Mapping
    else if (modalType === "todos") {
      getSubjects(); // Fetch subjects for the dropdown
      Object.assign(form, {
        id: data.id,
        title: data.title,
        description: data.description ?? '',
        priority: data.priority ?? 'low',
        due_date: data.due_date ?? new Date().toISOString().split("T")[0],
        completed: data.completed ?? false,
        subject_id: data.subject_id ?? null,
      });
    }
  } else if (modalType === "assessment") {
    // Ensure subjects are loaded for new assessment creation
    getSubjects();
  } else if (modalType === "todos") {
    // Ensure subjects are loaded for new task creation
    getSubjects();
  }

  if (!bsModal) bsModal = new Modal(modalRef.value);
  bsModal.show();
};

const loading = ref(false);

const handleSubmit = async () => {
  loading.value = true;
  try {
    if (type.value === "subject") {
      // Add or update subject
      isEditing.value ? await updateSubject(form) : await addSubject(form);
    } else if (type.value === "assessment") {
      // Prepare payload for assessment (using computed totals and detailed score items)
      const payload = {
        subject_id: form.subject_id,
        name_assessment: form.name_assessment,
        type_quarter: form.type_quarter,
        type_activity: form.type_activity,
        mode: form.mode,
        score: totalGrade.value,
        total_items: totalMaxGrade.value,
        score_items: form.score_items,
        date_taken: form.date_taken,
      };

      isEditing.value
        ? await updateAssessment({ id: form.id, ...payload })
        : await addAssessment(payload);

      // Since assessment is tightly coupled with subjects, refresh subject data after adding/updating assessment
      await getSubjects();

    } else if (type.value === "todos") {
      const payload = {
        subject_id: form.subject_id || null, // Ensure null if "No subject" selected
        title: form.title,
        description: form.description ?? '',
        priority: form.priority ?? 'low',
        due_date: form.due_date ?? new Date().toISOString().split("T")[0],
        completed: form.completed ?? false ,
      };

      isEditing.value
        ? await updateTodo({ id: form.id, ...payload })
        : await addTodo(payload);

      // Refresh todos after adding/updating
      await getTodos();
    }

    // Notify parent component of the update/add
    emit("update", { ...form });

  } catch (error) {
    console.error(`Error handling ${type.value}:`, error);
  } finally {
    loading.value = false;
    // Safely hide the modal instance
    Modal.getInstance(modalRef.value)?.hide();
  }
};

defineExpose({
  openForAdd: (t) => openModal(t),
  openForEdit: (t, d) => openModal(t, d),
});
</script>


<template>
  <div class="modal fade" tabindex="-1" aria-hidden="true" ref="modalRef">

    <div class="modal-dialog modal-lg"> <!-- Changed to modal-lg for better assessment form layout -->
      <div v-if="loading"  class="text-center loading-customized rounded">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mb-0 ps-3 fw-semibold text-white">Saving Data...</p>
      </div>
      <div class="modal-content rounded-4 shadow-lg border-0">
          <div class="modal-header bg-primary text-white py-3 rounded-top-4"> <!-- Improved header styling -->
            <div class="container-fluid">
              <h1 class="modal-title fs-5 fw-bold">
                {{ isEditing ? "Edit" : "Add New" }}
                <span v-if="type === 'subject'">Subject</span>
                <span v-else-if="type === 'assessment'">Assessment</span>
                <span v-else-if="type === 'todos'">Task</span>
              </h1>
              <p class="mb-0 text-white small opacity-75">
                <template v-if="type === 'subject'">
                  {{ isEditing ? "Update your subject details and settings." : "Create a new record to track your progress." }}
                </template>
                <template v-else-if="type === 'assessment'">
                  Record a new quiz, test, exam, or assignment grade.
                </template>
                <template v-else-if="type === 'todos'">
                  {{ isEditing ? "Update your task details and settings." : "Create a new task to keep track of your assignments and deadlines." }}
                </template>
              </p>
            </div>
            <button type="button" class="btn-close btn-close-white" @click="bsModal.hide()"></button>
          </div>

          <div class="modal-body bg-white p-4 text-black">
            <form @submit.prevent="handleSubmit">

              <!-- SUBJECT FORM -->
              <template v-if="type === 'subject'">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Subject Name</label>
                  <input v-model="form.subject_name" type="text" class="form-control rounded-pill shadow-sm" required />
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Color Tag</label>
                  <div class="d-flex gap-3 flex-wrap mt-2">
                    <button
                      v-for="color in colors"
                      :key="color"
                      type="button"
                      class="rounded-circle border-2 transition duration-150"
                      :class="[color, form.color === color ? 'border-4 border-dark ring-2 ring-offset-2 ring-primary shadow-lg' : 'border-gray-300 hover:scale-105']"
                      style="width: 40px; height: 40px;"
                      @click="setColor(color)"
                    ></button>
                  </div>
                </div>
              </template>

              <!-- ASSESSMENT FORM -->
              <template v-if="type === 'assessment'">
                  <div class="assessment-form-container">

                      <h6 class="text-secondary border-bottom pb-2 mb-3">Assessment Identity</h6>
                      <div class="row g-3">
                          <div class="col-12 col-md-4">
                              <label for="subjectSelect" class="form-label fw-semibold">Subject</label>
                              <select
                                  v-model="form.subject_id"
                                  id="subjectSelect"
                                  class="form-select custom-select-style rounded-pill shadow-sm"
                                  required
                              >
                                  <option value="" disabled>Select a subject</option>
                                  <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                                      {{ subject.subject_name }}
                                  </option>
                              </select>
                          </div>

                          <div class="col-12 col-md-4">
                              <label for="assessmentNameInput" class="form-label fw-semibold">Assessment Name</label>
                              <input
                                  v-model="form.name_assessment"
                                  id="assessmentNameInput"
                                  type="text"
                                  class="form-control custom-input-style rounded-pill shadow-sm"
                                  placeholder="e.g., Chapter 5 Quiz"
                                  required
                              />
                          </div>

                          <div class="col-12 col-md-4">
                              <label for="quarterSelect" class="form-label fw-semibold">Quarter/Semester</label>
                              <select
                                  v-model="form.type_quarter"
                                  id="quarterSelect"
                                  class="form-select custom-select-style rounded-pill shadow-sm"
                                  required
                              >
                                  <option value="" disabled>Select a quarter</option>
                                  <option v-for="q in quarters" :key="q.value" :value="q.value">
                                      {{ q.text }}
                                  </option>
                              </select>
                          </div>
                      </div>

                      <div class="mb-3 mt-4">
                          <label for="typeSelect" class="form-label fw-semibold">Activity Type</label>
                          <select
                              v-model="form.type_activity"
                              id="typeSelect"
                              class="form-select custom-select-style rounded-pill shadow-sm"
                          >
                              <option value="quiz">Quiz</option>
                              <option value="exam">Exam</option>
                              <option value="assignment">Assignment</option>
                              <option value="project">Project</option>
                          </select>
                      </div>

                      <hr class="my-4">

                      <h6 class="text-secondary mb-3 d-flex justify-content-between align-items-center">
                          Score Details
                          <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="addScorePair">
                              <i class="bi bi-plus"></i> Add Item
                          </button>
                      </h6>

                      <div class="mb-3 border p-3 rounded-3 bg-light">
                          <div class="row g-3 align-items-end mb-3" v-for="(scoreItem, index) in form.score_items" :key="index">

                              <div class="col-5 col-md-5">
                                  <label :for="'scoreInput-' + index" class="form-label fw-semibold d-block">Score Earned ({{ index + 1 }})</label>
                                  <input
                                      v-model.number="scoreItem.grade"
                                      :id="'scoreInput-' + index"
                                      type="number"
                                      class="form-control custom-input-style shadow-sm rounded-pill"
                                      min="0"
                                      :max="scoreItem.max_grade"
                                      required
                                  />
                                  <small v-if="scoreItem.max_grade && scoreItem.grade > scoreItem.max_grade" class="text-danger">
                                      Exceeds total items.
                                  </small>
                              </div>

                              <div class="col-5 col-md-5">
                                  <label :for="'totalScoreInput-' + index" class="form-label fw-semibold d-block">Total Score ({{ index + 1 }})</label>
                                  <input
                                      v-model.number="scoreItem.max_grade"
                                      :id="'totalScoreInput-' + index"
                                      type="number"
                                      class="form-control custom-input-style shadow-sm rounded-pill"
                                      min="1"
                                      required
                                  />
                              </div>

                              <div class="col-2 col-md-2 d-flex justify-content-center">
                                  <button
                                      type="button"
                                      class="btn btn-sm btn-outline-danger rounded-circle p-2"
                                      @click="removeScorePair(index)"
                                      :disabled="form.score_items.length === 1"
                                      style="width: 38px; height: 38px;"
                                  >
                                      <i class="bi bi-x-lg"></i>
                                  </button>
                              </div>
                          </div>
                      </div>
                      <div class="alert alert-info py-2 fw-semibold shadow-sm" v-if="form.score_items.length > 0 && totalMaxGrade > 0">
                          Overall Assessment: {{ totalGrade }} / {{ totalMaxGrade }} ({{ ((totalGrade / totalMaxGrade) * 100).toFixed(2) }}%)
                      </div>


                      <hr class="my-4">

                      <h6 class="text-secondary border-bottom pb-2 mb-3">Timeline</h6>
                      <div class="row g-3">
                          <div class="col-12 col-md-6">
                              <label for="typeSelect" class="form-label fw-semibold">Mode</label>
                              <select
                                  v-model="form.mode"
                                  id="typeSelect"
                                  class="form-select custom-select-style rounded-pill shadow-sm"
                                  required
                              >
                                  <option value="" disabled>Select a mode</option>
                                  <option value="f2f">Face to Face</option>
                                  <option value="online">Online</option>
                              </select>
                          </div>
                          <div class="col-12 col-md-6">
                              <label for="dateTakenInput" class="form-label fw-semibold">Date Taken</label>
                              <input
                                  v-model="form.date_taken"
                                  id="dateTakenInput"
                                  type="date"
                                  class="form-control custom-input-style rounded-pill shadow-sm"
                                  required
                              />
                          </div>
                      </div>
                  </div>
              </template>



                <!-- TASK FORM -->
              <template v-if="type === 'todos'">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Title</label>
                  <input v-model="form.title" type="text" class="form-control rounded-pill shadow-sm" required />
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Subject <small class="text-muted">(Optional)</small></label>
                  <select v-model="form.subject_id" class="form-select rounded-pill shadow-sm">
                    <option :value="null">No subject</option>
                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.subject_name }}</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Description</label>
                  <textarea v-model="form.description" class="form-control rounded-3 shadow-sm" rows="3" placeholder="Optional"></textarea>
                </div>

                <div class="row g-3">
                  <div class="col">
                    <label class="form-label fw-semibold">Priority</label>
                    <select v-model="form.priority" class="form-select rounded-pill shadow-sm" required>
                      <option value="low">Low</option>
                      <option value="medium">Medium</option>
                      <option value="high">High</option>
                    </select>
                  </div>
                  <div class="col">
                    <label class="form-label fw-semibold">Due Date</label>
                    <input v-model="form.due_date" type="date" class="form-control rounded-pill shadow-sm" />
                  </div>
                </div>
              </template>

              <!-- Buttons -->
              <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" @click="bsModal.hide()">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 rounded-pill">
                  <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                  {{ isEditing ? "Update" : "Add" }}
                </button>
              </div>
            </form>
          </div>
        </div>
    </div>
  </div>


</template>

<style scoped>
/* Custom style for the loading overlay */
.loading-customized {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6); /* Darker overlay */
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
  z-index: 1050; /* Ensure it's above the modal content */
  pointer-events: all;
}

/* Custom styles for form elements for aesthetics */
.form-control.rounded-pill,
.form-select.rounded-pill {
  border-radius: 50rem !important;
}
.form-control, .form-select {
  border-color: #e0e0e0;
}
.modal-header.bg-primary {
  background: linear-gradient(135deg, #007bff, #0056b3);
}

.transition {
    transition: all 0.2s ease-in-out;
}
</style>
