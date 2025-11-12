<script setup>
import { ref, computed, onMounted } from "vue";
import NavigationChild from "./components/NavigationChild.vue";
import SubNavigation from "./components/SubNavigation.vue";
import { useSubjects } from "../composables/subjects.js";
import { useTodos } from "@/composables/task.js";
import { useAssessment } from "@/composables/assessment";
// import ChartComponent from "@/components/ChartComponent.vue
import ChartsComponent from "./components/ChartsComponent.vue";

const token = localStorage.getItem("token");

// ---------------------- Composables ----------------------
const { subjects, getSubjects } = useSubjects(token);
const { todos, getTodos } = useTodos(token);
const { Assessment, getAssessment } = useAssessment(token);

const loading = ref(true);

// ---------------------- Task Streak ----------------------
const taskStreak = computed(() => {
  if (!Assessment.value?.length) return 0;

  // Group assessments by ISO week
  const weeks = [...new Set(
    Assessment.value
      .filter(a => a.date_taken)
      .map(a => {
        const d = new Date(a.date_taken);
        const oneJan = new Date(d.getFullYear(), 0, 1);
        const week = Math.ceil((((d - oneJan) / 86400000 + oneJan.getDay() + 1) / 7));
        return `${d.getFullYear()}-W${week.toString().padStart(2,"0")}`;
      })
  )].sort();

  // Count consecutive weeks from latest week backwards
  let streak = 0;
  for (let i = weeks.length - 1; i > 0; i--) {
    const [y1, w1] = weeks[i].split('-W').map(Number);
    const [y2, w2] = weeks[i - 1].split('-W').map(Number);
    if ((y1 === y2 && w1 - w2 === 1) || (y1 - y2 === 1 && w2 === 52 && w1 === 1)) streak++;
    else break;
  }

  return streak + 1; // include current week
});

// ---------------------- Ranked Subjects ----------------------
const rankedSubjects = computed(() =>
  Array.isArray(subjects.value)
    ? [...subjects.value].sort((a, b) => (b.current_grade || 0) - (a.current_grade || 0))
    : []
);

// ---------------------- Total Assessments ----------------------
const totalAssessments = computed(() =>
  subjects.value?.reduce((acc, s) => acc + (s.assessments?.length || 0), 0) || 0
);

// ---------------------- Improvement Percentage ----------------------
const improvementPercentage = computed(() => {
  if (!Array.isArray(subjects.value) || !subjects.value.length) return 0;

  let totalChange = 0;
  let countedSubjects = 0;

  subjects.value.forEach(subject => {
    const grades = subject.assessments
      ?.filter(a => a.grade != null)
      .sort((a, b) => new Date(a.date_taken) - new Date(b.date_taken)) || [];

    if (grades.length >= 2) {
      const first = Number(grades[0].grade);
      const last = Number(grades[grades.length - 1].grade);
      if (!isNaN(first) && !isNaN(last)) {
        totalChange += last - first;
        countedSubjects++;
      }
    }
  });

  return countedSubjects ? (totalChange / countedSubjects).toFixed(1) : 0;
});

// ----------------- Line Chart: Grade Trends -----------------
const lineData = computed(() => {
  if (!subjects.value?.length) return null;

  const labelsSet = new Set();
  const datasets = subjects.value.map(subject => {
    const data = (subject.assessments || [])
      .sort((a, b) => new Date(a.date_taken) - new Date(b.date_taken))
      .map(a => {
        const date = new Date(a.date_taken);
        const label = date.toLocaleDateString("en-US", { month: "short", day: "numeric" }); // "Nov 5"
        labelsSet.add(label);
        return Number(a.grade);
      });
    return {
      label: subject.name,
      data,
      borderColor: subject.color || "#007bff",
      backgroundColor: subject.color || "#007bff",
      fill: false,
    };
  });

  const labels = Array.from(labelsSet).sort((a, b) => new Date(a) - new Date(b));
  return { labels, datasets };
});

const lineOptions = {
  responsive: true,
  plugins: { legend: { position: "top" } },
  scales: { y: { beginAtZero: true, max: 100 } },
};

// ----------------- Bar Chart: Subject Performance -----------------
const barData = computed(() => {
  if (!subjects.value?.length) return null;

  return {
    labels: subjects.value.map(s => s.name),
    datasets: [
      {
        label: "Current Grade",
        data: subjects.value.map(s => s.current_grade || 0),
        backgroundColor: subjects.value.map(s => s.color || "#007bff"),
      },
      {
        label: "Target Grade",
        data: subjects.value.map(s => Number(s.target_grade) || 0),
        backgroundColor: "rgba(0,0,0,0.1)",
      },
    ],
  };
});

const barOptions = {
  responsive: true,
  plugins: { legend: { position: "top" } },
  scales: { y: { beginAtZero: true, max: 100 } },
};

// ----------------- Pie Chart: Assessment Type Distribution -----------------
const pieData = computed(() => {
  if (!Assessment.value?.length) return null;

  const typeCounts = {};
  Assessment.value.forEach(a => {
    typeCounts[a.type] = (typeCounts[a.type] || 0) + 1;
  });

  return {
    labels: Object.keys(typeCounts),
    datasets: [
      {
        data: Object.values(typeCounts),
        backgroundColor: ["#007bff", "#28a745", "#ffc107", "#dc3545"],
      },
    ],
  };
});

const pieOptions = { responsive: true, plugins: { legend: { position: "right" } } };

// ----------------- Area Chart: Task Completion Trends -----------------
const areaData = computed(() => {
  if (!todos.value?.length) return null;

  const weeksMap = {};
  todos.value.forEach(todo => {
    if (!todo.due_date) return;
    const d = new Date(todo.due_date);
    const monthLabel = d.toLocaleDateString("en-US", { month: "short", day: "numeric" }); // "Nov 5"
    if (!weeksMap[monthLabel]) weeksMap[monthLabel] = { created: 0, completed: 0 };
    weeksMap[monthLabel].created += 1;
    if (todo.completed) weeksMap[monthLabel].completed += 1;
  });

  const sortedWeeks = Object.keys(weeksMap).sort((a, b) => new Date(a) - new Date(b));

  return {
    labels: sortedWeeks,
    datasets: [
      {
        label: "Created",
        data: sortedWeeks.map(w => weeksMap[w].created),
        borderColor: "#007bff",
        backgroundColor: "rgba(0,123,255,0.3)",
        fill: true,
      },
      {
        label: "Completed",
        data: sortedWeeks.map(w => weeksMap[w].completed),
        borderColor: "#28a745",
        backgroundColor: "rgba(40,167,69,0.3)",
        fill: true,
      },
    ],
  };
});

const areaOptions = {
  responsive: true,
  plugins: { legend: { position: "top" } },
  scales: { y: { beginAtZero: true } },
};

// ---------------------- Fetch Data ----------------------
onMounted(async () => {
  loading.value = true;
  try {
    await Promise.all([getSubjects(), getTodos(), getAssessment()]);
  } catch (err) {
    console.error("Error loading data:", err);
  } finally {
    loading.value = false;
  }
});
</script>



<template>
  <div v-if="loading" class="bg-black bg-opacity-50 loading-screen d-flex flex-column gap-2">
    <div class="spinner-border text-primary" role="status"></div>
    <div class="text-white text-lg font-semibold animate-pulse">Loading analytics data...</div>
  </div>

  <NavigationChild />
  <SubNavigation />

  <div class="container py-3 px-3 mt-3">
    <div class="row g-4">
      <!-- Performance Overview -->
      <div class="col-lg-7">
        <div class="border rounded-4 p-4 shadow-sm bg-white">
          <div class="px-3 mb-3">
            <h4 class="fw-semibold text-primary">Performance Insights</h4>
            <p class="fst-italic text-secondary mb-0">Key trends and achievements in your academic journey</p>
          </div>

          <!-- Highlight -->
        <div class="px-3">
          <div class="row justify-content-around py-3 rounded-4 border align-items-center bg-gradient text-white shadow-sm">
            <div class="col-1 text-center"><i class="ri-line-chart-line fs-2"></i></div>
            <div class="col text-black">
             <h5 class="fw-semibold mb-1">
                {{ improvementPercentage || 0 }}% Average Improvement
              </h5>
              <p class="mb-0 small">
                Your tasks have been completed for <strong>{{ taskStreak }}</strong> consecutive weeks
              </p>
            </div>
          </div>
        </div>

          <div class="row row-cols-3 g-1 my-4 text-center px-1 gap-5 justify-content-center">
             <div class="col border rounded-4 p-3 bg-light shadow-sm ">
                <h3 class="text-primary fw-bold mb-1">{{ totalAssessments }}</h3>
                <p class="text-muted small mb-0 fst-italic">Total Assessments</p>
              </div>
              <div class="col border rounded-4 p-3 bg-light shadow-sm">
                <h3 class="text-primary fw-bold mb-1">{{ taskStreak }}</h3>
                <p class="text-muted small mb-0 fst-italic">Week Task Streak</p>
              </div>
          </div>
        </div>
      </div>

      <!-- Subject Rankings -->
      <div class="col-lg-5">
        <div class="border rounded-4 p-4 shadow-sm bg-white">
          <h4 class="fw-semibold text-primary mb-1">Subject Rankings</h4>
          <p class="fst-italic text-secondary mb-3">Ranked by current performance</p>

          <div v-if="rankedSubjects.length > 0" class="list-group">
            <div v-for="(subject, index) in rankedSubjects" :key="subject.id"
              class="list-group-item d-flex justify-content-between align-items-center border-0 border-bottom py-3">
              <div class="d-flex align-items-center">
                <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center me-3"
                  style="width: 32px; height: 32px;">{{ index + 1 }}</span>
                <div>
                  <h6 class="mb-0">{{ subject.name }}</h6>
                  <small class="text-muted">{{ subject.current_grade >= subject.target_grade ? 'Above Target' : 'Below Target' }}</small>
                </div>
              </div>
              <div class="text-end">
                <h6 class="mb-0 fw-semibold">{{ subject.current_grade ?? 0 }}%</h6>
                <small :class="subject.current_grade >= subject.target_grade ? 'text-success' : 'text-danger'">
                  {{ subject.current_grade >= subject.target_grade ? 'On Track' : 'Needs Work' }}
                </small>
              </div>
            </div>
          </div>
          <p v-else class="text-center text-muted fst-italic mt-3">No subject data available</p>
        </div>
      </div>
    </div>
  </div>

  <div class="container py-4">
    <div class="px-2 row g-4 justify-content-center align-items-stretch gap-3">
     <div class="col-lg-6 shadow rounded-4 border p-4 bg-white">
        <h4 class="fw-semibold">Grade Trends Over Time</h4>
        <p class="text-muted small">Track your academic progress across subjects</p>

        <div v-if="lineData && lineData.datasets.some(ds => ds.data.some(val => val > 0))">
          <ChartsComponent type="line" :chart-data="lineData" :chart-options="lineOptions" />
          </div>
          <p v-else class="text-center text-muted fst-italic">
            Not enough data to display grade trends.
          </p>
        </div>

        <div class="col-lg-5 shadow rounded-4 border p-4 bg-white">
          <h4 class="fw-semibold">Subject Performance</h4>
          <p class="text-muted small">Compare current vs target grades</p>

          <div v-if="barData && barData.datasets.some(ds => ds.data.some(val => val > 0))">
            <ChartsComponent type="bar" :chart-data="barData" :chart-options="barOptions" />
          </div>
          <p v-else class="text-center text-muted fst-italic">
            Not enough data to display subject performance.
          </p>
        </div>
    </div>
    <div class="px-2 row g-4 mt-4 justify-content-center gap-3">

      <div class="col-lg-6 shadow rounded-4 border p-4 bg-white">
        <h4 class="fw-semibold">Assessment Type Distribution</h4>
        <p class="text-muted small">Breakdown of your recorded assessments</p>

        <div v-if="pieData && pieData.datasets[0].data.some(val => val > 0)">
          <ChartsComponent type="pie" :chart-data="pieData" :chart-options="pieOptions" />
        </div>
        <p v-else class="text-center text-muted fst-italic">
          Not enough data to display assessment distribution.
        </p>
      </div>

      <div class="col-lg-5 shadow rounded-4 border p-4 bg-white">
        <h4 class="fw-semibold">Task Completion Trends</h4>
        <p class="text-muted small">Weekly task creation vs completion</p>

        <div v-if="areaData && areaData.datasets[0].data.some(val => val > 0 || areaData.datasets[1].data.some(v => v > 0))">
          <ChartsComponent type="line" :chart-data="areaData" :chart-options="areaOptions" />
        </div>
        <p v-else class="text-center text-muted fst-italic">
          Not enough data to display task completion trends.
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.chart-container {
  position: relative;
  width: 100%;
  height: 320px;
  display: flex;
  justify-content: center;
  align-items: center;
}

canvas {
  width: 100% !important;
  height: 100% !important;
}

.bg-gradient {
  background: linear-gradient(135deg, #16a34a, #22c55e);
}
.loading-screen{
  width: 100%;
  height: 100vh;
  display: flex;
  position: fixed;
  top: 0px;
  bottom: 0px;
  z-index: 1000;
  justify-content: center;
  align-items: center;
}
.progress-bar {
  transition: width 0.4s ease;
}

</style>

