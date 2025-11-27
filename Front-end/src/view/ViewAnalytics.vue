<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import NavigationChild from "./components/NavigationChild.vue";
import SubNavigation from "./components/SubNavigation.vue";
import { useSubjects } from "../composables/subjects.js";
import { useTodos } from "@/composables/task.js";
import { useAssessment } from "@/composables/assessment";
import ChartsComponent from "./components/ChartsComponent.vue";

const token = localStorage.getItem("token");

// ---------------------- Composables & State ----------------------
const { subjects, getSubjects } = useSubjects(token);
const { todos, getTodos } = useTodos(token);
const {
 assessments: rawAssessments,
 getAssessment,
 // We rely on getFinalOverallGrade to return the consolidated JSON now
 getFinalOverallGrade
} = useAssessment(token);

const loading = ref(true);
const intervalId = ref(null);

// Define the stages for iteration and mapping (MATCHING API OUTPUT)
const API_QUARTER_KEYS = ["preliminary", "midterm", "pre_final", "final"];
const DISPLAY_STAGES = ["Preliminary", "Midterm", "Pre-Final", "Final"];

// State for Grades (Fetched from the consolidated API response)
const overallAverageGrade = ref('N/A'); // Represents GWA Percentage average
const stageAverages = ref({
 Preliminary: 'N/A',
 Midterm: 'N/A',
 'Pre-Final': 'N/A',
 Final: 'N/A',
});

// State for Subject Breakdown Table
const subjectBreakdownData = ref([]);

// ---------------------- Data Fetching (Consolidated and Optimized) ----------------------

const fetchOverallGrades = async () => {
  try {
    // Fetch the consolidated data structure from the server
    const data = await getFinalOverallGrade();
    if (!data || !data.subjects) return;

    // Reset cumulative counters for calculating Stage Averages
    const stageTotals = {};
    const stageCounts = {};
    let overallRawScoreSum = 0; // For overall GWA Percentage
    let overallRawScoreCount = 0; // Count of subjects with GWA percentage

    API_QUARTER_KEYS.forEach(key => {
      stageTotals[key] = 0;
      stageCounts[key] = 0;
    });

    const subjectsArray = [];
    const subjectsMap = data.subjects;

    // 1. Process Subjects for Table and Stage Averages
    for (const subjectName in subjectsMap) {
      const subjectData = subjectsMap[subjectName];

      // 'grades' now stores an object {f2f: x, online: y, overall: z} for each stage
      const grades = {};
      let subjectRawScoreSum = 0;
      let subjectRawScoreCount = 0;

      API_QUARTER_KEYS.forEach((apiKey, index) => {
        const displayStage = DISPLAY_STAGES[index];
        const quarter = subjectData.quarters[apiKey];

        // --- Extracting F2F, Online, and Overall scores from the nested structure ---

        // 1. Overall Raw Score (used for GWA calculations)
        const overallRawScore = quarter?.overall?.raw_score !== null && quarter?.overall?.raw_score !== undefined
          ? Number(quarter.overall.raw_score)
          : null;

        // 2. F2F Raw Score
        const f2fRawScore = quarter?.f2f?.raw_score !== null && quarter?.f2f?.raw_score !== undefined
          ? Number(quarter.f2f.raw_score)
          : null;

        // 3. Online Raw Score
        const onlineRawScore = quarter?.online?.raw_score !== null && quarter?.online?.raw_score !== undefined
          ? Number(quarter.online.raw_score)
          : null;
        // ----------------------------------------------------------------------------------

        // We still use the Overall Raw Score for the Stage/Quarter Averages (Containers 2-5)
        if (overallRawScore !== null) {
          stageTotals[apiKey] += overallRawScore;
          stageCounts[apiKey] += 1;

          subjectRawScoreSum += overallRawScore;
          subjectRawScoreCount += 1;
        }

        // For Table Display, store the three breakdown scores as strings
        grades[displayStage] = {
          f2f: f2fRawScore !== null ? f2fRawScore.toFixed(1) : 'N/A',
          online: onlineRawScore !== null ? onlineRawScore.toFixed(1) : 'N/A',
          overall: overallRawScore !== null ? overallRawScore.toFixed(1) : 'N/A',
        };
      });

      // Calculate Subject Final Average (Percentage) using the Overall Raw Score
      const subjectRawAverage = subjectRawScoreCount > 0
        ? subjectRawScoreSum / subjectRawScoreCount
        : null;

      if (subjectRawAverage !== null) {
        overallRawScoreSum += subjectRawAverage;
        overallRawScoreCount += 1;
      }

      subjectsArray.push({
        name: subjectName,
        stages: grades,
        // Display the calculated subject GWA Percentage here
        currentGrade: subjectRawAverage !== null
               ? subjectRawAverage.toFixed(1)
               : 'N/A',
        // Keep the status from the server, which is based on the transmuted grade (1.0-5.0)
        status: subjectData.status,
      });
    }

    subjectBreakdownData.value = subjectsArray;


    // 2. Set Overall Final Grade Percentage (Header Container)
    overallAverageGrade.value = overallRawScoreCount > 0
                  ? (overallRawScoreSum / overallRawScoreCount).toFixed(1)
                  : 'N/A';

    // 3. Calculate and Set Stage Averages (Containers 2-5)
    API_QUARTER_KEYS.forEach((apiKey, index) => {
      const displayStage = DISPLAY_STAGES[index];
      const count = stageCounts[apiKey];
      const total = stageTotals[apiKey];

      stageAverages.value[displayStage] = count > 0
        ? (total / count).toFixed(1)
        : 'N/A';
    });

  } catch (err) {
    console.error("Error fetching consolidated grades:", err);
  }
};

const fetchAllData = async () => {
  loading.value = true;
  try {
    // Fetch base data (subjects, todos, raw assessments)
    await Promise.all([getSubjects(), getTodos(), getAssessment()]);

    // Fetch all consolidated calculated grades from the server
    await fetchOverallGrades();

  } catch (err) {
    console.error("Error loading data:", err);
  } finally {
    loading.value = false;
  }
}


// ---------------------- Derived Data (KPIs and Charts) - Rely on raw data for calculation ----------------------

const taskStreak = computed(() => {
  if (!rawAssessments.value?.length) return 0;
  // This logic correctly uses rawAssessments (from getAssessment())
  const weeks = [...new Set(
    rawAssessments.value
      .filter(a => a.date_taken)
      .map(a => {
        const d = new Date(a.date_taken);
        const oneJan = new Date(d.getFullYear(), 0, 1);
        const week = Math.ceil((((d - oneJan) / 86400000 + oneJan.getDay() + 1) / 7));
        return `${d.getFullYear()}-W${week.toString().padStart(2,"0")}`;
      })
  )].sort();

  let streak = 0;
  for (let i = weeks.length - 1; i > 0; i--) {
    const [y1, w1] = weeks[i].split('-W').map(Number);
    const [y2, w2] = weeks[i - 1].split('-W').map(Number);
    if ((y1 === y2 && w1 - w2 === 1) || (y1 - y2 === 1 && w2 === 52 && w1 === 1)) streak++;
    else break;
  }

  return streak + 1;
});

const totalAssessments = computed(() =>
  // This logic correctly uses subjects (from getSubjects())
  subjects.value?.reduce((acc, s) => acc + (s.assessments?.length || 0), 0) || 0
);

const improvementPercentage = computed(() => {
    // 1. Ensure subjects array is valid and non-empty
    if (!Array.isArray(subjects.value) || !subjects.value.length) return '0.0';

    let totalChange = 0;
    let countedSubjects = 0;

    subjects.value.forEach(subject => {
        // We MUST assume that the original 'assessments' array is retained
        // within the subject object after the data merge in useSubjects.js.
        const rawAssessments = subject.assessments || [];

        // 2. Filter valid grades and map them to percentage scores
        const percentageGrades = rawAssessments
            .filter(a => a.score != null && a.total_items != null && Number(a.total_items) > 0 && a.date_taken)
            .map(a => ({
                // 3. Use the ISO date string directly for reliable chronological sorting
                date_taken: a.date_taken,
                grade: (Number(a.score) / Number(a.total_items)) * 100
            }));

        // 4. Sort the scores chronologically using the ISO date string
        percentageGrades.sort((a, b) => {
            if (a.date_taken < b.date_taken) return -1;
            if (a.date_taken > b.date_taken) return 1;
            return 0;
        });

        if (percentageGrades.length >= 2) {
            const first = percentageGrades[0].grade;
            const last = percentageGrades[percentageGrades.length - 1].grade;

            // 5. Calculate change (Latest score - Earliest score)
            if (!isNaN(first) && !isNaN(last)) {
                totalChange += last - first;
                countedSubjects++;
            }
        }
    });

    // Calculate the average change across all counted subjects
    return countedSubjects > 0 ? (totalChange / countedSubjects).toFixed(1) : '0.0';
});

// Line Chart Data
const lineData = computed(() => {
  if (!subjects.value?.length) return null;
  const labelsSet = new Set();
  const datasets = subjects.value.map(subject => {
    // This correctly uses raw score/total_items for trend tracking
    const dataPoints = (subject.assessments || [])
      .filter(a => a.score != null && a.total_items != null && Number(a.total_items) > 0)
      .sort((a, b) => new Date(a.date_taken) - new Date(b.date_taken))
      .map(a => {
        const date = new Date(a.date_taken);
        const label = date.toLocaleDateString("en-US", { month: "short", day: "numeric" });
        labelsSet.add(label);
        return (Number(a.score) / Number(a.total_items)) * 100; // Calculate percentage
      });

    return {
      label: subject.subject_name,
      data: dataPoints,
      borderColor: subject.color || "#007bff",
      backgroundColor: subject.color || "#007bff",
      fill: false,
    };
  });
  const labels = Array.from(labelsSet).sort((a, b) => new Date(a) - new Date(b));
  return { labels, datasets };
});

const lineOptions = {
  responsive: true, plugins: { legend: { position: "top" } },
  scales: { y: { beginAtZero: true, max: 100 } },
};

// Define a sequential color palette for the quarters
const QUARTER_COLORS = {
    'Preliminary': 'rgba(255, 159, 64, 0.8)', // Orange
    'Midterm': 'rgba(54, 162, 235, 0.8)',     // Blue
    'Pre-Final': 'rgba(75, 192, 192, 0.8)',  // Teal
    'Final': 'rgba(153, 102, 255, 0.8)',      // Purple
};

// Bar Chart Data
const barData = computed(() => {
  // Use the processed data from the table (subjectBreakdownData)
    const dataForChart = subjectBreakdownData.value;

  if (!dataForChart?.length) return null;

  // Dynamically create a dataset for each defined quarter/stage
    const datasets = DISPLAY_STAGES.map(stage => {
        return {
            label: `${stage} Grade`,
            // Extract the 'overall' grade percentage for this stage/quarter
            // parseFloat is used because subject.stages[stage].overall is a string (e.g., '86.4')
            data: dataForChart.map(s => parseFloat(s.stages[stage]?.overall) || 0),
            backgroundColor: QUARTER_COLORS[stage] || 'rgba(108, 117, 125, 0.6)',
            borderWidth: 1,
        };
    });

  return {
    labels: dataForChart.map(s => s.name), // Subject names
    datasets: datasets, // All quarter datasets
  };
});

const barOptions = {
  responsive: true, plugins: { legend: { position: "top" } },
  scales: { y: { beginAtZero: true, max: 100 } },
};

// Pie Chart Data
const pieData = computed(() => {
  if (!rawAssessments.value?.length) return null;
  const typeCounts = {};
  rawAssessments.value.forEach(a => {
    typeCounts[a.type_activity] = (typeCounts[a.type_activity] || 0) + 1;
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

// Area Chart Data
const areaData = computed(() => {
  if (!todos.value?.length) return null;
  const weeksMap = {};
  todos.value.forEach(todo => {
    if (!todo.due_date) return;
    const d = new Date(todo.due_date);
    const monthLabel = d.toLocaleDateString("en-US", { month: "short", day: "numeric" });
    if (!weeksMap[monthLabel]) weeksMap[monthLabel] = { created: 0, completed: 0 };
    weeksMap[monthLabel].created += 1;
    if (todo.completed) weeksMap[monthLabel].completed += 1;
  });
  const sortedWeeks = Object.keys(weeksMap).sort((a, b) => new Date(a) - new Date(b));
  return {
    labels: sortedWeeks,
    datasets: [
      {
        label: "Created", data: sortedWeeks.map(w => weeksMap[w].created),
        borderColor: "#007bff", backgroundColor: "rgba(0,123,255,0.3)", fill: true,
      },
      {
        label: "Completed", data: sortedWeeks.map(w => weeksMap[w].completed),
        borderColor: "#28a745", backgroundColor: "rgba(40,167,69,0.3)", fill: true,
      },
    ],
  };
});

const areaOptions = {
  responsive: true, plugins: { legend: { position: "top" } },
  scales: { y: { beginAtZero: true } },
};


// ---------------------- Lifecycle Hooks ----------------------

onMounted(async () => {
  await fetchAllData();

  // Start Polling every 30 seconds for dynamic updates
  intervalId.value = setInterval(async () => {
    console.log('Polling for updates...');
    // Re-fetch everything to ensure all states are synchronized with the server
    await fetchAllData();
  }, 30000);
});

onUnmounted(() => {
  // Clear the interval when the component is destroyed
  if (intervalId.value) {
    clearInterval(intervalId.value);
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
    <h4 class="fw-semibold text-primary mb-3">Academic Performance Overview 📊</h4>

    <div class="row g-4 mb-4">
      <div class="col-12 col-lg-2 col-md-4">
        <div class="border rounded-4 p-3 shadow-sm bg-primary text-white h-100 d-flex flex-column justify-content-between">
          <div>
            <h6 class="fw-normal mb-1 text-uppercase opacity-75 small">Overall Final Grade</h6>
                        <h1 class="fw-bold mb-0">
              {{ overallAverageGrade }}{{ overallAverageGrade !== 'N/A' ? '%' : '' }}
            </h1>
          </div>
          <p class="mb-0 small opacity-75 mt-2">GWA Percentage (Overall Scores Avg)</p>
        </div>
      </div>

      <div class="col-12 col-lg-10 col-md-8">
        <div class="row g-4 h-100">
          <div v-for="(stage, index) in DISPLAY_STAGES" :key="index" class="col-6 col-sm-3">
            <div class="border rounded-4 p-3 shadow-sm bg-light h-100">
              <h6 class="fw-semibold text-primary mb-1">{{ stage }} Overall Average</h6>
              <p class="fst-italic text-secondary mb-3 small">Mean Overall Grade %</p>
                            <h3 class="fw-bold" :class="stageAverages[stage] === 'N/A' ? 'text-muted' : 'text-success'">
                {{ stageAverages[stage] }}{{ stageAverages[stage] !== 'N/A' ? '%' : '' }}
              </h3>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 mt-1">
      <div class="col-12">
        <div class="border rounded-4 p-4 shadow-sm bg-white">
          <h4 class="fw-semibold text-primary mb-3">Subject Grade Breakdown</h4>
          <p class="text-muted small mb-3">
              Grades are weighted per stage: the Overall score is calculated based on a 60% Face-to-Face (F2F) component and a 40% Online component.
          </p>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="bg-light">
                <tr>
                  <th rowspan="2">Subject</th>
                                    <th v-for="stage in DISPLAY_STAGES" :key="stage" class="text-center" colspan="3">{{ stage }} Grade (%)</th>
                  <th rowspan="2" class="text-center">Final Average (%)</th>
                </tr>
                <tr>
                                    <template v-for="stage in DISPLAY_STAGES" :key="stage + '-breakdown'">
                    <th class="text-center small text-primary fw-medium">F2F</th>
                    <th class="text-center small text-success fw-medium">Online</th>
                    <th class="text-center small text-dark fw-medium">Overall</th>
                  </template>
                </tr>
              </thead>
              <tbody>
                <tr v-for="subject in subjectBreakdownData" :key="subject.name">
                  <td class="fw-medium">{{ subject.name }}</td>

                                    <template v-for="stage in DISPLAY_STAGES" :key="subject.name + '-' + stage">
                                        <td class="text-center">
                      <span :class="subject.stages[stage].f2f === 'N/A' ? 'text-muted' : 'text-primary'">
                        {{ subject.stages[stage].f2f }}%
                      </span>
                    </td>
                                        <td class="text-center">
                      <span :class="subject.stages[stage].online === 'N/A' ? 'text-muted' : 'text-success'">
                        {{ subject.stages[stage].online }}%
                      </span>
                    </td>
                                        <td class="text-center">
                      <span class="fw-medium" :class="subject.stages[stage].overall === 'N/A' ? 'text-muted' : 'text-dark'">
                        {{ subject.stages[stage].overall }}%
                      </span>
                    </td>
                  </template>

                                    <td class="text-center">
                    <span class="fw-bold" :class="subject.status === 'Failed' ? 'text-danger' : 'text-success'">
                      {{ subject.currentGrade }}%
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="!subjectBreakdownData.length" class="text-center text-muted fst-italic mt-3">No subject data available to display breakdown.</p>
        </div>

      </div>
    </div>
  </div>
  <div class="container py-3 px-3 mt-3">
    <h4 class="fw-semibold text-primary mb-3">Key Performance Indicators ✨</h4>
    <div class="row g-4 justify-content-center">
      <div class="col-md-4">
        <div class="border rounded-4 p-4 shadow-sm bg-white h-100">
          <h5 class="fw-semibold text-primary mb-3">Streak & Volume</h5>
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <p class="mb-0 small text-muted">Week Task Streak</p>
            <h4 class="text-primary fw-bold mb-0">{{ taskStreak }}</h4>
          </div>
          <div class="d-flex justify-content-between align-items-center py-2">
            <p class="mb-0 small text-muted">Total Assessments Taken</p>
            <h4 class="text-primary fw-bold mb-0">{{ totalAssessments }}</h4>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="border rounded-4 p-4 shadow-sm bg-white h-100">
          <h5 class="fw-semibold text-primary mb-3">Improvement Insight</h5>
          <div class="row justify-content-around py-3 rounded-4 border align-items-center bg-gradient text-white shadow-sm">
            <div class="col-1 text-center"><i class="ri-line-chart-line fs-2"></i></div>
            <div class="col text-black">
              <h5 class="fw-semibold mb-1">
                {{ improvementPercentage || 0 }}% Average Change
              </h5>
              <p class="mb-0 small">
                Calculated using the first and latest assessment for subjects with 2+ grades.
              </p>
          </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="container py-4">
    <div class="px-2 row g-4 justify-content-center align-items-stretch gap-3">
      <div class="col-lg-6 shadow rounded-4 border p-4 bg-white">
        <h4 class="fw-semibold">Grade Trends Over Time 📈</h4>
        <p class="text-muted small">Track your academic progress across subjects</p>

        <div v-if="lineData && lineData.datasets.some(ds => ds.data.some(val => val > 0))">
          <ChartsComponent type="line" :chart-data="lineData" :chart-options="lineOptions" />
        </div>
        <p v-else class="text-center text-muted fst-italic">
          Not enough data to display grade trends.
        </p>
      </div>

      <div class="col-lg-5 shadow rounded-4 border p-4 bg-white">
        <h4 class="fw-semibold">Subject Performance 🎯</h4>
                <p class="text-muted small">Compare subject performance across all academic quarters.</p>

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
        <h4 class="fw-semibold">Assessment Type Distribution 📊</h4>
        <p class="text-muted small">Breakdown of your recorded assessments</p>

        <div v-if="pieData && pieData.datasets[0].data.some(val => val > 0)">
          <ChartsComponent type="pie" :chart-data="pieData" :chart-options="pieOptions" />
        </div>
        <p v-else class="text-center text-muted fst-italic">
          Not enough data to display assessment distribution.
        </p>
      </div>

      <div class="col-lg-5 shadow rounded-4 border p-4 bg-white">
        <h4 class="fw-semibold">Task Completion Trends 📋</h4>
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
