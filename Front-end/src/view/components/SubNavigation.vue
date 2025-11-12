<script setup>
import { useRoute } from 'vue-router';
import { onMounted, computed } from 'vue';
import { useSubjects } from "../../composables/subjects.js";
import { useTodos } from '@/composables/task.js';

const route = useRoute();

const token = localStorage.getItem("token");
const { subjects, getSubjects } = useSubjects(token);
const { todos } = useTodos(token);

const allTodos = computed(() => todos.value);

const todoCounts = computed(() => {
  const all = allTodos.value;
  const today = new Date().toISOString().split("T")[0]; 

  return {
    All: all.length,
    Pending: all.filter(t => !t.completed).length,
    Completed: all.filter(t => t.completed).length,
    DueToday: all.filter(
      t => !t.completed && (t.due_date || "").split("T")[0] === today
    ).length,
    Overdue: all.filter(
      t => !t.completed && (t.due_date || "") < today
    ).length,
  };
});

const studyStreak = computed(() => {
  if (!allTodos.value.length) return 0;

  const today = new Date();
  let streak = 0;

  // Sort todos by due_date descending
  const sortedTodos = [...allTodos.value]
    .filter(t => t.completed && t.due_date)
    .sort((a, b) => new Date(b.due_date) - new Date(a.due_date));

  for (let i = 0; i < sortedTodos.length; i++) {
    const todoDate = new Date(sortedTodos[i].due_date);

    // Check if this todo is exactly streak days behind today
    const diffDays = Math.floor((today - todoDate) / (1000 * 60 * 60 * 24));
    if (diffDays === streak) {
      streak++;
    } else {
      break;
    }
  }

  return streak;
});

// ---------- Helpers ----------
const safeWeightedAverage = (arr, valueFn, weightFn) => {
  if (!arr.length) return 0;
  let total = 0;
  let totalWeight = 0;

  arr.forEach(item => {
    const value = valueFn(item);
    const w = Number(weightFn(item)) || 1;
    total += value * w;
    totalWeight += w;
  });

  return totalWeight ? total / totalWeight : 0;
};

const totalAssessments = computed(() =>
  subjects.value.reduce((acc, s) => acc + (s.assessments?.length || 0), 0)
);


const taskCompletionPercentage = computed(() => {
  if (!todoCounts.value.All) return 0;
  return Math.round((todoCounts.value.Completed / todoCounts.value.All) * 100);
});


const percentageToGPA = (percent) => {
  if (percent >= 90) return 1.0;
  if (percent >= 80) return 2.0;
  if (percent >= 70) return 3.0;
  if (percent >= 60) return 4.0;
  return 5.0; // fail, if using PH/CHED style
};

// ---------- Subjects ----------
const aboveTarget = computed(() =>
  subjects.value.filter(s => s.current_grade > s.target_grade).length
);

// Weighted average grade across all subjects (%)
const averageGrade = computed(() =>
  safeWeightedAverage(
    subjects.value,
    s => Number(s.current_grade) || 0,
    s => s.weight || 1
  ).toFixed(1)
);

// Weighted overall GPA (4.0 scale or PH scale)
const overallGPA4 = computed(() =>
  safeWeightedAverage(
    subjects.value,
    s => percentageToGPA(Number(s.current_grade) || 0),
    s => s.weight || 1
  ).toFixed(2)
);

// ---------- Dashboard ----------
const dashboardStats = computed(() => {
  return {
    averagePercent: averageGrade.value,   // weighted percent
    overallGPA: overallGPA4.value,       // weighted GPA
    activeSubjects: subjects.value.length,
    pendingTasks: todoCounts.value.Pending,
    completedTasks: todoCounts.value.Completed,
  };
});
// ---------- Lifecycle ----------
onMounted(getSubjects);
</script>


<template>
  <div v-if="route.name == 'Dashboard'" class="stats-container">
    <div class="stat-card" v-for="card in [
      { title: 'Overall GPA', value: dashboardStats.overallGPA, icon: 'ri-donut-chart-line', note: '+2.1 from last semester', color: 'gradient-indigo' },
      { title: 'Active Subjects', value: dashboardStats.activeSubjects, icon: 'ri-book-open-line', note: 'Currently Tracking', color: 'gradient-purple' },
      { title: 'Pending Tasks', value: dashboardStats.pendingTasks, icon: 'ri-calendar-line', note: 'Due this week', color: 'gradient-orange' },
      { title: 'Completed Tasks', value: dashboardStats.completedTasks, icon: 'ri-checkbox-circle-line', note: 'Task this month', color: 'gradient-green' }
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

   <div v-if="route.name === 'Analytics'" class="stats-container">
    <!-- 🎓 Overall GPA -->
    <div class="stat-card gradient-indigo">
      <div class="stat-header">
        <span>Overall GPA</span>
        <i class="ri-donut-chart-line"></i>
      </div>
      <div class="stat-body">
        <h4>{{ dashboardStats.overallGPA }}</h4>
        <p>Currently Tracking</p>
      </div>
    </div>

    <!-- 📊 Task Completion -->
    <div class="stat-card gradient-blue">
      <div class="stat-header">
        <span>Task Completion</span>
        <i class="ri-calendar-line"></i>
      </div>
      <div class="stat-body">
        <h4>{{ taskCompletionPercentage }}%</h4>
        <div class="progress mt-3" role="progressbar"
          aria-valuenow="taskCompletionPercentage" aria-valuemin="0" aria-valuemax="100"
          style="height: 10px; background: rgba(255,255,255,0.25); border-radius: 6px;">
          <div class="progress-bar"
            :style="{ width: taskCompletionPercentage + '%', background: '#fff', borderRadius: '6px' }"></div>
        </div>
        <p class="mt-2">Completed tasks</p>
      </div>
    </div>

    <!-- 🏆 Target Achieved -->
    <div class="stat-card gradient-green">
      <div class="stat-header">
        <span>Target Achieved</span>
        <i class="ri-award-line"></i>
      </div>
      <div class="stat-body">
        <h4>{{ aboveTarget }} / {{ dashboardStats.activeSubjects }}</h4>
        <p>Subjects above target</p>
      </div>
    </div>

    <!-- 📚 Study Streak -->
    <div class="stat-card gradient-orange">
      <div class="stat-header">
        <span>Study Streak</span>
        <i class="ri-book-open-line"></i>
      </div>
      <div class="stat-body">
        <h4>{{ studyStreak }}</h4>
        <p>Days consecutive</p>
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
        <span>Above Target</span>
        <i class="ri-donut-chart-line"></i>
      </div>
      <div class="stat-body">
        <h4>{{ aboveTarget }}</h4>
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
        <p>Across all subjects</p>
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
  <div v-if="route.name == 'Grades'" class="stats-container">
  <!-- 🎓 Overall GPA -->
  <div class="stat-card gradient-indigo">
    <div class="stat-header">
      <span>Overall GPA</span>
      <i class="ri-donut-chart-line"></i>
    </div>
    <div class="stat-body">
      <h4>{{ overallGPA4 }}</h4>
      <p>Currently Tracking</p>
    </div>
  </div>

  <!-- 📘 Total Assessments -->
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

  <!-- 🎯 Targets Met -->
  <div class="stat-card gradient-green">
    <div class="stat-header">
      <span>Targets Met</span>
      <i class="ri-calendar-line"></i>
    </div>
    <div class="stat-body">
      <h4>{{ aboveTarget }}</h4>
      <p>Out of {{ subjects.length }} subjects</p>
    </div>
  </div>

  <!-- 🏆 Best Subject -->
  <div class="stat-card gradient-orange">
    <div class="stat-header">
      <span>Best Subject</span>
      <i class="ri-award-line"></i>
    </div>
    <div class="stat-body">
      <h4>
        {{
          subjects.length
            ? Math.max(...subjects.map(s => s.current_grade || 0)) + '%'
            : '0%'
        }}
      </h4>
      <p>
        {{
          subjects.length
            ? subjects.reduce((best, s) =>
                (s.current_grade || 0) > (best.current_grade || 0) ? s : best
              ).name
            : '-'
        }}
      </p>
    </div>
  </div>
  </div>
  <div v-else-if="route.name == 'ManageTask'" class="stats-container">
    <div v-for="task in [
      { title: 'Total Tasks', value: todoCounts.All, icon: 'ri-list-check-3', color: 'gradient-indigo' },
      { title: 'Pending', value: todoCounts.Pending, icon: 'ri-history-line', color: 'gradient-orange' },
      { title: 'Due Today', value: todoCounts.DueToday, icon: 'ri-error-warning-line', color: 'gradient-blue' },
      { title: 'Overdue', value: todoCounts.Overdue, icon: 'ri-error-warning-line', color: 'gradient-red' },
      { title: 'Completed', value: todoCounts.Completed, icon: 'ri-checkbox-circle-line', color: 'gradient-green' }
    ]" :key="task.title" class="stat-card" :class="task.color">
      <div class="stat-header">
        <span>{{ task.title }}</span>
        <i :class="task.icon"></i>
      </div>
      <div class="stat-body">
        <h4>{{ task.value }}</h4>
      </div>
    </div>
  </div>
</template>

<style scoped>
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.2rem;
  margin: 1.5rem auto;
  padding: 0 1rem;
}

/* ✨ Modern Stat Card */
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

/* 🌈 Gradients */
.gradient-indigo { background: linear-gradient(135deg, #6366f1, #3b82f6); }
.gradient-purple { background: linear-gradient(135deg, #a855f7, #ec4899); }
.gradient-green { background: linear-gradient(135deg, #22c55e, #10b981); }
.gradient-orange { background: linear-gradient(135deg, #f59e0b, #f97316); }
.gradient-blue { background: linear-gradient(135deg, #3b82f6, #06b6d4); }
.gradient-red { background: linear-gradient(135deg, #ef4444, #f43f5e); }
</style>

