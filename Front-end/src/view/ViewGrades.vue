<script setup>
import NavigationChild from './components/NavigationChild.vue';
import SubNavigation from './components/SubNavigation.vue';
import FilterComponent from './components/FilterComponent.vue';
import AddUpdateComponent from './components/AddUpdateComponent.vue';
import { ref, reactive, onMounted, computed } from 'vue';
import { Modal } from 'bootstrap';
import { useAssessment } from "../composables/assessment.js";
import { useSubjects } from "../composables/subjects.js";

// Global data and composables
const token = localStorage.getItem("token");
const assessmentApi = useAssessment(token);
const subjectsApi = useSubjects(token);

// --- State ---
const selectedSubject = ref(null);
const subjectModalRef = ref(null);
let bsSubjectModal = null;
const assessmentModalRef = ref(null);

const searchQueryGrade = ref("");
const sortOptionGrade = ref("name");
const isEditing = ref(false);

// State for modal filtering
const activityFilter = ref('all');
const quarterFilter = ref('all');
const quarterGradeData = ref(null); // State kept, but no longer used for fetching aggregate grades


// Initial form structure for adding/editing an assessment
const initialFormState = {
    id: null,
    subject_id: null,
    name: '',
    type_assessment: '',
    grade: 0,
    max_grade: 0,
    weight: 0,
    date_taken: ''
};
const form = reactive({ ...initialFormState });

/**
 * Resets the assessment form to its initial state.
 */
const resetForm = () => {
    Object.assign(form, initialFormState);
    isEditing.value = false;
};

// ===================================================================
// NOTE: The fetchAggregatedGrade function and the watch block are removed
// as requested, to rely solely on local filtering and remove grade computation logic.
// ===================================================================


// --- Lifecycle and Initialization ---

onMounted(async () => {
    // 1. Initial data fetch
    await subjectsApi.getSubjects();

    // 2. Initialize Bootstrap Modal
    if (subjectModalRef.value) {
        bsSubjectModal = new Modal(subjectModalRef.value);

        // Listen for the Bootstrap modal 'hidden' event for cleanup/refresh
        subjectModalRef.value.addEventListener('hidden.bs.modal', async () => {
            selectedSubject.value = null;
            // RESET: Reset all modal filters and data when closing
            activityFilter.value = 'all';
            quarterFilter.value = 'all';
            quarterGradeData.value = null; // Still good practice to reset this
            await subjectsApi.getSubjects();
        });
    }
});

// The watch block triggering fetchAggregatedGrade is REMOVED.

const getSubjectInitial = (name) => {
    if (!name || typeof name !== 'string' || name.length === 0) {
        return '?';
    }
    return name.charAt(0).toUpperCase();
};

// The averageGrade function is REMOVED.


// --- Computed Properties ---

/**
 * Computed property to filter and sort the subjects list based on user input.
 */
const filteredSubjects = computed(() => {
    if (!subjectsApi.subjects.value) return [];

    let result = subjectsApi.subjects.value;

    // 1. **Default/Static Filter:** Only show subjects that have at least one assessment recorded.
    result = result.filter(sub => Array.isArray(sub.assessments) && sub.assessments.length > 0);

    // 2. **Search Filter:** Filter by subject name (case-insensitive)
    if (searchQueryGrade.value) {
        const nameKey = result[0]?.name ? 'name' : 'subject_name';
        result = result.filter(sub =>
            (sub[nameKey] || '').toLowerCase().includes(searchQueryGrade.value.toLowerCase())
        );
    }

    // 3. **Assessment Type Filter (Sort Option):**
    const typeMap = {
        "quiz": "quiz", "test": "test", "exam": "exam", "assignment": "assignment", "project": "project",
    };
    const targetType = typeMap[sortOptionGrade.value];

    if (targetType) {
        result = result.filter(sub =>
            Array.isArray(sub.assessments) && sub.assessments.some(a =>
                (a.type_activity || a.type)?.toLowerCase() === targetType
            )
        );
    }


    return result;
});


const filteredAssessments = computed(() => {
    if (!selectedSubject.value || !selectedSubject.value.assessments) {
        return [];
    }

    let assessments = selectedSubject.value.assessments;
    const typeFilter = activityFilter.value;
    const qtrFilter = quarterFilter.value; // Use new quarter filter state

    // 1. Filter by Activity Type
    if (typeFilter !== 'all') {
        assessments = assessments.filter(assessment =>
            (assessment.type_activity || assessment.type)?.toLowerCase() === typeFilter.toLowerCase()
        );
    }

    // 2. Filter by Quarter
    if (qtrFilter !== 'all') {
        assessments = assessments.filter(assessment =>
            assessment.type_quarter?.toLowerCase() === qtrFilter.toLowerCase()
        );
    }

    return assessments;
});

// --- Event Handlers and Methods (Kept for completeness) ---

const openSubjectModal = (subject) => { selectedSubject.value = subject; bsSubjectModal?.show(); };
const openAssessmentEdit = (assessment) => {
    if (!assessment) return;
    isEditing.value = true;
    // Note: The original form names (name, grade, max_grade) are inconsistent with model fields (name_assessment, score, total_items)
    // We try to map the new data structure (assessment model) back to the form structure.
    Object.assign(form, {
        id: assessment.id,
        subject_id: assessment.subject_id,
        name: assessment.name_assessment, // Use name_assessment from API
        type_assessment: assessment.type_activity, // Use type_activity from API
        grade: assessment.score, // Use score from API
        max_grade: assessment.total_items, // Use total_items from API
        weight: assessment.weight,
        date_taken: assessment.date_taken,
    });
    bsSubjectModal?.hide();
    setTimeout(() => { assessmentModalRef.value?.openForEdit("assessment", form); }, 300);
};
const closeSubjectModal = () => { bsSubjectModal?.hide(); };
const handleDelete = async (id) => {
    if (!confirm("Are you sure you want to delete this assessment?")) return;
    try {
        const success = await assessmentApi.deleteAssessment(id);
        if (!success) throw new Error("Delete failed");
        await subjectsApi.getSubjects();
        const updatedSubject = subjectsApi.subjects.value.find(s => s.id === selectedSubject.value.id);
        if (updatedSubject) { selectedSubject.value = updatedSubject; }
        else { bsSubjectModal?.hide(); }
    } catch (error) { console.error("Something went wrong while deleting the assessment.", error); }
};
const handleAssessmentUpdate = async () => {
    await subjectsApi.getSubjects();
    if (form.subject_id) {
        const updatedSubject = subjectsApi.subjects.value.find(s => s.id === form.subject_id);
        if (updatedSubject) {
            selectedSubject.value = updatedSubject;
            bsSubjectModal?.show();
        }
    }
    resetForm();
};
</script>

<template>
    <NavigationChild />
    <SubNavigation />

    <FilterComponent
        v-model:searchQueryGrade="searchQueryGrade"
        v-model:sortOptionGrade="sortOptionGrade"
    />

    <transition-group name="fade-slide" tag="div" class="container mt-4 py-5">
        <div v-if="subjectsApi.subjects.loading" class="text-center text-primary py-5">
            Loading subjects...
        </div>
        <div v-else-if="filteredSubjects.length">
            <button
                v-for="subject in filteredSubjects"
                :key="subject.id"
                class="btn mb-3 w-100 shadow-sm border-0 rounded-4 py-3 px-4 text-start subject-card"
                @click="openSubjectModal(subject)"
            >
                <div class="row align-items-center">
                    <div :class="subject.color" class="col-auto rounded-circle shadow-sm text-center align-items-center pt-3" style="height:50px; width:50px">
                       <span :class="subject.color === '#FF5733' ? 'text-black' : 'text-white'" class="avatar-initials fw-bold">{{ getSubjectInitial(subject.name || subject.subject_name) }}</span>
                    </div>
                    <div class="col ms-3">
                        <h5 class="mb-0">{{ subject.name || subject.subject_name }}</h5>
                    </div>
                    <!-- Removed the Avg. Grade display block -->
                </div>
            </button>
        </div>
        <div v-else class="text-center text-secondary py-5">
            No subjects found for this filter.
        </div>
    </transition-group>

    <div class="modal fade" ref="subjectModalRef" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg" v-if="selectedSubject">
                <div class="modal-header bg-gradient text-black py-3 rounded-top-4">
                    <h1 class="modal-title fs-5 text-uppercase fst-italic">
                        {{ selectedSubject.name || selectedSubject.subject_name }}
                    </h1>
                    <button type="button" class="btn-close btn-close-black" @click="closeSubjectModal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="fst-italic text-secondary mb-0">Assessments</p>

                        <div class="d-flex align-items-center flex-wrap gap-3">
                            <div class="d-flex align-items-center">
                                <label for="activityFilter" class="form-label text-muted me-2 mb-0 small">Filter Type:</label>
                                <select
                                    id="activityFilter"
                                    v-model="activityFilter"
                                    class="form-select form-select-sm"
                                    style="width: 150px;"
                                >
                                    <option value="all">All Activities</option>
                                    <option value="quiz">Quiz</option>
                                    <option value="exam">Exam</option>
                                    <option value="assignment">Assignment</option>
                                    <option value="project">Project</option>
                                </select>
                            </div>

                            <div class="d-flex align-items-center">
                                <label for="quarterFilter" class="form-label text-muted me-2 mb-0 small">Filter Quarter/Semester :</label>
                                <select
                                    id="quarterFilter"
                                    v-model="quarterFilter"
                                    class="form-select form-select-sm"
                                    style="width: 150px;"
                                >
                                    <option value="all">All Quarter</option>
                                    <option value="preliminary">Preliminary</option>
                                    <option value="midterm">Midterm</option>
                                    <option value="semi_final">Semi-Final</option>
                                    <option value="final">Final</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div v-if="filteredAssessments.length" class="table-responsive">
                        <table class="table table-hover table-sm align-middle rounded-4 shadow-sm assessment-table">
                            <thead class="bg-white">
                                <tr>
                                    <th scope="col" class="text-uppercase small">Name</th>
                                    <th scope="col" class="text-uppercase small">Type / Quarter</th>
                                    <th scope="col" class="text-end text-uppercase small">Grade (Score)</th>
                                    <th scope="col" class="text-end text-uppercase small">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="assessment in filteredAssessments" :key="assessment.id">
                                    <td>
                                        <p class="fs-6 mb-0 text-capitalize">{{ assessment.name_assessment }}</p>
                                        <small class="text-secondary">{{ assessment.date_taken ? new Date(assessment.date_taken).toLocaleDateString() : 'N/A' }}</small>
                                    </td>
                                    <td>
                                        <p class="mb-0 fw-semibold text-capitalize">{{ assessment.type_activity || assessment.type_activity }}</p>
                                        <small class="text-muted text-capitalize">{{ assessment.type_quarter || 'N/A' }}</small>
                                    </td>
                                    <td class="text-end">
                                        <p class="mb-0 fw-bold">{{ assessment.score }}/{{ assessment.total_items }}</p>
                                        <small class="text-secondary" v-if="assessment.total_items > 0">{{ ((assessment.score / assessment.total_items) * 100).toFixed(1) }}%</small>
                                        <small class="text-secondary" v-else>0.0%</small>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm ri-edit-circle-line me-1" title="Edit Assessment" @click="openAssessmentEdit(assessment)"></button>
                                        <button type="button" class="btn btn-sm ri-delete-bin-line text-danger" title="Delete Assessment" @click="handleDelete(assessment.id)"></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="text-center text-secondary py-3">
                        <span v-if="activityFilter === 'all' && quarterFilter === 'all'">No assessments recorded for {{ selectedSubject.name || selectedSubject.subject_name }}.</span>
                        <span v-else>No assessments match the selected filter criteria (Type: **{{ activityFilter }}**, Quarter: **{{ quarterFilter }}**).</span>
                    </div>
                </div>

                <!-- Removed the complex modal-footer grade computation block -->

                <div class="modal-footer p-2 bg-white">
                    <button type="button" class="btn btn-secondary" @click="closeSubjectModal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <AddUpdateComponent
        ref="assessmentModalRef"
        :form="form"
        :isEditing="isEditing"
        @update="handleAssessmentUpdate"
    />
</template>

<style scoped>
/* Scoped styles remain the same */
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
