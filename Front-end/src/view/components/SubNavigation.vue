<script setup>
import { useRoute } from 'vue-router';
import { onMounted, computed } from 'vue';
import { useSubjects } from "../../composables/subjects.js";
import { useTodos } from '@/composables/task.js';

const route = useRoute();

const token = localStorage.getItem("token");
// subjects is the MERGED array from useSubjects (includes quarters_data)
const { subjects, getSubjects } = useSubjects(token);
const { todos, getTodos } = useTodos(token);

const allTodos = computed(() => todos.value);

// Helper function to calculate a subject's single weighted percentage across ALL quarters
const getSubjectWeightedAveragePercent = (subject) => {
    let rawScoreSum = 0;
    let gradedQuartersCount = 0;

    // Check for the quarters_data merged from the final_grade API
    const quarters = subject.quarters_data || {};

    Object.values(quarters).forEach(quarterData => {
        const rawScore = quarterData.overall?.raw_score;

        // Use the weighted raw percentage from the backend
        if (rawScore !== null && rawScore !== undefined) {
            rawScoreSum += Number(rawScore);
            gradedQuartersCount++;
        }
    });

    return gradedQuartersCount > 0 ? (rawScoreSum / gradedQuartersCount) : 0;
};

// --------------------------------------------------------------------------------
// ➡️ NEW/OPTIMIZED: Study Streak Calculation
// --------------------------------------------------------------------------------

const studyStreak = computed(() => {
    // 1. Collect and normalize all assessment dates to milliseconds for accurate comparison
    const activityTimestamps = subjects.value
        .flatMap(s => s.assessments || [])
        .map(a => {
            if (!a.date_taken) return null;
            // Normalize to start of the day (midnight) to compare dates, not times
            const date = new Date(a.date_taken);
            date.setHours(0, 0, 0, 0);
            return date.getTime();
        })
        .filter(t => t !== null);

    if (activityTimestamps.length === 0) return 0;

    // 2. Get unique timestamps and sort them chronologically
    const uniqueTimestamps = [...new Set(activityTimestamps)].sort((a, b) => a - b);

    let currentStreak = 0;
    let maxStreak = 0;

    for (let i = 0; i < uniqueTimestamps.length; i++) {
        // Start a new streak or continue the existing one
        if (i === 0) {
            currentStreak = 1;
        } else {
            // Calculate difference in milliseconds
            const diffMs = uniqueTimestamps[i] - uniqueTimestamps[i - 1];
            // Convert difference to days (86400000 ms in a day)
            const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));

            if (diffDays === 1) {
                // Activity was exactly the day after the previous one
                currentStreak++;
            } else if (diffDays > 1) {
                // Gap found, reset streak to 1 (for the current day)
                currentStreak = 1;
            }
            // diffDays === 0 means duplicate date, currentStreak remains unchanged
        }
        maxStreak = Math.max(maxStreak, currentStreak);
    }

    // NOTE: The calculated maxStreak represents the longest consecutive run.
    return maxStreak;
});

// --------------------------------------------------------------------------------
// ➡️ Task Counters (Optimized for calculation efficiency)
// --------------------------------------------------------------------------------
const todoCounts = computed(() => {
    const all = allTodos.value;
    const todayISO = new Date().toISOString().split("T")[0];

    const metrics = {
        All: all.length,
        Pending: 0,
        Completed: 0,
        DueToday: 0,
        Overdue: 0,
    };

    all.forEach(t => {
        if (t.completed) {
            metrics.Completed++;
        } else {
            metrics.Pending++;
            const dueDateISO = (t.due_date || "").split("T")[0];

            if (dueDateISO) {
                if (dueDateISO === todayISO) {
                    metrics.DueToday++;
                } else if (dueDateISO < todayISO) {
                    metrics.Overdue++;
                }
            }
        }
    });

    return metrics;
});

// ---------- Grade/Subject Helpers (Optimized) ----------

const totalAssessments = computed(() =>
    subjects.value.reduce((acc, s) => acc + (s.assessments?.length || 0), 0)
);

// Helper to get the GPA from the subject_final_average (from API merge)
const getSubjectFinalGPA = (subject) => {
    // We assume subject_final_average is the final transmuted grade (1.0 - 5.0)
    return Number(subject.subject_final_average) || 5.0;
};

// Subjects passing (status == 'Passing')
const passingSubjects = computed(() =>
    subjects.value.filter(s => s.status === 'Passing')
);

// Weighted average grade percentage across all subjects
const averageGrade = computed(() => {
    const totalWeightedScores = subjects.value.reduce((sum, s) => {
        return sum + getSubjectWeightedAveragePercent(s);
    }, 0);

    return subjects.value.length > 0
        ? (totalWeightedScores / subjects.value.length).toFixed(1)
        : '0.0';
});


// Weighted overall GPA (Derived from the final transmuted grade)
const overallGPA = computed(() => {
    const gradedSubjects = subjects.value.filter(s => s.subject_final_average !== null);

    if (gradedSubjects.length === 0) return 'N/A';

    const gpaSum = gradedSubjects.reduce((sum, s) => {
        // Summing the final transmuted GPA (1.0 - 5.0) for each subject
        return sum + getSubjectFinalGPA(s);
    }, 0);

    return (gpaSum / gradedSubjects.length).toFixed(2);
});

// ---------- Dashboard & Analytics Stats (REVISED) ----------
const dashboardStats = computed(() => {
    return {
        averagePercent: averageGrade.value,
        overallGPA: overallGPA.value,
        activeSubjects: subjects.value.length,
        pendingTasks: todoCounts.value.Pending,
        completedTasks: todoCounts.value.Completed,
    };
});


// ---------- Lifecycle ----------
onMounted(() => {
    // Call all necessary fetch actions on mount
    getSubjects();
    getTodos();
});
</script>

<template>
    <div v-if="route.name == 'Dashboard'" class="stats-container">
        <div class="stat-card" v-for="card in [
            { title: 'Overall GPA', value: dashboardStats.overallGPA, icon: 'ri-donut-chart-line', note: 'Transmuted Grade (1.00-5.00)', color: 'gradient-indigo' },
            { title: 'Active Subjects', value: dashboardStats.activeSubjects, icon: 'ri-book-open-line', note: 'Currently Tracking', color: 'gradient-purple' },
            { title: 'Pending Tasks', value: dashboardStats.pendingTasks, icon: 'ri-calendar-line', note: 'Awaiting completion', color: 'gradient-orange' },
            { title: 'Completed Tasks', value: dashboardStats.completedTasks, icon: 'ri-checkbox-circle-line', note: 'Total finished items', color: 'gradient-green' }
        ]" :key="card.title" :class="card.color">
            <div class="stat-header">
                <span>{{ card.title }}</span>
                <i :class="card.icon"></i>
            </div>
            <div class="stat-body">
                <h4>{{ card.value }}</h4>
                <p>{{ card.note }}</p>
            </div>
        </div>
    </div>

    <div v-else-if="route.name == 'Analytics'" class="stats-container">
        <div class="stat-card gradient-indigo">
            <div class="stat-header">
                <span>Overall GPA</span>
                <i class="ri-donut-chart-line"></i>
            </div>
            <div class="stat-body">
                <h4>{{ overallGPA }}</h4>
                <p>Weighted Average (1.00-5.00)</p>
            </div>
        </div>

        <div class="stat-card gradient-blue">
            <div class="stat-header">
                <span>Task Completion</span>
                <i class="ri-calendar-line"></i>
            </div>
            <div class="stat-body">
                <h4>{{ todoCounts.All > 0 ? (todoCounts.Completed / todoCounts.All * 100).toFixed(0) : 0 }}%</h4>
                <div class="progress mt-3" role="progressbar"
                    :aria-valuenow="todoCounts.All > 0 ? (todoCounts.Completed / todoCounts.All * 100).toFixed(0) : 0" aria-valuemin="0" aria-valuemax="100"
                    style="height: 10px; background: rgba(255,255,255,0.25); border-radius: 6px;">
                    <div class="progress-bar"
                        :style="{ width: (todoCounts.All > 0 ? (todoCounts.Completed / todoCounts.All * 100) : 0) + '%', background: '#fff', borderRadius: '6px' }"></div>
                </div>
                <p class="mt-2">Overall completion rate</p>
            </div>
        </div>

        <div class="stat-card gradient-green">
            <div class="stat-header">
                <span>Passing Subjects</span>
                <i class="ri-award-line"></i>
            </div>
            <div class="stat-body">
                <h4>{{ passingSubjects.length }} / {{ subjects.length }}</h4>
                <p>Subjects with final grade &le; 3.0</p>
            </div>
        </div>

        <div class="stat-card gradient-orange">
            <div class="stat-header">
                <span>Study Streak</span>
                <i class="ri-book-open-line"></i>
            </div>
            <div class="stat-body">
                <h4>{{ studyStreak }}</h4>
                <p>Days consecutive activity</p>
            </div>
        </div>
    </div>

    <div v-else-if="route.name == 'Subjects'" class="stats-container">
        <div class="stat-card gradient-blue">
            <div class="stat-header">
                <span>Total Subjects</span>
                <i class="ri-book-open-line"></i>
            </div>
            <div class="stat-body">
                <h4>{{ subjects.length || 0 }}</h4>
                <p>Currently Tracking</p>
            </div>
        </div>

        <div class="stat-card gradient-green">
            <div class="stat-header">
                <span>Passing Subjects</span>
                <i class="ri-donut-chart-line"></i>
            </div>
            <div class="stat-body">
                <h4>{{ passingSubjects.length }}</h4>
                <p>Out of {{ subjects.length }} Subjects</p>
            </div>
        </div>

        <div class="stat-card gradient-orange">
            <div class="stat-header">
                <span>Average Grade</span>
                <i class="ri-line-chart-fill"></i>
            </div>
            <div class="stat-body">
                <h4>{{ averageGrade }}%</h4>
                <p>Mean of weighted raw scores</p>
            </div>
        </div>

        <div class="stat-card gradient-purple">
            <div class="stat-header">
                <span>Total Assessments</span>
                <i class="ri-award-line"></i>
            </div>
            <div class="stat-body">
                <h4>{{ totalAssessments }}</h4>
                <p>Recorded this semester</p>
            </div>
        </div>
    </div>

    <div v-else-if="route.name == 'Grades'" class="stats-container">
        <div class="stat-card gradient-indigo">
            <div class="stat-header">
                <span>Overall GPA</span>
                <i class="ri-donut-chart-line"></i>
            </div>
            <div class="stat-body">
                <h4>{{ overallGPA }}</h4>
                <p>Transmuted Grade (1.00-5.00)</p>
            </div>
        </div>

        <div class="stat-card gradient-purple">
            <div class="stat-header">
                <span>Total Assessments</span>
                <i class="ri-book-open-line"></i>
            </div>
            <div class="stat-body">
                <h4>{{ totalAssessments }}</h4>
                <p>Recorded this semester</p>
            </div>
        </div>

        <div class="stat-card gradient-green">
            <div class="stat-header">
                <span>Passing Subjects</span>
                <i class="ri-calendar-line"></i>
            </div>
            <div class="stat-body">
                <h4>{{ passingSubjects.length }}</h4>
                <p>Out of {{ subjects.length }} subjects</p>
            </div>
        </div>
    </div>

    <div v-else-if="route.name == 'ManageTask'" class="stats-container">
        <div v-for="task in [
            { title: 'Total Tasks', value: todoCounts.All, icon: 'ri-list-check-3', color: 'gradient-indigo', note: 'Total items created' },
            { title: 'Pending', value: todoCounts.Pending, icon: 'ri-history-line', color: 'gradient-orange', note: 'Awaiting completion' },
            { title: 'Due Today', value: todoCounts.DueToday, icon: 'ri-error-warning-line', color: 'gradient-blue', note: 'Items due today' },
            { title: 'Overdue', value: todoCounts.Overdue, icon: 'ri-error-warning-line', color: 'gradient-red', note: 'Past due date' },
            { title: 'Completed', value: todoCounts.Completed, icon: 'ri-checkbox-circle-line', color: 'gradient-green', note: 'Total tasks finished' }
        ]" :key="task.title" class="stat-card" :class="task.color">
            <div class="stat-header">
                <span>{{ task.title }}</span>
                <i :class="task.icon"></i>
            </div>
            <div class="stat-body">
                <h4>{{ task.value }}</h4>
                <p v-if="task.note">{{ task.note }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>

/* (Styles omitted for brevity, assumed unchanged) */

.stats-container {

 display: grid;

 grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));

 gap: 1.2rem;

 margin: 1.5rem auto;

 padding: 0 1rem;

}

/* ... rest of your styles ... */

.stat-card {

 color: #fff;

 border-radius: 16px;

 padding: 1.5rem;

 box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);

 transition: transform 0.25s ease, box-shadow 0.25s ease;

 cursor: default;

}

.stat-card:hover {

 transform: translateY(-6px);

 box-shadow: 0 15px 25px rgba(0, 0, 0, 0.15);

}

.stat-header {

 display: flex;

 justify-content: space-between;

 align-items: center;

 font-weight: 600;

 font-size: 1rem;

}

.stat-header i {

 font-size: 1.6rem;

 opacity: 0.9;

}

.stat-body {

 margin-top: 1rem;

}

.stat-body h4 {

 font-size: 2rem;

 font-weight: 700;

 margin-bottom: 0.3rem;

}

.stat-body p {

 font-size: 0.9rem;

 opacity: 0.85;

}

.gradient-indigo { background: linear-gradient(135deg, #6366f1, #3b82f6); }

.gradient-purple { background: linear-gradient(135deg, #a855f7, #ec4899); }

.gradient-green { background: linear-gradient(135deg, #22c55e, #10b981); }

.gradient-orange { background: linear-gradient(135deg, #f59e0b, #f97316); }

.gradient-blue { background: linear-gradient(135deg, #3b82f6, #06b6d4); }

.gradient-red { background: linear-gradient(135deg, #ef4444, #f43f5e); }

</style>
