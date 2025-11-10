<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from "vue";
import { Chart } from "chart.js/auto";

const props = defineProps({
  type: { type: String, default: "bar" },            // "bar", "line", "pie", etc.
  chartId: { type: String, default: "chart" },       // unique ID for canvas
  chartData: { type: Object, required: true },       // chart data (reactive/computed)
  chartOptions: { type: Object, default: () => ({}) } // chart configuration options
});

const chartRef = ref(null);
let chartInstance = null;

// 🟢 Initialize chart
const renderChart = () => {
  const ctx = chartRef.value.getContext("2d");
  chartInstance = new Chart(ctx, {
    type: props.type,
    data: props.chartData,
    options: {
      ...props.chartOptions,
      responsive: true,
      maintainAspectRatio: false,
    },
  });
};

onMounted(() => {
  renderChart();
});

// 🟡 Watch for data updates only (smooth updates)
watch(
  () => props.chartData,
  (newData) => {
    if (chartInstance) {
      chartInstance.data = newData;
      chartInstance.update();
    }
  },
  { deep: true }
);

// 🔵 Watch for type or options changes (rebuild chart)
watch(
  () => [props.type, props.chartOptions],
  () => {
    if (chartInstance) {
      chartInstance.destroy();
      chartInstance = null;
    }
    renderChart();
  },
  { deep: true }
);

// 🔴 Clean up when unmounted
onBeforeUnmount(() => {
  if (chartInstance) {
    chartInstance.destroy();
    chartInstance = null;
  }
});
</script>

<template>
  <div class="chart-container">
    <canvas :id="chartId" ref="chartRef"></canvas>
  </div>
</template>

<style scoped>
.chart-container {
  position: relative;
  width: 100%;
  height: 100%;
  min-height: 280px; /* ensures visibility even in small containers */
}
</style>
