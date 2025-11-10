<script setup>
import { reactive, ref } from "vue";
import { Modal } from "bootstrap";
import { useSubjects } from "../../composables/subjects.js";
import { useAssessment } from "../../composables/assessment.js";
import { useTodos } from "@/composables/task.js";


const token = localStorage.getItem("token");
const { getSubjects, subjects, addSubject, updateSubject } = useSubjects(token);
const { addTodo , updateTodo , getTodos} = useTodos(token);
const { addAssessment, updateAssessment } = useAssessment(token);

const emit = defineEmits(["add", "update"]);
const isEditing = ref(false);
const modalRef = ref(null);
let bsModal = null;

const type = ref("subject");

const defaultForm = {
  id: null,
  name: "",
  target_grade: "",
  color: "bg-secondary",

  subject_id: null,
  type_assessment: "quiz",
  grade: 0,
  max_grade: 100,
  weight: 0,
  date_taken: new Date().toISOString().split("T")[0],

  title: '',
  description: '',
  priority: 'low',
  due_date: new Date().toISOString().split("T")[0],
  completed: false
};

const form = reactive({ ...defaultForm });

const colors = [
  "bg-warning bg-opacity-75",
  "bg-danger",
  "bg-secondary",
  "bg-primary bg-opacity-25",
  "bg-black",
  "bg-primary",
  "bg-danger bg-opacity-25",
  "bg-warning bg-opacity-25",
  "bg-black bg-opacity-25",
  "bg-success",
];

const setColor = (color) => (form.color = color);

const resetForm = () => Object.assign(form, { ...defaultForm });

/**
 * Open modal for add/edit
 */
const openModal = (modalType, data = null) => {
  type.value = modalType;
  isEditing.value = !!data;

  resetForm();

  if (data) {
    if (modalType === "subject") {
      Object.assign(form, {
        id: data.id,
        name: data.name,
        target_grade: data.target_grade,
        color: data.color || "bg-secondary",
      });
    } else if (modalType === "assessment") {
      Object.assign(form, {
        id: data.id,
        subject_id: data.subject_id,
        name: data.name,
        type_assessment: data.type_assessment ?? data.type,
        grade: data.grade,
        max_grade: data.max_grade,
        weight: data.weight,
        date_taken: data.date_taken,
      });
    } else if (modalType === "todos") {
       Object.assign(form, {
        id: data.id,
        title: data.title,
        description: data.description ?? '',
        priority: data.priority ?? 'low',
        due_date: data.due_date ?? new Date().toISOString().split("T")[0],
        completed: data.completed ?? false,
        subject_id: data.subject_id ?? '',
      });
    }
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
    // Prepare payload for assessment
    const payload = {
      subject_id: form.subject_id,
      name: form.name,
      type: form.type_assessment,
      grade: form.grade,
      max_grade: form.max_grade,
      weight: form.weight,
      date_taken: form.date_taken,
    };
    isEditing.value
      ? await updateAssessment({ id: form.id, ...payload })
      : await addAssessment(payload);
    await getSubjects();
  } else if (type.value === "todos") {
    const payload = {
      subject_id: form.subject_id ?? '',
      title: form.title,
      description: form.description ?? '',
      priority: form.priority ?? 'low',
      due_date: form.due_date ?? new Date().toISOString().split("T")[0],
      completed: form.completed ?? false ,
    };
    isEditing.value
      ? await updateTodo({ id: form.id, ...payload })
      : await addTodo(payload);

      await getTodos();
    }
    emit("update", { ...form });
} finally {
  loading.value = false;
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

    <div class="modal-dialog">
      <div v-if="loading"  class="text-center loading-customized rounded">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mb-0 ps-3 fw-semibold">Loading...</p>
      </div>
      <div class="modal-content rounded-4 shadow-lg border-0">
          <div class="modal-header bg-gradient text-black py-3 rounded-top-4">
            <div class="container">
              <h1 class="modal-title fs-5 fw-semibold">
                {{ isEditing ? "Edit" : "Add New" }}
                <span v-if="type === 'subject'">Subject</span>
                <span v-else-if="type === 'assessment'">Assessment</span>
                <span v-else-if="type === 'todos'">Task</span>
              </h1>
              <p class="mb-0 text-black small">
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
            <button type="button" class="btn-close btn-close-black" @click="bsModal.hide()"></button>
          </div>

          <div class="modal-body bg-light p-4 text-black">
            <form @submit.prevent="handleSubmit">

              <!-- SUBJECT -->
              <template v-if="type === 'subject'">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Subject Name</label>
                  <input v-model="form.name" type="text" class="form-control rounded-pill shadow-sm" required />
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Target Grade (%)</label>
                  <input v-model="form.target_grade" type="number" class="form-control rounded-pill shadow-sm" required />
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Color</label>
                  <div class="d-flex gap-2 flex-wrap mt-2">
                    <button
                      v-for="color in colors"
                      :key="color"
                      type="button"
                      class="rounded-circle border-2"
                      :class="[color, form.color === color ? 'border-3 border-dark shadow-sm' : '']"
                      style="width: 36px; height: 36px;"
                      @click="setColor(color)"
                    ></button>
                  </div>
                </div>
              </template>

              <!-- ASSESSMENT -->
              <template v-if="type === 'assessment'">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Subject</label>
                  <select v-model="form.subject_id" class="form-select rounded-pill shadow-sm" required>
                    <option value="" disabled>Select a subject</option>
                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                      {{ subject.name }}
                    </option>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Assessment Name</label>
                  <input v-model="form.name" type="text" class="form-control rounded-pill shadow-sm" required />
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Type</label>
                  <select v-model="form.type_assessment" class="form-select rounded-pill shadow-sm">
                    <option value="quiz">Quiz</option>
                    <option value="test">Test</option>
                    <option value="exam">Exam</option>
                    <option value="assignment">Assignment</option>
                    <option value="project">Project</option>
                  </select>
                </div>

                <div class="row g-3">
                  <div class="col">
                    <label class="form-label fw-semibold">Score</label>
                    <input v-model.number="form.grade" type="number" class="form-control rounded-pill shadow-sm" :max="form.max_grade" required />
                  </div>
                  <div class="col">
                    <label class="form-label fw-semibold">Total Score</label>
                    <input v-model.number="form.max_grade" type="number" class="form-control rounded-pill shadow-sm" required />
                  </div>
                </div>

                <div class="row g-3 mt-2">
                  <div class="col">
                    <label class="form-label fw-semibold">Weight (%) <small class="text-muted fst-italic">(Grade impact)</small></label>
                    <input v-model.number="form.weight" type="number" class="form-control rounded-pill shadow-sm" step="0.01" min="0" max="100" />
                  </div>
                  <div class="col">
                    <label class="form-label fw-semibold">Date Taken</label>
                    <input v-model="form.date_taken" type="date" class="form-control rounded-pill shadow-sm" required />
                  </div>
                </div>
              </template>

              <!-- TASK -->
              <template  v-if="type === 'todos'">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Title</label>
                  <input v-model="form.title" type="text" class="form-control rounded-pill shadow-sm" required />
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Subject <small class="text-muted">(Optional)</small></label>
                  <select v-model="form.subject_id" class="form-select rounded-pill shadow-sm">
                    <option value="null">No subject</option>
                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
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
                <button type="button" class="btn btn-outline-primary px-4 rounded-pill" @click="bsModal.hide()">Close</button>
                <button type="submit" class="btn btn-primary px-4 rounded-pill">{{ isEditing ? "Update" : "Add" }}</button>
              </div>
            </form>
          </div>
        </div>
    </div>
  </div>


</template>

<style scoped>
.loading-customized {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 10;
   pointer-events: all;
}

.h-75 {
  height: 75px !important;
}
</style>
